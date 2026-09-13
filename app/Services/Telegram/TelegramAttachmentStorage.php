<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;

final class TelegramAttachmentStorage
{
    /**
     * Download Telegram files to Laravel's public filesystem and return
     * attachment metadata suitable for the support_messages JSON column.
     *
     * Runtime files intentionally live under storage/app/public rather than
     * resources/, because resources/ is application source code and is not a
     * persistent runtime upload directory.
     *
     * @param array<int, array<string, mixed>> $attachments
     * @return array<int, array<string, mixed>>
     */
    public function store(array $attachments, string $departmentSlug): array
    {
        $stored = [];

        foreach ($attachments as $attachment) {
            $fileId = (string) ($attachment['file_id'] ?? '');
            if ($fileId === '') {
                continue;
            }

            $file = BOT::getFile($fileId);
            $filePath = (string) data_get($file, 'file_path', '');
            if ($filePath === '') {
                throw new RuntimeException('Telegram did not return a file path for the requested attachment.');
            }

            $remoteSize = data_get($file, 'file_size');
            $maxBytes = max(1, (int) config('telegram-files.max_download_bytes', 20 * 1024 * 1024));
            if (is_numeric($remoteSize) && (int) $remoteSize > $maxBytes) {
                throw new RuntimeException('Telegram attachment exceeds the configured download size limit.');
            }

            $originalName = $this->originalName($attachment, $filePath);
            $extension = $this->extension($originalName, $filePath, (string) ($attachment['type'] ?? 'file'));
            $directory = 'telegram/support/'.$this->departmentSlug($departmentSlug).'/'.now()->format('Y/m');
            $filename = (string) Str::uuid().($extension !== '' ? '.'.$extension : '');
            $relativePath = $directory.'/'.$filename;
            $disk = (string) config('telegram-files.disk', 'public');

            $temporaryPath = tempnam(sys_get_temp_dir(), 'horton-telegram-');
            if ($temporaryPath === false) {
                throw new RuntimeException('Unable to create a temporary file for Telegram attachment.');
            }

            try {
                $response = $this->http()->withOptions(['sink' => $temporaryPath])->get($this->downloadUrl($filePath));
                $response->throw();

                $size = filesize($temporaryPath);
                if ($size === false) {
                    throw new RuntimeException('Unable to determine the downloaded Telegram attachment size.');
                }
                if ($size > $maxBytes) {
                    throw new RuntimeException('Downloaded Telegram attachment exceeds the configured size limit.');
                }

                $stream = fopen($temporaryPath, 'rb');
                if ($stream === false) {
                    throw new RuntimeException('Unable to open the downloaded Telegram attachment.');
                }

                try {
                    if (!Storage::disk($disk)->put($relativePath, $stream)) {
                        throw new RuntimeException('Unable to persist Telegram attachment to the configured filesystem.');
                    }
                } finally {
                    fclose($stream);
                }

                $stored[] = [
                    ...$attachment,
                    'file_id' => $fileId,
                    'disk' => $disk,
                    'path' => $relativePath,
                    'url' => Storage::disk($disk)->url($relativePath),
                    'original_name' => $originalName,
                    'mime_type' => $this->mimeType($attachment, $temporaryPath),
                    'size' => $size,
                    'telegram_file_path' => $filePath,
                ];
            } finally {
                @unlink($temporaryPath);
            }
        }

        return $stored;
    }

    private function http(): PendingRequest
    {
        return Http::timeout(max(5, (int) config('telegram-files.timeout', 60)))
            ->connectTimeout(max(5, (int) config('telegram-files.connect_timeout', 10)))
            ->retry(
                max(0, (int) config('telegram-files.retries', 2)),
                max(100, (int) config('telegram-files.retry_sleep_ms', 500)),
                throw: false,
            );
    }

    private function downloadUrl(string $filePath): string
    {
        return rtrim((string) config('telegram-bot-router.polling.api_url', 'https://api.telegram.org'), '/')
            .'/file/bot'.config('telegram-bot-router.token').'/'.ltrim($filePath, '/');
    }

    private function departmentSlug(string $slug): string
    {
        $slug = Str::slug($slug);
        return $slug !== '' ? $slug : 'unknown';
    }

    /** @param array<string, mixed> $attachment */
    private function originalName(array $attachment, string $filePath): string
    {
        $name = (string) ($attachment['file_name'] ?? $attachment['original_name'] ?? '');
        if ($name !== '') {
            return Str::limit(basename($name), 180, '');
        }

        return basename($filePath) !== '' ? basename($filePath) : 'telegram-file';
    }

    private function extension(string $originalName, string $filePath, string $type): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== '') {
            return preg_replace('/[^a-z0-9]+/i', '', $extension) ?? '';
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== '') {
            return preg_replace('/[^a-z0-9]+/i', '', $extension) ?? '';
        }

        return match ($type) {
            'photo' => 'jpg',
            default => '',
        };
    }

    /** @param array<string, mixed> $attachment */
    private function mimeType(array $attachment, string $temporaryPath): ?string
    {
        $mime = $attachment['mime_type'] ?? $attachment['mime'] ?? null;
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }

        $detected = function_exists('mime_content_type') ? mime_content_type($temporaryPath) : false;
        return is_string($detected) && $detected !== '' ? $detected : null;
    }
}
