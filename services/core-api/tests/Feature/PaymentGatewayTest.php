<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Person;
use App\Services\Invoice\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * بوابة الدفع عبر Adapter + Webhook موقّع Idempotent. PRD §18, ADR-005.
 */
class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function issuedInvoice(Organization $org): Invoice
    {
        return $this->withinTenant($org, function () use ($org) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'دورة', 'default_price' => '1000.00']);
            $cohort = Cohort::create(['organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'د', 'capacity' => 0, 'price' => '1000.00', 'tax_rate' => '15.00', 'status' => 'enrollment_open']);
            $person = Person::create(['organization_id' => $org->id, 'first_name' => 'ع', 'phone_e164' => '+96650' . random_int(1000000, 9999999)]);
            $e = Enrollment::create(['organization_id' => $org->id, 'cohort_id' => $cohort->id, 'person_id' => $person->id, 'status' => 'pending_invoice', 'price_snapshot' => '1000.00', 'tax_rate_snapshot' => '15.00', 'discount_amount_snapshot' => '0', 'tax_amount_snapshot' => '150.00', 'total_snapshot' => '1150.00', 'enrolled_at' => now()]);
            return app(InvoiceService::class)->createDraftFromEnrollment($e);
        });
    }

    private function signed(array $payload): array
    {
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', $raw, config('payments.webhook_secret'));

        return [$raw, $sig];
    }

    public function test_create_intent_returns_reference(): void
    {
        config(['payments.driver' => 'simulation']);
        [$org, $user] = $this->makeTenant(['payments.manage']);
        $invoice = $this->issuedInvoice($org);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/payment-intents', ['amount' => '1150.00', 'invoice_id' => $invoice->id])
            ->assertCreated()
            ->assertJsonStructure(['reference', 'status', 'client_secret']);
    }

    public function test_valid_webhook_creates_payment_and_clears_invoice(): void
    {
        config(['payments.driver' => 'simulation']);
        [$org, $user] = $this->makeTenant(['payments.view', 'payments.manage', 'invoices.view']);
        $invoice = $this->issuedInvoice($org);

        Sanctum::actingAs($user);
        $ref = $this->postJson('/api/v1/payment-intents', ['amount' => '1150.00', 'invoice_id' => $invoice->id])->json('reference');

        [$raw, $sig] = $this->signed(['id' => 'evt_1', 'type' => 'payment.succeeded', 'reference' => $ref, 'amount' => '1150.00', 'status' => 'succeeded']);
        $this->call('POST', '/api/v1/webhooks/payments/sim', [], [], [], ['HTTP_X_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw)
            ->assertOk();

        // أُنشئت دفعة بوابة بسند، والفاتورة سُدّدت
        $payment = Payment::withoutGlobalScopes()->where('method', 'gateway')->first();
        $this->assertNotNull($payment);
        $this->assertSame($ref, $payment->gateway_txn_ref);
        $this->getJson("/api/v1/invoices/{$invoice->id}/balance")->assertJsonPath('outstanding', '0.00');
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config(['payments.driver' => 'simulation']);
        [$org] = $this->makeTenant(['payments.manage']);
        [$raw] = $this->signed(['id' => 'evt_x', 'status' => 'succeeded', 'reference' => 'sim_x', 'amount' => '10']);

        $this->call('POST', '/api/v1/webhooks/payments/sim', [], [], [], ['HTTP_X_SIGNATURE' => 'wrong', 'CONTENT_TYPE' => 'application/json'], $raw)
            ->assertStatus(400);
    }

    public function test_duplicate_event_is_idempotent(): void
    {
        config(['payments.driver' => 'simulation']);
        [$org, $user] = $this->makeTenant(['payments.manage']);
        $invoice = $this->issuedInvoice($org);

        Sanctum::actingAs($user);
        $ref = $this->postJson('/api/v1/payment-intents', ['amount' => '1150.00', 'invoice_id' => $invoice->id])->json('reference');
        [$raw, $sig] = $this->signed(['id' => 'evt_dup', 'type' => 'payment.succeeded', 'reference' => $ref, 'amount' => '1150.00', 'status' => 'succeeded']);

        $server = ['HTTP_X_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'];
        $this->call('POST', '/api/v1/webhooks/payments/sim', [], [], [], $server, $raw)->assertOk();
        $this->call('POST', '/api/v1/webhooks/payments/sim', [], [], [], $server, $raw)->assertOk();

        // حدث مكرر → دفعة واحدة فقط
        $this->assertSame(1, Payment::withoutGlobalScopes()->where('method', 'gateway')->count());
    }
}
