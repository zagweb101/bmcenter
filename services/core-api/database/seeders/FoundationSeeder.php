<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * بذور الأساس المؤسسي (MVP-0A). PRD §10.
 * مؤسسة افتراضية + الأدوار السبعة + قاموس صلاحيات أولي.
 * idempotent: يمكن إعادة تشغيله بأمان.
 */
class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::firstOrCreate(
            ['slug' => 'bayt-almoswer'],
            [
                'name_ar' => 'بيت المصور',
                'name_en' => 'BAYT ALMOSWER ACADEMY',
                'country_code' => 'SA',
                'default_currency' => 'SAR',
                'is_active' => true,
            ]
        );

        // قاموس الصلاحيات الأولي (يُوسَّع عبر 0B/0C)
        $permissions = [
            ['key' => 'persons.view',     'name_ar' => 'عرض الأشخاص',        'group' => 'person360'],
            ['key' => 'persons.manage',   'name_ar' => 'إدارة الأشخاص',      'group' => 'person360'],
            ['key' => 'persons.merge',    'name_ar' => 'دمج الأشخاص',        'group' => 'person360'],
            ['key' => 'persons.viewSensitive', 'name_ar' => 'عرض البيانات الحساسة', 'group' => 'person360'],
            ['key' => 'users.manage',     'name_ar' => 'إدارة المستخدمين',   'group' => 'admin'],
            ['key' => 'roles.manage',     'name_ar' => 'إدارة الأدوار',      'group' => 'admin'],
            ['key' => 'settings.manage',  'name_ar' => 'إدارة الإعدادات',    'group' => 'admin'],
            ['key' => 'consents.manage',  'name_ar' => 'إدارة الموافقات',    'group' => 'privacy'],
            ['key' => 'privacy.handle',   'name_ar' => 'معالجة طلبات الخصوصية', 'group' => 'privacy'],
            ['key' => 'audit.view',       'name_ar' => 'عرض سجل التدقيق',    'group' => 'compliance'],
            ['key' => 'invoices.view',    'name_ar' => 'عرض الفواتير',       'group' => 'finance'],
            ['key' => 'invoices.issue',   'name_ar' => 'إصدار الفواتير',     'group' => 'finance'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['key' => $p['key']], $p);
        }

        // الأدوار السبعة (PRD §10) مع صلاحياتها الأولية
        $roles = [
            'super_admin'         => ['ar' => 'مدير النظام',          'perms' => '*'],
            'executive_manager'   => ['ar' => 'المدير التنفيذي',      'perms' => ['persons.view', 'users.manage', 'settings.manage', 'audit.view', 'invoices.view']],
            'branch_manager'      => ['ar' => 'مدير الفرع',           'perms' => ['persons.view', 'persons.manage', 'invoices.view']],
            'crm_agent'           => ['ar' => 'موظف علاقات العملاء',  'perms' => ['persons.view', 'persons.manage', 'consents.manage']],
            'registration_officer'=> ['ar' => 'موظف التسجيل',         'perms' => ['persons.view', 'persons.manage']],
            'accountant'          => ['ar' => 'المحاسب',              'perms' => ['invoices.view', 'invoices.issue']],
            'compliance_reviewer' => ['ar' => 'مراجع الامتثال',       'perms' => ['audit.view', 'privacy.handle', 'consents.manage', 'persons.viewSensitive']],
        ];

        $allPermissionIds = Permission::pluck('id', 'key');

        foreach ($roles as $key => $def) {
            $role = Role::firstOrCreate(
                ['organization_id' => $org->id, 'key' => $key],
                ['name_ar' => $def['ar'], 'is_system' => true]
            );

            $ids = $def['perms'] === '*'
                ? $allPermissionIds->values()->all()
                : $allPermissionIds->only($def['perms'])->values()->all();

            $role->permissions()->sync($ids);
        }
    }
}
