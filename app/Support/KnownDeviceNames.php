<?php

namespace App\Support;

/**
 * نام تجاری مدل‌های رایج سامسونگ، برای وقتی که فهرست Google Play هنوز import نشده است
 * (دانلود آن از سرورهای داخل ایران 403 می‌دهد). ردیف‌های import‌شده بر این فهرست اولویت دارند.
 */
final class KnownDeviceNames
{
    public const SAMSUNG = [
        'SM-A022F' => 'Galaxy A02', 'SM-A025F' => 'Galaxy A02s', 'SM-A032F' => 'Galaxy A03 Core',
        'SM-A035F' => 'Galaxy A03', 'SM-A037F' => 'Galaxy A03s', 'SM-A042F' => 'Galaxy A04e',
        'SM-A045F' => 'Galaxy A04', 'SM-A047F' => 'Galaxy A04s', 'SM-A055F' => 'Galaxy A05',
        'SM-A057F' => 'Galaxy A05s', 'SM-A105F' => 'Galaxy A10', 'SM-A107F' => 'Galaxy A10s',
        'SM-A115F' => 'Galaxy A11', 'SM-A125F' => 'Galaxy A12', 'SM-A127F' => 'Galaxy A12',
        'SM-A135F' => 'Galaxy A13', 'SM-A137F' => 'Galaxy A13', 'SM-A145F' => 'Galaxy A14',
        'SM-A146B' => 'Galaxy A14 5G', 'SM-A155F' => 'Galaxy A15', 'SM-A156E' => 'Galaxy A15 5G',
        'SM-A205F' => 'Galaxy A20', 'SM-A207F' => 'Galaxy A20s', 'SM-A217F' => 'Galaxy A21s',
        'SM-A225F' => 'Galaxy A22', 'SM-A226B' => 'Galaxy A22 5G', 'SM-A235F' => 'Galaxy A23',
        'SM-A245F' => 'Galaxy A24', 'SM-A256B' => 'Galaxy A25 5G', 'SM-A256E' => 'Galaxy A25 5G',
        'SM-A305F' => 'Galaxy A30', 'SM-A307FN' => 'Galaxy A30s', 'SM-A315F' => 'Galaxy A31',
        'SM-A325F' => 'Galaxy A32', 'SM-A326B' => 'Galaxy A32 5G', 'SM-A336B' => 'Galaxy A33 5G',
        'SM-A336E' => 'Galaxy A33 5G', 'SM-A346B' => 'Galaxy A34 5G', 'SM-A346E' => 'Galaxy A34 5G',
        'SM-A356B' => 'Galaxy A35 5G', 'SM-A356E' => 'Galaxy A35 5G', 'SM-A505F' => 'Galaxy A50',
        'SM-A507FN' => 'Galaxy A50s', 'SM-A515F' => 'Galaxy A51', 'SM-A525F' => 'Galaxy A52',
        'SM-A526B' => 'Galaxy A52 5G', 'SM-A528B' => 'Galaxy A52s 5G', 'SM-A536B' => 'Galaxy A53 5G',
        'SM-A536E' => 'Galaxy A53 5G', 'SM-A546B' => 'Galaxy A54 5G', 'SM-A546E' => 'Galaxy A54 5G',
        'SM-A556B' => 'Galaxy A55 5G', 'SM-A556E' => 'Galaxy A55 5G', 'SM-A705F' => 'Galaxy A70',
        'SM-A715F' => 'Galaxy A71', 'SM-A725F' => 'Galaxy A72', 'SM-A736B' => 'Galaxy A73 5G',
        'SM-M115F' => 'Galaxy M11', 'SM-M127F' => 'Galaxy M12', 'SM-M215F' => 'Galaxy M21',
        'SM-M315F' => 'Galaxy M31', 'SM-M325F' => 'Galaxy M32', 'SM-M515F' => 'Galaxy M51',
        'SM-G960F' => 'Galaxy S9', 'SM-G965F' => 'Galaxy S9+', 'SM-G970F' => 'Galaxy S10e',
        'SM-G973F' => 'Galaxy S10', 'SM-G975F' => 'Galaxy S10+', 'SM-G980F' => 'Galaxy S20',
        'SM-G985F' => 'Galaxy S20+', 'SM-G988B' => 'Galaxy S20 Ultra 5G', 'SM-G780F' => 'Galaxy S20 FE',
        'SM-G781B' => 'Galaxy S20 FE 5G', 'SM-G990B' => 'Galaxy S21 FE 5G', 'SM-G991B' => 'Galaxy S21 5G',
        'SM-G996B' => 'Galaxy S21+ 5G', 'SM-G998B' => 'Galaxy S21 Ultra 5G', 'SM-S901B' => 'Galaxy S22',
        'SM-S906B' => 'Galaxy S22+', 'SM-S908B' => 'Galaxy S22 Ultra', 'SM-S911B' => 'Galaxy S23',
        'SM-S916B' => 'Galaxy S23+', 'SM-S918B' => 'Galaxy S23 Ultra', 'SM-S711B' => 'Galaxy S23 FE',
        'SM-S921B' => 'Galaxy S24', 'SM-S926B' => 'Galaxy S24+', 'SM-S928B' => 'Galaxy S24 Ultra',
        'SM-N970F' => 'Galaxy Note10', 'SM-N975F' => 'Galaxy Note10+', 'SM-N980F' => 'Galaxy Note20',
        'SM-N981B' => 'Galaxy Note20 5G', 'SM-N985F' => 'Galaxy Note20 Ultra', 'SM-N986B' => 'Galaxy Note20 Ultra 5G',
    ];

    public static function find(string $model): ?string
    {
        return self::SAMSUNG[strtoupper($model)] ?? null;
    }
}
