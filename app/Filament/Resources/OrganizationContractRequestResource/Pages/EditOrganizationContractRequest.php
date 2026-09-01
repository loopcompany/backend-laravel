<?php

namespace App\Filament\Resources\OrganizationContractRequestResource\Pages;

use App\Filament\Resources\OrganizationContractRequestResource;
use App\Helpers\Helper;
use App\Jobs\SendFirebaseNotificationJob;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditOrganizationContractRequest extends EditRecord
{
    protected static string $resource = OrganizationContractRequestResource::class;

    private string|int|null $oldStatus = null;
    private bool $statusChanged = false;
    private bool $needDocsChanged = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldStatus = $this->record->status;
        $this->statusChanged = array_key_exists('status', $data)
            && (string) $this->oldStatus !== (string) $data['status'];

        $oldNeedDocs = $this->record->need_docs ?? '';
        $newNeedDocs = $data['need_docs'] ?? '';
        $this->needDocsChanged = $oldNeedDocs !== $newNeedDocs && !empty($newNeedDocs);

        if ($this->needDocsChanged && !is_null($newNeedDocs)) {
            Log::info('595134 sent', ['phone' => $this->record->user?->phone, 'organization_name' => $this->record->organization?->organization_name]);
            Helper::send_sms($this->record->user?->phone, 595134, ['NAME'], [$this->record->organization?->organization_name]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // بررسی تغییر وضعیت به "انجام شده" (2)
        $this->record->refresh();
        // اگر uploaded_by_admin_at خالی باشه، الان ست کن
        if (!$this->record->uploaded_by_admin_at && !is_null($this->record->contract_file_path)) {

            if ($this->record->user?->phone && $this->record->organization?->organization_name) {
                Log::info('283705 sent', ['phone' => $this->record->user?->phone, 'organization_name' => $this->record->organization?->organization_name]);

                Helper::send_sms($this->record->user?->phone, 283705, ['NAME'], [$this->record->organization?->organization_name]);
            }
            $this->record->update(['uploaded_by_admin_at' => now()]);
        }

        if ($this->needDocsChanged) {
            $this->sendPush(
                'مدارک موردنیاز درخواست سازمانی',
                'برای ادامه بررسی درخواست شما، مدارک موردنیاز را ارسال کنید.',
                ['type' => 'organization_documents_required', 'contract_request_id' => $this->record->id, 'screen' => 'organization-contract']
            );
        }

        if ($this->statusChanged) {
            $status = (int) $this->record->status;
            $notification = match ($status) {
                1 => [
                    'درخواست سازمانی تأیید شد',
                    'درخواست شما تأیید شد و قرارداد برای شما ارسال شده است.',
                    'organization_request_approved',
                ],
                2 => [
                    'درخواست سازمانی رد شد',
                    'درخواست سازمانی شما رد شد: ' . ($this->record->reject_reason ?: 'لطفاً جزئیات را در اپلیکیشن بررسی کنید.'),
                    'organization_request_rejected',
                ],
                3 => [
                    'قرارداد سازمانی تأیید شد',
                    'قرارداد ارسال‌شده شما تأیید شد.',
                    'organization_contract_approved',
                ],
                4 => [
                    'قرارداد سازمانی رد شد',
                    'قرارداد ارسال‌شده شما رد شد: ' . ($this->record->reject_contract_reason ?: 'لطفاً جزئیات را در اپلیکیشن بررسی کنید.'),
                    'organization_contract_rejected',
                ],
                default => null,
            };

            if ($notification) {
                $this->sendPush(
                    $notification[0],
                    $notification[1],
                    [
                        'type' => $notification[2],
                        'contract_request_id' => $this->record->id,
                        'screen' => 'organization-contract',
                        'status' => $status,
                    ]
                );
            }
        }

    }

    private function sendPush(string $title, string $body, array $data): void
    {
        $user = $this->record->user;

        if (!config('firebase.enabled') || !$user) {
            return;
        }

        try {
            SendFirebaseNotificationJob::dispatch($user, $title, $body, $data);
        } catch (\Throwable $exception) {
            Log::warning('Organization contract push notification could not be queued.', [
                'contract_request_id' => $this->record->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

}
