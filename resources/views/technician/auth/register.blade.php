@extends('layout.main.header')
@section('meta_title', 'ثبت‌نام تکنسین | لوپ')

@php
    // فیلدها، گزینه‌ها و برچسب‌ها همانند فرم ثبت‌نام اپ تکنسین (screens/auth/SignIn.js) است
    $maritalStatuses = ['متأهل' => 'fa-heart-o', 'مجرد' => 'fa-user-o'];
    $militaryStatuses = ['مشمول خدمت', 'درانتظار اعزام', 'فاقد سابقه خدمت', 'اتمام خدمت', 'معافیت', 'در حال تحصیل'];
    $educationStatuses = ['دیپلم', 'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری'];
    $vehicleTypes = ['موتور سیکلت' => 'fa-motorcycle', 'خودرو' => 'fa-car', 'دوچرخه' => 'fa-bicycle', 'پیاده' => 'fa-male'];

    $steps = [
        [
            'title' => 'اطلاعات فردی',
            'hint' => 'هویت، تحصیلات و وضعیت خدمت',
            'fields' => ['name', 'melicode', 'birth_date', 'father_name', 'issued_from', 'serial_number',
                'marital_status', 'military_status', 'education_status', 'education_field'],
        ],
        [
            'title' => 'تماس و سکونت',
            'hint' => 'ارتباط، گواهینامه و نشانی',
            'fields' => ['phone', 'mobile', 'telephone', 'email', 'certificate_number', 'certificate_issue_date',
                'certificate_expiry_date', 'vehicle_type', 'region', 'city', 'home_postal_code', 'home_address', 'other_referral_code'],
        ],
        [
            'title' => 'دانش کامپیوتر',
            'hint' => 'مهارت، تخصص و رزومه',
            'fields' => ['idea', 'software_skill', 'hardware_skill', 'software_weakness', 'hardware_weakness',
                'expertise_ids', 'resume', 'accept_terms'],
        ],
    ];

    // بعد از خطای سرور، فرم در اولین مرحله‌ای باز می‌شود که خطا دارد
    $submitted = session()->hasOldInput();
    $errorFields = array_map(fn ($key) => explode('.', $key)[0], array_keys($errors->getMessages()));
    $initialStep = 0;
    if ($submitted) {
        $initialStep = count($steps) - 1;
        foreach ($steps as $index => $step) {
            if (array_intersect($errorFields, $step['fields'])) {
                $initialStep = $index;
                break;
            }
        }
    }

    $selectedExpertises = array_map('intval', (array) old('expertise_ids', []));
    $maxBirthYear = (int) substr($maxBirthDate, 0, 4);
    $licenceMaxDate = \Morilog\Jalali\Jalalian::now()->addYears(10)->format('Y/m/d');
    // ورود تکنسین در خود اپ تکنسین (نسخه‌ی وب) انجام می‌شود تا سایت و اپ یک سیستم باشند
    $technicianPanelLoginUrl = rtrim(config('app.technician_panel_url'), '/') . '/login';
@endphp

@section('content')
    <style>
        .tr-page {
            --navy-950: #050b16; --navy-900: #0a1526; --navy-800: #0f2038; --navy-700: #16304f;
            --gold-50: #fbf6ea; --gold-300: #ecc873; --gold-500: #d9a940; --gold-600: #b8892a;
            --cream: #f7f4ef; --ink: #161a22; --ink-soft: #5b6472; --ink-mute: #8a919c;
            --line: #e7e1d6; --field: #fdfcfa; --field-line: #ddd6c9; --field-hover: #c9c0b0;
            --danger: #b42318; --danger-bg: #fef3f2;
            --ease: cubic-bezier(.16, .84, .32, 1);
            font-family: 'Vazirmatn FD', 'Vazirmatn', sans-serif;
            color: var(--ink);
            line-height: 1.8;
            background:
                radial-gradient(900px 420px at 90% -5%, rgba(217, 169, 64, .10), transparent 60%),
                radial-gradient(700px 380px at 0% 100%, rgba(10, 21, 38, .05), transparent 60%),
                var(--cream);
            padding: 40px 0 72px;
        }
        .tr-page *, .tr-page *::before, .tr-page *::after { box-sizing: border-box; }
        .tr-shell { max-width: 1240px; margin: 0 auto; padding: 0 16px; }
        .tr-layout { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 28px; align-items: start; }
        .tr-main { grid-column: 1; grid-row: 1; min-width: 0; }
        .tr-aside { grid-column: 2; grid-row: 1; position: sticky; top: 100px; }

        /* ---------- تصویر کناری ---------- */
        .tr-visual {
            position: relative; overflow: hidden; isolation: isolate;
            border-radius: 24px; background: var(--navy-900);
            height: calc(100vh - 140px); min-height: 560px; max-height: 780px;
            box-shadow: 0 30px 60px -24px rgba(5, 11, 22, .55);
        }
        .tr-visual picture, .tr-visual img { position: absolute; inset: 0; width: 100%; height: 100%; }
        .tr-visual img { object-fit: cover; object-position: center 30%; transform: scale(1.02); transition: transform 1.4s var(--ease); }
        .tr-visual:hover img { transform: scale(1.06); }
        .tr-visual::after {
            content: ""; position: absolute; inset: 0; z-index: 1;
            background: linear-gradient(to top, rgba(5, 11, 22, .97) 0%, rgba(5, 11, 22, .82) 36%, rgba(5, 11, 22, .2) 66%, rgba(5, 11, 22, 0) 100%);
        }
        .tr-visual-content { position: absolute; inset: auto 0 0 0; z-index: 2; padding: 28px 26px; color: #f4f0e6; }
        .tr-eyebrow {
            display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 700;
            color: var(--gold-300); background: rgba(217, 169, 64, .12); border: 1px solid rgba(217, 169, 64, .35);
            border-radius: 999px; padding: 2px 12px;
        }
        .tr-page .tr-visual h2 { font-size: 24px; font-weight: 800; color: #fff; margin: 12px 0 4px; line-height: 1.5; }
        .tr-visual p { color: #c4cbda; font-size: 14px; margin: 0 0 18px; }
        .tr-journey { list-style: none; margin: 0; padding: 16px 0 0; border-top: 1px solid rgba(255, 255, 255, .12); display: grid; gap: 12px; }
        .tr-journey li { display: flex; gap: 12px; align-items: flex-start; font-size: 13.5px; color: #dfe4ee; line-height: 1.7; }
        .tr-journey li span {
            flex: none; width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center;
            background: rgba(217, 169, 64, .14); color: var(--gold-300); border: 1px solid rgba(217, 169, 64, .45);
            font-size: 12.5px; font-weight: 700;
        }

        /* ---------- کارت فرم ---------- */
        .tr-card {
            background: #fff; border: 1px solid var(--line); border-radius: 24px;
            box-shadow: 0 1px 2px rgba(20, 24, 31, .04), 0 22px 44px -22px rgba(20, 24, 31, .22);
        }
        .tr-card-head { padding: 30px 32px 0; }
        .tr-page .tr-card-head h1 { font-size: 24px; font-weight: 800; margin: 0 0 4px; color: var(--ink); line-height: 1.6; }
        .tr-card-head p { margin: 0; color: var(--ink-soft); font-size: 14px; }
        .tr-card-head p b { color: var(--danger); }
        .tr-card-body { padding: 4px 32px 12px; }
        .tr-card-foot { display: flex; align-items: center; gap: 12px; padding: 20px 32px 26px; border-top: 1px solid var(--line); }
        .tr-foot-status { flex: 1; text-align: center; color: var(--ink-mute); font-size: 13px; }

        /* ---------- استپر ---------- */
        .tr-stepper { position: relative; margin: 26px 0 6px; }
        .tr-steps { display: grid; grid-template-columns: repeat(3, 1fr); list-style: none; margin: 0; padding: 0; }
        .tr-stepper-track { position: absolute; top: 21px; right: 16.666%; left: 16.666%; height: 3px; border-radius: 3px; background: var(--line); overflow: hidden; }
        .tr-stepper-fill { position: absolute; top: 0; bottom: 0; right: 0; width: 0; background: linear-gradient(to left, var(--gold-500), var(--gold-300)); transition: width .55s var(--ease); }
        .tr-step-btn {
            all: unset; box-sizing: border-box; position: relative; z-index: 1; width: 100%;
            display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; cursor: default;
        }
        .tr-step-btn.is-clickable { cursor: pointer; }
        .tr-step-dot {
            width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center;
            background: #fff; border: 2px solid var(--line); color: var(--ink-mute); font-weight: 800; font-size: 16px;
            transition: all .35s var(--ease);
        }
        .tr-step-dot .fa { display: none; font-size: 16px; }
        .tr-step-btn.is-clickable:hover .tr-step-dot { border-color: var(--gold-500); }
        .tr-step-btn.is-current .tr-step-dot { background: var(--navy-900); border-color: var(--navy-900); color: var(--gold-300); box-shadow: 0 0 0 6px rgba(10, 21, 38, .08); }
        .tr-step-btn.is-done .tr-step-dot { background: var(--gold-500); border-color: var(--gold-500); color: var(--navy-900); }
        .tr-step-btn.is-done .tr-step-num { display: none; }
        .tr-step-btn.is-done .tr-step-dot .fa { display: inline; }
        .tr-step-title { font-size: 14px; font-weight: 700; color: var(--ink-soft); line-height: 1.5; }
        .tr-step-btn.is-current .tr-step-title { color: var(--ink); }
        .tr-step-hint { display: block; font-size: 12px; font-weight: 400; color: var(--ink-mute); margin-top: 2px; }
        .tr-step-btn:focus-visible .tr-step-dot { outline: 2px solid var(--gold-500); outline-offset: 3px; }

        /* ---------- مراحل و بخش‌ها ---------- */
        .tr-panel[hidden] { display: none; }
        .tr-panel { animation: trIn .45s var(--ease); }
        @keyframes trIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        .tr-section { padding: 24px 0 8px; }
        .tr-section + .tr-section { border-top: 1px dashed var(--line); }
        .tr-page .tr-section-title { display: flex; align-items: center; gap: 10px; font-size: 15.5px; font-weight: 800; color: var(--ink); margin: 0 0 18px; line-height: 1.6; }
        .tr-section-title > i {
            width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: none;
            background: var(--gold-50); color: var(--gold-600); font-size: 15px; border: 1px solid rgba(217, 169, 64, .3);
        }
        .tr-section-title small { margin-right: auto; font-size: 12.5px; font-weight: 500; color: var(--ink-mute); }
        .tr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 20px; }
        .tr-span-2 { grid-column: 1 / -1; }

        /* ---------- فیلدها ---------- */
        .tr-field { min-width: 0; }
        .tr-page .tr-label { display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; color: var(--ink); margin: 0 0 7px; line-height: 1.6; }
        .tr-label b { color: var(--danger); }
        .tr-opt { font-size: 11px; font-weight: 500; color: var(--ink-mute); background: #f2efe9; border-radius: 999px; padding: 0 8px; line-height: 20px; }
        .tr-control { position: relative; }
        .tr-page .tr-input {
            display: block; width: 100%; height: 50px; margin: 0; padding: 0 16px;
            border: 1.5px solid var(--field-line); border-radius: 14px; background: var(--field);
            font: inherit; font-size: 14.5px; color: var(--ink); box-shadow: none; outline: none;
            -webkit-appearance: none; appearance: none;
            transition: border-color .2s, box-shadow .2s, background-color .2s;
        }
        .tr-page textarea.tr-input { height: auto; min-height: 108px; padding: 12px 16px 28px; resize: vertical; line-height: 1.9; }
        .tr-page select.tr-input {
            cursor: pointer; padding-left: 40px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5l5 5 5-5' fill='none' stroke='%238a919c' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: left 16px center;
        }
        .tr-control.has-icon .tr-input { padding-right: 46px; }
        .tr-ico { position: absolute; right: 17px; top: 25px; transform: translateY(-50%); z-index: 1; color: var(--ink-mute); font-size: 16px; pointer-events: none; transition: color .2s; }
        .tr-page .tr-input::placeholder { color: #a7adb6; opacity: 1; }
        .tr-page .tr-input:hover { border-color: var(--field-hover); }
        .tr-page .tr-input:focus { border-color: var(--gold-500); background-color: #fff; box-shadow: 0 0 0 4px rgba(217, 169, 64, .16); outline: none; }
        .tr-control:focus-within .tr-ico { color: var(--gold-600); }
        .tr-page .tr-input[data-jdp] { cursor: pointer; }
        .tr-page .tr-input.is-locked { background: #f3f1ec; color: var(--ink-soft); cursor: not-allowed; }
        .tr-page .tr-ltr { direction: ltr; text-align: right; }
        .tr-page .tr-ltr::placeholder { text-align: right; }
        .tr-count { position: absolute; left: 12px; bottom: 7px; font-size: 11.5px; color: var(--ink-mute); pointer-events: none; }
        .tr-hint { margin: 6px 0 0; font-size: 12px; color: var(--ink-mute); line-height: 1.7; }
        .tr-err { display: flex; gap: 6px; align-items: baseline; margin: 6px 0 0; font-size: 12.5px; color: var(--danger); line-height: 1.7; }
        .tr-err:empty { display: none; }
        .tr-err::before { content: "\f06a"; font-family: FontAwesome; }
        .has-error .tr-hint { display: none; }
        .tr-page .has-error .tr-input { border-color: var(--danger); background-color: #fffafa; }
        .tr-page .has-error .tr-input:focus { box-shadow: 0 0 0 4px rgba(180, 35, 24, .12); }
        .has-error .tr-ico { color: var(--danger); }

        /* پیش‌شماره تلفن ثابت */
        .tr-affix { display: flex; }
        .tr-affix-tag {
            display: grid; place-items: center; padding: 0 14px; flex: none;
            border: 1.5px solid var(--field-line); border-right: 0; border-radius: 14px 0 0 14px;
            background: #f2efe9; color: var(--ink-soft); font-weight: 700; letter-spacing: .5px;
        }
        .tr-page .tr-affix .tr-input { border-radius: 0 14px 14px 0; text-align: left; }

        /* منوی کشویی سفارشی */
        .tr-dd { position: relative; }
        .tr-page .tr-dd > select.tr-input { display: none; }
        .tr-page .tr-dd-btn { display: flex; align-items: center; gap: 8px; text-align: right; cursor: pointer; }
        .tr-dd-value { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tr-dd-btn.is-placeholder .tr-dd-value { color: #a7adb6; }
        .tr-dd-caret { color: var(--ink-mute); transition: transform .25s var(--ease); }
        .tr-dd.is-open .tr-dd-caret { transform: rotate(180deg); color: var(--gold-600); }
        .tr-page .tr-dd.is-open .tr-dd-btn { border-color: var(--gold-500); background-color: #fff; box-shadow: 0 0 0 4px rgba(217, 169, 64, .16); }
        .tr-menu {
            position: absolute; top: calc(100% + 8px); right: 0; left: 0; z-index: 40;
            list-style: none; margin: 0; padding: 6px; max-height: 290px; overflow: auto;
            background: #fff; border: 1px solid var(--line); border-radius: 16px;
            box-shadow: 0 22px 46px -14px rgba(10, 21, 38, .3);
            transform-origin: top center; animation: trMenu .18s var(--ease);
        }
        .tr-menu[hidden] { display: none; }
        .tr-dd.drop-up .tr-menu { top: auto; bottom: calc(100% + 8px); transform-origin: bottom center; }
        @keyframes trMenu { from { opacity: 0; transform: translateY(-4px) scale(.98); } to { opacity: 1; transform: none; } }
        .tr-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 14px; color: var(--ink); cursor: pointer; transition: background-color .15s; }
        .tr-option + .tr-option { margin-top: 2px; }
        .tr-option-mark {
            flex: none; width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center;
            border: 1.5px solid var(--field-line); color: transparent; font-size: 10px; transition: all .15s;
        }
        .tr-option.is-active { background: #f6f2e9; }
        .tr-option[aria-selected="true"] { font-weight: 700; color: var(--navy-900); }
        .tr-option[aria-selected="true"] .tr-option-mark { background: var(--navy-900); border-color: var(--navy-900); color: var(--gold-300); }

        /* انتخاب دوحالته (وضعیت تأهل) */
        .tr-seg { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; height: 50px; padding: 4px; border: 1.5px solid var(--field-line); border-radius: 14px; background: #f2efe9; }
        .tr-page .tr-seg-item { position: relative; display: block; margin: 0; }
        .tr-seg-item input { position: absolute; opacity: 0; pointer-events: none; }
        .tr-seg-item span {
            display: flex; align-items: center; justify-content: center; gap: 8px; height: 100%;
            border-radius: 10px; font-size: 14px; font-weight: 600; color: var(--ink-soft); cursor: pointer; transition: all .2s var(--ease);
        }
        .tr-seg-item span:hover { color: var(--ink); }
        .tr-seg-item input:checked + span { background: #fff; color: var(--navy-900); box-shadow: 0 2px 8px rgba(20, 24, 31, .12); }
        .tr-seg-item input:checked + span .fa { color: var(--gold-600); }
        .tr-seg-item input:focus-visible + span { outline: 2px solid var(--gold-500); outline-offset: 1px; }

        /* کارت‌های وسیله نقلیه */
        .tr-tiles { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .tr-page .tr-tile { position: relative; display: block; margin: 0; }
        .tr-tile input { position: absolute; opacity: 0; pointer-events: none; }
        .tr-tile > span {
            position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
            min-height: 96px; padding: 16px 8px; text-align: center; cursor: pointer;
            border: 1.5px solid var(--field-line); border-radius: 16px; background: var(--field); transition: all .2s var(--ease);
        }
        .tr-tile-icon { font-size: 24px; color: var(--ink-mute); transition: color .2s; }
        .tr-tile em { font-style: normal; font-size: 13.5px; font-weight: 600; color: var(--ink-soft); }
        .tr-tile-check { position: absolute; top: 8px; left: 8px; font-size: 17px; color: var(--gold-500); opacity: 0; transform: scale(.6); transition: all .2s var(--ease); }
        .tr-tile:hover > span { border-color: var(--field-hover); transform: translateY(-2px); }
        .tr-tile input:checked + span { border-color: var(--navy-900); background: #fff; box-shadow: 0 12px 24px -14px rgba(10, 21, 38, .5); }
        .tr-tile input:checked + span .tr-tile-icon, .tr-tile input:checked + span em { color: var(--navy-900); }
        .tr-tile input:checked + span .tr-tile-check { opacity: 1; transform: none; }
        .tr-tile input:focus-visible + span { outline: 2px solid var(--gold-500); outline-offset: 2px; }

        /* تخصص‌ها */
        .tr-chips { display: flex; flex-wrap: wrap; gap: 10px; }
        .tr-page .tr-chip { position: relative; margin: 0; }
        .tr-chip input { position: absolute; opacity: 0; pointer-events: none; }
        .tr-chip span {
            display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px 9px 14px; cursor: pointer; user-select: none;
            border: 1.5px solid var(--field-line); border-radius: 999px; background: var(--field);
            font-size: 13.5px; font-weight: 600; color: var(--ink-soft); transition: all .2s var(--ease);
        }
        .tr-chip span i {
            width: 18px; height: 18px; border-radius: 50%; display: grid; place-items: center;
            border: 1.5px solid #c9c2b4; font-size: 9px; color: transparent; transition: all .2s;
        }
        .tr-chip:hover span { border-color: var(--field-hover); color: var(--ink); }
        .tr-chip input:checked + span { background: var(--navy-900); border-color: var(--navy-900); color: #fff; }
        .tr-chip input:checked + span i { background: var(--gold-500); border-color: var(--gold-500); color: var(--navy-900); }
        .tr-chip input:focus-visible + span { outline: 2px solid var(--gold-500); outline-offset: 2px; }
        .tr-badge { font-size: 12px; font-weight: 700; color: var(--navy-900); background: var(--gold-50); border: 1px solid rgba(217, 169, 64, .4); border-radius: 999px; padding: 0 10px; }
        .tr-badge[hidden] { display: none; }

        /* بارگذاری رزومه */
        .tr-drop { position: relative; }
        .tr-file-input { position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
        .tr-page .tr-drop-area {
            display: flex; flex-direction: column; align-items: center; gap: 4px; margin: 0; padding: 28px 16px;
            border: 2px dashed #d3cbbb; border-radius: 18px; background: var(--field); text-align: center; cursor: pointer; transition: all .2s;
        }
        .tr-drop-icon {
            width: 56px; height: 56px; margin-bottom: 8px; border-radius: 16px; display: grid; place-items: center;
            background: var(--gold-50); color: var(--gold-600); font-size: 24px; border: 1px solid rgba(217, 169, 64, .3); transition: transform .25s var(--ease);
        }
        .tr-drop-area strong { font-size: 14.5px; font-weight: 700; color: var(--ink); }
        .tr-drop-area strong u { text-decoration: none; color: var(--gold-600); border-bottom: 1px dashed currentColor; }
        .tr-drop-area small { font-size: 12.5px; color: var(--ink-mute); }
        .tr-drop-area:hover, .tr-drop.is-drag .tr-drop-area { border-color: var(--gold-500); background: #fffdf7; }
        .tr-drop-area:hover .tr-drop-icon, .tr-drop.is-drag .tr-drop-icon { transform: translateY(-3px); }
        .tr-file-input:focus-visible + .tr-drop-area { outline: 2px solid var(--gold-500); outline-offset: 2px; }
        .has-error .tr-drop-area { border-color: var(--danger); background: #fffafa; }
        .tr-drop.has-file .tr-drop-area { display: none; }
        .tr-file-card { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border: 1.5px solid var(--navy-900); border-radius: 16px; background: #fff; }
        .tr-file-card[hidden] { display: none; }
        .tr-file-icon { flex: none; width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: var(--navy-900); color: var(--gold-300); font-size: 20px; }
        .tr-file-meta { flex: 1; min-width: 0; }
        .tr-file-meta strong { display: block; font-size: 14px; direction: ltr; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tr-file-meta small { font-size: 12px; color: var(--ink-mute); }
        .tr-file-remove {
            all: unset; box-sizing: border-box; flex: none; width: 38px; height: 38px; border-radius: 10px; cursor: pointer;
            display: grid; place-items: center; color: var(--ink-soft); background: #f2efe9; transition: all .2s;
        }
        .tr-file-remove:hover { background: var(--danger-bg); color: var(--danger); }
        .tr-file-remove:focus-visible { outline: 2px solid var(--gold-500); outline-offset: 2px; }

        /* پذیرش قوانین */
        .tr-page .tr-check {
            position: relative; display: flex; align-items: flex-start; gap: 12px; margin: 0; padding: 14px 16px; cursor: pointer;
            border: 1.5px solid var(--line); border-radius: 14px; background: #faf8f4; transition: border-color .2s;
        }
        .tr-check input { position: absolute; opacity: 0; }
        .tr-check-box {
            flex: none; width: 22px; height: 22px; margin-top: 3px; border-radius: 7px; display: grid; place-items: center;
            border: 1.5px solid #bdb5a6; background: #fff; color: transparent; font-size: 12px; transition: all .2s;
        }
        .tr-check input:checked + .tr-check-box { background: var(--navy-900); border-color: var(--navy-900); color: var(--gold-300); }
        .tr-check input:focus-visible + .tr-check-box { outline: 2px solid var(--gold-500); outline-offset: 2px; }
        .tr-check-text { font-size: 14px; color: var(--ink-soft); line-height: 1.9; }
        .tr-check-text a { color: var(--navy-800); font-weight: 700; border-bottom: 1px dashed currentColor; }
        .has-error .tr-check { border-color: var(--danger); background: #fffafa; }

        /* دکمه‌ها */
        .tr-btn {
            all: unset; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            height: 50px; padding: 0 26px; border-radius: 14px; font-family: inherit; font-size: 15px; font-weight: 700;
            white-space: nowrap; cursor: pointer; transition: all .25s var(--ease);
        }
        .tr-btn[hidden] { display: none; }
        .tr-btn:focus-visible { outline: 2px solid var(--gold-500); outline-offset: 3px; }
        .tr-btn-primary { background: var(--navy-900); color: #fff; box-shadow: 0 12px 22px -12px rgba(10, 21, 38, .7); }
        .tr-btn-primary:hover { background: var(--navy-800); color: #fff; transform: translateY(-1px); }
        .tr-btn-primary .fa { color: var(--gold-300); }
        .tr-btn-gold { background: linear-gradient(135deg, var(--gold-300), var(--gold-500)); color: var(--navy-950); box-shadow: 0 14px 26px -14px rgba(150, 112, 31, .9); }
        .tr-btn-gold:hover { filter: brightness(1.05); transform: translateY(-1px); }
        .tr-btn-ghost { border: 1.5px solid var(--field-line); color: var(--ink-soft); }
        .tr-btn-ghost:hover { background: #f6f3ed; color: var(--ink); }
        .tr-btn[disabled] { opacity: .75; cursor: progress; transform: none; }
        .tr-spinner { display: none; width: 18px; height: 18px; border-radius: 50%; border: 2.5px solid currentColor; border-top-color: transparent; animation: trSpin .7s linear infinite; }
        .tr-btn.is-loading .tr-spinner { display: inline-block; }
        .tr-btn.is-loading .fa { display: none; }
        @keyframes trSpin { to { transform: rotate(360deg); } }

        /* پیام‌ها */
        .tr-alert {
            display: flex; align-items: flex-start; gap: 12px; margin: 20px 0 0; padding: 14px 16px; border-radius: 14px;
            font-size: 14px; line-height: 1.8; background: var(--danger-bg); border: 1px solid #fecdca; color: #7a271a;
        }
        .tr-alert[hidden] { display: none; }
        .tr-alert .fa { margin-top: 5px; }
        .tr-alert.is-success { background: #ecfdf3; border-color: #abefc6; color: #085d3a; }
        .tr-alert.is-info { background: var(--gold-50); border-color: rgba(217, 169, 64, .45); color: #6b4e12; }
        .tr-login { margin: 20px 0 0; text-align: center; font-size: 14px; color: var(--ink-soft); }
        .tr-login a { font-weight: 700; color: var(--navy-800); }
        .tr-login a:hover { color: var(--gold-600); }

        /* تقویم شمسی همرنگ صفحه */
        jdp-container { border-radius: 14px !important; box-shadow: 0 22px 46px -14px rgba(10, 21, 38, .35) !important; font-family: 'Vazirmatn FD', 'Vazirmatn', sans-serif; }
        jdp-container .jdp-day.selected { background-color: #0a1526 !important; color: #ecc873 !important; }
        jdp-container .jdp-btn-today, jdp-container .jdp-btn-empty, jdp-container .jdp-btn-close { background: #0a1526; border-radius: 8px; }

        @media (max-width: 991.98px) {
            .tr-page { padding-top: 24px; }
            .tr-layout { grid-template-columns: minmax(0, 1fr); gap: 20px; }
            .tr-aside { grid-column: 1; grid-row: 1; position: static; }
            .tr-main { grid-row: 2; }
            .tr-visual { height: 320px; min-height: 0; border-radius: 20px; }
            .tr-visual img { object-position: center 25%; }
            .tr-visual-content { padding: 20px; }
            .tr-page .tr-visual h2 { font-size: 20px; margin-top: 8px; }
            .tr-visual p { margin: 0; font-size: 13px; line-height: 1.85; }
            .tr-journey { display: none; }
        }
        @media (max-width: 767.98px) {
            .tr-grid { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 575.98px) {
            .tr-card { border-radius: 20px; }
            .tr-card-head { padding: 22px 18px 0; }
            .tr-page .tr-card-head h1 { font-size: 20px; }
            .tr-card-body { padding: 0 18px 8px; }
            .tr-card-foot { flex-wrap: wrap; padding: 16px 18px 20px; }
            .tr-foot-status { order: -1; flex-basis: 100%; }
            .tr-card-foot .tr-btn { flex: 1; padding: 0 16px; }
            .tr-step-hint { display: none; }
            .tr-step-title { font-size: 12.5px; }
            .tr-step-dot { width: 38px; height: 38px; font-size: 14px; }
            .tr-stepper-track { top: 18px; }
            .tr-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (prefers-reduced-motion: reduce) {
            .tr-page *, jdp-container { animation: none !important; transition: none !important; }
        }
    </style>

    <main class="tr-page">
        <div class="tr-shell">
            <div class="tr-layout">

                <!-- ========== فرم ========== -->
                <div class="tr-main">
                    <div class="tr-card" id="tr-card">
                        <div class="tr-card-head">
                            <h1>ثبت‌نام تکنسین</h1>
                            <p>لطفاً اطلاعات خود را با دقت وارد کنید. پر کردن فیلدهای <b>*</b>دار الزامی است.</p>

                            <nav class="tr-stepper" aria-label="مراحل ثبت‌نام">
                                <div class="tr-stepper-track" aria-hidden="true"><div class="tr-stepper-fill"></div></div>
                                <ol class="tr-steps">
                                @foreach ($steps as $index => $step)
                                    <li>
                                        <button type="button" class="tr-step-btn" data-goto="{{ $index }}">
                                            <span class="tr-step-dot">
                                                <span class="tr-step-num">{{ $index + 1 }}</span>
                                                <i class="fa fa-check" aria-hidden="true"></i>
                                            </span>
                                            <span class="tr-step-title">
                                                {{ $step['title'] }}
                                                <span class="tr-step-hint">{{ $step['hint'] }}</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                                </ol>
                            </nav>

                            @if($errors->has('message'))
                                <div class="tr-alert" role="alert"><i class="fa fa-exclamation-circle"></i><div>{{ $errors->first('message') }}</div></div>
                            @elseif($errors->any())
                                <div class="tr-alert" role="alert"><i class="fa fa-exclamation-circle"></i><div>لطفاً فیلدهای مشخص شده را تکمیل کنید.</div></div>
                            @endif

                            @if(session('success'))
                                <div class="tr-alert is-success" role="status"><i class="fa fa-check-circle"></i><div>{{ session('success') }}</div></div>
                            @endif

                            <div class="tr-alert" id="tr-client-alert" role="alert" hidden><i class="fa fa-exclamation-circle"></i><div></div></div>
                        </div>

                        <form method="POST" action="{{ route('web.technician.register') }}" enctype="multipart/form-data"
                            id="technician-register-form" novalidate>
                            @csrf

                            <div class="tr-card-body">

                                <!-- ========== مرحله ۱: اطلاعات فردی ========== -->
                                <div class="tr-panel" data-panel="0" @if($initialStep !== 0) hidden @endif>
                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-id-card-o" aria-hidden="true"></i>مشخصات هویتی</h2>
                                        <div class="tr-grid">
                                            <x-technician-register.field name="name" label="نام و نام خانوادگی" icon="fa-user-o" required>
                                                <input type="text" class="tr-input" id="name" name="name" value="{{ old('name') }}" placeholder="مثال: علی احمدی" autocomplete="name">
                                            </x-technician-register.field>

                                            <x-technician-register.field name="melicode" label="شماره ملی" icon="fa-id-card-o" required>
                                                <input type="text" class="tr-input tr-ltr" id="melicode" name="melicode" value="{{ old('melicode') }}"
                                                    placeholder="0123456789" maxlength="10" inputmode="numeric" data-digits>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="birth_date" label="متولد" icon="fa-calendar" required
                                                :hint="'ثبت‌نام برای متولدین سال ' . $maxBirthYear . ' و قبل از آن امکان‌پذیر است.'">
                                                <input type="text" class="tr-input" id="birth_date" name="birth_date" value="{{ old('birth_date') }}"
                                                    placeholder="انتخاب تاریخ تولد" readonly
                                                    data-jdp data-jdp-min-date="1364/01/01" data-jdp-max-date="{{ $maxBirthDate }}">
                                            </x-technician-register.field>

                                            <x-technician-register.field name="father_name" label="نام پدر" icon="fa-user-o" required>
                                                <input type="text" class="tr-input" id="father_name" name="father_name" value="{{ old('father_name') }}">
                                            </x-technician-register.field>

                                            <x-technician-register.field name="issued_from" label="صادره از" icon="fa-map-o" required>
                                                <input type="text" class="tr-input" id="issued_from" name="issued_from" value="{{ old('issued_from') }}" placeholder="مثال: تهران">
                                            </x-technician-register.field>

                                            <x-technician-register.field name="serial_number" label="شماره شناسنامه" icon="fa-hashtag" required>
                                                <input type="text" class="tr-input tr-ltr" id="serial_number" name="serial_number" value="{{ old('serial_number') }}">
                                            </x-technician-register.field>
                                        </div>
                                    </section>

                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-graduation-cap" aria-hidden="true"></i>تأهل، خدمت و تحصیلات</h2>
                                        <div class="tr-grid">
                                            <x-technician-register.field name="marital_status" label="وضعیت تأهل" required group>
                                                <div class="tr-seg">
                                                    @foreach ($maritalStatuses as $status => $statusIcon)
                                                        <label class="tr-seg-item">
                                                            <input type="radio" name="marital_status" value="{{ $status }}" @checked(old('marital_status', 'متأهل') === $status)>
                                                            <span><i class="fa {{ $statusIcon }}" aria-hidden="true"></i>{{ $status }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="military_status" label="وضعیت خدمت" icon="fa-shield" required>
                                                <select class="tr-input" id="military_status" name="military_status" data-tr-select>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach ($militaryStatuses as $status)
                                                        <option value="{{ $status }}" @selected(old('military_status') === $status)>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="education_status" label="وضعیت تحصیلات" icon="fa-graduation-cap" required>
                                                <select class="tr-input" id="education_status" name="education_status" data-tr-select>
                                                    @foreach ($educationStatuses as $status)
                                                        <option value="{{ $status }}" @selected(old('education_status', 'دیپلم') === $status)>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="education_field" label="رشته تحصیلی" icon="fa-book" required>
                                                <input type="text" class="tr-input" id="education_field" name="education_field" value="{{ old('education_field') }}" placeholder="مثال: مهندسی کامپیوتر">
                                            </x-technician-register.field>
                                        </div>
                                    </section>
                                </div>

                                <!-- ========== مرحله ۲: تماس و سکونت ========== -->
                                <div class="tr-panel" data-panel="1" @if($initialStep !== 1) hidden @endif>
                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-phone" aria-hidden="true"></i>راه‌های ارتباطی</h2>
                                        <div class="tr-grid">
                                            <x-technician-register.field name="phone" label="شماره تلفن موبایل ۱۱ رقمی" icon="fa-mobile" required
                                                hint="کد تأیید و رمز ورود به این شماره پیامک می‌شود.">
                                                <input type="text" class="tr-input tr-ltr" id="phone" name="phone" value="{{ old('phone') }}"
                                                    placeholder="09123456789" maxlength="11" inputmode="numeric" autocomplete="tel" data-digits>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="mobile" label="شماره موبایل دوم" icon="fa-mobile">
                                                <input type="text" class="tr-input tr-ltr" id="mobile" name="mobile" value="{{ old('mobile') }}"
                                                    placeholder="09123456789" maxlength="11" inputmode="numeric" data-digits>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="telephone" label="شماره تلفن ثابت" required hint="۸ رقم، بدون پیش‌شماره">
                                                <div class="tr-affix" dir="ltr">
                                                    <span class="tr-affix-tag">021</span>
                                                    <input type="text" class="tr-input" id="telephone" name="telephone" value="{{ old('telephone') }}"
                                                        placeholder="12345678" maxlength="8" inputmode="numeric" data-digits>
                                                </div>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="email" label="آدرس ایمیل" icon="fa-envelope-o" required>
                                                <input type="email" class="tr-input tr-ltr" id="email" name="email" value="{{ old('email') }}"
                                                    placeholder="example@email.com" autocomplete="email">
                                            </x-technician-register.field>
                                        </div>
                                    </section>

                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-car" aria-hidden="true"></i>گواهینامه و وسیله نقلیه</h2>
                                        <div class="tr-grid">
                                            <x-technician-register.field name="certificate_number" label="شماره گواهینامه" icon="fa-hashtag" wide>
                                                <input type="text" class="tr-input tr-ltr" id="certificate_number" name="certificate_number" value="{{ old('certificate_number') }}"
                                                    placeholder="123456789" inputmode="numeric" data-digits>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="certificate_issue_date" label="تاریخ صدور گواهینامه" icon="fa-calendar">
                                                <input type="text" class="tr-input" id="certificate_issue_date" name="certificate_issue_date" value="{{ old('certificate_issue_date') }}"
                                                    placeholder="مثال: 1400/05/15" readonly data-jdp data-jdp-max-date="today">
                                            </x-technician-register.field>

                                            <x-technician-register.field name="certificate_expiry_date" label="تاریخ اعتبار گواهینامه" icon="fa-calendar-check-o">
                                                <input type="text" class="tr-input" id="certificate_expiry_date" name="certificate_expiry_date" value="{{ old('certificate_expiry_date') }}"
                                                    placeholder="انتخاب تاریخ اعتبار" readonly data-jdp data-jdp-min-date="today" data-jdp-max-date="{{ $licenceMaxDate }}">
                                            </x-technician-register.field>

                                            <x-technician-register.field name="vehicle_type" label="نوع وسیله نقلیه" wide group
                                                hint="برای لغو انتخاب، دوباره روی گزینه‌ی انتخاب‌شده بزنید.">
                                                <div class="tr-tiles">
                                                    @foreach ($vehicleTypes as $vehicleType => $vehicleIcon)
                                                        <label class="tr-tile">
                                                            <input type="radio" name="vehicle_type" value="{{ $vehicleType }}" @checked(old('vehicle_type') === $vehicleType)>
                                                            <span>
                                                                <i class="fa {{ $vehicleIcon }} tr-tile-icon" aria-hidden="true"></i>
                                                                <em>{{ $vehicleType }}</em>
                                                                <i class="fa fa-check-circle tr-tile-check" aria-hidden="true"></i>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </x-technician-register.field>
                                        </div>
                                    </section>

                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-map-marker" aria-hidden="true"></i>نشانی محل سکونت</h2>
                                        <div class="tr-grid">
                                            <x-technician-register.field name="city" label="شهر" icon="fa-lock" required>
                                                <input type="text" class="tr-input is-locked" id="city" name="city" value="تهران" readonly>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="region" label="منطقه" icon="fa-map-signs" required>
                                                <input type="text" class="tr-input tr-ltr" id="region" name="region" value="{{ old('region') }}"
                                                    placeholder="مثال: 5" maxlength="2" inputmode="numeric" data-digits>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="home_postal_code" label="کد پستی منزل" icon="fa-envelope-open-o" required wide>
                                                <input type="text" class="tr-input tr-ltr" id="home_postal_code" name="home_postal_code" value="{{ old('home_postal_code') }}"
                                                    placeholder="۱۰ رقم" maxlength="10" inputmode="numeric" data-digits>
                                            </x-technician-register.field>

                                            <x-technician-register.field name="home_address" label="آدرس منزل" required wide>
                                                <textarea class="tr-input" id="home_address" name="home_address" rows="3" maxlength="1000" data-counter
                                                    placeholder="خیابان، کوچه، پلاک، واحد">{{ old('home_address') }}</textarea>
                                            </x-technician-register.field>
                                        </div>
                                    </section>

                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-user-plus" aria-hidden="true"></i>معرف</h2>
                                        <div class="tr-grid">
                                            <x-technician-register.field name="other_referral_code" label="کد پرسنلی معرف" icon="fa-handshake-o" wide
                                                hint="اگر یکی از تکنسین‌های لوپ شما را معرفی کرده است، کد پرسنلی او را وارد کنید.">
                                                <input type="text" class="tr-input tr-ltr" id="other_referral_code" name="other_referral_code" value="{{ old('other_referral_code') }}">
                                            </x-technician-register.field>
                                        </div>
                                    </section>
                                </div>

                                <!-- ========== مرحله ۳: دانش کامپیوتر ========== -->
                                <div class="tr-panel" data-panel="2" @if($initialStep !== 2) hidden @endif>
                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-laptop" aria-hidden="true"></i>دانش کامپیوتر</h2>
                                        <div class="tr-grid">
                                            @foreach ([
                                                'idea' => ['ایده / خلاقیت', true],
                                                'software_skill' => ['تسلط / توانایی ها (نرم افزار)', false],
                                                'hardware_skill' => ['تسلط / توانایی ها (سخت افزار)', false],
                                                'software_weakness' => ['ناآگاهی / نقطه ضعف (نرم افزار)', false],
                                                'hardware_weakness' => ['ناآگاهی / نقاط ضعف (سخت افزار)', false],
                                            ] as $field => [$fieldLabel, $fieldWide])
                                                <x-technician-register.field :name="$field" :label="$fieldLabel" :wide="$fieldWide" required>
                                                    <textarea class="tr-input" id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="1000" data-counter>{{ old($field) }}</textarea>
                                                </x-technician-register.field>
                                            @endforeach
                                        </div>
                                    </section>

                                    <section class="tr-section">
                                        <h2 class="tr-section-title">
                                            <i class="fa fa-wrench" aria-hidden="true"></i>گرایش فعالیت / تخصص
                                            <small><span class="tr-badge" id="tr-expertise-count" hidden></span></small>
                                        </h2>
                                        <x-technician-register.field name="expertise_ids" label="حداقل یک تخصص را انتخاب کنید" required group>
                                            <div class="tr-chips">
                                                @forelse ($expertises as $expertise)
                                                    <label class="tr-chip">
                                                        <input type="checkbox" name="expertise_ids[]" value="{{ $expertise->id }}"
                                                            @checked(in_array($expertise->id, $selectedExpertises, true))>
                                                        <span><i class="fa fa-check" aria-hidden="true"></i>{{ $expertise->title }}</span>
                                                    </label>
                                                @empty
                                                    <p class="tr-hint">لیست تخصص‌ها در دسترس نیست.</p>
                                                @endforelse
                                            </div>
                                        </x-technician-register.field>
                                    </section>

                                    <section class="tr-section">
                                        <h2 class="tr-section-title"><i class="fa fa-file-text-o" aria-hidden="true"></i>بارگذاری رزومه</h2>
                                        <x-technician-register.field name="resume" label="فایل رزومه" required
                                            hint="می‌توانید رزومه / اطلاعات تکمیلی خود را امضا شده با موضوع (همکاری / فعالیت در لوپ) بارگذاری نمایید.">
                                            <div class="tr-drop" id="tr-drop">
                                                <input type="file" class="tr-file-input" id="resume" name="resume" accept=".pdf,.doc,.docx,.jpg,.jpeg">
                                                <label class="tr-drop-area" for="resume">
                                                    <span class="tr-drop-icon"><i class="fa fa-cloud-upload" aria-hidden="true"></i></span>
                                                    <strong>فایل را اینجا رها کنید یا <u>انتخاب کنید</u></strong>
                                                    <small>PDF، DOC، DOCX یا JPG — حداکثر ۵ مگابایت</small>
                                                </label>
                                                <div class="tr-file-card" id="tr-file-card" hidden>
                                                    <span class="tr-file-icon"><i class="fa fa-file-text-o" aria-hidden="true"></i></span>
                                                    <span class="tr-file-meta"><strong id="tr-file-name"></strong><small id="tr-file-size"></small></span>
                                                    <button type="button" class="tr-file-remove" id="tr-file-remove" aria-label="حذف فایل"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                                                </div>
                                            </div>
                                        </x-technician-register.field>
                                        @if($submitted && !$errors->has('resume'))
                                            <div class="tr-alert is-info"><i class="fa fa-info-circle"></i><div>به دلیل محدودیت امنیتی مرورگر، لطفاً فایل رزومه را دوباره انتخاب کنید.</div></div>
                                        @endif
                                    </section>

                                    <section class="tr-section">
                                        <x-technician-register.field name="accept_terms" label="قوانین و مقررات" required group>
                                            <label class="tr-check">
                                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" @checked(old('accept_terms'))>
                                                <span class="tr-check-box"><i class="fa fa-check" aria-hidden="true"></i></span>
                                                <span class="tr-check-text">
                                                    با ادامه، <a href="{{ route('web.terms') }}" target="_blank" rel="noopener">شرایط استفاده</a> و
                                                    <a href="{{ route('web.privacy') }}" target="_blank" rel="noopener">سیاست حفظ حریم خصوصی</a> لوپ را می‌پذیرم.
                                                </span>
                                            </label>
                                        </x-technician-register.field>
                                    </section>
                                </div>
                            </div>

                            <div class="tr-card-foot">
                                <button type="button" class="tr-btn tr-btn-ghost" id="tr-prev">
                                    <i class="fa fa-arrow-right" aria-hidden="true"></i>مرحله قبل
                                </button>
                                <span class="tr-foot-status" id="tr-status"></span>
                                <button type="button" class="tr-btn tr-btn-primary" id="tr-next">
                                    <span class="tr-spinner" aria-hidden="true"></span>
                                    مرحله بعد<i class="fa fa-arrow-left" aria-hidden="true"></i>
                                </button>
                                <button type="submit" class="tr-btn tr-btn-gold" id="tr-submit">
                                    <span class="tr-spinner" aria-hidden="true"></span>
                                    <i class="fa fa-check" aria-hidden="true"></i><span id="tr-submit-text">ثبت‌نام و دریافت کد تأیید</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <p class="tr-login">قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ $technicianPanelLoginUrl }}">ورود به پنل تکنسین</a></p>
                </div>

                <!-- ========== تصویر و مسیر ثبت‌نام ========== -->
                <aside class="tr-aside">
                    <div class="tr-visual">
                        <picture>
                            <source srcset="{{ asset('assets/images/technician/register.webp') }}" type="image/webp">
                            <img src="{{ asset('assets/images/technician/register.png') }}" alt="تکنسین‌های لوپ در کارگاه تعمیرات" width="1024" height="1536">
                        </picture>
                        <div class="tr-visual-content">
                            <span class="tr-eyebrow"><i class="fa fa-star" aria-hidden="true"></i>همکاری با لوپ</span>
                            <h2>به متخصصان لوپ بپیوندید</h2>
                            <p>فرصت همکاری برای تکنسین‌های رایانه، بانوان و آقایان فراهم است. اگر به فناوری علاقه‌مند هستید، دعوت می‌کنیم فرم همکاری را تکمیل و درخواست خود را ثبت کنید.</p>
                            <ol class="tr-journey">
                                <li><span>۱</span>فرم ثبت‌نام را در سه مرحله تکمیل کنید.</li>
                                <li><span>۲</span>شماره موبایل خود را با کد پیامکی تأیید کنید.</li>
                                <li><span>۳</span>کد پرسنلی و رمز ورود برایتان پیامک می‌شود.</li>
                                <li><span>۴</span>پس از بررسی و تأیید مدارک توسط لوپ، وارد پنل شوید.</li>
                            </ol>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- Jalali DatePicker JS -->
    <script src="{{ asset('assets/jalali/jalalidatepicker.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            jalaliDatepicker.startWatch({
                minDate: "attr",
                maxDate: "attr",
                separatorChar: "/",
                hasMaxYear: true,
                hasMinYear: true
            });

            const form = document.getElementById('technician-register-form');
            const card = document.getElementById('tr-card');
            const panels = [...form.querySelectorAll('.tr-panel')];
            const stepButtons = [...card.querySelectorAll('.tr-step-btn')];
            const stepFill = card.querySelector('.tr-stepper-fill');
            const prevBtn = document.getElementById('tr-prev');
            const nextBtn = document.getElementById('tr-next');
            const submitBtn = document.getElementById('tr-submit');
            const statusEl = document.getElementById('tr-status');
            const clientAlert = document.getElementById('tr-client-alert');

            const STEP_FIELDS = @json(array_column($steps, 'fields'));
            const TOTAL = STEP_FIELDS.length;
            const REFERRAL_STEP = STEP_FIELDS.findIndex(fields => fields.includes('other_referral_code'));
            const MAX_BIRTH_YEAR = {{ $maxBirthYear }};
            const MAX_RESUME_BYTES = 5 * 1024 * 1024;
            const RESUME_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg'];
            const referralUrl = @json(route('api.technician.validate-referral'));

            let current = {{ $initialStep }};
            let reached = {{ $submitted ? count($steps) - 1 : $initialStep }};
            const done = new Set([...Array(current).keys()]);
            let submitting = false;

            // ---------- کمکی‌ها ----------
            const value = (name) => {
                const el = form.elements[name];
                return el ? String(el.value || '').trim() : '';
            };
            const fieldEl = (name) => form.querySelector(`.tr-field[data-field="${name}"]`);
            const toLatinDigits = (text) => text
                .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
                .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
            const isValidMeliCode = (code) => {
                if (!/^\d{10}$/.test(code) || /^(\d)\1{9}$/.test(code)) return false;
                let sum = 0;
                for (let i = 0; i < 9; i++) sum += Number(code[i]) * (10 - i);
                const remainder = sum % 11;
                return Number(code[9]) === (remainder < 2 ? remainder : 11 - remainder);
            };
            const required = (label) => (v) => (v ? '' : `${label} الزامی است`);
            const mobileFormat = 'فرمت شماره تلفن صحیح نیست (09xxxxxxxxx)';

            // قواعد اعتبارسنجی همانند utils/validation.js در اپ تکنسین
            const checks = {
                name: required('نام و نام خانوادگی'),
                melicode: (v) => !v ? 'کد ملی الزامی است'
                    : v.length !== 10 ? 'کد ملی باید 10 رقم باشد'
                    : !isValidMeliCode(v) ? 'کد ملی نامعتبر است' : '',
                birth_date: (v) => !v ? 'تاریخ تولد الزامی است'
                    : Number(toLatinDigits(v).slice(0, 4)) > MAX_BIRTH_YEAR
                        ? `ثبت‌نام برای متولدین سال ${MAX_BIRTH_YEAR} و قبل از آن امکان‌پذیر است` : '',
                father_name: required('نام پدر'),
                issued_from: required('محل صدور'),
                serial_number: required('شماره شناسنامه'),
                marital_status: required('وضعیت تأهل'),
                military_status: required('وضعیت نظام وظیفه'),
                education_status: required('وضعیت تحصیلات'),
                education_field: required('رشته تحصیلی'),
                phone: (v) => !v ? 'شماره تلفن موبایل الزامی است' : !/^09\d{9}$/.test(v) ? mobileFormat : '',
                mobile: (v) => v && !/^09\d{9}$/.test(v) ? mobileFormat : '',
                telephone: (v) => !v ? 'تلفن ثابت الزامی است' : !/^\d{8,9}$/.test(v) ? 'فرمت شماره تلفن ثابت صحیح نیست' : '',
                email: (v) => !v ? 'آدرس ایمیل الزامی است' : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? 'فرمت ایمیل صحیح نیست' : '',
                region: required('منطقه'),
                home_postal_code: (v) => !v ? 'کد پستی الزامی است' : !/^\d{10}$/.test(v) ? 'کد پستی باید 10 رقم باشد' : '',
                home_address: required('آدرس منزل'),
                idea: required('ایده / خلاقیت'),
                software_skill: required('تسلط نرم‌افزار'),
                hardware_skill: required('تسلط سخت‌افزار'),
                software_weakness: required('نقطه ضعف نرم‌افزار'),
                hardware_weakness: required('نقطه ضعف سخت‌افزار'),
                expertise_ids: () => form.querySelector('input[name="expertise_ids[]"]:checked') ? '' : 'لطفاً حداقل یک تخصص انتخاب کنید',
                resume: () => resumeError(form.elements.resume.files[0]),
                accept_terms: () => form.elements.accept_terms.checked ? '' : 'پذیرش قوانین و مقررات الزامی می‌باشد',
            };

            function resumeError(file) {
                if (!file) return 'بارگذاری رزومه الزامی است.';
                const ext = file.name.split('.').pop().toLowerCase();
                if (!RESUME_EXTENSIONS.includes(ext)) return 'فرمت فایل باید PDF، DOC، DOCX یا JPG باشد.';
                if (file.size > MAX_RESUME_BYTES) return 'حجم فایل نباید بیشتر از 5 مگابایت باشد.';
                return '';
            }

            function setError(name, message) {
                const field = fieldEl(name);
                if (!field) return;
                field.classList.toggle('has-error', !!message);
                const err = field.querySelector('.tr-err');
                if (err) err.textContent = message || '';
                field.querySelectorAll('input, select, textarea, .tr-dd-btn').forEach(control => {
                    if (message) control.setAttribute('aria-invalid', 'true');
                    else control.removeAttribute('aria-invalid');
                });
            }

            function validateStep(step) {
                const invalid = [];
                STEP_FIELDS[step].forEach(name => {
                    if (!checks[name]) return;
                    const message = checks[name](value(name));
                    setError(name, message);
                    if (message) invalid.push(name);
                });
                return invalid;
            }

            function showAlert(message) {
                clientAlert.querySelector('div').textContent = message || '';
                clientAlert.hidden = !message;
            }

            function scrollToCard() {
                const top = card.getBoundingClientRect().top + window.scrollY - 100;
                if (window.scrollY > top) window.scrollTo({ top, behavior: 'smooth' });
            }

            function focusFirstError(step) {
                const field = panels[step].querySelector('.tr-field.has-error');
                if (!field) return;
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const control = field.querySelector('.tr-dd-btn, input:not([type="hidden"]):not([readonly]), textarea');
                if (control) control.focus({ preventScroll: true });
            }

            // ---------- استپر ----------
            function showStep(step, scroll = true) {
                current = step;
                reached = Math.max(reached, step);
                panels.forEach((panel, index) => { panel.hidden = index !== step; });
                stepButtons.forEach((button, index) => {
                    const isCurrent = index === step;
                    button.classList.toggle('is-current', isCurrent);
                    button.classList.toggle('is-done', !isCurrent && done.has(index));
                    button.classList.toggle('is-clickable', !isCurrent && index <= reached);
                    if (isCurrent) button.setAttribute('aria-current', 'step');
                    else button.removeAttribute('aria-current');
                });
                stepFill.style.width = `${(step / (TOTAL - 1)) * 100}%`;
                prevBtn.hidden = step === 0;
                nextBtn.hidden = step === TOTAL - 1;
                submitBtn.hidden = step !== TOTAL - 1;
                statusEl.textContent = `مرحله ${step + 1} از ${TOTAL}`;
                if (scroll) scrollToCard();
            }

            async function referralCodeIsValid() {
                const code = value('other_referral_code');
                if (!code) return true;
                try {
                    const response = await fetch(referralUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ referral_code: code })
                    });
                    const result = await response.json();
                    if (result?.data?.is_valid) return true;
                    setError('other_referral_code', result?.data?.message || 'کد معرف نامعتبر است');
                } catch (e) {
                    setError('other_referral_code', 'خطا در بررسی کد معرف، دوباره تلاش کنید');
                }
                return false;
            }

            async function leaveStep(step) {
                if (validateStep(step).length) return false;
                if (step === REFERRAL_STEP) {
                    nextBtn.disabled = true;
                    nextBtn.classList.add('is-loading');
                    const ok = await referralCodeIsValid();
                    nextBtn.disabled = false;
                    nextBtn.classList.remove('is-loading');
                    if (!ok) return false;
                }
                done.add(step);
                return true;
            }

            async function goTo(target) {
                if (target === current || target < 0 || target >= TOTAL) return;
                if (target < current) {
                    showAlert('');
                    showStep(target);
                    return;
                }
                for (let step = current; step < target; step++) {
                    if (!(await leaveStep(step))) {
                        done.delete(step);
                        if (step !== current) showStep(step, false);
                        showAlert('لطفاً فیلدهای مشخص شده را تکمیل کنید.');
                        focusFirstError(step);
                        return;
                    }
                }
                showAlert('');
                showStep(target);
            }

            prevBtn.addEventListener('click', () => goTo(current - 1));
            nextBtn.addEventListener('click', () => goTo(current + 1));
            stepButtons.forEach(button => button.addEventListener('click', () => {
                const target = Number(button.dataset.goto);
                if (target <= reached) goTo(target);
            }));

            // Enter در مراحل میانی به مرحله بعد می‌رود، نه ارسال فرم
            form.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter' || e.target.tagName === 'TEXTAREA' || e.target.closest('.tr-dd')) return;
                if (current < TOTAL - 1) {
                    e.preventDefault();
                    goTo(current + 1);
                }
            });

            form.addEventListener('submit', (e) => {
                if (submitting) {
                    e.preventDefault();
                    return;
                }
                for (let step = 0; step < TOTAL; step++) {
                    if (validateStep(step).length) {
                        e.preventDefault();
                        if (step !== current) showStep(step, false);
                        showAlert('لطفاً فیلدهای مشخص شده را تکمیل کنید.');
                        focusFirstError(step);
                        return;
                    }
                }
                submitting = true;
                submitBtn.disabled = true;
                submitBtn.classList.add('is-loading');
                document.getElementById('tr-submit-text').textContent = 'در حال ثبت نام...';
            });

            // پاک کردن خطای فیلد هنگام تغییر
            const clearError = (e) => {
                const field = e.target.closest && e.target.closest('.tr-field');
                if (field && field.classList.contains('has-error')) setError(field.dataset.field, '');
            };
            form.addEventListener('input', clearError);
            form.addEventListener('change', clearError);

            // ارقام فارسی/عربی را در فیلدهای عددی به لاتین تبدیل می‌کند
            form.querySelectorAll('[data-digits]').forEach(input => {
                input.addEventListener('input', () => {
                    const latin = toLatinDigits(input.value).replace(/\D/g, '');
                    if (latin !== input.value) input.value = latin;
                });
            });

            // شمارنده‌ی کاراکتر
            form.querySelectorAll('textarea[data-counter]').forEach(textarea => {
                const counter = document.createElement('span');
                counter.className = 'tr-count';
                counter.setAttribute('aria-hidden', 'true');
                textarea.after(counter);
                const update = () => { counter.textContent = `${textarea.value.length} / ${textarea.maxLength}`; };
                textarea.addEventListener('input', update);
                update();
            });

            // ---------- منوی کشویی سفارشی ----------
            const dropdowns = [];
            form.querySelectorAll('select[data-tr-select]').forEach(select => {
                const wrap = document.createElement('div');
                wrap.className = 'tr-dd';
                select.parentNode.insertBefore(wrap, select);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'tr-input tr-dd-btn';
                button.id = `${select.id}-button`;
                button.setAttribute('aria-haspopup', 'listbox');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-controls', `${select.id}-menu`);
                button.innerHTML = '<span class="tr-dd-value"></span><i class="fa fa-angle-down tr-dd-caret" aria-hidden="true"></i>';

                const menu = document.createElement('ul');
                menu.className = 'tr-menu';
                menu.id = `${select.id}-menu`;
                menu.setAttribute('role', 'listbox');
                menu.hidden = true;

                const placeholder = [...select.options].find(option => option.value === '');
                const items = [...select.options].filter(option => option.value !== '').map((option, index) => {
                    const item = document.createElement('li');
                    item.className = 'tr-option';
                    item.id = `${select.id}-option-${index}`;
                    item.setAttribute('role', 'option');
                    item.dataset.value = option.value;
                    item.innerHTML = '<span class="tr-option-mark"><i class="fa fa-check" aria-hidden="true"></i></span><span></span>';
                    item.lastChild.textContent = option.textContent;
                    menu.appendChild(item);
                    return item;
                });

                wrap.append(select, button, menu);
                const label = form.querySelector(`label[for="${select.id}"]`);
                if (label) {
                    label.htmlFor = button.id;
                    label.id = `${select.id}-label`;
                    menu.setAttribute('aria-labelledby', label.id);
                }

                let active = -1;
                const sync = () => {
                    const selected = select.selectedOptions[0];
                    const hasValue = selected && selected.value !== '';
                    button.querySelector('.tr-dd-value').textContent = hasValue
                        ? selected.textContent
                        : (placeholder ? placeholder.textContent : '');
                    button.classList.toggle('is-placeholder', !hasValue);
                    items.forEach(item => item.setAttribute('aria-selected', String(item.dataset.value === select.value)));
                };
                const highlight = (index) => {
                    active = Math.max(0, Math.min(items.length - 1, index));
                    items.forEach((item, i) => item.classList.toggle('is-active', i === active));
                    button.setAttribute('aria-activedescendant', items[active].id);
                    items[active].scrollIntoView({ block: 'nearest' });
                };
                const open = () => {
                    dropdowns.forEach(dd => dd !== api && dd.close(false));
                    menu.hidden = false;
                    wrap.classList.add('is-open');
                    button.setAttribute('aria-expanded', 'true');
                    const rect = button.getBoundingClientRect();
                    wrap.classList.toggle('drop-up', window.innerHeight - rect.bottom < menu.offsetHeight + 16 && rect.top > menu.offsetHeight + 16);
                    highlight(Math.max(0, items.findIndex(item => item.dataset.value === select.value)));
                };
                const close = (focus = true) => {
                    if (menu.hidden) return;
                    menu.hidden = true;
                    wrap.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                    button.removeAttribute('aria-activedescendant');
                    if (focus) button.focus();
                };
                const choose = (index) => {
                    select.value = items[index].dataset.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    sync();
                    close();
                };
                const api = { close };
                dropdowns.push(api);

                button.addEventListener('click', () => (menu.hidden ? open() : close()));
                button.addEventListener('keydown', (e) => {
                    const isOpen = !menu.hidden;
                    if (['ArrowDown', 'ArrowUp'].includes(e.key)) {
                        e.preventDefault();
                        if (!isOpen) open();
                        else highlight(active + (e.key === 'ArrowDown' ? 1 : -1));
                    } else if (['Enter', ' '].includes(e.key)) {
                        e.preventDefault();
                        isOpen ? choose(active) : open();
                    } else if (e.key === 'Home' && isOpen) {
                        e.preventDefault();
                        highlight(0);
                    } else if (e.key === 'End' && isOpen) {
                        e.preventDefault();
                        highlight(items.length - 1);
                    } else if (e.key === 'Escape' && isOpen) {
                        e.preventDefault();
                        close();
                    } else if (e.key === 'Tab') {
                        close(false);
                    }
                });
                items.forEach((item, index) => {
                    item.addEventListener('mousemove', () => active !== index && highlight(index));
                    item.addEventListener('click', () => choose(index));
                });
                document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(false); });
                sync();
            });

            // ---------- وسیله نقلیه: لغو انتخاب با کلیک دوباره ----------
            form.querySelectorAll('input[name="vehicle_type"]').forEach(radio => {
                const tile = radio.closest('.tr-tile');
                tile.addEventListener('pointerdown', () => { radio.dataset.wasChecked = radio.checked ? '1' : ''; });
                radio.addEventListener('click', () => {
                    if (radio.dataset.wasChecked) {
                        radio.checked = false;
                        radio.dataset.wasChecked = '';
                    }
                });
            });

            // ---------- شمارنده‌ی تخصص‌ها ----------
            const expertiseCount = document.getElementById('tr-expertise-count');
            const updateExpertiseCount = () => {
                const count = form.querySelectorAll('input[name="expertise_ids[]"]:checked').length;
                expertiseCount.hidden = count === 0;
                expertiseCount.textContent = `${count} مورد انتخاب شده`;
            };
            form.querySelectorAll('input[name="expertise_ids[]"]').forEach(input => input.addEventListener('change', updateExpertiseCount));
            updateExpertiseCount();

            // ---------- بارگذاری رزومه ----------
            const drop = document.getElementById('tr-drop');
            const resumeInput = form.elements.resume;
            const fileCard = document.getElementById('tr-file-card');
            const formatSize = (bytes) => bytes < 1024 * 1024
                ? `${Math.max(1, Math.round(bytes / 1024))} کیلوبایت`
                : `${(bytes / 1024 / 1024).toFixed(1)} مگابایت`;
            const renderFile = () => {
                const file = resumeInput.files[0];
                const message = file ? resumeError(file) : '';
                if (file && message) {
                    resumeInput.value = '';
                    setError('resume', message);
                }
                const valid = file && !message;
                drop.classList.toggle('has-file', !!valid);
                fileCard.hidden = !valid;
                if (valid) {
                    document.getElementById('tr-file-name').textContent = file.name;
                    document.getElementById('tr-file-size').textContent = formatSize(file.size);
                    fileCard.querySelector('.tr-file-icon .fa').className = `fa ${/\.pdf$/i.test(file.name) ? 'fa-file-pdf-o' : /\.jpe?g$/i.test(file.name) ? 'fa-file-image-o' : 'fa-file-word-o'}`;
                }
            };
            resumeInput.addEventListener('change', renderFile);
            document.getElementById('tr-file-remove').addEventListener('click', () => {
                resumeInput.value = '';
                renderFile();
                resumeInput.focus();
            });
            ['dragenter', 'dragover'].forEach(type => drop.addEventListener(type, (e) => {
                e.preventDefault();
                drop.classList.add('is-drag');
            }));
            ['dragleave', 'drop'].forEach(type => drop.addEventListener(type, (e) => {
                e.preventDefault();
                drop.classList.remove('is-drag');
            }));
            drop.addEventListener('drop', (e) => {
                if (!e.dataTransfer.files.length) return;
                resumeInput.files = e.dataTransfer.files;
                resumeInput.dispatchEvent(new Event('change', { bubbles: true }));
            });

            showStep(current, false);
        });
    </script>
@endsection
