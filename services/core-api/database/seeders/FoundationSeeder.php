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
            ['key' => 'payments.view',    'name_ar' => 'عرض المدفوعات',      'group' => 'finance'],
            ['key' => 'payments.manage',  'name_ar' => 'تسجيل المدفوعات',    'group' => 'finance'],
            // CRM / Leads (PRD §13)
            ['key' => 'leads.view',       'name_ar' => 'عرض العملاء المحتملين', 'group' => 'crm'],
            ['key' => 'leads.manage',     'name_ar' => 'إدارة العملاء المحتملين', 'group' => 'crm'],
            ['key' => 'leads.assign',     'name_ar' => 'إسناد العملاء المحتملين', 'group' => 'crm'],
            // الدورات والتسجيل (PRD §14)
            ['key' => 'courses.view',       'name_ar' => 'عرض الدورات',       'group' => 'training'],
            ['key' => 'courses.manage',     'name_ar' => 'إدارة الدورات والمجموعات', 'group' => 'training'],
            ['key' => 'enrollments.view',   'name_ar' => 'عرض التسجيلات',     'group' => 'training'],
            ['key' => 'enrollments.manage', 'name_ar' => 'إدارة التسجيلات',   'group' => 'training'],
            ['key' => 'approvals.review',   'name_ar' => 'اعتماد الطلبات',     'group' => 'training'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['key' => $p['key']], $p);
        }

        // الأدوار السبعة (PRD §10) مع صلاحياتها وحد الخصم (PRD §15؛ null = غير محدود)
        $roles = [
            'super_admin'         => ['ar' => 'مدير النظام',          'limit' => null, 'perms' => '*'],
            'executive_manager'   => ['ar' => 'المدير التنفيذي',      'limit' => null, 'perms' => ['persons.view', 'users.manage', 'settings.manage', 'audit.view', 'invoices.view', 'leads.view', 'courses.view', 'enrollments.view', 'approvals.review']],
            'branch_manager'      => ['ar' => 'مدير الفرع',           'limit' => '1000.00', 'perms' => ['persons.view', 'persons.manage', 'invoices.view', 'leads.view', 'leads.manage', 'leads.assign', 'courses.view', 'courses.manage', 'enrollments.view', 'enrollments.manage', 'approvals.review']],
            'crm_agent'           => ['ar' => 'موظف علاقات العملاء',  'limit' => '0.00', 'perms' => ['persons.view', 'persons.manage', 'consents.manage', 'leads.view', 'leads.manage', 'courses.view']],
            'registration_officer'=> ['ar' => 'موظف التسجيل',         'limit' => '200.00', 'perms' => ['persons.view', 'persons.manage', 'courses.view', 'enrollments.view', 'enrollments.manage']],
            'accountant'          => ['ar' => 'المحاسب',              'limit' => '0.00', 'perms' => ['invoices.view', 'invoices.issue', 'payments.view', 'payments.manage', 'enrollments.view']],
            'compliance_reviewer' => ['ar' => 'مراجع الامتثال',       'limit' => '0.00', 'perms' => ['audit.view', 'privacy.handle', 'consents.manage', 'persons.viewSensitive']],
        ];

        $allPermissionIds = Permission::pluck('id', 'key');

        foreach ($roles as $key => $def) {
            $role = Role::firstOrCreate(
                ['organization_id' => $org->id, 'key' => $key],
                ['name_ar' => $def['ar'], 'is_system' => true]
            );
            $role->update(['max_discount_amount' => $def['limit']]);

            $ids = $def['perms'] === '*'
                ? $allPermissionIds->values()->all()
                : $allPermissionIds->only($def['perms'])->values()->all();

            $role->permissions()->sync($ids);
        }

        // مصادر Leads الافتراضية (PRD §13)
        $sources = [
            'landing_form' => 'نموذج التقاط',
            'crm_manual' => 'إدخال يدوي',
            'referral' => 'ترشيح',
            'social' => 'وسائل التواصل',
            'walk_in' => 'زيارة',
        ];
        foreach ($sources as $key => $nameAr) {
            \App\Models\LeadSource::firstOrCreate(
                ['organization_id' => $org->id, 'key' => $key],
                ['name_ar' => $nameAr, 'is_active' => true],
            );
        }
    }
}
