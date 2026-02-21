<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            [
                'user_type' => 'agent',
                'name'      => 'Ahmed Al Mansouri',
                'email'     => 'ahmed@example.com',
                'phone'     => '+971501234567',
                'address'   => 'Dubai, UAE',
                'photo'     => null,
                'join_date' => now(),
                'trn'       => 'TRN100123456',
            ],
            [
                'user_type' => 'agent',
                'name'      => 'Sara Hassan',
                'email'     => 'sara@example.com',
                'phone'     => '+971502345678',
                'address'   => 'Abu Dhabi, UAE',
                'photo'     => null,
                'join_date' => now(),
                'trn'       => 'TRN100234567',
            ],
            [
                'user_type' => 'agent',
                'name'      => 'Mohammed Khalid',
                'email'     => 'mohammed@example.com',
                'phone'     => '+971503456789',
                'address'   => 'Sharjah, UAE',
                'photo'     => null,
                'join_date' => now(),
                'trn'       => 'TRN100345678',
            ],
            [
                'user_type' => 'agent',
                'name'      => 'Fatima Al Zaabi',
                'email'     => 'fatima@example.com',
                'phone'     => '+971504567890',
                'address'   => 'Ajman, UAE',
                'photo'     => null,
                'join_date' => now(),
                'trn'       => 'TRN100456789',
            ],
            [
                'user_type' => 'agent',
                'name'      => 'Omar Bin Rashid',
                'email'     => 'omar@example.com',
                'phone'     => '+971505678901',
                'address'   => 'Ras Al Khaimah, UAE',
                'photo'     => null,
                'join_date' => now(),
                'trn'       => 'TRN100567890',
            ],
        ];

        foreach ($agents as $agentData) {
            // Generate username from first word of name
            $firstWord = strtolower(strtok($agentData['name'], ' '));
            do {
                $randomNumber = rand(100, 999);
                $username     = $firstWord . '-' . $randomNumber;
            } while (Agent::where('username', $username)->exists());

            $user = User::create([
                'user_type' => $agentData['user_type'],
                'name'      => $agentData['name'],
                'email'     => $agentData['email'],
                'password'  => Hash::make($username), // password = username (same as controller)
                'phone'     => $agentData['phone'],
                'address'   => $agentData['address'],
                'photo'     => $agentData['photo'],
                'join_date' => $agentData['join_date'],
                'role_id'   => 2
            ]);

            Agent::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'username' => $username,
                    'trn'      => $agentData['trn'],
                    'commission' => 10
                ]
            );
            if (! $user->hasRole('Agent')) {
                $user->syncRoles(['Agent']);
            }
        }
    }
}
