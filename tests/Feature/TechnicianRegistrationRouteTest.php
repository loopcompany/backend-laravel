<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianRegistrationRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_path_shows_form_instead_of_public_technician_profile(): void
    {
        $this->get('/technician/register')
            ->assertOk()
            ->assertSee('ثبت نام تکنسین')
            ->assertDontSee('تکنسین یافت نشد');
    }
}
