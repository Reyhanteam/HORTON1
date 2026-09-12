<?php

namespace App\Contracts;

interface SettingsStore
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, bool $isPublic = false): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    public function flush(): void;
}
