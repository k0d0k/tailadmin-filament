<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeds a sample principal hierarchy so the demo accounts have somewhere to
 * belong. Safe to re-run: records are matched by their unique code.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $principal = Principal::firstOrCreate(
            ['code' => 'PRN-001'],
            [
                'name' => 'PT Contoh Utama',
                'email' => 'hello@example.com',
                'is_active' => true,
            ],
        );

        foreach ([
            'TNT-001' => 'Tenant Jakarta',
            'TNT-002' => 'Tenant Surabaya',
        ] as $code => $name) {
            Tenant::firstOrCreate(
                ['principal_id' => $principal->id, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
