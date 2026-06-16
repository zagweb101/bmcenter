<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * أدوات إنشاء مؤسسات/مستخدمين للاختبارات (عزل + صلاحيات).
 */
trait CreatesTenants
{
    /**
     * ينشئ مؤسسة + دورًا بالصلاحيات المحددة + مستخدمًا مربوطًا بها.
     *
     * @return array{0: Organization, 1: User}
     */
    protected function makeTenant(array $permissionKeys = ['persons.view', 'persons.manage'], ?string $discountLimit = null): array
    {
        $org = Organization::create([
            'name_ar' => 'مؤسسة ' . Str::random(4),
            'slug' => 'org-' . Str::random(8),
            'country_code' => 'SA',
            'default_currency' => 'SAR',
            'is_active' => true,
        ]);

        $permissions = collect($permissionKeys)->map(
            fn ($key) => Permission::firstOrCreate(['key' => $key], ['name_ar' => $key])
        );

        app(Tenancy::class)->set($org->id);

        $role = Role::create([
            'organization_id' => $org->id,
            'key' => 'role_' . Str::random(5),
            'name_ar' => 'دور اختبار',
            'is_system' => false,
            'max_discount_amount' => $discountLimit, // null = غير محدود (PRD §15)
        ]);
        $role->permissions()->sync($permissions->pluck('id')->all());

        $user = User::create([
            'name' => 'مستخدم ' . Str::random(4),
            'email' => Str::random(10) . '@test.local',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        app(Tenancy::class)->forget();

        return [$org, $user];
    }

    /**
     * ينفّذ إغلاقًا ضمن سياق مؤسسة محددة ثم يعيد ضبط السياق.
     */
    protected function withinTenant(Organization $org, callable $callback): mixed
    {
        app(Tenancy::class)->set($org->id);
        try {
            return $callback();
        } finally {
            app(Tenancy::class)->forget();
        }
    }
}
