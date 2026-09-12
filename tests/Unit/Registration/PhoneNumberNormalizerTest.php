<?php

declare(strict_types=1);

namespace Tests\Unit\Registration;

use App\Services\Registration\PhoneNumberNormalizer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PhoneNumberNormalizerTest extends TestCase
{
    public function test_it_normalizes_an_international_phone_number(): void
    {
        $normalizer = app(PhoneNumberNormalizer::class);

        self::assertSame('+491234567890', $normalizer->normalize('+49 123 456 7890'));
    }

    public function test_it_converts_an_international_00_prefix(): void
    {
        $normalizer = app(PhoneNumberNormalizer::class);

        self::assertSame('+491234567890', $normalizer->normalize('0049 123 456 7890'));
    }

    public function test_it_adds_plus_to_a_valid_digit_only_number(): void
    {
        $normalizer = app(PhoneNumberNormalizer::class);

        self::assertSame('+491234567890', $normalizer->normalize('491234567890'));
    }

    public function test_it_rejects_too_short_numbers(): void
    {
        $this->expectException(ValidationException::class);

        app(PhoneNumberNormalizer::class)->normalize('+1234567');
    }

    public function test_it_rejects_invalid_country_code(): void
    {
        $this->expectException(ValidationException::class);

        app(PhoneNumberNormalizer::class)->normalize('+01234567890');
    }

    public function test_it_rejects_numbers_longer_than_e164_limit(): void
    {
        $this->expectException(ValidationException::class);

        app(PhoneNumberNormalizer::class)->normalize('+49123456789012345');
    }
}
