<?php

namespace App\Services;

use InvalidArgumentException;

class TokenCodec
{
    public const VERSION = 1;
    public const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public const TYPE_FULL = 'full';
    public const TYPE_GRACE = 'grace';

    private const COUNTER_BITS = 20;
    private const DURATION_BITS = 12;
    private const FLAG_BITS = 4;
    private const SIGNATURE_BITS = 60;

    public function encode(int $counter, int $durationDays, string $type, string $hmacSecretHex): string
    {
        if ($counter < 0 || $counter > (1 << self::COUNTER_BITS) - 1) {
            throw new InvalidArgumentException('Counter out of range.');
        }

        if ($durationDays < 0 || $durationDays > (1 << self::DURATION_BITS) - 1) {
            throw new InvalidArgumentException('Duration out of range.');
        }

        $payloadInt = $this->payloadInt($counter, $durationDays, $this->flagsFor($type));
        $bits = $this->intToBits($payloadInt, 40) . $this->signatureBits($payloadInt, $hmacSecretHex);

        return $this->group($this->bitsToToken($bits));
    }

    public function decode(string $token, string $hmacSecretHex): ?array
    {
        $bits = $this->tokenToBits($token);

        if ($bits === null) {
            return null;
        }

        $payloadBits = substr($bits, 0, 40);
        $signatureBits = substr($bits, 40, self::SIGNATURE_BITS);
        $payloadInt = bindec($payloadBits);

        $expected = $this->signatureBits($payloadInt, $hmacSecretHex);

        if (! hash_equals($expected, $signatureBits)) {
            return null;
        }

        $flags = $payloadInt & ((1 << self::FLAG_BITS) - 1);

        return [
            'version' => ($payloadInt >> 36) & 0xF,
            'counter' => ($payloadInt >> (self::DURATION_BITS + self::FLAG_BITS)) & ((1 << self::COUNTER_BITS) - 1),
            'duration_days' => ($payloadInt >> self::FLAG_BITS) & ((1 << self::DURATION_BITS) - 1),
            'flags' => $flags,
            'type' => ($flags & 1) ? self::TYPE_GRACE : self::TYPE_FULL,
        ];
    }

    private function flagsFor(string $type): int
    {
        return $type === self::TYPE_GRACE ? 1 : 0;
    }

    private function payloadInt(int $counter, int $durationDays, int $flags): int
    {
        return (self::VERSION << 36)
            | ($counter << (self::DURATION_BITS + self::FLAG_BITS))
            | ($durationDays << self::FLAG_BITS)
            | $flags;
    }

    private function signatureBits(int $payloadInt, string $hmacSecretHex): string
    {
        $key = ctype_xdigit($hmacSecretHex) && strlen($hmacSecretHex) % 2 === 0
            ? hex2bin($hmacSecretHex)
            : $hmacSecretHex;

        $digest = hash_hmac('sha256', $this->payloadBytes($payloadInt), $key, true);

        $digestBits = '';
        foreach (str_split(substr($digest, 0, 8)) as $byte) {
            $digestBits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return substr($digestBits, 0, self::SIGNATURE_BITS);
    }

    private function payloadBytes(int $payloadInt): string
    {
        $bytes = '';
        for ($shift = 32; $shift >= 0; $shift -= 8) {
            $bytes .= chr(($payloadInt >> $shift) & 0xFF);
        }

        return $bytes;
    }

    private function intToBits(int $value, int $length): string
    {
        return str_pad(decbin($value), $length, '0', STR_PAD_LEFT);
    }

    private function bitsToToken(string $bits): string
    {
        $token = '';
        for ($i = 0, $len = strlen($bits); $i < $len; $i += 5) {
            $token .= self::ALPHABET[bindec(substr($bits, $i, 5))];
        }

        return $token;
    }

    private function tokenToBits(string $token): ?string
    {
        $normalized = strtoupper(preg_replace('/[\s-]+/', '', $token));
        $normalized = strtr($normalized, ['O' => '0', 'I' => '1', 'L' => '1', 'U' => 'V']);

        if (strlen($normalized) !== 20) {
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

        return $bits;
    }

    private function group(string $token): string
    {
        return implode('-', str_split($token, 5));
    }
}
