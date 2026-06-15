<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * هدف غير قابل للتفاوض: لا تسجيل مكرر للشخص (Exact Match). PRD §11, §27.
 */
class PersonDeduplicationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_duplicate_phone_is_blocked_with_409(): void
    {
        [, $user] = $this->makeTenant();
        Sanctum::actingAs($user);

        // الأرقام بصيغ مختلفة لكنها نفس الرقم بعد التطبيع → يجب أن تتطابق
        $this->postJson('/api/v1/persons', [
            'first_name' => 'علي', 'phone' => '0501112233',
        ])->assertCreated();

        $this->postJson('/api/v1/persons', [
            'first_name' => 'علي مكرر', 'phone' => '+966 50 111 2233',
        ])->assertStatus(409)
          ->assertJsonPath('match.phone_e164', '+966501112233');
    }

    public function test_duplicate_email_is_blocked(): void
    {
        [, $user] = $this->makeTenant();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/persons', [
            'first_name' => 'سارة', 'email' => 'Sara@Example.com',
        ])->assertCreated();

        // اختلاف حالة الأحرف فقط → نفس البريد بعد التطبيع
        $this->postJson('/api/v1/persons', [
            'first_name' => 'سارة ٢', 'email' => 'sara@example.com',
        ])->assertStatus(409);
    }

    public function test_same_phone_in_different_organizations_is_allowed(): void
    {
        [, $userA] = $this->makeTenant();
        [, $userB] = $this->makeTenant();

        Sanctum::actingAs($userA);
        $this->postJson('/api/v1/persons', [
            'first_name' => 'فهد', 'phone' => '0509998877',
        ])->assertCreated();

        // نفس الرقم في مؤسسة أخرى مسموح (العزل بين المؤسسات)
        Sanctum::actingAs($userB);
        $this->postJson('/api/v1/persons', [
            'first_name' => 'فهد آخر', 'phone' => '0509998877',
        ])->assertCreated();
    }
}
