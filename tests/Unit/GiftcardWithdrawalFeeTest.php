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

    public function test_amounts_at_or_below_five_thousand_use_the_first_tier(): void
    {
        $this->assertSame(10.0, GiftcardSetting::withdrawalFeeForAmount(5000, $this->tiers));
        $this->assertSame(10.0, GiftcardSetting::withdrawalFeeForAmount(1000, $this->tiers));
    }

    public function test_amounts_from_five_thousand_one_to_fifty_thousand_use_the_second_tier(): void
    {
        $this->assertSame(25.0, GiftcardSetting::withdrawalFeeForAmount(5000.01, $this->tiers));
        $this->assertSame(25.0, GiftcardSetting::withdrawalFeeForAmount(50000, $this->tiers));
    }

    public function test_amounts_above_fifty_thousand_use_the_third_tier(): void
    {
        $this->assertSame(50.0, GiftcardSetting::withdrawalFeeForAmount(50000.01, $this->tiers));
        $this->assertSame(50.0, GiftcardSetting::withdrawalFeeForAmount(50001, $this->tiers));
    }

    public function test_fee_is_rounded_to_two_decimal_places_and_never_negative(): void
    {
        $tiers = ['up_to_5000' => -5, '5001_to_50000' => 1.876, 'over_50000' => 0];

        $this->assertSame(0.0, GiftcardSetting::withdrawalFeeForAmount(5000, $tiers));
        $this->assertSame(1.88, GiftcardSetting::withdrawalFeeForAmount(5001, $tiers));
    }
}
