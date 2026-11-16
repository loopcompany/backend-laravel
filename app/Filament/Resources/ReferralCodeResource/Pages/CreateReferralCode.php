<?php
namespace App\Filament\Resources\ReferralCodeResource\Pages;
use App\Filament\Resources\ReferralCodeResource;
use App\Models\ReferralCode;
use Filament\Resources\Pages\CreateRecord;
class CreateReferralCode extends CreateRecord
{
    protected static string $resource = ReferralCodeResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return array_merge($data,['policy_version'=>ReferralCode::CURRENT_POLICY,'issued_by_admin_id'=>auth('admin')->id(),'multi_use'=>false,'status'=>ReferralCode::STATUS_ACTIVE]);
    }
    protected function afterCreate(): void
    {
        $user=$this->record->user;
        $user->forceFill(['is_referral_agent'=>true])->save();
        app(\App\Services\CodeDeliveryService::class)->issue($this->record,$user,auth('admin')->id());
    }
}
