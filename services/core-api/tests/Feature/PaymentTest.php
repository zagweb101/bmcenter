<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * نزاهة المدفوعات: سند لكل دفعة، لا تخصيص زائد، رصيد دقيق. PRD §17, §27.
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    /** ينشئ تسجيلًا ثم فاتورته، ويعيد [invoiceId, enrollmentId, total]. */
    private function invoiceFor(Organization $org, string $price = '1000.00'): array
    {
        $cohort = $this->withinTenant($org, function () use ($org, $price) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'دورة', 'default_price' => $price]);
            return Cohort::create([
                'organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'دفعة',
                'capacity' => 0, 'price' => $price, 'tax_rate' => '15.00', 'status' => 'enrollment_open',
            ]);
        });
        $person = $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'دافع', 'phone_e164' => '+96650' . random_int(1000000, 9999999),
        ]));

        $enrollmentId = $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohort->id, 'person_id' => $person->id])->json('data.id');
        $invoice = $this->postJson("/api/v1/enrollments/{$enrollmentId}/invoice")->json('data');

        return [$invoice['id'], $enrollmentId, $invoice['total_including_tax']];
    }

    public function test_full_payment_produces_receipt_and_clears_balance(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'invoices.view', 'invoices.issue', 'payments.view', 'payments.manage']);
        Sanctum::actingAs($user);
        [$invoiceId, $enrollmentId, $total] = $this->invoiceFor($org); // 1150.00

        $this->postJson('/api/v1/payments', [
            'method' => 'cash', 'amount' => $total,
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => $total]],
        ])->assertCreated()
          ->assertJsonPath('data.receipt.amount', $total)
          ->assertJsonPath('data.receipt.receipt_number', 'REC-000001');

        $this->getJson("/api/v1/invoices/{$invoiceId}/balance")
            ->assertOk()->assertJsonPath('outstanding', '0.00');

        // الفاتورة مسدّدة بالكامل → التسجيل مؤكَّد (§14)
        $this->assertSame('confirmed', Enrollment::withoutGlobalScopes()->find($enrollmentId)->status);
    }

    public function test_partial_then_final_payment(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'invoices.view', 'invoices.issue', 'payments.manage']);
        Sanctum::actingAs($user);
        [$invoiceId, , $total] = $this->invoiceFor($org); // 1150.00

        $this->postJson('/api/v1/payments', [
            'method' => 'cash', 'amount' => '150.00',
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => '150.00']],
        ])->assertCreated();

        $this->getJson("/api/v1/invoices/{$invoiceId}/balance")->assertJsonPath('outstanding', '1000.00');

        $this->postJson('/api/v1/payments', [
            'method' => 'bank_transfer', 'amount' => '1000.00',
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => '1000.00']],
        ])->assertCreated();

        $this->getJson("/api/v1/invoices/{$invoiceId}/balance")->assertJsonPath('outstanding', '0.00');
    }

    public function test_over_allocation_beyond_outstanding_is_rejected(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'invoices.issue', 'payments.manage']);
        Sanctum::actingAs($user);
        [$invoiceId, , $total] = $this->invoiceFor($org); // 1150.00

        // تخصيص أكبر من المتبقّي
        $this->postJson('/api/v1/payments', [
            'method' => 'cash', 'amount' => '2000.00',
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => '1300.00']],
        ])->assertStatus(422);
    }

    public function test_allocations_cannot_exceed_payment_amount(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'invoices.issue', 'payments.manage']);
        Sanctum::actingAs($user);
        [$invoiceId] = $this->invoiceFor($org);

        $this->postJson('/api/v1/payments', [
            'method' => 'cash', 'amount' => '100.00',
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => '150.00']],
        ])->assertStatus(422);
    }

    public function test_recording_payment_requires_permission(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'invoices.issue']); // بلا payments.manage
        Sanctum::actingAs($user);
        [$invoiceId, , $total] = $this->invoiceFor($org);

        $this->postJson('/api/v1/payments', [
            'method' => 'cash', 'amount' => $total,
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => $total]],
        ])->assertForbidden();
    }
}
