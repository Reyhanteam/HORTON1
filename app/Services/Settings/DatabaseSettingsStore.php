<?php

namespace App\Services\Settings;

use App\Models\BotSetting;

class DatabaseSettingsStore extends AbstractSettingsStore
{
    public function set(string $key, mixed $value, bool $isPublic = false): void
    {
        $this->store($key, $value, $isPublic);
    }

    protected function load(): array
    {
        return BotSetting::query()
            ->get(['key', 'value', 'type', 'is_public'])
            ->mapWithKeys(fn (BotSetting $setting) => [
                $setting->key => [
                    'value' => $this->decode($setting->value, $setting->type),
                    'is_public' => $setting->is_public,
                ],
            ])->all();
    }

    protected function write(string $key, string $value, string $type, bool $isPublic): void
    {
        BotSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'is_public' => $isPublic]
        );
    }

    protected function delete(string $key): void
    {
        BotSetting::query()->where('key', $key)->delete();
    }

    private function decode(mixed $value, string $type): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $value;
        }

        return match ($type) {
            'boolean' => (bool) $decoded,
            'integer' => (int) $decoded,
            'float' => (float) $decoded,
            'json' => is_array($decoded) ? $decoded : [],
            default => is_string($decoded) ? $decoded : (string) $decoded,
        };
    }
}
