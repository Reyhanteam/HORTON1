<?php

namespace App\Contracts;

interface FeatureManager
{
    public function enabled(string $feature, bool $default = false): bool;

    public function enable(string $feature): void;

    public function disable(string $feature): void;
}
