<?php

namespace Tests\Feature;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * هدف غير قابل للتفاوض: تسرّب بين المؤسسات = 0. PRD §12, §27, ADR-003.
 */
class OrganizationIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_user_only_sees_persons_from_their_own_organization(): void
    {
        [$orgA, $userA] = $this->makeTenant();
        [$orgB] = $this->makeTenant();

        $personA = $this->withinTenant($orgA, fn () => Person::create([
            'first_name' => 'أحمد', 'full_name' => 'أحمد من أ', 'phone_e164' => '+966500000001',
        ]));
        $this->withinTenant($orgB, fn () => Person::create([
            'first_name' => 'خالد', 'full_name' => 'خالد من ب', 'phone_e164' => '+966500000002',
        ]));

        Sanctum::actingAs($userA);
        $response = $this->getJson('/api/v1/persons');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($personA->id), 'يجب أن يرى المستخدم شخص مؤسسته');
        $this->assertCount(1, $ids, 'يجب ألا يرى أشخاص مؤسسة أخرى (تسرّب = 0)');
    }

    public function test_user_cannot_access_person_of_another_organization(): void
    {
        [, $userA] = $this->makeTenant();
        [$orgB] = $this->makeTenant();

        $personB = $this->withinTenant($orgB, fn () => Person::create([
            'first_name' => 'سعيد', 'full_name' => 'سعيد من ب', 'phone_e164' => '+966500000003',
        ]));

        Sanctum::actingAs($userA);

        // OrganizationScope يحجب السجل → 404 وليس 403 (عدم الكشف عن وجوده)
        $this->getJson("/api/v1/persons/{$personB->id}")->assertNotFound();
    }
}
