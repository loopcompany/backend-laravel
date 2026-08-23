<?php

if (!function_exists('safe_jalali_date')) {
    /**
     * Safely format a date in Jalali format
     *
     * @param mixed $date
     * @param string $format
     * @return string
     */
    function safe_jalali_date($date, string $format = 'Y/m/d'): string
    {
        if (!$date) {
            return '-';
        }

        try {
            if (is_string($date)) {
                $date = \Carbon\Carbon::parse($date);
            }

            return \Morilog\Jalali\Jalalian::forge($date)->format($format);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Invalid date for Jalali conversion', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            return 'تاریخ نامعتبر';
        }
    }
}

if (!function_exists('safe_jalali_datetime')) {
    /**
     * Safely format a datetime in Jalali format
     *
     * @param mixed $date
     * @return string
     */
    function safe_jalali_datetime($date): string
    {
        return safe_jalali_date($date, 'Y/m/d H:i');
    }
}

if (!function_exists('gregorian_to_jalali')) {
    /**
     * Convert Gregorian date to Jalali format
     *
     * @param string $gregorianDate Date in Y-m-d format
     * @param string $format Output format (default: Y/m/d)
     * @return string
     */
    function gregorian_to_jalali(string $gregorianDate, string $format = 'Y/m/d'): string
    {
        try {
            // Parse the Gregorian date
            $date = \Carbon\Carbon::parse($gregorianDate);
            
            // Convert to Jalali
            return \Morilog\Jalali\Jalalian::forge($date)->format($format);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to convert Gregorian to Jalali', [
                'date' => $gregorianDate,
                'error' => $e->getMessage()
            ]);
            
            return $gregorianDate; // Return original on error
        }
    }
}