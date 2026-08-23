<?php

namespace App\Services;

use App\DTOs\LoopLearnRegisterationDTO; 
use App\Repositories\Interfaces\LoopLearnRegisterationRepositoryInterface; 

class LoopLearnRegistrationService
{
    public function __construct(
        protected LoopLearnRegisterationRepositoryInterface $repository
    ) {}

    public function register(LoopLearnRegisterationDTO $dto)
    {
        // در اینجا می‌توانید لاجیک‌های اضافی (مثل ارسال SMS یا چک کردن محدودیت‌ها) را اضافه کنید
        return $this->repository->create($dto->toArray());
    }
}
