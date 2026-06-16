<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * الاسترداد — لا استرداد زائد. PRD §17, §27.
 */
class RefundTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function payment(Organization $org, string $amount = '1000.00'): Payment
    {
        return $this->withinTenant($org, fn () => Payment::create([
            'organization_id' => $org->id, 'method' => 'cash', 'amount' => $amount,
            'currency' => 'SAR', 'status' => 'confirmed', 'paid_at' => now(),
        ]));
    }

    public function test_refund_within_limit_succeeds(): void
    {
        [$org, $user] = $this->makeTenant(['payments.manage']);
        $payment = $this->payment($org, '1000.00');

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/payments/{$payment->id}/refund", ['amount' => 400, 'reason' => 'إلغاء جزئي'])
            ->assertCreated()
            ->assertJsonPath('amount', '400.00')
            ->assertJsonPath('remaining_refundable', '600.00');
    }

    public function test_partial_refunds_accumulate_and_block_over_refund(): void
    {
        [$org, $user] = $this->makeTenant(['payments.manage']);
        $payment = $this->payment($org, '1000.00');

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/payments/{$payment->id}/refund", ['amount' => 700])->assertCreated();
        // المتبقّي 300؛ محاولة استرداد 400 تتجاوز → 422
        $this->postJson("/api/v1/payments/{$payment->id}/refund", ['amount' => 400])->assertStatus(422);
        // 300 بالضبط مسموح
        $this->postJson("/api/v1/payments/{$payment->id}/refund", ['amount' => 300])
            ->assertCreated()->assertJsonPath('remaining_refundable', '0.00');
    }

    public function test_refund_requires_permission(): void
    {
        [$org, $user] = $this->makeTenant(['payments.view']); // بلا manage
        $payment = $this->payment($org);

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/payments/{$payment->id}/refund", ['amount' => 100])->assertForbidden();
    }
}
