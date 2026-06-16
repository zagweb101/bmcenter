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
 * توليد فاتورة Draft من تسجيل. PRD §8.3, §16, §17.
 */
class InvoiceFromEnrollmentTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function enrollment(Organization $org, string $price = '1000.00', string $discount = '0'): int
    {
        $cohort = $this->withinTenant($org, function () use ($org, $price) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'تصوير', 'default_price' => $price]);
            return Cohort::create([
                'organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'دفعة الربيع',
                'capacity' => 0, 'price' => $price, 'tax_rate' => '15.00', 'status' => 'enrollment_open',
            ]);
        });
        $person = $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'طالب', 'phone_e164' => '+96650' . random_int(1000000, 9999999),
        ]));

        return $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id, 'discount_amount' => $discount,
        ])->json('data.id');
    }

    public function test_invoice_is_generated_from_enrollment_snapshot(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'invoices.view', 'invoices.issue']);
        Sanctum::actingAs($user);
        $enrollmentId = $this->enrollment($org, '1000.00');

        $this->postJson("/api/v1/enrollments/{$enrollmentId}/invoice")
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal', '1000.00')
            ->assertJsonPath('data.tax_total', '150.00')
            ->assertJsonPath('data.total_including_tax', '1150.00')
            ->assertJsonPath('data.enrollment_id', $enrollmentId);

        // التسجيل ينتقل إلى pending_payment (§14)
        $this->assertSame('pending_payment', Enrollment::withoutGlobalScopes()->find($enrollmentId)->status);
    }

    public function test_duplicate_invoice_for_enrollment_is_blocked(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'invoices.issue']);
        Sanctum::actingAs($user);
        $enrollmentId = $this->enrollment($org);

        $this->postJson("/api/v1/enrollments/{$enrollmentId}/invoice")->assertCreated();
        $this->postJson("/api/v1/enrollments/{$enrollmentId}/invoice")->assertStatus(422);
    }

    public function test_invoice_issue_requires_permission(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage']); // بلا invoices.issue
        Sanctum::actingAs($user);
        $enrollmentId = $this->enrollment($org);

        $this->postJson("/api/v1/enrollments/{$enrollmentId}/invoice")->assertForbidden();
    }
}
