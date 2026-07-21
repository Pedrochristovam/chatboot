<?php

namespace Application\Services\Operations;

use Application\Services\WhatsApp\WhatsAppConfigService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
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
            'redis' => $this->redisCheck(),
            'scheduler' => $this->schedulerCheck(),
            'queue' => $this->queueCheck(),
            'reverb' => $this->reverbCheck(),
            'meta' => $this->metaCheck(),
        ];

        return [
            'ok' => collect($checks)->every(fn ($check) => $check['ok']),
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
            'queue_stats' => $this->queueStats(),
        ];
    }

    public function queueStats(): array
    {
        $driver = config('queue.default');
        $pending = 0;
        $failed = 0;
        $reserved = 0;

        try {
            $failed = DB::table('failed_jobs')->count();
            if ($driver === 'database') {
                $pending = (int) DB::table('jobs')->count();
                $reserved = (int) DB::table('jobs')->whereNotNull('reserved_at')->count();
            } elseif ($driver === 'redis') {
                $connection = config('queue.connections.redis.connection', 'default');
                $queue = config('queue.connections.redis.queue', 'default');
                $pending = (int) Redis::connection($connection)->llen('queues:'.$queue);
                $reserved = (int) Redis::connection($connection)->zcard('queues:'.$queue.':reserved');
            }
        } catch (\Throwable) {
            // ignore
        }

        return [
            'driver' => $driver,
            'pending' => $pending,
            'reserved' => $reserved,
            'failed' => $failed,
            'last_activity' => Cache::get('operations.queue.last_activity'),
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

    private function redisCheck(): array
    {
        $cache = config('cache.default');
        $queue = config('queue.default');
        $session = config('session.driver');
        $usesRedis = in_array('redis', [$cache, $queue, $session], true);

        if (! $usesRedis) {
            return [
                'ok' => true,
                'label' => 'Redis',
                'detail' => 'Não utilizado (cache/queue/session em outros drivers)',
            ];
        }

        try {
            Redis::connection()->ping();

            return ['ok' => true, 'label' => 'Redis', 'detail' => 'Conectado'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'label' => 'Redis', 'detail' => $e->getMessage()];
        }
    }

    private function queueCheck(): array
    {
        try {
            $stats = $this->queueStats();
            $lastActivity = $stats['last_activity'];
            $workerFresh = $lastActivity && now()->diffInMinutes($lastActivity) <= 5;
            $ok = $stats['failed'] === 0 && ($stats['pending'] === 0 || $workerFresh || $stats['driver'] !== 'database');

            return [
                'ok' => $ok,
                'label' => 'Fila',
                'detail' => "Driver {$stats['driver']}: {$stats['pending']} pendente(s), {$stats['reserved']} em processamento, {$stats['failed']} falha(s)"
                    .($stats['pending'] > 0 && ! $workerFresh && $stats['driver'] === 'database' ? ' — worker sem atividade recente' : ''),
                'pending' => $stats['pending'],
                'failed' => $stats['failed'],
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

    private function reverbCheck(): array
    {
        $configured = filled(config('broadcasting.connections.reverb.key'));
        $host = config('broadcasting.connections.reverb.options.host');
        $port = config('broadcasting.connections.reverb.options.port');

        if (! $configured) {
            return [
                'ok' => true,
                'label' => 'Tempo real',
                'detail' => 'Não configurado (polling ativo)',
            ];
        }

        $reachable = false;
        if ($host && $port) {
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen((string) $host, (int) $port, $errno, $errstr, 1);
            if (is_resource($socket)) {
                $reachable = true;
                fclose($socket);
            }
        }

        return [
            'ok' => $reachable,
            'label' => 'Tempo real',
            'detail' => $reachable
                ? "Reverb em {$host}:{$port}"
                : "Configurado, mas {$host}:{$port} inacessível",
        ];
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
