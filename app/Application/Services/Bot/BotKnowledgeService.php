<?php

namespace Application\Services\Bot;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\BotKnowledge;
use Infrastructure\Persistence\Eloquent\Models\BotTopic;
use Infrastructure\Persistence\Eloquent\Models\Setting;

class BotKnowledgeService
{
    public function topicsWithKnowledge(): Collection
    {
        return BotTopic::query()
            ->with(['knowledge' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function topicsPayload(): array
    {
        return $this->topicsWithKnowledge()
            ->map(fn (BotTopic $topic) => $this->serializeTopic($topic))
            ->values()
            ->all();
    }

    public function serializeTopic(BotTopic $topic): array
    {
        $topic->loadMissing('knowledge');

        return [
            'id' => $topic->id,
            'slug' => $topic->slug,
            'title' => $topic->title,
            'description' => $topic->description,
            'sort_order' => (int) $topic->sort_order,
            'is_active' => (bool) $topic->is_active,
            'transfers_to_human' => (bool) $topic->transfers_to_human,
            'knowledge_count' => $topic->knowledge->count(),
            'knowledge' => $topic->knowledge
                ->map(fn (BotKnowledge $item) => $this->serializeKnowledge($item))
                ->values()
                ->all(),
        ];
    }

    public function serializeKnowledge(BotKnowledge $item): array
    {
        return [
            'id' => $item->id,
            'bot_topic_id' => $item->bot_topic_id,
            'question' => $item->question,
            'answer' => $item->answer,
            'keywords' => $item->keywords ?? [],
            'is_active' => (bool) $item->is_active,
            'sort_order' => (int) $item->sort_order,
        ];
    }

    public function stats(): array
    {
        return [
            'topics' => BotTopic::query()->count(),
            'active_topics' => BotTopic::query()->where('is_active', true)->count(),
            'answers' => BotKnowledge::query()->count(),
            'active_answers' => BotKnowledge::query()->where('is_active', true)->count(),
        ];
    }

    public function askNameMessage(): string
    {
        return (string) Setting::getValue(
            'notifications',
            'bot_ask_name_message',
            config('bot.ask_name_message')
        );
    }

    public function welcomeBackMessage(): string
    {
        return (string) Setting::getValue(
            'notifications',
            'bot_welcome_back_message',
            config('bot.welcome_back_message')
        );
    }

    public function updateAskNameMessage(string $message): void
    {
        Setting::setValue('notifications', 'bot_ask_name_message', $message, 'string');
    }

    public function updateWelcomeBackMessage(string $message): void
    {
        Setting::setValue('notifications', 'bot_welcome_back_message', $message, 'string');
    }

    public function createTopic(array $data): BotTopic
    {
        $slug = $this->uniqueSlug($data['slug'] ?? $data['title']);

        return BotTopic::query()->create([
            'slug' => $slug,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? ((int) BotTopic::query()->max('sort_order') + 1),
            'is_active' => $data['is_active'] ?? true,
            'transfers_to_human' => $data['transfers_to_human'] ?? false,
        ]);
    }

    public function updateTopic(BotTopic $topic, array $data): BotTopic
    {
        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $topic->sort_order,
            'is_active' => $data['is_active'] ?? $topic->is_active,
            'transfers_to_human' => $data['transfers_to_human'] ?? $topic->transfers_to_human,
        ];

        if (! empty($data['slug']) && $data['slug'] !== $topic->slug) {
            $payload['slug'] = $this->uniqueSlug($data['slug'], $topic->id);
        }

        $topic->update($payload);

        return $topic->fresh('knowledge');
    }

    public function deleteTopic(BotTopic $topic): void
    {
        $topic->delete();
    }

    public function createKnowledge(array $data): BotKnowledge
    {
        return BotKnowledge::query()->create([
            'bot_topic_id' => $data['bot_topic_id'],
            'question' => $data['question'],
            'answer' => $data['answer'],
            'keywords' => $this->normalizeKeywords($data['keywords'] ?? []),
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function updateKnowledge(BotKnowledge $item, array $data): BotKnowledge
    {
        $item->update([
            'bot_topic_id' => $data['bot_topic_id'] ?? $item->bot_topic_id,
            'question' => $data['question'],
            'answer' => $data['answer'],
            'keywords' => $this->normalizeKeywords($data['keywords'] ?? []),
            'is_active' => $data['is_active'] ?? $item->is_active,
            'sort_order' => $data['sort_order'] ?? $item->sort_order,
        ]);

        return $item->fresh();
    }

    public function deleteKnowledge(BotKnowledge $item): void
    {
        $item->delete();
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::limit($value, 60, '')) ?: 'assunto';
        $slug = $base;
        $i = 2;

        while (
            BotTopic::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /** @param array|string $keywords */
    private function normalizeKeywords(array|string $keywords): array
    {
        if (is_string($keywords)) {
            $keywords = preg_split('/[,;\n]+/', $keywords) ?: [];
        }

        return collect($keywords)
            ->map(fn ($k) => trim(Str::lower((string) $k)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
