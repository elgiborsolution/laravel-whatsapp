<?php

namespace ESolution\WhatsApp\Traits;

trait NormalizesPhoneNumbers
{
    /**
     * Normalize a phone number for WhatsApp API.
     * - Removes all non-digit characters
     * - Converts leading 0 to 62 (Indonesian country code)
     *
     * @param string|null $phone
     * @return string
     */
    public function normalizePhone(?string $phone): string
    {
        $n = preg_replace('/\D+/', '', (string)$phone);
        if ($n === '') return $n;
        if (str_starts_with($n, '0')) $n = '62'.substr($n, 1);
        return $n;
    }
}
