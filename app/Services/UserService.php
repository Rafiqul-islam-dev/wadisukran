<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data): User
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
            'photo' => $data['photo'],
            'join_date' => $data['join_date'] ?? now()
        ]);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user;
    }

    protected function uploadPhoto($photo): string
    {
        $filename = time() . rand() . '_' . $photo->getClientOriginalName();
        $photo->storeAs('uploads/users/photos', $filename);
        return 'uploads/users/photos/' . $filename;
    }

    public function delete(User $user): ?bool
    {
        return $user->delete();
    }
    public function statusChange(User $user): ?bool
    {
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();
        return 'Status changed successfully.';
    }

    public function updateUser(User $user, array $data): string
    {
        if (isset($data['photo'])) {
            $data['photo'] = $this->uploadPhoto($data['photo']);
            if(file_exists(public_path($user->photo))){
                unlink(public_path($user->photo));
            }
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update([
            'user_type' => $data['user_type'] ?? $user->user_type,
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'password' => $data['password'] ?? $user->password,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
            'photo' => $data['photo'] ?? $user->photo,
            'join_date' => $data['join_date'] ?? $user->join_date,
        ]);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return 'User Updated Successfully';
    }
}
