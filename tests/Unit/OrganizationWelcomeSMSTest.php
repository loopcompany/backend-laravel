<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Organization;
use App\Services\PhoneVerificationService;
use App\Services\SmsService;
use App\Repositories\UserRepository;
use App\Services\SecurePasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class OrganizationWelcomeSMSTest extends TestCase
{
    use RefreshDatabase;

    private PhoneVerificationService $verificationService;
    private UserRepository $userRepository;
    private SmsService $smsServiceMock;
    private SecurePasswordService $securePasswordService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock SmsService
        $this->smsServiceMock = Mockery::mock(SmsService::class);
        $this->app->instance(SmsService::class, $this->smsServiceMock);

        // Real dependencies
        $this->userRepository = app(UserRepository::class);
        $this->securePasswordService = app(SecurePasswordService::class);

        // Service under test
        $this->verificationService = new PhoneVerificationService(
            $this->userRepository,
            $this->securePasswordService
        );
    }

    /** @test */
    public function it_sends_welcome_sms_when_organization_phone_is_verified()
    {
        // Arrange: Create organization user
        $user = User::factory()->create([
            'phone' => '09123456789',
            'account_type' => 'organization',
            'phone_verified_at' => null,
            'phone_verify_code' => password_hash('123456', PASSWORD_DEFAULT),
        ]);

        $organization = Organization::factory()->create([
            'user_id' => $user->id,
            'organization_name' => 'شرکت تست',
            'organization_code' => 'ORG-12345',
        ]);

        // Refresh user to load organization relationship
        $user = $user->fresh(['organization']);

        // Mock: Expect welcome SMS to be sent
        $this->smsServiceMock
            ->shouldReceive('sendOrganizationWelcome')
            ->once()
            ->with('09123456789', 'شرکت تست', 'ORG-12345')
            ->andReturn(true);

        // Act: Verify phone
        $result = $this->verificationService->verifyPhone('09123456789', '123456', false);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertTrue($result['organization_welcome_sent']);
        $this->assertStringContainsString('اطلاعات ورود به شماره شما ارسال گردید', $result['message']);
        
        // Verify phone was marked as verified
        $user = $user->fresh();
        $this->assertNotNull($user->phone_verified_at);
    }

    /** @test */
    public function it_does_not_send_welcome_sms_for_individual_users()
    {
        // Arrange: Create individual user
        $user = User::factory()->create([
            'phone' => '09123456780',
            'account_type' => 'individual',
            'phone_verified_at' => null,
            'phone_verify_code' => password_hash('123456', PASSWORD_DEFAULT),
        ]);

        // Mock: Should NOT call organization welcome SMS
        $this->smsServiceMock
            ->shouldNotReceive('sendOrganizationWelcome');

        // Mock: Should call secure password SMS instead
        $this->smsServiceMock
            ->shouldReceive('sendSecurePassword')
            ->once()
            ->andReturn(true);

        // Act: Verify phone
        $result = $this->verificationService->verifyPhone('09123456780', '123456', false);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertFalse($result['organization_welcome_sent'] ?? false);
        $this->assertTrue($result['password_generated']);
    }

    /** @test */
    public function it_handles_failed_welcome_sms_gracefully()
    {
        // Arrange: Create organization user
        $user = User::factory()->create([
            'phone' => '09123456781',
            'account_type' => 'organization',
            'phone_verified_at' => null,
            'phone_verify_code' => password_hash('123456', PASSWORD_DEFAULT),
        ]);

        $organization = Organization::factory()->create([
            'user_id' => $user->id,
            'organization_name' => 'شرکت تست 2',
            'organization_code' => 'ORG-67890',
        ]);

        $user = $user->fresh(['organization']);

        // Mock: SMS send fails
        $this->smsServiceMock
            ->shouldReceive('sendOrganizationWelcome')
            ->once()
            ->with('09123456781', 'شرکت تست 2', 'ORG-67890')
            ->andReturn(false);

        // Act: Verify phone
        $result = $this->verificationService->verifyPhone('09123456781', '123456', false);

        // Assert: Verification should still succeed
        $this->assertTrue($result['success']);
        $this->assertFalse($result['organization_welcome_sent']);
        
        // Message should be fallback message
        $this->assertStringContainsString('می‌توانید با کد سازمانی و رمز عبور خود وارد شوید', $result['message']);
        
        // Phone should be verified despite SMS failure
        $user = $user->fresh();
        $this->assertNotNull($user->phone_verified_at);
    }

    /** @test */
    public function welcome_sms_includes_correct_organization_details()
    {
        // Arrange
        $user = User::factory()->create([
            'phone' => '09123456782',
            'account_type' => 'organization',
            'phone_verified_at' => null,
            'phone_verify_code' => password_hash('123456', PASSWORD_DEFAULT),
        ]);

        $organization = Organization::factory()->create([
            'user_id' => $user->id,
            'organization_name' => 'سازمان آزمایشی',
            'organization_code' => 'TEST-001',
        ]);

        $user = $user->fresh(['organization']);

        // Mock with specific parameter assertions
        $this->smsServiceMock
            ->shouldReceive('sendOrganizationWelcome')
            ->once()
            ->with(
                Mockery::on(fn($phone) => $phone === '09123456782'),
                Mockery::on(fn($name) => $name === 'سازمان آزمایشی'),
                Mockery::on(fn($code) => $code === 'TEST-001')
            )
            ->andReturn(true);

        // Act
        $result = $this->verificationService->verifyPhone('09123456782', '123456', false);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertTrue($result['organization_welcome_sent']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
