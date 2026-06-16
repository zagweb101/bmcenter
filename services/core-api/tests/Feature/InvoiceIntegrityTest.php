<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * نزاهة الفاتورة: لا تعديل/حذف بعد الإصدار؛ التصحيح عبر إشعار. PRD §16, §17, §27.
 */
class InvoiceIntegrityTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function draftInvoice(Organization $org): Invoice
    {
        return $this->withinTenant($org, function () use ($org) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'دورة', 'default_price' => '1000.00']);
            $cohort = Cohort::create([
                'organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'دفعة',
                'capacity' => 0, 'price' => '1000.00', 'tax_rate' => '15.00', 'status' => 'enrollment_open',
            ]);
            $person = Person::create(['organization_id' => $org->id, 'first_name' => 'ع', 'phone_e164' => '+966500000301']);
            $enrollment = \App\Models\Enrollment::create([
                'organization_id' => $org->id, 'cohort_id' => $cohort->id, 'person_id' => $person->id,
                'status' => 'pending_invoice', 'price_snapshot' => '1000.00', 'tax_rate_snapshot' => '15.00',
                'discount_amount_snapshot' => '0', 'tax_amount_snapshot' => '150.00', 'total_snapshot' => '1150.00',
                'enrolled_at' => now(),
            ]);
            return app(\App\Services\Invoice\InvoiceService::class)->createDraftFromEnrollment($enrollment);
        });
    }

    public function test_issue_moves_draft_to_issued(): void
    {
        [$org, $user] = $this->makeTenant(['invoices.view', 'invoices.issue']);
        $invoice = $this->draftInvoice($org);

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/invoices/{$invoice->id}/issue")
            ->assertOk()->assertJsonPath('data.status', 'issued');
    }

    public function test_issued_invoice_amounts_cannot_be_modified(): void
    {
        [$org] = $this->makeTenant(['invoices.issue']);
        $invoice = $this->draftInvoice($org);

        $this->withinTenant($org, function () use ($invoice) {
            app(\App\Services\Invoice\InvoiceService::class)->issue($invoice);

            $this->expectException(\DomainException::class);
            $invoice->fresh()->update(['total_including_tax' => '1.00']);
        });
    }

    public function test_issued_invoice_cannot_be_deleted(): void
    {
        [$org] = $this->makeTenant(['invoices.issue']);
        $invoice = $this->draftInvoice($org);

        $this->withinTenant($org, function () use ($invoice) {
            app(\App\Services\Invoice\InvoiceService::class)->issue($invoice);

            $this->expectException(\DomainException::class);
            $invoice->fresh()->delete();
        });
    }

    public function test_credit_note_corrects_issued_invoice(): void
    {
        [$org, $user] = $this->makeTenant(['invoices.view', 'invoices.issue']);
        $invoice = $this->draftInvoice($org);
        $this->withinTenant($org, fn () => app(\App\Services\Invoice\InvoiceService::class)->issue($invoice));

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/invoices/{$invoice->id}/credit-note", ['amount' => 200, 'reason' => 'خصم لاحق'])
            ->assertCreated()
            ->assertJsonPath('data.invoice_type_code', '381')
            ->assertJsonPath('data.total_including_tax', '200.00');

        $note = Invoice::withoutGlobalScopes()->where('invoice_type_code', '381')->first();
        $this->assertSame($invoice->id, $note->original_invoice_id);
    }

    public function test_cannot_create_note_for_draft_invoice(): void
    {
        [$org, $user] = $this->makeTenant(['invoices.issue']);
        $invoice = $this->draftInvoice($org); // draft (غير صادرة)

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/invoices/{$invoice->id}/credit-note", ['amount' => 50])
            ->assertStatus(422);
    }
}
