<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * بند ۵ «توضیحات پنل»: عکس پروفایل تکنسین فقط از پنل تغییر می‌کند؛ تکنسین فقط آن را می‌بیند.
 */
class TechnicianProfilePhotoTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    public function test_technician_app_cannot_change_profile_photo(): void
    {
        Storage::fake('public');
        $technician = $this->makeTechnician(['profile_photo_path' => 'technicians/profiles/panel.jpg']);
        Sanctum::actingAs($technician);

        $this->put('/api/technician/profile/personal-info', [
            'father_name' => 'پدر جدید',
            'profile_photo' => UploadedFile::fake()->image('selfie.jpg'),
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('message', 'اطلاعات شخصی به‌روزرسانی شد. تصویر پروفایل فقط از طریق پنل مدیریت قابل تغییر است.');

        $technician->refresh();
        $this->assertSame('پدر جدید', $technician->father_name);
        $this->assertSame('technicians/profiles/panel.jpg', $technician->profile_photo_path);
        $this->assertEmpty(Storage::disk('public')->allFiles('technicians/profiles'));
    }

    public function test_replacing_or_removing_photo_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('technicians/profiles/old.jpg', 'x');
        $technician = $this->makeTechnician(['profile_photo_path' => 'technicians/profiles/old.jpg']);

        $technician->update(['profile_photo_path' => 'technicians/profiles/new.jpg']);
        Storage::disk('public')->assertMissing('technicians/profiles/old.jpg');

        Storage::disk('public')->put('technicians/profiles/new.jpg', 'x');
        $technician->update(['profile_photo_path' => null]);
        Storage::disk('public')->assertMissing('technicians/profiles/new.jpg');
    }
}
