<?php

namespace Tests\Unit;

use App\Models\GiftcardSetting;
use PHPUnit\Framework\TestCase;

class GiftcardWithdrawalFeeTest extends TestCase
{
    private array $tiers = [
        'up_to_5000' => 10,
        '5001_to_50000' => 25,
        'over_50000' => 50,
    ];

    public function test_withdrawals_up_to_five_thousand_use_first_fee(): void
    {
        $this->assertSame(10.0, GiftcardSetting::withdrawalFeeForAmount(5000, $this->tiers));
    }

    public function test_withdrawals_from_five_thousand_one_to_fifty_thousand_use_second_fee(): void
    {
        $this->assertSame(25.0, GiftcardSetting::withdrawalFeeForAmount(5001, $this->tiers));
        $this->assertSame(25.0, GiftcardSetting::withdrawalFeeForAmount(50000, $this->tiers));
    }

    public function test_withdrawals_above_fifty_thousand_use_third_fee(): void
    {
        $this->assertSame(50.0, GiftcardSetting::withdrawalFeeForAmount(50001, $this->tiers));
    }

    public function test_fee_is_non_negative_and_rounded_to_cents(): void
    {
        $tiers = [
            'up_to_5000' => -5,
            '5001_to_50000' => 25.678,
            'over_50000' => 0,
        ];

        $this->assertSame(0.0, GiftcardSetting::withdrawalFeeForAmount(5000, $tiers));
        $this->assertSame(25.68, GiftcardSetting::withdrawalFeeForAmount(5001, $tiers));
    }
}
