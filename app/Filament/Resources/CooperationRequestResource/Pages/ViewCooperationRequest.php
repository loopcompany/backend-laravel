<?php

namespace App\Filament\Resources\CooperationRequestResource\Pages;

use App\Filament\Resources\CooperationRequestResource;
use App\Filament\Resources\EmploymentFileResource;
use App\Models\CooperationRequest;
use App\Models\EmploymentFile;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCooperationRequest extends ViewRecord
{
    protected static string $resource = CooperationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('فرم گزینش و مصاحبه'),

            // «استخدام — انتقال اطلاعات تأییدشده به پرونده کارکنان»
            Actions\Action::make('create_employment_file')
                ->label('تشکیل پرونده استخدامی')
                ->icon('heroicon-o-folder-plus')
                ->color('success')
                ->visible(fn (CooperationRequest $record) => in_array($record->status, ['accepted', 'hired'], true)
                    && !$record->employmentFile()->exists()
                    && auth('admin')->user()?->can('edit-employment-files'))
                ->requiresConfirmation()
                ->modalDescription('پیش‌نویس پرونده‌ی استخدامی با اطلاعات تأییدشده‌ی متقاضی ساخته و وضعیت او «تبدیل به کارمند» می‌شود.')
                ->action(function (CooperationRequest $record) {
                    $file = EmploymentFile::draftFromRequest($record, auth('admin')->id());
                    $record->changeStatus('hired', auth('admin')->id(), 'پرونده‌ی استخدامی #' . $file->id . ' تشکیل شد.', 'employment_file_created');

                    Notification::make()->title('پرونده‌ی استخدامی تشکیل شد.')->success()->send();
                    $this->redirect(EmploymentFileResource::getUrl('edit', ['record' => $file]));
                }),

            Actions\Action::make('open_employment_file')
                ->label('مشاهده پرونده استخدامی')
                ->icon('heroicon-o-folder-open')
                ->visible(fn (CooperationRequest $record) => $record->employmentFile()->exists() && auth('admin')->user()?->can('view-employment-files'))
                ->url(fn (CooperationRequest $record) => EmploymentFileResource::getUrl('edit', ['record' => $record->employmentFile])),
        ];
    }
}
