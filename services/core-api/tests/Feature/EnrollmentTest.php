<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * التسجيل — السعة وSnapshot. PRD §14, §15, §27 (تجاوز السعة = 0).
 */
class EnrollmentTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeCohort(Organization $org, int $capacity = 0, string $price = '1000.00', string $tax = '15.00'): Cohort
    {
        return $this->withinTenant($org, function () use ($org, $capacity, $price, $tax) {
            $course = Course::create([
                'organization_id' => $org->id, 'name_ar' => 'تصوير', 'default_price' => $price,
            ]);
            return Cohort::create([
                'organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'دفعة 1',
                'capacity' => $capacity, 'price' => $price, 'tax_rate' => $tax, 'status' => 'enrollment_open',
            ]);
        });
    }

    private function makePerson(Organization $org, string $phone): Person
    {
        return $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'طالب', 'phone_e164' => $phone,
        ]));
    }

    public function test_enroll_computes_price_tax_snapshot(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.view', 'enrollments.manage']);
        $cohort = $this->makeCohort($org, capacity: 10, price: '1000.00', tax: '15.00');
        $person = $this->makePerson($org, '+966500000001');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id,
        ])->assertCreated()
          ->assertJsonPath('data.status', 'pending_invoice')
          ->assertJsonPath('data.price_snapshot', '1000.00')
          ->assertJsonPath('data.tax_amount_snapshot', '150.00')
          ->assertJsonPath('data.total_snapshot', '1150.00');
    }

    public function test_discount_snapshot_is_applied(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage']);
        $cohort = $this->makeCohort($org, capacity: 10, price: '1000.00', tax: '15.00');
        $person = $this->makePerson($org, '+966500000002');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $cohort->id, 'person_id' => $person->id,
            'discount_amount' => 100, 'discount_reason' => 'طالب سابق',
        ])->assertCreated()
          ->assertJsonPath('data.discount_amount_snapshot', '100.00')
          ->assertJsonPath('data.tax_amount_snapshot', '135.00')   // (1000-100)*15%
          ->assertJsonPath('data.total_snapshot', '1035.00');
    }

    public function test_capacity_is_never_exceeded_extra_goes_to_waitlist(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage']);
        $cohort = $this->makeCohort($org, capacity: 1);
        $p1 = $this->makePerson($org, '+966500000011');
        $p2 = $this->makePerson($org, '+966500000012');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohort->id, 'person_id' => $p1->id])
            ->assertCreated()->assertJsonPath('data.status', 'pending_invoice');

        // المقعد ممتلئ → قائمة انتظار (تجاوز السعة = 0)
        $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohort->id, 'person_id' => $p2->id])
            ->assertCreated()->assertJsonPath('data.status', 'waitlisted');
    }

    public function test_duplicate_enrollment_is_blocked(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage']);
        $cohort = $this->makeCohort($org, capacity: 10);
        $person = $this->makePerson($org, '+966500000020');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohort->id, 'person_id' => $person->id])
            ->assertCreated();
        $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohort->id, 'person_id' => $person->id])
            ->assertStatus(422);
    }

    public function test_requires_manage_permission(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.view']); // بلا manage
        $cohort = $this->makeCohort($org, capacity: 10);
        $person = $this->makePerson($org, '+966500000030');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohort->id, 'person_id' => $person->id])
            ->assertForbidden();
    }

    public function test_cannot_enroll_into_another_organizations_cohort(): void
    {
        [, $userA] = $this->makeTenant(['enrollments.manage']);
        [$orgB] = $this->makeTenant(['enrollments.manage']);
        $cohortB = $this->makeCohort($orgB, capacity: 10);
        $personB = $this->makePerson($orgB, '+966500000040');

        Sanctum::actingAs($userA);
        // كيانات المؤسسة الأخرى محجوبة بالـ scope → 422
        $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohortB->id, 'person_id' => $personB->id])
            ->assertStatus(422);
    }
}
