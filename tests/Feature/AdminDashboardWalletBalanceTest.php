<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardWalletBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_total_user_wallet_balance(): void
    {
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'phone' => '08000000000',
            'status' => 'active',
        ]);

        $users = User::factory()->count(3)->create();

        Wallet::create(['user_id' => $users[0]->id, 'balance' => 500]);
        Wallet::create(['user_id' => $users[1]->id, 'balance' => 400]);
        Wallet::create(['user_id' => $users[2]->id, 'balance' => 1000]);

        $this->actingAs($admin, 'admins')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Total User Balance')
            ->assertSee('₦1,900');
    }
}
