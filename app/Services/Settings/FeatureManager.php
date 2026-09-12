<?php

namespace App\Services\Settings;

use App\Contracts\FeatureManager as FeatureManagerContract;
use App\Contracts\SettingsStore;

class FeatureManager implements FeatureManagerContract
{
    private const PREFIX = 'features.';

    public function __construct(private readonly SettingsStore $settings) {}

    public function enabled(string $feature, bool $default = false): bool
    {
        return (bool) $this->settings->get(self::PREFIX . $feature, $default);
    }

    public function enable(string $feature): void
    {
        $this->settings->set(self::PREFIX . $feature, true, false);
    }

    public function disable(string $feature): void
    {
        $this->settings->set(self::PREFIX . $feature, false, false);
    }
}
