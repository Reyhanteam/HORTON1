<?php

namespace Tests\Feature;

use App\Contracts\FeatureManager;
use App\Contracts\SettingsStore;
use App\Models\BotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_store_persists_and_returns_typed_values(): void
    {
        $settings = app(SettingsStore::class);

        $settings->set('shop.enabled', true);
        $settings->set('shop.max_items', 12);
        $settings->set('shop.tax_rate', 7.5);
        $settings->set('shop.options', ['currency' => 'IRR', 'show_trial' => true]);

        $this->assertTrue($settings->get('shop.enabled'));
        $this->assertSame(12, $settings->get('shop.max_items'));
        $this->assertSame(7.5, $settings->get('shop.tax_rate'));
        $this->assertSame(
            ['currency' => 'IRR', 'show_trial' => true],
            $settings->get('shop.options')
        );

        $this->assertSame(
            'integer',
            BotSetting::query()->where('key', 'shop.max_items')->value('type')
        );
    }

    public function test_settings_return_defaults_and_support_existence_and_deletion(): void
    {
        $settings = app(SettingsStore::class);

        $this->assertSame('fallback', $settings->get('missing.key', 'fallback'));
        $this->assertFalse($settings->has('missing.key'));

        $settings->set('temporary.key', 'value');
        $this->assertTrue($settings->has('temporary.key'));

        $settings->forget('temporary.key');

        $this->assertFalse($settings->has('temporary.key'));
        $this->assertSame('fallback', $settings->get('temporary.key', 'fallback'));
    }

    public function test_settings_cache_is_invalidated_after_updates(): void
    {
        $settings = app(SettingsStore::class);

        $settings->set('cache.test', 'first');
        $this->assertSame('first', $settings->get('cache.test'));

        $settings->set('cache.test', 'second');
        $this->assertSame('second', $settings->get('cache.test'));
    }

    public function test_feature_manager_persists_feature_flags_through_settings_abstraction(): void
    {
        $features = app(FeatureManager::class);

        $this->assertFalse($features->enabled('shop', false));

        $features->enable('shop');
        $this->assertTrue($features->enabled('shop'));

        $features->disable('shop');
        $this->assertFalse($features->enabled('shop'));
    }
}
