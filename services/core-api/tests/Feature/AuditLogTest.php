<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Person;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * سجل التدقيق يعمل ويُخفي البيانات الحساسة. PRD §6, §11, §22.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_creating_person_writes_an_audit_log(): void
    {
        [$org] = $this->makeTenant();

        $person = $this->withinTenant($org, fn () => Person::create([
            'first_name' => 'نورة', 'full_name' => 'نورة', 'phone_e164' => '+966500000099',
        ]));

        $log = AuditLog::where('subject_type', $person->getMorphClass())
            ->where('subject_id', $person->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log, 'يجب كتابة سجل تدقيق عند إنشاء شخص');
        $this->assertSame($org->id, $log->organization_id);
    }

    public function test_sensitive_fields_are_redacted_in_audit_values(): void
    {
        [$org] = $this->makeTenant();

        $person = $this->withinTenant($org, fn () => Person::create([
            'first_name' => 'بدر',
            'national_id_encrypted' => '1234567890',
            'national_id_hash' => hash('sha256', '1234567890'),
        ]));

        $log = AuditLog::where('subject_type', $person->getMorphClass())
            ->where('subject_id', $person->id)->where('action', 'created')->first();

        $this->assertSame('[REDACTED]', $log->new_values['national_id_encrypted'] ?? null);
        $this->assertSame('[REDACTED]', $log->new_values['national_id_hash'] ?? null);
        $this->assertSame('بدر', $log->new_values['first_name'] ?? null);
    }

    public function test_updating_person_logs_only_changed_fields(): void
    {
        [$org] = $this->makeTenant();

        $person = $this->withinTenant($org, fn () => Person::create([
            'first_name' => 'ليلى', 'full_name' => 'ليلى',
        ]));

        $this->withinTenant($org, fn () => $person->update(['first_name' => 'ليلى المعدّلة']));

        $log = AuditLog::where('subject_type', $person->getMorphClass())
            ->where('subject_id', $person->id)->where('action', 'updated')->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('first_name', $log->new_values);
        $this->assertSame('ليلى', $log->old_values['first_name']);
        $this->assertSame('ليلى المعدّلة', $log->new_values['first_name']);
    }

    public function test_activity_timeline_requires_audit_permission(): void
    {
        [$org, $userNoAudit] = $this->makeTenant(['persons.view', 'persons.manage']);
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'تركي']));

        Sanctum::actingAs($userNoAudit);
        $this->getJson("/api/v1/persons/{$person->id}/activity")->assertForbidden();
    }

    public function test_activity_timeline_returns_logs_with_permission(): void
    {
        [$org, $user] = $this->makeTenant(['persons.view', 'persons.manage', 'audit.view']);
        $person = $this->withinTenant($org, fn () => Person::create(['first_name' => 'ماجد']));

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/persons/{$person->id}/activity")
            ->assertOk()
            ->assertJsonFragment(['action' => 'created']);
    }

    public function test_revealing_national_id_logs_sensitive_access(): void
    {
        [$org, $user] = $this->makeTenant(['persons.view', 'persons.viewSensitive']);
        $person = $this->withinTenant($org, fn () => Person::create([
            'first_name' => 'هند', 'national_id_encrypted' => '1010101010',
        ]));

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/persons/{$person->id}/national-id")
            ->assertOk()
            ->assertJsonPath('national_id', '1010101010');

        $this->assertDatabaseHas('audit_logs', [
            'subject_id' => $person->id,
            'action' => 'viewed_sensitive',
            'actor_user_id' => $user->id,
        ]);
    }
}
