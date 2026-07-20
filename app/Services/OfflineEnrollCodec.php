<?php

namespace App\Services;

use InvalidArgumentException;

class OfflineEnrollCodec
{
    public const VERSION = 2;
    public const ALPHABET = TokenCodec::ALPHABET;
    public const PREFIX = 'ZGE';
    public const MAX_GRACE_DAYS = 255;

    private const VERSION_BITS = 4;
    private const SECRET_BITS = 256;
    private const GRACE_BITS = 8;
    private const PAD_BITS = 2;
    private const CHECK_BITS = 40;

    private const V1_VERSION = 1;
    private const V1_SYMBOLS = 60;
    private const V2_SYMBOLS = 62;

    public function encode(string $hmacSecretHex, string $accountNumber, int $graceDays = 0): string
    {
        if (! ctype_xdigit($hmacSecretHex) || strlen($hmacSecretHex) !== 64) {
            throw new InvalidArgumentException('The device secret must be 64 hex characters.');
        }

        $account = self::normalizeAccount($accountNumber);
        if ($account === '') {
            throw new InvalidArgumentException('The device has no account number.');
        }

        if ($graceDays < 0 || $graceDays > self::MAX_GRACE_DAYS) {
            throw new InvalidArgumentException('Grace days must be between 0 and ' . self::MAX_GRACE_DAYS . '.');
        }

        $secretBytes = hex2bin($hmacSecretHex);

        $bits = str_pad(decbin(self::VERSION), self::VERSION_BITS, '0', STR_PAD_LEFT)
            . $this->bytesToBits($secretBytes)
            . str_pad(decbin($graceDays), self::GRACE_BITS, '0', STR_PAD_LEFT)
            . str_repeat('0', self::PAD_BITS)
            . $this->checkBits($secretBytes, $account, $graceDays);

        $symbols = '';
        for ($i = 0, $len = strlen($bits); $i < $len; $i += 5) {
            $symbols .= self::ALPHABET[bindec(substr($bits, $i, 5))];
        }

        return self::PREFIX . '-' . implode('-', str_split($symbols, 5));
    }

    /**
     * Returns ['secret' => 64-char hex, 'grace_days' => int] or null. Version 1
     * codes (no grace field) still decode — bundles printed before the field
     * existed keep working — and report zero grace days.
     */
    public function decode(string $code, string $accountNumber): ?array
    {
        $normalized = strtoupper(preg_replace('/[\s-]+/', '', $code));

        if (! str_starts_with($normalized, self::PREFIX)) {
            return null;
        }

        $normalized = substr($normalized, strlen(self::PREFIX));
        $normalized = strtr($normalized, ['O' => '0', 'I' => '1', 'L' => '1', 'U' => 'V']);

        $length = strlen($normalized);
        if ($length !== self::V1_SYMBOLS && $length !== self::V2_SYMBOLS) {
            return null;
        }

        $bits = '';
        foreach (str_split($normalized) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                return null;
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $version = bindec(substr($bits, 0, self::VERSION_BITS));
        $expectedVersion = $length === self::V1_SYMBOLS ? self::V1_VERSION : self::VERSION;
        if ($version !== $expectedVersion) {
            return null;
        }

        $secretBytes = $this->bitsToBytes(substr($bits, self::VERSION_BITS, self::SECRET_BITS));
        $account = self::normalizeAccount($accountNumber);

        if ($version === self::V1_VERSION) {
            $graceDays = 0;
            $checkOffset = self::VERSION_BITS + self::SECRET_BITS;
            $expected = $this->checkBits($secretBytes, $account, null);
        } else {
            $graceDays = bindec(substr($bits, self::VERSION_BITS + self::SECRET_BITS, self::GRACE_BITS));
            $checkOffset = self::VERSION_BITS + self::SECRET_BITS + self::GRACE_BITS + self::PAD_BITS;
            $expected = $this->checkBits($secretBytes, $account, $graceDays);

            // The pad bits are dead space today; a nonzero value is a corrupted
            // or future code, not something to guess at.
            if (substr($bits, $checkOffset - self::PAD_BITS, self::PAD_BITS) !== str_repeat('0', self::PAD_BITS)) {
                return null;
            }
        }

        $actual = substr($bits, $checkOffset, self::CHECK_BITS);

        if (! hash_equals($expected, $actual)) {
            return null;
        }

        return ['secret' => bin2hex($secretBytes), 'grace_days' => $graceDays];
    }

    public static function normalizeAccount(string $accountNumber): string
    {
        $normalized = strtoupper(preg_replace('/[\s-]+/', '', $accountNumber));

        return strtr($normalized, ['O' => '0', 'I' => '1', 'L' => '1', 'U' => 'V']);
    }

    /**
     * v1 binds the check to "ZGE1" + account; v2 appends the grace-days byte so
     * the field cannot be altered without failing the check. The client's
     * EnrollCodec mirrors both messages byte for byte.
     */
    private function checkBits(string $secretBytes, string $normalizedAccount, ?int $graceDays): string
    {
        $message = $graceDays === null
            ? 'ZGE1' . $normalizedAccount
            : 'ZGE2' . $normalizedAccount . chr($graceDays);

        $digest = hash_hmac('sha256', $message, $secretBytes, true);

        return substr($this->bytesToBits(substr($digest, 0, 5)), 0, self::CHECK_BITS);
    }

    private function bytesToBits(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return $bits;
    }

    private function bitsToBytes(string $bits): string
    {
        $bytes = '';
        for ($i = 0, $len = strlen($bits); $i < $len; $i += 8) {
            $bytes .= chr(bindec(substr($bits, $i, 8)));
        }

        return $bytes;
    }
}
