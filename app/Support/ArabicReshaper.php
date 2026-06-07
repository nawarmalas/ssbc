<?php

namespace App\Support;

/**
 * Converts standard Arabic Unicode (U+0620–U+06FF) to contextual Presentation
 * Forms (U+FE70–U+FEFF) so that PDF engines like dompdf — which do not apply
 * OpenType GSUB shaping — can render connected Arabic glyphs correctly using
 * fonts such as DejaVu Sans that include the Presentation Forms block.
 *
 * The algorithm:
 *  1. Walk each character in logical (storage) order.
 *  2. Determine whether neighbours can join on each side.
 *  3. Pick the correct contextual glyph: isolated / final / initial / medial.
 *  4. Apply required lam–alef ligatures.
 */
class ArabicReshaper
{
    private const T = 0; // transparent (diacritics – pass through, don't break chain)
    private const N = 1; // non-joining  (ء bare hamza)
    private const R = 2; // right-joining only  (ا, د, ر, و …)
    private const D = 3; // dual-joining (ب, ت, ن …)

    private static array $joinType = [
        // Transparent diacritics
        0x064B => self::T, 0x064C => self::T, 0x064D => self::T,
        0x064E => self::T, 0x064F => self::T, 0x0650 => self::T,
        0x0651 => self::T, 0x0652 => self::T, 0x0670 => self::T,

        0x0621 => self::N, // ء bare hamza (non-joining)

        // Right-joining only
        0x0622 => self::R, // آ
        0x0623 => self::R, // أ
        0x0624 => self::R, // ؤ
        0x0625 => self::R, // إ
        0x0627 => self::R, // ا
        0x0629 => self::R, // ة
        0x062F => self::R, // د
        0x0630 => self::R, // ذ
        0x0631 => self::R, // ر
        0x0632 => self::R, // ز
        0x0648 => self::R, // و
        0x0649 => self::R, // ى
        0x0671 => self::R, // ٱ

        // Dual-joining
        0x0626 => self::D, // ئ
        0x0628 => self::D, // ب
        0x062A => self::D, // ت
        0x062B => self::D, // ث
        0x062C => self::D, // ج
        0x062D => self::D, // ح
        0x062E => self::D, // خ
        0x0633 => self::D, // س
        0x0634 => self::D, // ش
        0x0635 => self::D, // ص
        0x0636 => self::D, // ض
        0x0637 => self::D, // ط
        0x0638 => self::D, // ظ
        0x0639 => self::D, // ع
        0x063A => self::D, // غ
        0x0640 => self::D, // ـ tatweel (extends connection, no contextual form)
        0x0641 => self::D, // ف
        0x0642 => self::D, // ق
        0x0643 => self::D, // ك
        0x0644 => self::D, // ل
        0x0645 => self::D, // م
        0x0646 => self::D, // ن
        0x0647 => self::D, // ه
        0x064A => self::D, // ي
    ];

    // Contextual forms: [isolated, final, initial, medial]
    // null = no such form (fall back to isolated).
    private static array $forms = [
        0x0621 => [0xFE80, null,   null,   null  ], // ء
        0x0622 => [0xFE81, 0xFE82, null,   null  ], // آ
        0x0623 => [0xFE83, 0xFE84, null,   null  ], // أ
        0x0624 => [0xFE85, 0xFE86, null,   null  ], // ؤ
        0x0625 => [0xFE87, 0xFE88, null,   null  ], // إ
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C], // ئ
        0x0627 => [0xFE8D, 0xFE8E, null,   null  ], // ا
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92], // ب
        0x0629 => [0xFE93, 0xFE94, null,   null  ], // ة
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98], // ت
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C], // ث
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0], // ج
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4], // ح
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8], // خ
        0x062F => [0xFEA9, 0xFEAA, null,   null  ], // د
        0x0630 => [0xFEAB, 0xFEAC, null,   null  ], // ذ
        0x0631 => [0xFEAD, 0xFEAE, null,   null  ], // ر
        0x0632 => [0xFEAF, 0xFEB0, null,   null  ], // ز
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4], // س
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8], // ش
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC], // ص
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0], // ض
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4], // ط
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8], // ظ
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC], // ع
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0], // غ
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4], // ف
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8], // ق
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC], // ك
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0], // ل
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4], // م
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8], // ن
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC], // ه
        0x0648 => [0xFEED, 0xFEEE, null,   null  ], // و
        0x0649 => [0xFEEF, 0xFEF0, null,   null  ], // ى
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4], // ي
        0x0671 => [0xFB50, 0xFB51, null,   null  ], // ٱ
    ];

    // Required lam–alef ligatures: alef_variant => [isolated, final]
    private static array $lamAlef = [
        0x0622 => [0xFEF5, 0xFEF6], // لآ
        0x0623 => [0xFEF7, 0xFEF8], // لأ
        0x0625 => [0xFEF9, 0xFEFA], // لإ
        0x0627 => [0xFEFB, 0xFEFC], // لا
    ];

    public static function hasArabic(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $text);
    }

    public static function reshape(string $text): string
    {
        if ($text === '' || ! self::hasArabic($text)) {
            return $text;
        }

        $chars = mb_str_split($text, 1, 'UTF-8');
        $codes = array_map(fn ($c) => mb_ord($c, 'UTF-8'), $chars);
        $len   = count($codes);
        $out   = '';

        for ($i = 0; $i < $len; $i++) {
            $cp = $codes[$i];

            if (! isset(self::$forms[$cp])) {
                $out .= $chars[$i];
                continue;
            }

            $type = self::$joinType[$cp] ?? self::N;

            // Lam–alef ligature (ل followed by an alef variant)
            if ($cp === 0x0644) {
                $next = self::nextArabicIndex($codes, $i + 1, $len);
                if ($next !== null && isset(self::$lamAlef[$codes[$next]])) {
                    $hasPrev    = self::prevJoins($codes, $i);
                    $ligatures  = self::$lamAlef[$codes[$next]];
                    $out .= mb_chr($hasPrev ? $ligatures[1] : $ligatures[0], 'UTF-8');
                    $i = $next; // consume the alef
                    continue;
                }
            }

            $prevJoin = self::prevJoins($codes, $i);
            $nextJoin = ($type === self::D) && self::nextJoins($codes, $i, $len);

            $idx = match (true) {
                $prevJoin && $nextJoin => 3, // medial
                $prevJoin             => 1, // final
                $nextJoin             => 2, // initial
                default               => 0, // isolated
            };

            $forms = self::$forms[$cp];
            $glyph = $forms[$idx] ?? $forms[0];

            $out .= mb_chr($glyph ?? $cp, 'UTF-8');
        }

        return $out;
    }

    // Does the character at position i receive a connection from position i-1?
    // True only when the preceding non-transparent character is dual-joining (D).
    private static function prevJoins(array $codes, int $i): bool
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            $t = self::$joinType[$codes[$j]] ?? null;
            if ($t === self::T) continue;
            return $t === self::D;
        }
        return false;
    }

    // Can this character (must be D-type) send a connection to position i+1?
    // True when the next non-transparent character is Arabic (R or D).
    private static function nextJoins(array $codes, int $i, int $len): bool
    {
        for ($j = $i + 1; $j < $len; $j++) {
            $t = self::$joinType[$codes[$j]] ?? null;
            if ($t === self::T) continue;
            return $t === self::R || $t === self::D;
        }
        return false;
    }

    // Index of the next non-transparent Arabic character, or null.
    private static function nextArabicIndex(array $codes, int $start, int $len): ?int
    {
        for ($j = $start; $j < $len; $j++) {
            $t = self::$joinType[$codes[$j]] ?? null;
            if ($t !== self::T) return $j;
        }
        return null;
    }
}
