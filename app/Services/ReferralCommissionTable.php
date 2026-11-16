<?php
namespace App\Services;

class ReferralCommissionTable
{
    /** Workbook contains sample amounts, not a formula: only exact rows are authoritative. */
    public function calculate(int $serviceAmount, int $goodsAmount): ?int
    {
        $rows = config('referral_commissions');
        $total = 0;
        foreach (['services'=>$serviceAmount, 'goods'=>$goodsAmount] as $kind=>$amount) {
            if ($amount === 0) continue;
            if (!array_key_exists($amount, $rows[$kind])) return null;
            $total += $rows[$kind][$amount];
        }
        return $total;
    }
}
