<?php
namespace App\DTOs;

class LoopLearnRegisterationDTO
{
    public function __construct(
        public readonly string $class,
        public readonly string $register_as,
        public readonly string $mastery_soft_level,
        public readonly string $mastery_hard_level,
        public readonly string $goal,
        public readonly string $name,
        public readonly string $lname,
        public readonly string $birth_date,
        public readonly string $marriage,
        public readonly string $gender,
        public readonly string $nationality,
        public readonly string $education,
        public readonly string $phone,
        public readonly ?string $telephone,
        public readonly string $address,
        public readonly string $vehicle,
        public readonly string $certificate,
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            class: $request->validated('class'),
            register_as: $request->validated('register_as'),
            mastery_soft_level: $request->validated('mastery_soft_level'),
            mastery_hard_level: $request->validated('mastery_hard_level'),
            goal: $request->validated('goal'),
            name: $request->validated('name'),
            lname: $request->validated('lname'),
            birth_date: $request->validated('birth_date'),
            marriage: $request->validated('marriage'),
            gender: $request->validated('gender'),
            nationality: $request->validated('nationality'),
            education: $request->validated('education'),
            phone: $request->validated('phone'),
            telephone: $request->validated('telephone'),
            address: $request->validated('address'),
            vehicle: $request->validated('vehicle'),
            certificate: $request->validated('certificate'),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
