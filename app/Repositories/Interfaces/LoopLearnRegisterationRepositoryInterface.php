<?php

namespace App\Repositories\Interfaces;

use App\Models\LoopLearnRegisteration;

interface LoopLearnRegisterationRepositoryInterface
{
    public function create(array $data): LoopLearnRegisteration;
}
