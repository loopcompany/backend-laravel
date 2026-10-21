{{-- پوسته‌ی هر فیلد فرم ثبت‌نام تکنسین: برچسب، آیکن، راهنما و پیام خطا --}}
@props([
    'name',
    'label',
    'for' => null,
    'icon' => null,
    'required' => false,
    'hint' => null,
    'wide' => false,
    'group' => false,
])

@php
    $message = $errors->first($name) ?: $errors->first($name . '.*');
@endphp

<div {{ $attributes->class(['tr-field', 'tr-span-2' => $wide, 'has-error' => $message !== '']) }} data-field="{{ $name }}"
    @if($group) role="group" aria-labelledby="{{ $name }}-label" @endif>
    @if($group)
        <span class="tr-label" id="{{ $name }}-label">
    @else
        <label class="tr-label" for="{{ $for ?? $name }}">
    @endif
        {{ $label }}
        @if($required)
            <b aria-hidden="true">*</b>
        @else
            <span class="tr-opt">اختیاری</span>
        @endif
    @if($group) </span> @else </label> @endif

    <div @class(['tr-control', 'has-icon' => $icon])>
        @if($icon)<i class="fa {{ $icon }} tr-ico" aria-hidden="true"></i>@endif
        {{ $slot }}
    </div>

    @if($hint)<p class="tr-hint">{{ $hint }}</p>@endif
    <p class="tr-err" id="{{ $name }}-error" aria-live="polite">{{ $message }}</p>
</div>
