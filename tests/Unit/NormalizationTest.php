<?php

namespace ESolution\WhatsApp\Tests\Unit;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Traits\NormalizesPhoneNumbers;

class NormalizationTest extends TestCase
{
    use NormalizesPhoneNumbers;

    public function test_it_normalizes_zero_leading_phone_numbers()
    {
        $this->assertEquals('628123456789', $this->normalizePhone('08123456789'));
        $this->assertEquals('628123456789', $this->normalizePhone(' 0812-3456-789 '));
    }

    public function test_it_removes_non_digit_characters()
    {
        $this->assertEquals('628123456789', $this->normalizePhone('+62 812-3456-789'));
        $this->assertEquals('12345', $this->normalizePhone('12-34-5'));
    }

    public function test_it_handles_null_or_empty_input()
    {
        $this->assertEquals('', $this->normalizePhone(null));
        $this->assertEquals('', $this->normalizePhone(''));
    }
}
