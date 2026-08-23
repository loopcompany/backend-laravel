<?php

namespace App\Support;

class DigitalBusinessCardTypography
{
    public const MARKER = '__DBC_TYPO__';

    public const ALLOWED_FONT_FAMILIES = [
        'loop_card' => "'LoopCardFont', sans-serif",
        'vazir' => "'VazirFont', sans-serif",
        'iransans' => "'IRANSansFont', sans-serif",
        'yekan' => "'YekanFont', sans-serif",
        'tanha' => "'TanhaFont', sans-serif",
        'sina' => "'BSinaBd', sans-serif",
        'KoodakB' => "'KoodakB', sans-serif",
        'Khodkar' => "'Khodkar', sans-serif",
    ];

    public const ALLOWED_TYPOGRAPHY_KEYS = [
        'title_font',
        'description_font',
        'description_size',
        'description_align',
        'link_title_font',
        'link_description_font',
        'caption_font',
        'faq_question_font',
        'faq_answer_font',
        'button_font',
        'address_font',
    ];

    public static function allowedFontKeys(): array
    {
        return array_keys(self::ALLOWED_FONT_FAMILIES);
    }

    public static function sanitizeTypography(mixed $typography): array
    {
        if (! is_array($typography)) {
            return [];
        }

        $allowedFonts = self::allowedFontKeys();
        $allowedFields = array_flip(self::ALLOWED_TYPOGRAPHY_KEYS);
        $sanitized = [];

        foreach ($typography as $key => $value) {
            if (! isset($allowedFields[$key])) {
                continue;
            }

            // Handle size fields (numbers)
            if (str_ends_with($key, '_size')) {
                $numericSize = is_numeric($value) ? (float) $value : 0;
                
                if ($numericSize < 10 || $numericSize > 72) {
                    continue;
                }

                $sanitized[$key] = (int) $numericSize;
                continue;
            }

            // Handle alignment fields (strings)
            if (str_ends_with($key, '_align')) {
                if (! is_string($value)) {
                    continue;
                }

                $normalizedValue = trim($value);
                $allowedAlignments = ['left', 'center', 'right'];

                if (! in_array($normalizedValue, $allowedAlignments, true)) {
                    continue;
                }

                $sanitized[$key] = $normalizedValue;
                continue;
            }

            // Handle font fields (strings)
            if (str_ends_with($key, '_font')) {
                if (! is_string($value)) {
                    continue;
                }

                $normalizedValue = trim($value);

                if ($normalizedValue === '' || ! in_array($normalizedValue, $allowedFonts, true)) {
                    continue;
                }

                $sanitized[$key] = $normalizedValue;
                continue;
            }
        }

        return $sanitized;
    }

    public static function encodeTypography(array $typography): string
    {
        $sanitized = self::sanitizeTypography($typography);

        if (empty($sanitized)) {
            return '';
        }

        return self::MARKER . json_encode($sanitized, JSON_UNESCAPED_UNICODE);
    }

    public static function decodeTypography(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $markerPosition = strrpos($value, self::MARKER);

        if ($markerPosition === false) {
            return [];
        }

        $jsonPayload = substr($value, $markerPosition + strlen(self::MARKER));

        if (! is_string($jsonPayload) || trim($jsonPayload) === '') {
            return [];
        }

        $decoded = json_decode($jsonPayload, true);

        return self::sanitizeTypography($decoded);
    }

    public static function stripTypographyMarker(?string $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $markerPosition = strrpos($value, self::MARKER);

        if ($markerPosition === false) {
            return $value;
        }

        return rtrim(substr($value, 0, $markerPosition));
    }

    public static function embedTypographyInText(?string $text, array $typography): string
    {
        $cleanText = self::stripTypographyMarker($text);
        $encodedTypography = self::encodeTypography($typography);

        if ($encodedTypography === '') {
            return $cleanText;
        }

        if ($cleanText === '') {
            return $encodedTypography;
        }

        return rtrim($cleanText) . PHP_EOL . $encodedTypography;
    }

    public static function resolveFontFamily(?string $fontKey): ?string
    {
        if (! is_string($fontKey) || trim($fontKey) === '') {
            return null;
        }

        $normalizedKey = trim($fontKey);

        return self::ALLOWED_FONT_FAMILIES[$normalizedKey] ?? null;
    }

    public static function buildTypographyStyle(array $typography, string $fieldKey): array
    {
        $sanitized = self::sanitizeTypography($typography);
        $style = [];
        
        // Get font family
        $fontKey = $sanitized[$fieldKey] ?? null;
        $fontFamily = self::resolveFontFamily($fontKey);
        
        if ($fontFamily) {
            $style['font-family'] = $fontFamily;
        }

        // Handle size and alignment for description_font
        if ($fieldKey === 'description_font') {
            // Handle description_size
            $sizeKey = 'description_size';
            if (isset($sanitized[$sizeKey])) {
                $sizeValue = $sanitized[$sizeKey];
                $numericSize = is_numeric($sizeValue) ? (float) $sizeValue : 0;
                
                if ($numericSize >= 10 && $numericSize <= 72) {
                    $style['font-size'] = (int) $numericSize . 'px';
                }
            }

            // Handle description_align
            $alignKey = 'description_align';
            if (isset($sanitized[$alignKey])) {
                $alignValue = $sanitized[$alignKey];
                $allowedAlignments = ['left', 'center', 'right'];
                
                if (is_string($alignValue) && in_array(trim($alignValue), $allowedAlignments, true)) {
                    $style['text-align'] = trim($alignValue);
                }
            }
        }

        return $style;
    }

    public static function typographyStyleToString(array $style): string
    {
        $cssParts = [];
        
        foreach ($style as $property => $value) {
            $cssParts[] = $property . ': ' . $value . ';';
        }
        
        return implode(' ', $cssParts);
    }
}