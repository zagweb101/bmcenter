<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * هدف غير قابل للتفاوض: لا صلاحية تعتمد على الواجهة. PRD §6, §22, §27.
 */
class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_user_without_view_permission_is_forbidden_from_listing(): void
    {
        [, $user] = $this->makeTenant(permissionKeys: []); // بلا صلاحيات

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/persons')->assertForbidden();
    }

    public function test_user_with_only_view_cannot_create_person(): void
    {
        [, $user] = $this->makeTenant(permissionKeys: ['persons.view']);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/persons', [
            'first_name' => 'منى',
            'phone' => '0500000010',
        ])->assertForbidden();
    }

    public function test_user_with_manage_can_create_person(): void
    {
        [, $user] = $this->makeTenant(permissionKeys: ['persons.view', 'persons.manage']);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/persons', [
            'first_name' => 'منى',
            'phone' => '0500000011',
        ])->assertCreated();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/persons')->assertUnauthorized();
    }
}
