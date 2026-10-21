<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Helpers\Helper;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /** استپر و تب مراحل فرآیند بالای فرم (filament.resources.order-resource.pages.edit-order) */
    protected static string $view = 'filament.resources.order-resource.pages.edit-order';

    public ?array $oldData = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // جزئیات فرآیند برای سفارش‌های قبلی ساخته می‌شود تا تب‌های مراحل پر شوند
        app(\App\Services\OrderWorkflowService::class)->details($this->record);
        $this->record->loadMissing(['user.organization', 'technician', 'technician_order_report', 'delivery_reports', 'workflow.reviewer', 'order_galleries']);
    }

    /** استپر + تب هر مرحله با اطلاعات و اقدام‌های همان مرحله (همان صفحه‌ی جزئیات) */
    public function workflowInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema(OrderResource\Workflow\OrderWorkflowPanel::schema());
    }

    /** فرم ویرایش و رابطه‌ها (بازدیدها، گالری، خدمات اضافی، …) به‌صورت تب زیر مراحل */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'ویرایش اطلاعات سفارش';
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-pencil-square';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // ذخیره وضعیت قبلی
        $this->oldData = $data;
        return $data;
    }

    // وضعیت، تکنسین و قیمت نهایی در این فرم فقط‌خواندنی‌اند و از مراحل صفحه‌ی جزئیات تغییر می‌کنند
    // (OrderWorkflowService: لغو، مختومه کردن پرونده با واریز سهم تکنسین، ثبت قیمت نهایی).

    protected function afterSave(): void
    {
        // پیامک «برآورد هزینه آماده است»: وقتی برآورد و درصد پیش‌پرداخت هر دو ثبت شدند.
        // درصد پیش‌پرداخت از هنگام اعزام به لوپ پیش‌فرض ۶۰ دارد، پس ثبت برآورد هم ملاک است.
        $estimateWasMissing = is_null($this->oldData['prepayment']) || is_null($this->oldData['loop_cost_estimate'] ?? null);
        if ($estimateWasMissing && $this->record->prepayment && $this->record->loop_cost_estimate) {
            $user = $this->record->user;
            if($user){
                Helper::send_sms($user->phone, 899612, ['ID'], [$this->record->id]);
            }
        }
        if($this->oldData['prepayment_payment_status'] ==0 && $this->record->prepayment_payment_status == 1){
            $this->record->update(['user_accept_date' => now()]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            OrderResource\Workflow\OrderWorkflowPanel::cancelAction(),
            Actions\Action::make('print_label')
                ->label('چاپ لیبل')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('admin.orders.label', ['order' => $this->record->getKey()]))
                ->openUrlInNewTab(),
            Actions\Action::make('download_invoice')
                ->label('دانلود رسید')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => route('web.reciept', ['id' => $this->record->getKey()]))
                ->openUrlInNewTab(),
        ];
    }

    /** پس از ذخیره روی همین صفحه (استپر و مراحل) می‌ماند */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
