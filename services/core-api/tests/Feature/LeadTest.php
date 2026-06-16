<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * CRM / Leads — قواعد Pipeline + عزل + صلاحيات. PRD §13, §27.
 */
class LeadTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeSource(Organization $org): LeadSource
    {
        return $this->withinTenant($org, fn () => LeadSource::create([
            'organization_id' => $org->id,
            'key' => 'landing_form',
            'name_ar' => 'نموذج التقاط',
        ]));
    }

    public function test_create_requires_manage_permission(): void
    {
        [$org, $user] = $this->makeTenant(['leads.view']); // بلا leads.manage
        $source = $this->makeSource($org);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/leads', [
            'full_name' => 'سالم',
            'lead_source_id' => $source->id,
        ])->assertForbidden();
    }

    public function test_create_lead_succeeds_and_starts_new(): void
    {
        [$org, $user] = $this->makeTenant(['leads.view', 'leads.manage']);
        $source = $this->makeSource($org);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/leads', [
            'full_name' => 'سالم',
            'phone' => '0501112233',
            'lead_source_id' => $source->id,
        ])->assertCreated()
          ->assertJsonPath('data.stage', 'new')
          ->assertJsonPath('data.phone_e164', '+966501112233');
    }

    public function test_closing_as_lost_requires_reason(): void
    {
        [$org, $user] = $this->makeTenant(['leads.view', 'leads.manage']);
        $source = $this->makeSource($org);
        $lead = $this->withinTenant($org, fn () => Lead::create([
            'organization_id' => $org->id, 'full_name' => 'نواف',
            'lead_source_id' => $source->id, 'stage' => 'contacted',
        ]));

        Sanctum::actingAs($user);

        // بلا سبب → 422
        $this->patchJson("/api/v1/leads/{$lead->id}/transition", ['stage' => 'lost'])
            ->assertStatus(422);

        // مع سبب → ok + status lost
        $this->patchJson("/api/v1/leads/{$lead->id}/transition", [
            'stage' => 'lost', 'lost_reason' => 'تغيّر القرار',
        ])->assertOk()
          ->assertJsonPath('data.status', 'lost')
          ->assertJsonPath('data.lost_reason', 'تغيّر القرار');
    }

    public function test_system_stage_cannot_be_set_manually(): void
    {
        [$org, $user] = $this->makeTenant(['leads.view', 'leads.manage']);
        $source = $this->makeSource($org);
        $lead = $this->withinTenant($org, fn () => Lead::create([
            'organization_id' => $org->id, 'full_name' => 'هيا',
            'lead_source_id' => $source->id,
        ]));

        Sanctum::actingAs($user);
        // 'enrolled' مرحلة نظام لا تُضبط يدويًا
        $this->patchJson("/api/v1/leads/{$lead->id}/transition", ['stage' => 'enrolled'])
            ->assertStatus(422);
    }

    public function test_lead_is_isolated_across_organizations(): void
    {
        [, $userA] = $this->makeTenant(['leads.view']);
        [$orgB] = $this->makeTenant(['leads.view']);
        $sourceB = $this->makeSource($orgB);
        $leadB = $this->withinTenant($orgB, fn () => Lead::create([
            'organization_id' => $orgB->id, 'full_name' => 'سرّي',
            'lead_source_id' => $sourceB->id,
        ]));

        Sanctum::actingAs($userA);
        $this->getJson("/api/v1/leads/{$leadB->id}")->assertNotFound();
    }

    public function test_convert_creates_and_links_new_person(): void
    {
        [$org, $user] = $this->makeTenant(['leads.view', 'leads.manage']);
        $source = $this->makeSource($org);
        $lead = $this->withinTenant($org, fn () => Lead::create([
            'organization_id' => $org->id, 'full_name' => 'ماجد جديد',
            'phone_e164' => '+966500001111', 'lead_source_id' => $source->id,
        ]));

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/leads/{$lead->id}/convert")
            ->assertCreated()
            ->assertJsonPath('matched', false);

        $this->assertNotNull($lead->fresh()->person_id);
    }

    public function test_convert_links_to_existing_matching_person(): void
    {
        [$org, $user] = $this->makeTenant(['leads.view', 'leads.manage']);
        $source = $this->makeSource($org);

        $person = $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'قائم', 'phone_e164' => '+966500002222',
        ]));
        $lead = $this->withinTenant($org, fn () => Lead::create([
            'organization_id' => $org->id, 'full_name' => 'نفس الرقم',
            'phone_e164' => '+966500002222', 'lead_source_id' => $source->id,
        ]));

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/leads/{$lead->id}/convert")
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('person.id', $person->id);

        // لم يُنشأ شخص مكرر
        $this->assertSame(1, Person::withoutGlobalScopes()->where('organization_id', $org->id)->count());
        $this->assertSame($person->id, $lead->fresh()->person_id);
    }

    public function test_assign_sets_owner_and_advances_stage(): void
    {
        [$org, $manager] = $this->makeTenant(['leads.view', 'leads.manage', 'leads.assign']);
        $source = $this->makeSource($org);
        $lead = $this->withinTenant($org, fn () => Lead::create([
            'organization_id' => $org->id, 'full_name' => 'فيصل',
            'lead_source_id' => $source->id, 'stage' => 'new',
        ]));

        Sanctum::actingAs($manager);
        $this->patchJson("/api/v1/leads/{$lead->id}/assign", [
            'owner_user_id' => $manager->id,
        ])->assertOk()
          ->assertJsonPath('data.owner_user_id', $manager->id)
          ->assertJsonPath('data.stage', 'assigned');
    }
}
