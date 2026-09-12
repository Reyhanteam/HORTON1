<?php

declare(strict_types=1);

namespace App\Services\Registration;

use Illuminate\Validation\ValidationException;

final class PhoneNumberNormalizer
{
    public function normalize(string $phone): string
    {
        $normalized = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';

        if (str_starts_with($normalized, '00')) {
            $normalized = '+' . substr($normalized, 2);
        }

        if (!str_starts_with($normalized, '+')) {
            $normalized = '+' . $normalized;
        }

        if (preg_match('/^\+[1-9][0-9]{7,14}$/', $normalized) !== 1) {
            throw ValidationException::withMessages([
                'phone' => 'Invalid phone number.',
            ]);
        }

        return $normalized;
    }
}
