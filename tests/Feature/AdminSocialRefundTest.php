<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSocialRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_partial_social_order_and_keep_the_delivered_count(): void
    {
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'phone' => '08000000000',
            'status' => 'active',
        ]);

        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 2000]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'social_boost',
            'status' => 'processing',
            'amount' => 500,
            'provider' => 'MoreThanPanel',
            'service_name' => 'Instagram Followers',
            'reference' => 'TXN-1001',
            'request_id' => 'ORDER-1001',
            'response' => [
                'quantity' => 500,
                'remains' => 450,
                'start_count' => 0,
                'smm_provider' => 'morethanpanel',
            ],
        ]);

        $response = $this->actingAs($admin, 'admins')
            ->postJson("/admin/transactions/{$transaction->id}/social/cancel", [
                'refund_amount' => 100,
                'full_refund' => false,
                'reason' => 'Cancelled after partial delivery',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $transaction->refresh();

        $this->assertSame('cancelled', strtolower((string) $transaction->status));
        $this->assertSame(50, (int) ($transaction->response['delivered_count'] ?? 0));
        $this->assertTrue((bool) ($transaction->response['cancelled_after_partial'] ?? false));
        $this->assertSame(2100.0, round((float) Wallet::where('user_id', $user->id)->value('balance'), 2));
    }
}
