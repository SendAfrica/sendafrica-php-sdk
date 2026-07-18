<?php

declare(strict_types=1);

namespace SendAfrica\Utils;

use SendAfrica\Exceptions\InvalidPhoneException;

class PhoneNormalizer
{
    private const DEFAULT_COUNTRY_CODE = '255';

    private const TRIM_CHARS = [
        ' ' => '',
        '-' => '',
        '(' => '',
        ')' => '',
        '.' => '',
    ];

    /**
     * Normalize a phone number to E.164 format.
     *
     * Accepts: "0712345678", "712345678", "255712345678",
     *          "+255712345678", "+255 712 345 678"
     *
     * Returns: "+255712345678"
     */
    public static function normalize(string $phone, ?string $defaultCountryCode = null): string
    {
        $phone = str_replace(array_keys(self::TRIM_CHARS), array_values(self::TRIM_CHARS), $phone);
        $phone = ltrim($phone, '+');

        if ($phone === '' || !preg_match('/^\d+$/', $phone)) {
            throw new InvalidPhoneException("Phone number must contain only digits after removing formatting: got \"{$phone}\"");
        }

        if (strlen($phone) >= 9 && strlen($phone) <= 15) {
            if (strlen($phone) >= 12) {
                return '+' . $phone;
            }

            $cc = $defaultCountryCode ?? self::DEFAULT_COUNTRY_CODE;
            return '+' . $cc . $phone;
        }

        throw new InvalidPhoneException(
            "Phone number must be between 9 and 15 digits. Got " . strlen($phone) . " digits."
        );
    }

    /**
     * Validate that a phone number looks reasonable without normalizing.
     */
    public static function isValid(string $phone): bool
    {
        try {
            self::normalize($phone);
            return true;
        } catch (InvalidPhoneException $e) {
            return false;
        }
    }
}
