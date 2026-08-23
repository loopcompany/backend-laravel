<?php
namespace App\Repositories\Eloquent;

use App\Models\LoopLearnRegisteration;
use App\Repositories\Interfaces\LoopLearnRegisterationRepositoryInterface; 

class EloquentLoopLearnRegistrationRepository implements LoopLearnRegisterationRepositoryInterface
{
    public function create(array $data): LoopLearnRegisteration
    {
        return LoopLearnRegisteration::create($data);
    }
}
