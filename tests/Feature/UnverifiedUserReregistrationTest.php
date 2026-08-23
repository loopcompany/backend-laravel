<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RegistrationService;
use App\DTOs\RegistrationDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UnverifiedUserReregistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_can_reregister_with_updated_info(): void
    {
        // Create an unverified user
        $user = User::factory()->create([
            'phone' => '09123456789',
            'melicode' => '0123456789',
            'email' => 'old@example.com',
            'phone_verified_at' => null, // Not verified
            'referral_code' => 'L12345'
        ]);

        // Attempt to re-register with updated information
        $registrationService = app(RegistrationService::class);
        
        $dto = new RegistrationDTO(
            melicode: '0123456789', // Same melicode
            phone: '09123456789',   // Same phone
            email: 'new@example.com', // Updated email
            other_referral_code: null
        );

        $result = $registrationService->register($dto);

        // Assert registration succeeded and is marked as update
        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_update']);
        $this->assertStringContainsString('به‌روزرسانی', $result['message']);

        // Verify user data was updated
        $updatedUser = User::find($user->id);
        $this->assertEquals('new@example.com', $updatedUser->email);
        $this->assertEquals('L12345', $updatedUser->referral_code); // Should keep original referral code
        $this->assertNull($updatedUser->phone_verified_at); // Should remain unverified
    }

    public function test_verified_user_cannot_reregister(): void
    {
        // Create a verified user
        $user = User::factory()->create([
            'phone' => '09555555555', // Different phone from other tests
            'melicode' => '5555555555',
            'email' => 'verified@example.com',
            'phone_verified_at' => now(), // Verified
        ]);

        // Attempt to re-register
        $registrationService = app(RegistrationService::class);
        
        $dto = new RegistrationDTO(
            melicode: '0987654321', // Different melicode
            phone: '09555555555',   // Same phone as created user
            email: 'new@example.com',
            other_referral_code: null
        );

        $result = $registrationService->register($dto);

        // Assert registration failed
        $this->assertFalse($result['success']);
        $this->assertEquals('phone_already_verified', $result['error']);
        $this->assertStringContainsString('قبلاً ثبت و تایید شده', $result['message']);
    }

    public function test_new_user_registration_works_normally(): void
    {
        // Attempt to register completely new user
        $registrationService = app(RegistrationService::class);
        
        $dto = new RegistrationDTO(
            melicode: '0123456789',
            phone: '09987654321',   // New phone
            email: 'new@example.com',
            other_referral_code: null
        );

        $result = $registrationService->register($dto);

        // Assert registration succeeded and is NOT marked as update
        $this->assertTrue($result['success']);
        $this->assertFalse($result['is_update']);
        $this->assertStringContainsString('ثبت نام با موفقیت', $result['message']);

        // Verify new user was created
        $newUser = User::where('phone', '09987654321')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('new@example.com', $newUser->email);
        $this->assertNull($newUser->phone_verified_at); // Should be unverified
    }

    public function test_validation_prevents_melicode_conflict_between_different_users(): void
    {
        // Create an unverified user
        User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '0123456789',
            'phone_verified_at' => null,
        ]);

        // Try to register different phone with same melicode
        $registrationService = app(RegistrationService::class);
        
        $dto = new RegistrationDTO(
            melicode: '0123456789', // Same melicode
            phone: '09222222222',   // Different phone
            email: 'test@example.com',
            other_referral_code: null
        );

        // This should be caught by validation in checkExistingUser
        $errors = $registrationService->checkExistingUser($dto);
        
        $this->assertArrayHasKey('melicode', $errors);
        $this->assertStringContainsString('برای شماره دیگری ثبت شده', $errors['melicode']);
    }
}