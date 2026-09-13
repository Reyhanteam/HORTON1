<?php

declare(strict_types=1);

namespace Tests\Unit\Telegram;

use App\Contracts\FeatureManager;
use App\Telegram\Conversations\FeatureStep;
use PHPUnit\Framework\TestCase;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class FeatureStepTest extends TestCase
{
    public function test_disabled_feature_skips_the_wrapped_action(): void
    {
        $called = false;

        $this->app = new class {
            public function make(string $abstract): FeatureManager
            {
                return new class implements FeatureManager {
                    public function enabled(string $feature, bool $default = false): bool { return false; }
                    public function enable(string $feature): void {}
                    public function disable(string $feature): void {}
                };
            }
        };

        $step = FeatureStep::guarded('phone_verification', [
            FeatureStepTestAction::class,
            'run',
        ]);

        $result = $step(
            $this->createMock(TelegramUpdate::class),
            null,
            ['accepted' => true],
        );

        self::assertFalse($called);
        self::assertFalse($result['done']);
        self::assertSame(['accepted' => true], $result['data']);
    }
}

final class FeatureStepTestAction
{
    public function run(): array
    {
        return ['done' => true];
    }
}
