<?php

namespace App\Services\Settings;

use App\Contracts\SettingsStore;
use App\Models\BotSetting;
use Illuminate\Support\Facades\Cache;

class DatabaseSettingsStore implements SettingsStore
{
    private const CACHE_KEY = 'horton.settings.all';

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return array_key_exists($key, $settings) ? $settings[$key]['value'] : $default;
    }

    public function set(string $key, mixed $value, bool $isPublic = false): void
    {
        BotSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $this->encode($value), 'is_public' => $isPublic]
        );

        $this->flush();
    }

    public function has(string $key): bool
    {
        return BotSetting::query()->where('key', $key)->exists();
    }

    public function forget(string $key): void
    {
        BotSetting::query()->where('key', $key)->delete();
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => BotSetting::query()
            ->get(['key', 'value', 'is_public'])
            ->mapWithKeys(fn (BotSetting $setting) => [
                $setting->key => [
                    'value' => $this->decode($setting->value),
                    'is_public' => $setting->is_public,
                ],
            ])->all());
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function decode(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $value;
        }
    }
}
