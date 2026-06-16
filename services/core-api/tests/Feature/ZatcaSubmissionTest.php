<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Person;
use App\Services\Invoice\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * إرسال الفاتورة إلى ZATCA عبر الـ Adapter (وضع simulation). PRD §16, ADR-004.
 */
class ZatcaSubmissionTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function issuedInvoice(Organization $org): Invoice
    {
        return $this->withinTenant($org, function () use ($org) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'دورة', 'default_price' => '1000.00']);
            $cohort = Cohort::create([
                'organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'دفعة',
                'capacity' => 0, 'price' => '1000.00', 'tax_rate' => '15.00', 'status' => 'enrollment_open',
            ]);
            $person = Person::create(['organization_id' => $org->id, 'first_name' => 'ع', 'phone_e164' => '+96650' . random_int(1000000, 9999999)]);
            $enrollment = Enrollment::create([
                'organization_id' => $org->id, 'cohort_id' => $cohort->id, 'person_id' => $person->id,
                'status' => 'pending_invoice', 'price_snapshot' => '1000.00', 'tax_rate_snapshot' => '15.00',
                'discount_amount_snapshot' => '0', 'tax_amount_snapshot' => '150.00', 'total_snapshot' => '1150.00',
                'enrolled_at' => now(),
            ]);
            $svc = app(InvoiceService::class);
            $invoice = $svc->createDraftFromEnrollment($enrollment);
            return $svc->issue($invoice);
        });
    }

    public function test_simplified_invoice_is_reported_with_qr_and_hash(): void
    {
        config(['zatca.driver' => 'simulation']);
        [$org, $user] = $this->makeTenant(['invoices.view', 'invoices.issue']);
        $invoice = $this->issuedInvoice($org); // transaction_type=simplified

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/invoices/{$invoice->id}/submit-zatca")
            ->assertOk()
            ->assertJsonPath('data.status', 'reported');

        $fresh = Invoice::withoutGlobalScopes()->find($invoice->id);
        $this->assertNotNull($fresh->qr_payload);
        $this->assertNotNull($fresh->invoice_hash);
        $this->assertSame(1, (int) $fresh->icv);
    }

    public function test_hash_chain_increments_icv_and_links_pih(): void
    {
        config(['zatca.driver' => 'simulation']);
        [$org, $user] = $this->makeTenant(['invoices.view', 'invoices.issue']);
        $first = $this->issuedInvoice($org);
        $second = $this->issuedInvoice($org);

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/invoices/{$first->id}/submit-zatca")->assertOk();
        $this->postJson("/api/v1/invoices/{$second->id}/submit-zatca")->assertOk();

        $f = Invoice::withoutGlobalScopes()->find($first->id);
        $s = Invoice::withoutGlobalScopes()->find($second->id);

        $this->assertSame(1, (int) $f->icv);
        $this->assertSame(2, (int) $s->icv);
        // السلسلة: PIH للفاتورة الثانية = Hash الأولى (لا تُكسر — §16.6)
        $this->assertSame($f->invoice_hash, $s->pih);
    }

    public function test_cannot_submit_a_draft_invoice(): void
    {
        config(['zatca.driver' => 'simulation']);
        [$org, $user] = $this->makeTenant(['invoices.view', 'invoices.issue']);
        // فاتورة draft (غير مُصدَرة)
        $draft = $this->withinTenant($org, function () use ($org) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'د', 'default_price' => '500.00']);
            $cohort = Cohort::create(['organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'x', 'capacity' => 0, 'price' => '500.00', 'tax_rate' => '15.00', 'status' => 'enrollment_open']);
            $person = Person::create(['organization_id' => $org->id, 'first_name' => 'د', 'phone_e164' => '+966500000402']);
            $e = Enrollment::create(['organization_id' => $org->id, 'cohort_id' => $cohort->id, 'person_id' => $person->id, 'status' => 'pending_invoice', 'price_snapshot' => '500.00', 'tax_rate_snapshot' => '15.00', 'discount_amount_snapshot' => '0', 'tax_amount_snapshot' => '75.00', 'total_snapshot' => '575.00', 'enrolled_at' => now()]);
            return app(InvoiceService::class)->createDraftFromEnrollment($e);
        });

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/invoices/{$draft->id}/submit-zatca")->assertStatus(422);
    }
}
