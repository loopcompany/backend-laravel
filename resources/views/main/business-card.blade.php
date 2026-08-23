<!DOCTYPE html>
<html lang="fa-IR" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $digital_business_card->title }}</title>
    <meta name="description" content="{{ $digital_business_card->des ?? $digital_business_card->title }}">
    <link rel="icon" type="image/png" sizes="56x56" href="{{ asset('assets/images/fav-icon/icon.png') }}">
    <link rel="stylesheet" href="https://static.neshan.org/sdk/leaflet/1.4.0/leaflet.css" />
    <script src="https://static.neshan.org/sdk/leaflet/1.4.0/leaflet.js"></script>
    @vite(['resources/css/app.css'])
    @php
        $typographyHelper = \App\Support\DigitalBusinessCardTypography::class;

        $resolveBusinessCardAsset = function ($assetPath): ?string {
            if (blank($assetPath)) {
                return null;
            }

            $value = trim((string) $assetPath);

            if ($value === '') {
                return null;
            }

            if (preg_match('/^(https?:\/\/|\/|data:image\/)/i', $value)) {
                return $value;
            }

            return asset('storage/' . ltrim(preg_replace('/^public\//', '', $value), '/'));
        };

        $resolveBusinessCardIcon = function ($iconValue) use ($resolveBusinessCardAsset): ?string {
            if (blank($iconValue)) {
                return null;
            }

            $value = trim((string) $iconValue);
            $looksLikeImage =
                preg_match('/\.(png|jpe?g|webp|gif|svg)(\?.*)?$/i', $value) ||
                str_starts_with($value, '/storage/') ||
                preg_match('/^https?:\/\//i', $value) ||
                str_starts_with($value, 'data:image/');

            return $looksLikeImage ? $resolveBusinessCardAsset($value) : null;
        };

        $fontFamilyFor = function (?string $fontKey) use ($typographyHelper): ?string {
            return $typographyHelper::resolveFontFamily($fontKey);
        };
    @endphp
    <style>
        
        @font-face {
            font-family: 'LoopCardFont';
            src: url('{{ asset('assets/fonts/vazir/UI-Farsi-Digits-Non-Latin/fonts/webfonts/Vazirmatn-UI-FD-NL-Regular.woff2') }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'VazirFont';
            src: url('{{ asset('/assets/fonts/vazir/Vazir.woff2') }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'IRANSansFont';
            src: url('{{ asset('/assets/fonts/IRANSans/IRANSansWeb(FaNum).woff') }}') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'YekanFont';
            src: url('{{ asset('/assets/fonts/Yekan/Yekan.woff') }}') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'TanhaFont';
            src: url('{{ asset('/assets/fonts/Tanha/Tanha-FD.woff2') }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'BSinaBd';
            src: url('/assets/fonts/businesscard/BSinaBd.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'KoodakB';
            src: url('/assets/fonts/businesscard/KoodakB.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Khodkar';
            src: url('/assets/fonts/businesscard/Khodkar.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }


        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: 'LoopCardFont', sans-serif;
            background: radial-gradient(circle at top, #1f2937 0%, #0f172a 45%, #020617 100%);
            color: #0f172a;
            overflow: hidden;
        }

        .scroll-anim.opacity-0 {
            opacity: 0;
        }

        .digital-card-public {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .digital-card-public .mobile-preview-frame {
            position: relative;
            width: min(100%, 410px);
            height: min(90vh, 800px);
            border-radius: 2.5rem;
            background: linear-gradient(180deg, rgb(15 23 42), rgb(30 41 59));
            padding: 0.9rem;
            box-shadow: 0 30px 80px rgb(15 23 42 / 0.42);
        }

        .digital-card-public .mobile-preview-notch {
            position: absolute;
            left: 50%;
            top: 0.55rem;
            z-index: 2;
            height: 0.7rem;
            width: 7.5rem;
            transform: translateX(-50%);
            border-radius: 9999px;
            background: rgb(15 23 42);
        }

        .digital-card-public .mobile-preview-screen {
            height: 100%;
            min-height: 0;
            overflow: hidden;
            border-radius: 2rem;
            background-color: rgb(248 250 252);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
        }

        .digital-card-public .mobile-preview-content {
            min-height: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            direction: rtl;
            text-align: right;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .digital-card-public .mobile-preview-content::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .digital-card-public .preview-header {
            min-height: 240px;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            padding: 1rem;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-color: transparent;
            flex-shrink: 0;
        }

        .digital-card-public .preview-header-content {
            background: transparent;
            backdrop-filter: none;
            border: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            width: auto;
            max-width: 100%;
            text-align: center;
        }

        .digital-card-public .preview-blocks {
            padding: 1rem 1rem 1.25rem;
            flex: 1;
        }

        .digital-card-public .digital-card-preview {
            font-family: 'LoopCardFont', sans-serif;
        }

        .digital-card-public .block-icon {
            width: 2rem;
            height: 2rem;
            object-fit: contain;
            flex-shrink: 0;
        }

        .digital-card-public .preview-link-title {
            color: inherit;
            text-decoration: none;
        }

        .digital-card-public .preview-link-title:hover {
            text-decoration: underline;
        }

        .digital-card-public .preview-gallery-grid {
            display: grid;
            gap: 0.75rem;
        }

        .digital-card-public .preview-block-card {
            color: #0f172a;
            background-color: #ffffff;
            border-color: rgba(148, 163, 184, 0.55);
        }

        .digital-card-public .anim-fade-in {
            animation: fadeIn 0.45s ease both;
        }

        .digital-card-public .anim-slide-up {
            animation: slideUp 0.45s ease both;
        }

        .digital-card-public .anim-slide-down {
            animation: slideDown 0.45s ease both;
        }

        .digital-card-public .anim-slide-left {
            animation: slideLeft 0.45s ease both;
        }

        .digital-card-public .anim-slide-right {
            animation: slideRight 0.45s ease both;
        }

        .digital-card-public .anim-zoom-in {
            animation: zoomIn 0.45s ease both;
        }

        .digital-card-public .anim-bounce {
            animation: bounce 0.6s ease both;
        }

        .digital-card-public .neshan-map-wrapper {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            background: rgb(15 23 42 / 0.08);
        }

        .digital-card-public .neshan-map {
            width: 100%;
            height: 220px;
            min-height: 220px;
        }

        .digital-card-public .faq-accordion {
            display: grid;
            gap: 0.75rem;
        }

        .digital-card-public .faq-item {
            border-radius: 0.75rem;
            background: rgb(15 23 42 / 0.06);
            overflow: hidden;
        }

        .digital-card-public .faq-question {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            font-weight: 700;
            color: inherit;
        }

        .digital-card-public .faq-question::-webkit-details-marker {
            display: none;
        }

        .digital-card-public .faq-question::after {
            content: '+';
            font-size: 1.1rem;
            line-height: 1;
            opacity: 0.8;
        }

        .digital-card-public .faq-item[open] .faq-question::after {
            content: '−';
        }

        .digital-card-public .faq-answer {
            padding: 0 1rem 1rem;
            font-size: 0.95rem;
            line-height: 1.8;
        }

        .anim-fade-in,
        .anim-slide-up,
        .anim-slide-down,
        .anim-slide-left,
        .anim-slide-right,
        .anim-zoom-in,
        .anim-bounce {
            animation-duration: 0.8s !important;
            animation-timing-function: ease-in-out !important;
            animation-iteration-count: infinite !important;
            animation-direction: alternate !important;
            animation-fill-mode: both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0.65;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 1;
                transform: translateY(-10px);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 1;
                transform: translateY(10px);
            }
        }

        @keyframes slideLeft {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 1;
                transform: translateX(10px);
            }
        }

        @keyframes slideRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 1;
                transform: translateX(-10px);
            }
        }

        @keyframes zoomIn {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 1;
                transform: scale(1.04);
            }
        }

        @keyframes bounce {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }

            100% {
                transform: translateY(0);
            }
        }

        @media (min-width: 640px) {
            .digital-card-public {
                padding: 1.5rem;
            }

            .digital-card-public .preview-gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 639px) {
            .digital-card-public {
                padding: 0;
            }

            .digital-card-public .mobile-preview-frame {
                width: 100vw;
                height: 100vh;
                max-width: none;
                max-height: none;
                border-radius: 0;
                padding: 0;
                box-shadow: none;
            }

            .digital-card-public .mobile-preview-notch {
                top: 0.5rem;
            }

            .digital-card-public .mobile-preview-screen {
                border-radius: 0;
            }
        }
    </style>
</head>

<body>
    @php
        $typeLabels = [
            'text' => 'متن',
            'link' => 'لینک',
            'social' => 'شبکه اجتماعی',
            'map' => 'نقشه',
            'faq' => 'سوالات متداول',
            'gallery' => 'گالری',
        ];

        $headerBackground = $resolveBusinessCardAsset($digital_business_card->image_background);
        $pageBackground = $resolveBusinessCardAsset($digital_business_card->page_background_image);
        $logo = $resolveBusinessCardAsset($digital_business_card->logo);

        $neshanApiKey = env('VITE_NESHAN_API_KEY', 'web.a7d38181a0094e0092a578bcc81b7641');

        $buildNeshanUrl = function ($lat, $lng) {
            if (!is_numeric($lat) || !is_numeric($lng)) {
                return null;
            }

            $latitude = (float) $lat;
            $longitude = (float) $lng;

            if (abs($latitude) > 90 || abs($longitude) > 180) {
                return null;
            }

            return 'https://neshan.org/maps/@' . $latitude . ',' . $longitude . ',16z';
        };

        $normalizeCssColor = function ($rawColor): ?string {
            if (blank($rawColor)) {
                return null;
            }

            $color = trim((string) $rawColor);

            if ($color === 'transparent') {
                return 'transparent';
            }

            // Check for hex colors (3, 4, 6, or 8 digits)
            if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color)) {
                // Convert 3-digit hex to 6-digit
                if (preg_match('/^#([A-Fa-f0-9]{3})$/', $color)) {
                    $hex = ltrim($color, '#');
                    $expanded = '';
                    for ($i = 0; $i < 3; $i++) {
                        $expanded .= $hex[$i] . $hex[$i];
                    }
                    return '#' . $expanded;
                }
                // Convert 4-digit hex (with alpha) to 6-digit hex (strip alpha)
                if (preg_match('/^#([A-Fa-f0-9]{4})$/', $color)) {
                    $hex = ltrim($color, '#');
                    $expanded = '';
                    for ($i = 0; $i < 3; $i++) {
                        $expanded .= $hex[$i] . $hex[$i];
                    }
                    return '#' . $expanded;
                }
                // Convert 8-digit hex (with alpha) to 6-digit hex (strip alpha)
                if (preg_match('/^#([A-Fa-f0-9]{8})$/', $color)) {
                    return '#' . substr($color, 1, 6);
                }
                return $color;
            }

            // Check for rgb(r, g, b)
            if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $color, $matches)) {
                $r = min(255, max(0, (int) $matches[1]));
                $g = min(255, max(0, (int) $matches[2]));
                $b = min(255, max(0, (int) $matches[3]));
                return "rgb({$r}, {$g}, {$b})";
            }

            // Check for rgba(r, g, b, a)
            if (
                preg_match(
                    '/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0|1|0?\.\d+)\s*\)$/i',
                    $color,
                    $matches,
                )
            ) {
                $r = min(255, max(0, (int) $matches[1]));
                $g = min(255, max(0, (int) $matches[2]));
                $b = min(255, max(0, (int) $matches[3]));
                $a = min(1, max(0, (float) $matches[4]));
                $a = round($a, 2);
                return "rgba({$r}, {$g}, {$b}, {$a})";
            }

            return null;
        };

        $blockAnimationClass = function (?string $animationType): string {
            $allowedAnimations = [
                'fade-in' => 'anim-fade-in',
                'slide-up' => 'anim-slide-up',
                'slide-down' => 'anim-slide-down',
                'slide-left' => 'anim-slide-left',
                'slide-right' => 'anim-slide-right',
                'zoom-in' => 'anim-zoom-in',
                'bounce' => 'anim-bounce',
            ];

            return $allowedAnimations[$animationType] ?? '';
        };
    @endphp

    <main class="digital-card-public">
        <div class="mobile-preview-frame">
            {{-- <div class="mobile-preview-notch"></div> --}}
            <div class="mobile-preview-screen"
                style="background-image: {{ $pageBackground ? 'linear-gradient(rgba(15, 23, 42, 0.12), rgba(15, 23, 42, 0.2)), url(' . $pageBackground . ')' : 'linear-gradient(180deg, #e2e8f0 0%, #f8fafc 100%)' }}; background-color: {{ $pageBackground ? '#f8fafc' : '#e2e8f0' }}; padding-bottom:20px">
                <div class="mobile-preview-content">
                    <section class="preview-header"
                        style="background-image: {{ $headerBackground ? 'url(' . $headerBackground . ')' : 'linear-gradient(135deg, #0f172a, #334155)' }};">
                        <div class="preview-header-content p-4"
                            style="color: {{ $digital_business_card->header_text_color ?: '#ffffff' }};">
                            @if ($logo)
                                <div class="mx-auto mb-3 h-16 w-16 overflow-hidden rounded-full bg-transparent">
                                    <img src="{{ $logo }}" alt="لوگو"
                                        class="block h-full w-full rounded-full object-cover object-center" />
                                </div>
                            @endif

                            <h1 class="text-2xl font-bold"
                                style="{{ $fontFamilyFor($digital_business_card->title_font_family) ? 'font-family: ' . $fontFamilyFor($digital_business_card->title_font_family) . ';' : '' }}">
                                {{ $digital_business_card->title }}</h1>
                            @if ($digital_business_card->des)
                                <p class="mt-2 text-sm whitespace-pre-line"
                                    style="{{ $fontFamilyFor($digital_business_card->description_font_family) ? 'font-family: ' . $fontFamilyFor($digital_business_card->description_font_family) . ';' : '' }}">
                                    {{ $digital_business_card->des }}</p>
                            @endif
                        </div>
                    </section>

                    <section class="preview-blocks space-y-4 text-slate-100">
                        @foreach ($digital_business_card->digital_business_card_blocks as $block)
                            @php
                                $blockLink = null;
                                $mapUrl = null;
                                $socialIconUrl = null;
                                $animationType = null;
                                $blockTypography = [];
                                $textDescription = null;
                                if ($block->type === 'link' && $block->link_blocks) {
                                    $blockLink = $block->link_blocks->link;
                                    $animationType = $block->link_blocks->animation_type;
                                    $blockTypography = $typographyHelper::decodeTypography(
                                        (string) ($block->link_blocks->title ?? ''),
                                    );
                                } elseif ($block->type === 'social' && $block->social_blocks) {
                                    $blockLink = $block->social_blocks->link;
                                    $socialIconUrl = $resolveBusinessCardIcon($block->social_blocks->icon);
                                    $animationType = $block->social_blocks->animation_type;
                                    $blockTypography = $typographyHelper::decodeTypography(
                                        (string) ($block->social_blocks->title ?? ''),
                                    );
                                } elseif ($block->type === 'map' && $block->map_blocks) {
                                    $mapUrl = $buildNeshanUrl(
                                        $block->map_blocks->latitude,
                                        $block->map_blocks->longitude,
                                    );
                                    $blockLink = $mapUrl;
                                    $blockTypography = $typographyHelper::decodeTypography(
                                        (string) ($block->map_blocks->title ?? ''),
                                    );
                                } elseif ($block->type === 'text' && $block->text_blocks) {
                                    $rawTextDescription = (string) ($block->text_blocks->descriptions ?? '');
                                    $blockTypography = $typographyHelper::decodeTypography($rawTextDescription);
                                    $textDescription = $typographyHelper::stripTypographyMarker($rawTextDescription);
                                } elseif ($block->type === 'faq' && $block->faq_blocks) {
                                    $blockTypography = $typographyHelper::decodeTypography(
                                        (string) ($block->faq_blocks->descriptions ?? ''),
                                    );
                                } elseif ($block->type === 'gallery' && $block->gallery_blocks) {
                                    $blockTypography = $typographyHelper::decodeTypography(
                                        (string) ($block->gallery_blocks->descriptions ?? ''),
                                    );
                                }

                                // $blockTitle = $block->title ?: $typeLabels[$block->type] ?? $block->type;
                                $blockTitle = $block->title;
                                $blockBackgroundColor = $normalizeCssColor($block->background_color) ?: '#ffffff';
                                $blockTextColor = $normalizeCssColor($block->color) ?: '#0f172a';
                                $animationClass = $blockAnimationClass($animationType ?? null);
                                $titleFontFamily = $fontFamilyFor(data_get($blockTypography, 'title_font'));
                                $descriptionFontFamily = $fontFamilyFor(data_get($blockTypography, 'description_font'));
                                $captionFontFamily = $fontFamilyFor(data_get($blockTypography, 'caption_font'));
                                $faqQuestionFontFamily = $fontFamilyFor(
                                    data_get($blockTypography, 'faq_question_font'),
                                );
                                $faqAnswerFontFamily = $fontFamilyFor(data_get($blockTypography, 'faq_answer_font'));
                                $buttonFontFamily = $fontFamilyFor(data_get($blockTypography, 'button_font'));
                                $addressFontFamily = $fontFamilyFor(data_get($blockTypography, 'address_font'));
                                
                                // Build typography style for description (includes font, size, align)
                                $descriptionStyle = $typographyHelper::typographyStyleToString(
                                    $typographyHelper::buildTypographyStyle($blockTypography, 'description_font')
                                );
                            @endphp

                            <article class="block-preview digital-card-preview preview-block-card rounded p-4"
                                style="color: {{ $blockTextColor }}; background-color: {{ $blockBackgroundColor }};">
                                @if($blockTitle && $block->type !== 'social' )
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <h2 class="{{ $animationClass ? 'opacity-0 scroll-anim' : '' }}" data-animation="{{ $animationClass }}" >
                                            @if ($blockLink && $block->title)
                                                <a href="{{ $blockLink }}" target="_blank" rel="noreferrer"
                                                    class="preview-link-title"
                                                    style="{{ $titleFontFamily ? 'font-family: ' . $titleFontFamily . ';' : '' }}; display: flex;gap: 9px;">
                                                    <img src="{{ asset("assets/link.svg") }}" style="height: 20px; width: 20px;" > 
                                                   <span style="font-size: 12px;"> {{ $blockTitle }}</span></a>
                                            @else
                                                <span
                                                    style="{{ $titleFontFamily ? 'font-family: ' . $titleFontFamily . ';' : '' }}">{{ $blockTitle }}</span>
                                            @endif
                                        </h2>
                                    </div>
                                </div>
                                @endif
                                @if(($block->text_blocks && $textDescription) || ($block->social_blocks ) || ($block->link_blocks && $block->link_blocks?->image) || ($block->map_blocks) || $block->gallery_blocks || $block->faq_blocks)
                                <div @class(['opacity-0 scroll-anim' => $animationClass !== '']) data-animation="{{ $animationClass }}">
                                    @if ($block->type === 'text' && $block->text_blocks)
                                        <div class="space-y-3 text-sm ">
                                            <p
                                                style="color: {{ $block->color ?? '#0f172a' }}; {{ $descriptionStyle }}">
                                                {{ $textDescription }}</p>
                                        </div>
                                    @elseif ($block->type === 'link' && $block->link_blocks)
                                        <div class="space-y-3 mt-3">
                                            @if ($block->link_blocks->image)
                                                <img src="{{ $resolveBusinessCardAsset($block->link_blocks->image) }}"
                                                    alt="{{ $block->title ?: 'تصویر لینک' }}"
                                                    class="h-32 w-full rounded-xl object-contain" />
                                            @endif
                                        </div>
                                    @elseif ($block->type === 'social' && $block->social_blocks)
                                        <div class="space-y-3 text-sm">
                                            <a href="{{ $blockLink }}" class="flex items-center gap-3">
                                                @if ($socialIconUrl)
                                                    <img src="{{ $socialIconUrl }}"
                                                        alt="{{ $block->title ?: 'آیکون' }}" class="block-icon" />
                                                @endif
                                                <span>{{ $block->title ?: 'شبکه اجتماعی' }}</span>
                                            </a>
                                        </div>
                                    @elseif ($block->type === 'map' && $block->map_blocks)
                                        <div class="space-y-2 text-sm">
                                            <p
                                                style="{{ $addressFontFamily ? 'font-family: ' . $addressFontFamily . ';' : '' }}">
                                                {{ $block->map_blocks->address ?: '' }}</p>
                                            @if ($mapUrl)
                                                <div class="neshan-map-wrapper">
                                                    <div class="neshan-map" data-neshan-map
                                                        data-latitude="{{ $block->map_blocks->latitude }}"
                                                        data-longitude="{{ $block->map_blocks->longitude }}"
                                                        data-zoom="16"></div>
                                                </div>
                                                <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer"
                                                    class="inline-flex rounded-xl bg-slate-900/80 px-3 py-2 text-xs font-semibold text-white"
                                                    style="{{ $buttonFontFamily ? 'font-family: ' . $buttonFontFamily . ';' : '' }}">
                                                    مشاهده روی نقشه نشان
                                                </a>
                                            @else
                                                <p class="text-xs opacity-70">مختصات معتبری وارد نشده.</p>
                                            @endif
                                            <p class="text-xs opacity-70">عرض:
                                                {{ $block->map_blocks->latitude ?? '-' }}, طول:
                                                {{ $block->map_blocks->longitude ?? '-' }}</p>
                                        </div>
                                    @elseif ($block->type === 'gallery' && $block->gallery_blocks)
                                        <div class="preview-gallery-grid">
                                            @foreach ($block->gallery_blocks->items as $galleryItem)
                                                @php
                                                    $galleryImageUrl = $resolveBusinessCardAsset($galleryItem->image);
                                                    $galleryCaption = trim((string) $galleryItem->caption);
                                                @endphp

                                                @if ($galleryImageUrl)
                                                    <div class="space-y-2">
                                                        <img src="{{ $galleryImageUrl }}"
                                                            alt="{{ $galleryCaption !== '' ? $galleryCaption : 'تصویر گالری' }}"
                                                            class="h-32 w-full rounded-xl object-contain" />
                                                        @if ($galleryCaption !== '')
                                                            <div class="space-y-1 text-sm">
                                                                <p
                                                                    style="{{ $captionFontFamily ? 'font-family: ' . $captionFontFamily . ';' : '' }}">
                                                                    {{ $galleryCaption }}</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif ($block->type === 'faq' && $block->faq_blocks)
                                        <div class="faq-accordion">
                                            @foreach ($block->faq_blocks->items as $faqItem)
                                                <details class="faq-item">
                                                    <summary class="faq-question"
                                                        style="{{ $faqQuestionFontFamily ? 'font-family: ' . $faqQuestionFontFamily . ';' : '' }}">
                                                        {{ $faqItem->question ?: 'سوال' }}
                                                    </summary>
                                                    <div class="faq-answer whitespace-pre-line"
                                                        style="{{ $faqAnswerFontFamily ? 'font-family: ' . $faqAnswerFontFamily . ';' : '' }}">
                                                        {{ $faqItem->answer ?: 'پاسخ' }}</div>
                                                </details>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                @endif

                            </article>
                        @endforeach
                    </section>
                </div>
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const apiKey = @json($neshanApiKey);

            if (!window.L) {
                console.error('Neshan Leaflet SDK is not loaded. window.L is missing.');
                return;
            }

            document.querySelectorAll('[data-neshan-map]').forEach((mapEl) => {
                const latitude = Number(mapEl.dataset.latitude);
                const longitude = Number(mapEl.dataset.longitude);
                const zoom = Number(mapEl.dataset.zoom || 16);

                if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || Math.abs(latitude) > 90 ||
                    Math.abs(longitude) > 180) {
                    return;
                }

                const map = new window.L.Map(mapEl, {
                    key: apiKey,
                    maptype: 'neshan',
                    poi: true,
                    traffic: false,
                    center: [latitude, longitude],
                    zoom,
                });

                window.L.marker([latitude, longitude]).addTo(map);

                setTimeout(() => {
                    map.invalidateSize();
                }, 250);
            });

            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.15 // وقتی ۱۵ درصد المان دیده شد انیمیشن اجرا می‌شود
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const el = entry.target;
                    const animClass = el.getAttribute('data-animation');

                    if (!animClass) {
                        return;
                    }

                    if (entry.isIntersecting) {
                        el.classList.remove('opacity-0');
                        el.classList.add(animClass);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.scroll-anim').forEach(el => {
                observer.observe(el);
            });



        });
    </script>
</body>

</html>
