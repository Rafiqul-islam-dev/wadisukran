<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data): string
    {
        if (isset($data['photo'])) {
            $data['photo'] = $this->uploadPhoto($data['photo']);
        }
        $data['password'] = Hash::make($data['password']);

        $user = User::create([
            'user_type' => $data['user_type'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'photo' => $data['photo']
        ]);

        if(isset($data['role'])){
            $user->syncRoles([$data['role']]);
        }

        return 'User Created Successfully';
    }

    protected function uploadPhoto($photo): string
    {
        $filename = time() .rand(). '_' . $photo->getClientOriginalName();
        $photo->storeAs('uploads/users/photos', $filename);
        return 'uploads/users/photos/' . $filename;
    }
}
