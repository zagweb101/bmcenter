<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
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
 * بوابة اعتماد الخصومات. PRD §15 (ما يتجاوز حد الدور لا يُطبَّق قبل الاعتماد).
 */
class DiscountApprovalTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function cohort(Organization $org): Cohort
    {
        return $this->withinTenant($org, function () use ($org) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'تصوير', 'default_price' => '1000.00']);
            return Cohort::create([
                'organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'دفعة',
                'capacity' => 0, 'price' => '1000.00', 'tax_rate' => '15.00', 'status' => 'enrollment_open',
            ]);
        });
    }

    private function person(Organization $org, string $phone): Person
    {
        return $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'طالب', 'phone_e164' => $phone,
        ]));
    }

    public function test_discount_within_limit_is_applied_immediately(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage'], '200.00');
        $cohort = $this->cohort($org);
        $person = $this->person($org, '+966500000101');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id, 'discount_amount' => 100,
        ])->assertCreated()
          ->assertJsonPath('data.status', 'pending_invoice')
          ->assertJsonPath('data.discount_amount_snapshot', '100.00');

        $this->assertSame(0, ApprovalRequest::withoutGlobalScopes()->count());
    }

    public function test_over_limit_discount_requires_approval_and_is_not_applied(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage'], '200.00');
        $cohort = $this->cohort($org);
        $person = $this->person($org, '+966500000102');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id,
            'discount_amount' => 500, 'discount_reason' => 'منحة',
        ])->assertCreated()
          ->assertJsonPath('data.status', 'pending_approval')
          ->assertJsonPath('data.discount_amount_snapshot', '0.00')   // لا يُطبَّق قبل الاعتماد
          ->assertJsonPath('data.total_snapshot', '1150.00');

        $approval = ApprovalRequest::withoutGlobalScopes()->first();
        $this->assertNotNull($approval);
        $this->assertSame('pending', $approval->status);
        $this->assertSame('500.00', $approval->amount);
    }

    public function test_approving_applies_the_discount(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'enrollments.view', 'approvals.review'], '0.00');
        $cohort = $this->cohort($org);
        $person = $this->person($org, '+966500000103');

        Sanctum::actingAs($user);
        $enrollmentId = $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id, 'discount_amount' => 500,
        ])->json('data.id');

        $approvalId = ApprovalRequest::withoutGlobalScopes()->value('id');
        $this->patchJson("/api/v1/approvals/{$approvalId}/approve", ['decision_note' => 'موافق'])
            ->assertOk()->assertJsonPath('data.status', 'approved');

        // التسجيل بعد الاعتماد: الخصم مطبَّق والمسار طبيعي
        $this->getJson("/api/v1/enrollments/{$enrollmentId}")
            ->assertJsonPath('data.status', 'pending_invoice')
            ->assertJsonPath('data.discount_amount_snapshot', '500.00')
            ->assertJsonPath('data.total_snapshot', '575.00'); // (1000-500)*1.15
    }

    public function test_rejecting_keeps_full_price(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage', 'enrollments.view', 'approvals.review'], '0.00');
        $cohort = $this->cohort($org);
        $person = $this->person($org, '+966500000104');

        Sanctum::actingAs($user);
        $enrollmentId = $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id, 'discount_amount' => 500,
        ])->json('data.id');

        $approvalId = ApprovalRequest::withoutGlobalScopes()->value('id');
        $this->patchJson("/api/v1/approvals/{$approvalId}/reject", ['decision_note' => 'مرفوض'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->getJson("/api/v1/enrollments/{$enrollmentId}")
            ->assertJsonPath('data.status', 'pending_invoice')
            ->assertJsonPath('data.discount_amount_snapshot', '0.00')
            ->assertJsonPath('data.total_snapshot', '1150.00');
    }

    public function test_review_requires_permission(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage'], '0.00'); // بلا approvals.review
        $cohort = $this->cohort($org);
        $person = $this->person($org, '+966500000105');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id, 'discount_amount' => 500,
        ])->assertCreated();

        $this->getJson('/api/v1/approvals')->assertForbidden();
    }

    public function test_unlimited_role_applies_any_discount(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage'], null); // غير محدود
        $cohort = $this->cohort($org);
        $person = $this->person($org, '+966500000106');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id, 'discount_amount' => 900,
        ])->assertCreated()
          ->assertJsonPath('data.status', 'pending_invoice')
          ->assertJsonPath('data.discount_amount_snapshot', '900.00');

        $this->assertSame(0, ApprovalRequest::withoutGlobalScopes()->count());
    }
}
