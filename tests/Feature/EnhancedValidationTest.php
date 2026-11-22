<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnhancedValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_prevents_verified_phone_reuse(): void
    {
        // Create a verified user
        User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '1111111111', 
            'phone_verified_at' => now(),
        ]);

        // Try to register with same phone
        $response = $this->postJson('/api/auth/register', [
            'phone' => '09111111111',
            'melicode' => '2222222222',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone']);
        
        $this->assertNotEmpty($response->json('errors.phone.0'));
    }

    public function test_registration_prevents_verified_melicode_reuse(): void
    {
        // Create a verified user
        User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '1111111111',
            'phone_verified_at' => now(),
        ]);

        // Try to register with same melicode
        $response = $this->postJson('/api/auth/register', [
            'phone' => '09222222222',
            'melicode' => '1111111111',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['melicode']);
        
        $this->assertNotEmpty($response->json('errors.melicode.0'));
    }

    public function test_registration_prevents_melicode_conflict_between_unverified_users(): void
    {
        // Create unverified user
        User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '1111111111',
            'phone_verified_at' => null,
        ]);

        // Try to register different phone with same melicode
        $response = $this->postJson('/api/auth/register', [
            'phone' => '09222222222',
            'melicode' => '1111111111',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['melicode']);
        
        $this->assertNotEmpty($response->json('errors.melicode.0'));
    }

    public function test_registration_allows_unverified_phone_reregistration(): void
    {
        // Create unverified user
        User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '1111111111',
            'phone_verified_at' => null,
        ]);

        // Re-register same phone with different melicode
        $response = $this->postJson('/api/auth/register', [
            'phone' => '09111111111',
            'melicode' => '2222222222',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(201)
                ->assertJson(['success' => true]);
    }

    public function test_disabled_account_cannot_change_its_primary_phone(): void
    {
        // Create verified user
        $verifiedUser = User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '1111111111',
            'phone_verified_at' => now(),
        ]);

        // Create another verified user to test conflict
        $currentUser = User::factory()->create([
            'phone' => '09222222222',
            'melicode' => '2222222222',
            'name' => 'رضا',
            'last_name' => 'محمدی',
            'phone_verified_at' => now(),
        ]);

        // Try to update to existing verified phone
        $response = $this->actingAs($currentUser)
                        ->putJson('/api/profile', [
                            'phone' => '09111111111', // Same as verified user
                        ]);

        $response->assertForbidden();
        $this->assertSame('09222222222', $currentUser->fresh()->phone);
    }

    public function test_primary_phone_is_not_taken_over_through_the_profile_endpoint(): void
    {
        // Create unverified user
        User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '1111111111',
            'phone_verified_at' => null, // Not verified
        ]);

        // Create verified user to update
        $currentUser = User::factory()->create([
            'phone' => '09222222222',
            'melicode' => '2222222222',
            'name' => 'حسن',
            'last_name' => 'رضایی',
            'phone_verified_at' => now(),
        ]);

        // Update to unverified phone should pass validation (no 422 error)
        $response = $this->actingAs($currentUser)
                        ->putJson('/api/profile', [
                            'phone' => '09111111111', // Unverified phone
                            'name' => 'حسن', // Keep existing name
                        ]);

        $response->assertForbidden();
        $this->assertSame('09222222222',$currentUser->fresh()->phone);
    }

    public function test_disabled_account_cannot_change_its_national_code(): void
    {
        // Create verified user with melicode
        User::factory()->create([
            'phone' => '09111111111',
            'melicode' => '1111111111',
            'phone_verified_at' => now(),
        ]);

        // Create another user
        $currentUser = User::factory()->create([
            'phone' => '09222222222',
            'melicode' => '2222222222',
            'name' => 'فرهاد',
            'last_name' => 'احمدی',
            'phone_verified_at' => now(),
        ]);

        // Try to update to existing verified melicode
        $response = $this->actingAs($currentUser)
                        ->putJson('/api/profile', [
                            'melicode' => '1111111111',
                        ]);

        $response->assertForbidden();
        $this->assertSame('2222222222', $currentUser->fresh()->melicode);
    }

    public function test_active_user_can_update_own_profile_name(): void
    {
        $user=User::factory()->create(['phone'=>'09350000010','account_type'=>'individual','has_access'=>1,'name'=>'علی','phone_verified_at'=>now(),'last_name'=>'رضایی','melicode'=>'1234567891','birth_date'=>'1370/01/01','city'=>'تهران','region'=>'08','postal_code'=>'1234567890','home_address'=>'تهران خیابان نمونه کوچه آزمایش پلاک ده واحد دو','home_number'=>'10','home_unit'=>'2','home_floor'=>'0']);
        $this->actingAs($user)->putJson('/api/profile',['name'=>'رضا'])->assertOk();
        $this->assertSame('رضا',$user->fresh()->name);
    }
}
