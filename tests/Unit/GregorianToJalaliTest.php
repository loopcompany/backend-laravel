<?php

namespace Tests\Unit;

use Tests\TestCase;

class GregorianToJalaliTest extends TestCase
{
    /** @test */
    public function it_converts_gregorian_date_to_jalali()
    {
        // Test case 1: 2000-01-01 (میلادی) = 1378/10/11 (شمسی)
        $result = gregorian_to_jalali('2000-01-01');
        $this->assertEquals('1378/10/11', $result);
    }

    /** @test */
    public function it_converts_with_custom_format()
    {
        // Test with different format
        $result = gregorian_to_jalali('2000-01-01', 'Y-m-d');
        $this->assertEquals('1378-10-11', $result);
    }

    /** @test */
    public function it_handles_leap_year()
    {
        // Leap year test: 2000-02-29
        $result = gregorian_to_jalali('2000-02-29');
        $this->assertEquals('1378/12/10', $result);
    }

    /** @test */
    public function it_converts_recent_date()
    {
        // Test with a recent date: 2025-01-01
        $result = gregorian_to_jalali('2025-01-01');
        $this->assertEquals('1403/10/12', $result);
    }

    /** @test */
    public function it_handles_invalid_date_gracefully()
    {
        // Should return original string on error
        $result = gregorian_to_jalali('invalid-date');
        // On error, it should log and return original
        $this->assertEquals('invalid-date', $result);
    }

    /** @test */
    public function it_converts_birthdate_format()
    {
        // Example birthdate: 1990-05-15 (میلادی) = 1369/02/25 (شمسی)
        $result = gregorian_to_jalali('1990-05-15');
        $this->assertEquals('1369/02/25', $result);
    }

    /** @test */
    public function it_converts_with_slash_format()
    {
        // Default format uses slash
        $result = gregorian_to_jalali('1995-10-20');
        $this->assertStringContainsString('/', $result);
        $this->assertEquals('1374/07/28', $result);
    }
}
