<?php

namespace App\Support;

class Jalali
{
    /** '2026-11-28' => '1405/09/07' (empty string for invalid / zero dates) */
    public static function fromDateString(?string $date): string
    {
        if (!$date || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return '';
        }
        if ((int) $m[1] < 1900 || (int) $m[2] < 1 || (int) $m[2] > 12) {
            return '';
        }

        [$jy, $jm, $jd] = self::toJalali((int) $m[1], (int) $m[2], (int) $m[3]);

        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }

    /** @return array{0:int,1:int,2:int} */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gDm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 365 * $gy + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400)
            - 80 + $gd + $gDm[$gm - 1];

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + $days % 31;
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + ($days - 186) % 30;
        }

        return [$jy, $jm, $jd];
    }
}
