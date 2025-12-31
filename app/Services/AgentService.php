<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\User;

class AgentService
{
    protected $userService;

    function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    function createAgent($data): string
    {
        $user = $this->userService->createUser($data);

        Agent::updateOrCreate(
            ['user_id' => $user->id],
            [
                'username' => $data['username'],
                'trn' => $data['trn'],
            ]
        );
        return 'Agent created successfully';
    }

    public function updateUser(User $user, array $data): string
    {
        $this->userService->updateUser($user, $data);
        Agent::updateOrCreate(['user_id' => $user->id], [
            'username' => $data['username'],
            'trn' => $data['trn'],
            'commission' => $data['commission'],
        ]);
        return 'Agent updated successfully.';
    }

    public function deleteAgent(User $user): string
    {
        Agent::where('user_id', $user->id)->delete();
        $this->userService->delete($user);
        return 'Agent deleted successfully';
    }
}
