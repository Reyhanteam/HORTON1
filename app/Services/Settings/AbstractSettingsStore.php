<?php

namespace App\Services\Settings;

use App\Contracts\SettingsStore;
use Illuminate\Support\Facades\Cache;

abstract class AbstractSettingsStore implements SettingsStore
{
    protected const CACHE_KEY = 'horton.settings.all';

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return array_key_exists($key, $settings) ? $settings[$key]['value'] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function forget(string $key): void
    {
        $this->delete($key);
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    protected function all(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, fn (): array => $this->load());
    }

    protected function store(string $key, mixed $value, bool $isPublic): void
    {
        $this->write($key, $this->encode($value), $this->typeOf($value), $isPublic);
        $this->flush();
    }

    protected function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function typeOf(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    abstract protected function load(): array;

    abstract protected function write(string $key, string $value, string $type, bool $isPublic): void;

    abstract protected function delete(string $key): void;
}
