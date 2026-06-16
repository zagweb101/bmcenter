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
 * نقل التسجيل بين المجموعات. PRD §14.
 */
class EnrollmentTransferTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function cohort(Organization $org, string $price, int $capacity = 0): Cohort
    {
        return $this->withinTenant($org, function () use ($org, $price, $capacity) {
            $course = Course::create(['organization_id' => $org->id, 'name_ar' => 'دورة', 'default_price' => $price]);
            return Cohort::create([
                'organization_id' => $org->id, 'course_id' => $course->id, 'name' => 'دفعة',
                'capacity' => $capacity, 'price' => $price, 'tax_rate' => '15.00', 'status' => 'enrollment_open',
            ]);
        });
    }

    public function test_transfer_releases_old_seat_and_resnapshots_target_price(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.view', 'enrollments.manage']);
        $from = $this->cohort($org, '1000.00');
        $to = $this->cohort($org, '2000.00');
        $person = $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'طالب', 'phone_e164' => '+966500000201',
        ]));

        Sanctum::actingAs($user);
        $enrollmentId = $this->postJson('/api/v1/enrollments', [
            'cohort_id' => $from->id, 'person_id' => $person->id,
        ])->json('data.id');

        // النقل إلى مجموعة أغلى → إعادة snapshot على سعر الهدف
        $this->postJson("/api/v1/enrollments/{$enrollmentId}/transfer", ['cohort_id' => $to->id])
            ->assertCreated()
            ->assertJsonPath('data.cohort_id', $to->id)
            ->assertJsonPath('data.status', 'pending_invoice')
            ->assertJsonPath('data.price_snapshot', '2000.00')
            ->assertJsonPath('data.total_snapshot', '2300.00'); // 2000 * 1.15

        // المقعد القديم محرَّر (transferred)
        $this->assertSame('transferred', Enrollment::withoutGlobalScopes()->find($enrollmentId)->status);
    }

    public function test_transfer_to_full_cohort_goes_to_waitlist(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage']);
        $from = $this->cohort($org, '1000.00');
        $to = $this->cohort($org, '1000.00', capacity: 1);
        $p1 = $this->withinTenant($org, fn () => Person::create(['organization_id' => $org->id, 'first_name' => 'أ', 'phone_e164' => '+966500000211']));
        $p2 = $this->withinTenant($org, fn () => Person::create(['organization_id' => $org->id, 'first_name' => 'ب', 'phone_e164' => '+966500000212']));

        Sanctum::actingAs($user);
        // املأ مقعد الهدف الوحيد
        $this->postJson('/api/v1/enrollments', ['cohort_id' => $to->id, 'person_id' => $p1->id])->assertCreated();
        // سجّل p2 في from ثم انقله للهدف الممتلئ
        $eId = $this->postJson('/api/v1/enrollments', ['cohort_id' => $from->id, 'person_id' => $p2->id])->json('data.id');

        $this->postJson("/api/v1/enrollments/{$eId}/transfer", ['cohort_id' => $to->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'waitlisted');
    }

    public function test_cannot_transfer_to_same_cohort(): void
    {
        [$org, $user] = $this->makeTenant(['enrollments.manage']);
        $cohort = $this->cohort($org, '1000.00');
        $person = $this->withinTenant($org, fn () => Person::create(['organization_id' => $org->id, 'first_name' => 'ج', 'phone_e164' => '+966500000221']));

        Sanctum::actingAs($user);
        $eId = $this->postJson('/api/v1/enrollments', ['cohort_id' => $cohort->id, 'person_id' => $person->id])->json('data.id');

        $this->postJson("/api/v1/enrollments/{$eId}/transfer", ['cohort_id' => $cohort->id])
            ->assertStatus(422);
    }
}
