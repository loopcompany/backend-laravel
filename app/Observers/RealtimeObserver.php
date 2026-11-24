<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderProcurement;
use App\Models\Organization;
use App\Services\RealtimePublisher;
use Illuminate\Database\Eloquent\Model;

class RealtimeObserver
{
    public const TOPICS = [
        'OrderReceipt' => 'orders',
        'ProcurementConsultation' => 'consultations', 'Order' => 'orders', 'OrderDetail' => 'orders', 'OrderExtraService' => 'orders',
        'OrderGallery' => 'orders', 'OrderQuote' => 'orders', 'OrderStageLog' => 'orders',
        'OrderWorkflow' => 'orders', 'OrderProcurement' => 'orders', 'OrderProcurementItem' => 'orders',
        'DeliveryReport' => 'orders', 'TechnicianOrderReport' => 'orders', 'DebtRequest' => 'orders',
        'FieldServiceVisit' => 'orders', 'FieldServicePhoto' => 'orders', 'FieldServiceReview' => 'orders',
        'Chat' => 'chat', 'UserTicket' => 'support', 'TechnicianTicket' => 'support',
        'FaultReport' => 'support', 'ReportViolation' => 'support', 'ContactUs' => 'support',
        'UserTransaction' => 'wallet', 'TechnicianTransaction' => 'wallet',
        'GemTransaction' => 'wallet', 'Settlement' => 'wallet', 'ReferralCommission' => 'wallet',
        'ReferralWithdrawal' => 'wallet', 'User' => 'account', 'Technician' => 'account',
        'Organization' => 'account', 'OrganizationUser' => 'account', 'UserAddress' => 'account',
        'OrganizationDocument' => 'contracts', 'OrganizationContract' => 'contracts',
        'OrganizationContractRequest' => 'contracts', 'Contract' => 'contracts', 'OrganizationEditRequest' => 'account',
        'EditRequest' => 'account', 'UserNote' => 'account', 'TechnicianNote' => 'account',
        'Category' => 'catalog', 'CategoryField' => 'catalog', 'Field' => 'catalog',
        'FieldDetail' => 'catalog', 'MinPrice' => 'catalog', 'LetterRate' => 'catalog',
        'Term' => 'catalog', 'OrganizationTerm' => 'catalog', 'Privacy' => 'catalog',
        'Warranty' => 'catalog', 'AppSetting' => 'catalog', 'EducationRegisteration' => 'account',
        'TechnicianEducationRegisteration' => 'account', 'LoginActivity' => 'activity',
        'SecurityAlert' => 'account', 'DeleteAccountRequest' => 'account',
        'ManpowerRequest' => 'requests', 'TransferRequest' => 'requests', 'LeaveRequest' => 'requests',
        'TerminationRequest' => 'requests', 'EducationRequest' => 'requests', 'ArchiveImage' => 'account',
        'TechnicianReview' => 'orders', 'TechnicianPoll' => 'orders', 'PollApplication' => 'account',
        'CodeDelivery' => 'account', 'PromoCode'=>'catalog', 'IncentivePlan' => 'catalog', 'Club' => 'catalog', 'DiscountCode' => 'catalog',
        'DiscountUse' => 'wallet', 'Faq' => 'catalog', 'FaqBlock' => 'catalog', 'FaqItem' => 'catalog',
        'PdfDocument' => 'catalog', 'Contact' => 'catalog', 'Social' => 'catalog', 'MapRadius' => 'catalog',
    ];

    public function saved(Model $model): void
    {
        $dirty = array_diff(array_keys($model->getChanges()), [
            'updated_at', 'last_seen_at', 'last_login_at', 'remember_token', 'phone_verify_code',
        ]);
        if (!$model->wasRecentlyCreated && !$dirty) return;
        $this->publish($model, $dirty);
    }

    public function deleted(Model $model): void
    {
        $this->publish($model);
    }

    public function restored(Model $model): void
    {
        $this->publish($model);
    }

    private function publish(Model $model, array $dirty = []): void
    {
        if (!config('realtime.enabled')) return;
        $name = class_basename($model);
        if ($name === 'DatabaseNotification') {
            $prefix = match ($model->notifiable_type) {
                \App\Models\Admin::class => 'admins.',
                \App\Models\User::class => 'users.',
                \App\Models\Technician::class => 'technicians.',
                default => null,
            };
            if ($prefix) app(RealtimePublisher::class)->changed($prefix . $model->notifiable_id, ['notifications']);
            return;
        }
        $topic = self::TOPICS[$name];
        $readOnly = !$model->wasRecentlyCreated && $dirty && !array_diff($dirty, ['is_read', 'read_at', 'assignment_viewed_at']);
        $topics = [$readOnly ? $topic . '-counts' : $topic];
        $publisher = app(RealtimePublisher::class);
        $publisher->changed('admin.' . $topic, $topics);

        $userIds = [$model->getAttribute('user_id'), $model->getOriginal('user_id')];
        $technicianIds = [$model->getAttribute('technician_id'), $model->getOriginal('technician_id')];
        if ($name === 'LoginActivity') {
            $userIds = $model->user_type === \App\Models\User::class ? [$model->user_id] : [];
            $technicianIds = $model->user_type === \App\Models\Technician::class ? [$model->user_id] : [];
        }
        if ($name === 'SecurityAlert') {
            $userIds = $model->account_type === \App\Models\User::class ? [$model->account_id] : [];
            $technicianIds = $model->account_type === \App\Models\Technician::class ? [$model->account_id] : [];
        }
        if ($name === 'User') $userIds[] = $model->getKey();
        if ($name === 'Technician') $technicianIds[] = $model->getKey();
        if ($name === 'Organization') $userIds[] = $model->user_id;

        $orderId = $model->getAttribute('order_id');
        if ($name === 'Order') {
            $order = $model;
        } else {
            if (!$orderId && $model->getAttribute('order_procurement_id')) {
                $orderId = OrderProcurement::whereKey($model->order_procurement_id)->value('order_id');
            }
            $order = $orderId ? Order::find($orderId) : null;
        }
        if ($order) {
            $userIds[] = $order->user_id;
            $technicianIds[] = $order->technician_id;
            if ($name === 'Order') $technicianIds[] = $model->getOriginal('technician_id');
            if (!$readOnly) $topics = array_unique(array_merge($topics, ['orders']));
        }
        if ($model->getAttribute('organization_id')) {
            $userIds[] = Organization::whereKey($model->organization_id)->value('user_id');
        }
        if ($model->getAttribute('field_service_visit_id')) {
            $visit = \App\Models\FieldServiceVisit::find($model->field_service_visit_id);
            if ($visit) {
                $technicianIds[] = $visit->technician_id;
                $visitOrder = $visit->order_id ? Order::find($visit->order_id) : null;
                if ($visitOrder) $userIds[] = $visitOrder->user_id;
            }
        }

        foreach (array_unique(array_filter($userIds)) as $id) $publisher->changed('users.' . $id, $topics);
        foreach (array_unique(array_filter($technicianIds)) as $id) $publisher->changed('technicians.' . $id, $topics);
        if ($topic === 'catalog') $publisher->changed('catalog', ['catalog']);
        if ($name === 'Contract') $publisher->changed('catalog', ['contracts']);
    }
}
