<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * تقرير التحصيل اليومي والإقفال. PRD §8.3.
 */
class CashClosingTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function payment(Organization $org, string $method, string $amount): void
    {
        $this->withinTenant($org, fn () => Payment::create([
            'organization_id' => $org->id, 'method' => $method, 'amount' => $amount,
            'currency' => 'SAR', 'status' => 'confirmed', 'paid_at' => now(),
        ]));
    }

    public function test_daily_report_groups_by_method(): void
    {
        [$org, $user] = $this->makeTenant(['payments.view']);
        $this->payment($org, 'cash', '500.00');
        $this->payment($org, 'cash', '250.00');
        $this->payment($org, 'bank_transfer', '1000.00');

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/reports/daily-collection?date=' . now()->toDateString())
            ->assertOk()
            ->assertJsonPath('totals_by_method.cash', '750.00')
            ->assertJsonPath('totals_by_method.bank_transfer', '1000.00')
            ->assertJsonPath('total_amount', '1750.00')
            ->assertJsonPath('payments_count', 3);
    }

    public function test_closing_snapshots_and_blocks_reclose(): void
    {
        [$org, $user] = $this->makeTenant(['payments.view', 'payments.manage']);
        $this->payment($org, 'cash', '300.00');
        $date = now()->toDateString();

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/cash-closings', ['date' => $date])
            ->assertCreated()
            ->assertJsonPath('total_amount', '300.00')
            ->assertJsonPath('payments_count', 1);

        // لا إعادة إقفال لنفس اليوم
        $this->postJson('/api/v1/cash-closings', ['date' => $date])->assertStatus(422);
    }

    public function test_report_is_org_scoped(): void
    {
        [, $userA] = $this->makeTenant(['payments.view']);
        [$orgB] = $this->makeTenant(['payments.view']);
        $this->payment($orgB, 'cash', '999.00');

        Sanctum::actingAs($userA);
        // لا يرى تحصيل مؤسسة أخرى
        $this->getJson('/api/v1/reports/daily-collection?date=' . now()->toDateString())
            ->assertOk()
            ->assertJsonPath('total_amount', '0')
            ->assertJsonPath('payments_count', 0);
    }
}
