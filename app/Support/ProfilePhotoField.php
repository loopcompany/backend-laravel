<?php

namespace App\Support;

use Filament\Forms\Components\FileUpload;

/**
 * فیلد یکسان «تصویر پروفایل» کارکنان و تکنسین‌ها در پنل: انتخاب، برش گرد، حذف و بزرگ‌نمایی.
 * عکس پروفایل کارکنان و تکنسین‌ها فقط از پنل تغییر می‌کند (اپ تکنسین فقط نمایش می‌دهد).
 */
final class ProfilePhotoField
{
    public static function make(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label('تصویر پروفایل')
            ->image()
            ->avatar()
            ->imageEditor()
            ->circleCropper()
            ->imageEditorAspectRatios(['1:1'])
            ->imageResizeTargetWidth('512')
            ->imageResizeTargetHeight('512')
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(5120)
            ->openable()
            ->downloadable()
            ->helperText('JPG، PNG یا WEBP تا ۵ مگابایت. برای حذف، روی ضربدر عکس بزنید.');
    }
}
