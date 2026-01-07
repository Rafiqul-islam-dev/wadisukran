<?php

namespace Database\Seeders;

use App\Models\CompannySetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateCompanySetting extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompannySetting::firstOrCreate(
            ['name' => 'Wadi Shukran']
        );

        $this->command->info('✅ Company Setting created.');
    }
}
