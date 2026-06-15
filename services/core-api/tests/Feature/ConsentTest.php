<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * الموافقات مسنودة بدليل وأغراض منفصلة. PRD §19.4.
 */
class ConsentTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_recording_a_consent_persists_evidence(): void
    {
        [$org, $user] = $this->makeTenant(['consents.manage']);
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'ريم']));

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/persons/{$person->id}/consents", [
            'purpose' => 'marketing',
            'text_version' => 'v1',
            'channel' => 'landing',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.purpose', 'marketing')
            ->assertJsonPath('data.status', 'granted')
            ->assertJsonPath('data.is_active', true);

        $consent = Consent::first();
        $this->assertNotNull($consent->granted_at);
        $this->assertNotNull($consent->evidence_ip);
    }

    public function test_withdrawing_a_consent_sets_status_and_timestamp(): void
    {
        [$org, $user] = $this->makeTenant(['consents.manage']);
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'وليد']));
        $consent = $this->withinTenant($org, fn () => $person->consents()->create([
            'organization_id' => $org->id, 'purpose' => 'whatsapp',
            'text_version' => 'v1', 'status' => 'granted', 'granted_at' => now(),
        ]));

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/consents/{$consent->id}/withdraw")
            ->assertOk()
            ->assertJsonPath('data.status', 'withdrawn')
            ->assertJsonPath('data.is_active', false);

        $this->assertNotNull($consent->fresh()->withdrawn_at);
    }

    public function test_consent_requires_manage_permission(): void
    {
        [$org, $user] = $this->makeTenant(['persons.view']); // بلا consents.manage
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'هيا']));

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/persons/{$person->id}/consents", [
            'purpose' => 'privacy', 'text_version' => 'v1',
        ])->assertForbidden();
    }

    public function test_invalid_purpose_is_rejected(): void
    {
        [$org, $user] = $this->makeTenant(['consents.manage']);
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'سلمى']));

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/persons/{$person->id}/consents", [
            'purpose' => 'spam', 'text_version' => 'v1',
        ])->assertStatus(422);
    }
}
