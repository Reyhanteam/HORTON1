<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

trait ConfiguresHortonQueue
{
    protected function configureHortonQueue(?string $queue = null): void
    {
        $config = config('queue.horton', []);

        $this->tries = max(1, (int) ($config['tries'] ?? 3));
        $this->timeout = max(1, (int) ($config['timeout'] ?? 120));

        $backoff = $config['backoff'] ?? [10, 30, 90];
        $this->backoff = is_array($backoff)
            ? array_values(array_map(static fn ($delay): int => max(0, (int) $delay), $backoff))
            : max(0, (int) $backoff);

        $connection = $config['connection'] ?? null;
        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }

        $this->onQueue($queue ?: (string) ($config['queue'] ?? 'default'));
    }

    protected function hortonQueueUniqueFor(): int
    {
        return max(1, (int) config('queue.horton.unique_for', 86400));
    }

    /** @return array<int, object> */
    protected function hortonQueueMiddleware(): array
    {
        $middleware = config('queue.horton.middleware', []);
        if (!is_array($middleware)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item): ?object {
            if (is_object($item)) {
                return $item;
            }

            return is_string($item) && class_exists($item) ? app()->make($item) : null;
        }, $middleware)));
    }
}
