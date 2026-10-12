<?php

namespace App\Filament\Pages\Auth;

use App\Support\ProfilePhotoField;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

/**
 * پروفایل کاربر پنل (کارکنان) با امکان انتخاب، برش گرد و حذف عکس پروفایل.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            ProfilePhotoField::make('avatar', 'admins'),
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }
}
