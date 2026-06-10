<?php

namespace App\Support;

use Carbon\Carbon;

class NewsDate
{
    private static array $arabicMonths = [
        1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',
        4  => 'أبريل',  5  => 'مايو',   6  => 'يونيو',
        7  => 'يوليو',  8  => 'أغسطس',  9  => 'سبتمبر',
        10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    public static function format(Carbon $date, string $locale, bool $short = false): string
    {
        if ($locale !== 'ar') {
            return $date->format($short ? 'd M Y' : 'd F Y');
        }

        if (extension_loaded('intl')) {
            $formatter = new \IntlDateFormatter(
                'ar-SY',
                $short ? \IntlDateFormatter::MEDIUM : \IntlDateFormatter::LONG,
                \IntlDateFormatter::NONE,
                'Asia/Damascus',
                \IntlDateFormatter::GREGORIAN
            );
            return $formatter->format($date->toDateTime());
        }

        $result = $date->day . ' ' . self::$arabicMonths[$date->month] . ' ' . $date->year;

        return strtr($result, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]);
    }
}
