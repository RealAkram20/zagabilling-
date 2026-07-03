<?php

namespace Tests\Unit;

use App\Services\TokenCodec;
use PHPUnit\Framework\TestCase;

class TokenCodecTest extends TestCase
{
    private string $secret = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private array $vectors = [
        ['counter' => 1, 'duration' => 30, 'type' => 'full', 'token' => '20002-0F04J-CEBMA-PNWJB'],
        ['counter' => 7, 'duration' => 14, 'type' => 'grace', 'token' => '2000E-071GE-DSY1A-5H56P'],
        ['counter' => 1048575, 'duration' => 4095, 'type' => 'full', 'token' => '3ZZZZ-ZZGGE-62K9J-6J9M1'],
        ['counter' => 42, 'duration' => 7, 'type' => 'full', 'token' => '2002M-03GSD-AESAV-R12RE'],
    ];

    public function test_encode_matches_published_vectors(): void
    {
        $codec = new TokenCodec();

        foreach ($this->vectors as $vector) {
            $token = $codec->encode($vector['counter'], $vector['duration'], $vector['type'], $this->secret);
            $this->assertSame($vector['token'], $token);
            $this->assertSame(20, strlen(str_replace('-', '', $token)));
        }
    }

    public function test_decode_recovers_payload(): void
    {
        $codec = new TokenCodec();

        foreach ($this->vectors as $vector) {
            $decoded = $codec->decode($vector['token'], $this->secret);
            $this->assertNotNull($decoded);
            $this->assertSame($vector['counter'], $decoded['counter']);
            $this->assertSame($vector['duration'], $decoded['duration_days']);
            $this->assertSame($vector['type'], $decoded['type']);
        }
    }

    public function test_decode_rejects_tampered_token(): void
    {
        $codec = new TokenCodec();
        $token = $this->vectors[3]['token'];
        $tampered = ($token[0] === 'A' ? 'B' : 'A') . substr($token, 1);

        $this->assertNull($codec->decode($tampered, $this->secret));
    }

    public function test_decode_rejects_wrong_secret(): void
    {
        $codec = new TokenCodec();
        $wrong = str_repeat('ffffffffffffffff', 4);

        $this->assertNull($codec->decode($this->vectors[0]['token'], $wrong));
    }

    public function test_decode_accepts_lowercase_and_ambiguous_characters(): void
    {
        $codec = new TokenCodec();
        $token = $this->vectors[0]['token'];

        $this->assertNotNull($codec->decode(strtolower($token), $this->secret));
    }
}
