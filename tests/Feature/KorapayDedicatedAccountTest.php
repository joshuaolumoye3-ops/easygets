<?php

namespace Tests\Feature;

use App\Models\DedicatedAccount;
use App\Models\User;
use App\Services\Payments\KorapayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KorapayDedicatedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_bvn_only_is_accepted_for_dedicated_account_creation(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        $korapay = Mockery::mock(KorapayService::class);
        $korapay->shouldReceive('createVirtualAccount')->once()->andReturn([
            'success' => true,
            'data' => [
                'account_reference' => 'KPY_TEST_1',
                'unique_id' => 'uniq-1',
                'account_number' => '1234567890',
                'bank_code' => '035',
                'bank_name' => 'Access Bank',
                'account_name' => 'Ada Lovelace',
                'currency' => 'NGN',
            ],
        ]);
        $this->app->instance(KorapayService::class, $korapay);

        $response = $this->actingAs($user)
            ->post(route('korapay.dedicated-account.store'), [
                'account_name' => 'Ada Lovelace',
                'bvn' => '12345678901',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('dedicated_accounts', [
            'user_id' => $user->id,
            'bvn_last4' => '8901',
        ]);
    }

    public function test_nin_only_is_accepted_for_dedicated_account_creation(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
        ]);

        $korapay = Mockery::mock(KorapayService::class);
        $korapay->shouldReceive('createVirtualAccount')->once()->andReturn([
            'success' => true,
            'data' => [
                'account_reference' => 'KPY_TEST_2',
                'unique_id' => 'uniq-2',
                'account_number' => '2234567890',
                'bank_code' => '035',
                'bank_name' => 'Access Bank',
                'account_name' => 'Grace Hopper',
                'currency' => 'NGN',
            ],
        ]);
        $this->app->instance(KorapayService::class, $korapay);

        $response = $this->actingAs($user)
            ->post(route('korapay.dedicated-account.store'), [
                'account_name' => 'Grace Hopper',
                'nin' => '22345678901',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('dedicated_accounts', [
            'user_id' => $user->id,
            'nin_last4' => '8901',
        ]);
    }

    public function test_both_bvn_and_nin_can_be_missing_when_the_other_is_supplied(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Linus',
            'last_name' => 'Torvalds',
        ]);

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('korapay.dedicated-account.store'), [
                'account_name' => 'Linus Torvalds',
            ]);

        $response->assertSessionHasErrors(['bvn']);
        $this->assertDatabaseCount('dedicated_accounts', 0);
    }
}
