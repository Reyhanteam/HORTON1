<?php

use App\Contracts\FeatureManager;
use App\Contracts\SettingsStore;
use App\Models\BotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('settings store persists and returns typed values', function () {
    $settings = app(SettingsStore::class);

    $settings->set('shop.enabled', true);
    $settings->set('shop.max_items', 12);
    $settings->set('shop.tax_rate', 7.5);
    $settings->set('shop.options', ['currency' => 'IRR', 'show_trial' => true]);

    expect($settings->get('shop.enabled'))->toBeTrue()
        ->and($settings->get('shop.max_items'))->toBe(12)
        ->and($settings->get('shop.tax_rate'))->toBe(7.5)
        ->and($settings->get('shop.options'))->toBe(['currency' => 'IRR', 'show_trial' => true]);

    expect(BotSetting::query()->where('key', 'shop.max_items')->value('type'))->toBe('integer');
});

test('settings return defaults and support existence and deletion', function () {
    $settings = app(SettingsStore::class);

    expect($settings->get('missing.key', 'fallback'))->toBe('fallback')
        ->and($settings->has('missing.key'))->toBeFalse();

    $settings->set('temporary.key', 'value');
    expect($settings->has('temporary.key'))->toBeTrue();

    $settings->forget('temporary.key');
    expect($settings->has('temporary.key'))->toBeFalse()
        ->and($settings->get('temporary.key', 'fallback'))->toBe('fallback');
});

test('settings cache is invalidated after updates', function () {
    $settings = app(SettingsStore::class);

    $settings->set('cache.test', 'first');
    expect($settings->get('cache.test'))->toBe('first');

    $settings->set('cache.test', 'second');
    expect($settings->get('cache.test'))->toBe('second');
});

test('feature manager persists feature flags through settings abstraction', function () {
    $features = app(FeatureManager::class);

    expect($features->enabled('shop', false))->toBeFalse();

    $features->enable('shop');
    expect($features->enabled('shop'))->toBeTrue();

    $features->disable('shop');
    expect($features->enabled('shop'))->toBeFalse();
});
