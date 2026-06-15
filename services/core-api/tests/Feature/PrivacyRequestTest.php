<?php

namespace Tests\Feature;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * طلبات حقوق صاحب البيانات — Workflow موثّق. PRD §19.5.
 */
class PrivacyRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_creating_request_sets_received_status_and_due_date(): void
    {
        [$org, $user] = $this->makeTenant(['privacy.handle']);
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'عبدالله']));

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/privacy-requests', [
            'person_id' => $person->id,
            'type' => 'access',
            'subject_reason' => 'أريد نسخة من بياناتي',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'access')
            ->assertJsonPath('data.status', 'received');
        $this->assertNotNull($response->json('data.due_at'));
    }

    public function test_fulfilling_request_records_handler_and_resolution(): void
    {
        [$org, $user] = $this->makeTenant(['privacy.handle']);
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'منيرة']));

        Sanctum::actingAs($user);
        $id = $this->postJson('/api/v1/privacy-requests', [
            'person_id' => $person->id, 'type' => 'erase',
        ])->json('data.id');

        $this->patchJson("/api/v1/privacy-requests/{$id}", [
            'status' => 'fulfilled',
            'identity_verified' => true,
            'verification_method' => 'national_id',
            'resolution_note' => 'تم التنفيذ بعد التحقق',
        ])->assertOk()
          ->assertJsonPath('data.status', 'fulfilled')
          ->assertJsonPath('data.identity_verified', true)
          ->assertJsonPath('data.handled_by_user_id', $user->id);
    }

    public function test_privacy_requests_require_handle_permission(): void
    {
        [, $user] = $this->makeTenant(['persons.view']); // بلا privacy.handle

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/privacy-requests')->assertForbidden();
    }

    public function test_cross_organization_privacy_request_is_not_visible(): void
    {
        [, $userA] = $this->makeTenant(['privacy.handle']);
        [$orgB, $userB] = $this->makeTenant(['privacy.handle']);

        Sanctum::actingAs($userB);
        $id = $this->postJson('/api/v1/privacy-requests', ['type' => 'inform'])->json('data.id');

        // مستخدم المؤسسة الأخرى لا يرى الطلب (OrganizationScope) → 404
        Sanctum::actingAs($userA);
        $this->getJson("/api/v1/privacy-requests/{$id}")->assertNotFound();
    }
}
