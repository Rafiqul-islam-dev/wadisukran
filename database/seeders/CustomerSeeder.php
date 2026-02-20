<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 10; $i++) {
            Customer::create([
                'name'  => $faker->name,
                'phone' => $faker->unique()->numerify('01#########'), // Bangladesh style
                'email' => $faker->unique()->safeEmail,
            ]);
        }
    }
}
