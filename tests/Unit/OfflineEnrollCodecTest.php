<?php

namespace Tests\Unit;

use App\Services\OfflineEnrollCodec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OfflineEnrollCodecTest extends TestCase
{
    private string $secret = '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f';

    private string $account = 'ZG-KUXQ2-MTR38';

    private string $golden = 'ZGE-40010-81G81-860W4-0J2GB-1G6GW-3RG24-91650-N2RBH-G68T3-CE1T7-GZ00M-1N4WR-2E';

    private string $goldenGrace = 'ZGE-40010-81G81-860W4-0J2GB-1G6GW-3RG24-91650-N2RBH-G68T3-CE1T7-GZ1RA-DGE37-9H';

    private string $goldenV1 = 'ZGE-20010-81G81-860W4-0J2GB-1G6GW-3RG24-91650-N2RBH-G68T3-CE1T7-GZY51-7W4GB';

    public function test_encode_matches_golden_vector(): void
    {
        $codec = new OfflineEnrollCodec();

        $this->assertSame($this->golden, $codec->encode($this->secret, $this->account));
        $this->assertSame($this->goldenGrace, $codec->encode($this->secret, $this->account, 14));
    }

    public function test_code_shape(): void
    {
        $code = (new OfflineEnrollCodec())->encode($this->secret, $this->account);

        $this->assertSame(78, strlen($code));
        $groups = explode('-', $code);
        $this->assertCount(14, $groups);
        $this->assertSame('ZGE', $groups[0]);
        foreach (array_slice($groups, 1, 12) as $group) {
            $this->assertSame(5, strlen($group));
        }
        $this->assertSame(2, strlen(end($groups)));
    }

    public function test_decode_recovers_secret_and_grace(): void
    {
        $codec = new OfflineEnrollCodec();

        $this->assertSame(
            ['secret' => $this->secret, 'grace_days' => 0],
            $codec->decode($this->golden, $this->account),
        );
        $this->assertSame(
            ['secret' => $this->secret, 'grace_days' => 14],
            $codec->decode($this->goldenGrace, $this->account),
        );
    }

    public function test_version_one_codes_still_decode_with_zero_grace(): void
    {
        $codec = new OfflineEnrollCodec();

        $this->assertSame(
            ['secret' => $this->secret, 'grace_days' => 0],
            $codec->decode($this->goldenV1, $this->account),
        );
    }

    public function test_every_mistyped_symbol_is_rejected(): void
    {
        $codec = new OfflineEnrollCodec();
        $symbols = str_replace('-', '', substr($this->goldenGrace, 4));

        for ($i = 0; $i < strlen($symbols); $i++) {
            $tampered = $symbols;
            $tampered[$i] = $symbols[$i] === 'A' ? 'B' : 'A';

            $this->assertNull(
                $codec->decode('ZGE' . $tampered, $this->account),
                "A flipped symbol at position {$i} was accepted."
            );
        }
    }

    public function test_wrong_account_is_rejected(): void
    {
        $codec = new OfflineEnrollCodec();

        $this->assertNull($codec->decode($this->golden, 'ZG-40000'));
    }

    public function test_normalization_forgives_typing_habits(): void
    {
        $codec = new OfflineEnrollCodec();
        $expected = ['secret' => $this->secret, 'grace_days' => 0];

        $this->assertSame($expected, $codec->decode(strtolower($this->golden), $this->account));
        $this->assertSame($expected, $codec->decode(str_replace('-', ' ', $this->golden), $this->account));
        $this->assertSame($expected, $codec->decode(str_replace('-', '', $this->golden), $this->account));
    }

    public function test_account_folding_absorbs_lookalike_registration_typos(): void
    {
        $codec = new OfflineEnrollCodec();
        $expected = ['secret' => $this->secret, 'grace_days' => 0];

        $this->assertSame($expected, $codec->decode($this->golden, 'ZG-KVXQ2-MTR38'));
        $this->assertSame($expected, $codec->decode($this->golden, 'zg kuxq2 mtr38'));
        $this->assertSame($expected, $codec->decode($this->golden, 'ZGKUXQ2MTR38'));
    }

    public function test_grace_survives_a_roundtrip_at_the_extremes(): void
    {
        $codec = new OfflineEnrollCodec();

        foreach ([1, 60, 255] as $days) {
            $code = $codec->encode($this->secret, $this->account, $days);
            $this->assertSame($days, $codec->decode($code, $this->account)['grace_days']);
        }
    }

    public function test_wrong_length_is_rejected(): void
    {
        $codec = new OfflineEnrollCodec();

        $this->assertNull($codec->decode(substr($this->golden, 0, -1), $this->account));
        $this->assertNull($codec->decode($this->golden . '2', $this->account));
    }

    public function test_missing_prefix_is_rejected(): void
    {
        $codec = new OfflineEnrollCodec();

        $this->assertNull($codec->decode(substr($this->golden, 4), $this->account));
    }

    public function test_online_style_code_is_rejected_not_crashed(): void
    {
        $this->assertNull((new OfflineEnrollCodec())->decode('ZGE4B7K2MP', $this->account));
    }

    public function test_encode_refuses_a_broken_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OfflineEnrollCodec())->encode('abab', $this->account);
    }

    public function test_encode_refuses_a_missing_account(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OfflineEnrollCodec())->encode($this->secret, '  ');
    }

    public function test_encode_refuses_out_of_range_grace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OfflineEnrollCodec())->encode($this->secret, $this->account, 256);
    }
}
