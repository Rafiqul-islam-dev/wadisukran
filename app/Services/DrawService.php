<?php

namespace App\Services;

use App\Models\Win;

class DrawService{
    public function createWin(array $data) : string {
        Win::create($data);
        return 'Win created successfully';
    }
}
