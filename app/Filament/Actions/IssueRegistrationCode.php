<?php

namespace App\Filament\Actions;

use App\Models\EmploymentFile;
use App\Models\Admin;
use App\Models\Technician;
use App\Models\User;
use App\Services\RegistrationCodeService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class IssueRegistrationCode
{
    public static function make(callable $canEdit): Action
    {
        return Action::make('issue_registration_code')
            ->label('صدور کد ثبت‌نام')
            ->icon('heroicon-o-identification')
            ->authorize($canEdit)
            ->visible(fn (Admin|Technician|User|EmploymentFile $record) => !RegistrationCodeService::isIssued(
                $record instanceof User ? $record->code : ($record instanceof Technician ? $record->referral_code : $record->personnel_code)
            ) && !($record instanceof EmploymentFile && $record->trashed()) && !($record instanceof Technician && !$record->isApproved()))
            ->modalDescription('کد دو رقمی شهر و منطقه و روش ثبت‌نام اولیه را وارد کنید. کد ثبت‌نام قبلی این پرونده، در صورت وجود، جایگزین می‌شود.')
            ->form(fn (Admin|Technician|User|EmploymentFile $record) => [
                Forms\Components\TextInput::make('city_code')->label('کد شهر')->required()->regex('/^[0-9]{2}$/D')->maxLength(2),
                Forms\Components\TextInput::make('region_code')->label('کد منطقه')->required()->regex('/^[0-9]{2}$/D')->maxLength(2),
                Forms\Components\Select::make('method')->label('روش ثبت‌نام اولیه')
                    ->options(RegistrationCodeService::METHODS)->required(),
                ...($record instanceof EmploymentFile ? [
                    Forms\Components\Select::make('staff_type')->label('نوع کارکنان')
                        ->options(RegistrationCodeService::staffTypes($record->person_type))->required(),
                ] : []),
                ...($record instanceof Technician ? [Forms\Components\Select::make('staff_type')->label('نوع تکنسین')->options(RegistrationCodeService::staffTypes('technician'))->required()] : []),
            ])
            ->action(function (Admin|Technician|User|EmploymentFile $record, array $data) use ($canEdit) {
                abort_unless($canEdit($record), 403);
                if ($record instanceof Technician) abort_unless($record->isApproved(), 422);
                $code = app(RegistrationCodeService::class)->issue($record, $data);
                Notification::make()->title('کد ثبت‌نام صادر شد')->body($code)->success()->send();
            });
    }
}
