<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\DB;

class OrderAccountingService
{
    public function payments(Order $order)
    {
        return UserTransaction::where('order_id',$order->id)->where('status',100)->lockForUpdate()->get()->filter->requiresAccountingReview();
    }

    public function assertCanAssign(Order $order, ?Admin $admin): void
    {
        $payments=$this->payments($order);
        if ($payments->contains(fn ($payment) => $payment->accounting_status !== 'approved')) {
            throw new OrderWorkflowException('پرداخت‌های درگاه و واریز به شرکت باید ابتدا به تأیید حسابداری برسند.', 'ACCOUNTING_APPROVAL_REQUIRED');
        }
        if ($payments->isNotEmpty() && (!$admin || !$admin->is_active || (!$admin->isSuperAdmin() && !$admin->can('assign-paid-orders')))) {
            throw new OrderWorkflowException('ارجاع این سفارش باید از مسیر مدیر تکنسین انجام شود.', 'TECHNICIAN_MANAGER_REQUIRED');
        }
    }

    public function review(UserTransaction $transaction, bool $approved, ?string $note, Admin $admin): void
    {
        if (!$admin->is_active || (!$admin->isSuperAdmin() && !$admin->can('review-order-payments'))) {
            throw new OrderWorkflowException('مجوز بررسی حسابداری ندارید.', 'ACCOUNTING_PERMISSION_REQUIRED');
        }
        if (!$approved && !filled($note)) throw new OrderWorkflowException('علت رد بررسی حسابداری الزامی است.');
        DB::transaction(function () use ($transaction,$approved,$note,$admin) {
            $locked=UserTransaction::lockForUpdate()->findOrFail($transaction->id);
            if (!$locked->requiresAccountingReview() || $locked->accounting_status !== 'pending') {
                throw new OrderWorkflowException('این پرداخت در انتظار بررسی حسابداری نیست.', 'PAYMENT_REVIEW_NOT_PENDING');
            }
            $locked->forceFill(['accounting_status'=>$approved?'approved':'rejected','accounting_reviewed_by'=>$admin->id,
                'accounting_reviewed_at'=>now(),'accounting_review_note'=>$note])->save();
            if ($locked->order_id) \App\Models\OrderStageLog::create([
                'order_id'=>$locked->order_id,'action'=>$approved?'payment_accounting_approved':'payment_accounting_rejected',
                'note'=>($approved?'تأیید وصول وجه توسط حسابداری':'رد بررسی حسابداری').($note?' — '.$note:''),
                'actor_type'=>'admin','actor_id'=>$admin->id,'actor_name'=>$admin->name,
                'meta'=>['transaction_id'=>$locked->id,'amount'=>$locked->price],
            ]);
        });
    }
}
