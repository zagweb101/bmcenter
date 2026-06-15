<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(FoundationSeeder::class);

        // مستخدم Super Admin أولي مربوط بالمؤسسة الافتراضية (تطوير فقط)
        $org = Organization::where('slug', 'bayt-almoswer')->first();

        if ($org) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@baytalmoswer.net'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password'),
                    'organization_id' => $org->id,
                    'is_active' => true,
                ]
            );

            // اضبط سياق المؤسسة ثم اربط دور super_admin (تطوير فقط)
            app(Tenancy::class)->set($org->id);
            $superAdmin = Role::where('key', 'super_admin')->first();
            if ($superAdmin) {
                $admin->roles()->syncWithoutDetaching([$superAdmin->id]);
            }
        }
    }
}
