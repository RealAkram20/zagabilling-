<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Payment;
use App\Models\UnlockCode;
use App\Models\User;
use App\Repositories\UnlockCodeRepository;

class UnlockCodeService
{
    private const FULL_VALIDITY_HOURS = 24;
    private const GRACE_VALIDITY_HOURS = 72;
    private const MAX_DURATION_DAYS = 4095;

    public function __construct(
        private UnlockCodeRepository $unlockCodes,
        private AuditLogger $auditLogger,
        private TokenCodec $tokenCodec,
    ) {
    }

    public function issue(
        Device $device,
        string $type = UnlockCode::TYPE_FULL,
        ?Payment $payment = null,
        ?User $issuedBy = null,
        int $installments = 1
    ): UnlockCode {
        $validityHours = $type === UnlockCode::TYPE_GRACE
            ? self::GRACE_VALIDITY_HOURS
            : self::FULL_VALIDITY_HOURS;

        $cadenceDays = $device->plan?->cadenceDays() ?? 30;
        $durationDays = min(max($installments, 1) * $cadenceDays, self::MAX_DURATION_DAYS);

        $device->increment('unlock_counter');
        $counter = (int) $device->unlock_counter;

        $token = $this->tokenCodec->encode($counter, $durationDays, $type, $device->hmac_secret);

        $unlockCode = $this->unlockCodes->create([
            'device_id' => $device->id,
            'payment_id' => $payment?->id,
            'issued_by' => $issuedBy?->id,
            'code' => $token,
            'counter' => $counter,
            'duration_days' => $durationDays,
            'type' => $type,
            'expires_at' => now()->addHours($validityHours),
        ]);

        $this->auditLogger->record(
            'unlock_code.issue',
            "Issued {$type} unlock code for {$device->account_number}",
            $device,
        );

        return $unlockCode;
    }
}
