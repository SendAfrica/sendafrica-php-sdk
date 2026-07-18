<?php

declare(strict_types=1);

namespace SendAfrica\Utils;

class SmsAnalyzer
{
    private const GSM7_BASIC = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F"
        . "\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1C\x1D\x1E\x1F"
        . " !\"#¤%&'()*+,-./:;<=>?¡"
        . "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
        . "ÄÖÑÜÀ"
        . "abcdefghijklmnopqrstuvwxyz"
        . "äöñüà"
        . "0123456789"
        . "@£\$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ";

    private const GSM7_EXTENDED = '^{}[]~|€\\';

    private const GSM7_SINGLE_LIMIT = 160;
    private const GSM7_CONCAT_LIMIT = 153;
    private const UCS2_SINGLE_LIMIT = 70;
    private const UCS2_CONCAT_LIMIT = 67;

    /** @var string[]|null Cached character array for GSM-7 basic set */
    private static ?array $gsm7Chars = null;

    /**
     * Analyze an SMS message for encoding, segment count, and credit cost.
     */
    public static function analyze(string $message): array
    {
        $chars = self::toGraphemeChars($message);
        $isGsm7 = self::isGsm7($chars);
        $encoding = $isGsm7 ? 'GSM-7' : 'UCS-2';

        $charCount = 0;
        foreach ($chars as $char) {
            $charCount += self::isGsm7Extended($char) ? 2 : 1;
        }

        $singleLimit = $isGsm7 ? self::GSM7_SINGLE_LIMIT : self::UCS2_SINGLE_LIMIT;
        $concatLimit = $isGsm7 ? self::GSM7_CONCAT_LIMIT : self::UCS2_CONCAT_LIMIT;

        if ($charCount <= $singleLimit) {
            $parts = 1;
        } else {
            $parts = (int) ceil($charCount / $concatLimit);
        }

        return [
            'encoding' => $encoding,
            'characters' => $charCount,
            'parts' => $parts,
            'credits' => $parts,
        ];
    }

    /**
     * Check if a message uses only GSM-7 characters.
     */
    public static function isGsm7(array $chars): bool
    {
        if (self::$gsm7Chars === null) {
            self::$gsm7Chars = str_split(self::GSM7_BASIC);
        }

        foreach ($chars as $char) {
            if (!in_array($char, self::$gsm7Chars, true)
                && !self::isGsm7Extended($char)
                && $char !== "\n") {
                return false;
            }
        }
        return true;
    }

    private static function isGsm7Extended(string $char): bool
    {
        return strpos(self::GSM7_EXTENDED, $char) !== false;
    }

    /**
     * Split a string into grapheme clusters for accurate character counting.
     */
    private static function toGraphemeChars(string $string): array
    {
        if (function_exists('grapheme_extract')) {
            $chars = [];
            $offset = 0;
            $len = strlen($string);
            while ($offset < $len) {
                $grapheme = grapheme_extract($string, 1, 0, $offset);
                if ($grapheme === false) {
                    break;
                }
                $chars[] = $grapheme;
                $offset += strlen($grapheme);
            }
            return $chars;
        }

        return str_split($string);
    }
}
