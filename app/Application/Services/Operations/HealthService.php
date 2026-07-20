<?php

namespace Application\Services\Operations;

use Application\Services\WhatsApp\WhatsAppConfigService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthService
{
    public function __construct(private readonly WhatsAppConfigService $whatsapp) {}

    public function snapshot(): array
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1'), 'Banco de dados'),
            'storage' => $this->check(fn () => Storage::disk('public')->exists('.'), 'Armazenamento'),
            'cache' => $this->cacheCheck(),
            'scheduler' => $this->schedulerCheck(),
            'queue' => $this->queueCheck(),
            'reverb' => [
                'ok' => filled(config('broadcasting.connections.reverb.key')),
                'label' => 'Tempo real',
                'detail' => filled(config('broadcasting.connections.reverb.key')) ? 'Configurado' : 'Não configurado',
            ],
            'meta' => $this->metaCheck(),
        ];

        return [
            'ok' => collect($checks)->every(fn ($check) => $check['ok']),
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    private function cacheCheck(): array
    {
        $store = config('cache.default');
        try {
            Cache::put('operations.health.ping', '1', 30);
            $ok = Cache::get('operations.health.ping') === '1';

            return [
                'ok' => $ok,
                'label' => 'Cache',
                'detail' => "Driver: {$store}".($ok ? '' : ' — falha de leitura/escrita'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'label' => 'Cache', 'detail' => "{$store}: ".$e->getMessage()];
        }
    }

    private function queueCheck(): array
    {
        try {
            $driver = config('queue.default');
            $failed = DB::table('failed_jobs')->count();
            $pending = $driver === 'database'
                ? (int) Cache::remember('operations.queue.pending_count', now()->addSeconds(15), fn () => DB::table('jobs')->count())
                : 0;
            $lastActivity = Cache::get('operations.queue.last_activity');
            $workerFresh = $lastActivity && now()->diffInMinutes($lastActivity) <= 5;
            $ok = $failed === 0 && ($pending === 0 || $workerFresh || $driver !== 'database');

            return [
                'ok' => $ok,
                'label' => 'Fila',
                'detail' => "Driver {$driver}: {$pending} pendente(s), {$failed} falha(s)"
                    .($pending > 0 && ! $workerFresh && $driver === 'database' ? ' — worker sem atividade recente' : ''),
                'pending' => $pending,
                'failed' => $failed,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'label' => 'Fila', 'detail' => $e->getMessage()];
        }
    }

    private function schedulerCheck(): array
    {
        try {
            $lastRun = Cache::get('operations.scheduler.last_run');
            $fresh = $lastRun && now()->diffInMinutes($lastRun) <= 2;

            return [
                'ok' => (bool) $fresh,
                'label' => 'Agendador',
                'detail' => $lastRun ? 'Último sinal: '.$lastRun->diffForHumans() : 'Sem sinal recente',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'label' => 'Agendador', 'detail' => $e->getMessage()];
        }
    }

    private function metaCheck(): array
    {
        $driver = $this->whatsapp->driver();
        $configured = $driver !== 'meta' || $this->whatsapp->isMetaConfigured();

        return [
            'ok' => $configured,
            'label' => 'WhatsApp Meta',
            'detail' => $driver === 'meta'
                ? ($configured ? 'Credenciais configuradas' : 'Credenciais incompletas')
                : 'Modo simulado',
        ];
    }

    private function check(callable $callback, string $label): array
    {
        try {
            $callback();

            return ['ok' => true, 'label' => $label, 'detail' => 'Operacional'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'label' => $label, 'detail' => $e->getMessage()];
        }
    }
}
