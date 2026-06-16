<?php

namespace Tests\Feature;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * المطابقة الاحتمالية (Probabilistic Match) — للمراجعة. PRD §11 (0B).
 */
class ProbabilisticMatchTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_similar_name_is_suggested_as_candidate(): void
    {
        [$org, $user] = $this->makeTenant(['persons.view', 'persons.manage']);
        $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'محمد', 'full_name' => 'محمد العلي',
            'phone_e164' => '+966500000001',
        ]));

        Sanctum::actingAs($user);
        $res = $this->postJson('/api/v1/persons/match', ['full_name' => 'محمد العلى'])
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, count($res->json('candidates')));
        $this->assertSame('محمد العلي', $res->json('candidates.0.person.full_name'));
    }

    public function test_same_national_number_different_country_format_matches(): void
    {
        [$org, $user] = $this->makeTenant(['persons.view']);
        $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'سارة', 'full_name' => 'سارة',
            'phone_e164' => '+966501234567',
        ]));

        Sanctum::actingAs($user);
        // نفس الرقم الوطني بصيغة محلية (0501234567) → آخر 9 أرقام متطابقة
        $res = $this->postJson('/api/v1/persons/match', ['phone' => '0501234567'])
            ->assertOk();

        $this->assertSame(1, count($res->json('candidates')));
        $this->assertEqualsWithDelta(1.0, (float) $res->json('candidates.0.match_score'), 0.001);
    }

    public function test_unrelated_query_returns_no_candidates(): void
    {
        [$org, $user] = $this->makeTenant(['persons.view']);
        $this->withinTenant($org, fn () => Person::create([
            'organization_id' => $org->id, 'first_name' => 'خالد', 'full_name' => 'خالد القحطاني',
            'phone_e164' => '+966505555555',
        ]));

        Sanctum::actingAs($user);
        $res = $this->postJson('/api/v1/persons/match', [
            'full_name' => 'عبدالرحمن الزهراني', 'phone' => '0539999999',
        ])->assertOk();

        $this->assertCount(0, $res->json('candidates'));
    }

    public function test_candidates_are_scoped_to_organization(): void
    {
        [, $userA] = $this->makeTenant(['persons.view']);
        [$orgB] = $this->makeTenant(['persons.view']);
        $this->withinTenant($orgB, fn () => Person::create([
            'organization_id' => $orgB->id, 'first_name' => 'منى', 'full_name' => 'منى',
            'phone_e164' => '+966507777777',
        ]));

        Sanctum::actingAs($userA);
        $res = $this->postJson('/api/v1/persons/match', ['phone' => '0507777777'])->assertOk();

        // شخص المؤسسة الأخرى لا يظهر كمرشّح (OrganizationScope)
        $this->assertCount(0, $res->json('candidates'));
    }
}
