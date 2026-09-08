<?php

namespace App\Support;

class VietnameseCurrency
{
    public static function format(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', '.').' đ';
    }

    public static function toWords(float|int|string|null $amount): string
    {
        $n = (int) round((float) $amount);
        if ($n === 0) {
            return 'Không đồng';
        }

        $negative = $n < 0;
        $n = abs($n);

        $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $scales = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ'];

        $chunks = [];
        while ($n > 0) {
            $chunks[] = $n % 1000;
            $n = intdiv($n, 1000);
        }

        $parts = [];
        $lastNonZero = 0;
        foreach ($chunks as $i => $chunk) {
            if ($chunk > 0) {
                $lastNonZero = $i;
            }
        }

        foreach ($chunks as $i => $chunk) {
            if ($chunk === 0) {
                continue;
            }
            $pad = $i < $lastNonZero;
            $parts[] = trim(self::readTriple($chunk, $units, $pad).' '.$scales[$i]);
        }

        $words = implode(' ', array_reverse($parts));
        $words = preg_replace('/\s+/', ' ', trim($words)) ?: 'không';
        $text = mb_strtoupper(mb_substr($words, 0, 1, 'UTF-8'), 'UTF-8')
            .mb_substr($words, 1, null, 'UTF-8')
            .' đồng';

        return $negative ? 'Âm '.$text : $text;
    }

    protected static function readTriple(int $n, array $units, bool $pad): string
    {
        $hundred = intdiv($n, 100);
        $ten = intdiv($n % 100, 10);
        $one = $n % 10;

        $out = [];

        if ($hundred > 0) {
            $out[] = $units[$hundred].' trăm';
        } elseif ($pad && ($ten > 0 || $one > 0)) {
            $out[] = 'không trăm';
        }

        if ($ten > 1) {
            $out[] = $units[$ten].' mươi';
            if ($one === 1) {
                $out[] = 'mốt';
            } elseif ($one === 5) {
                $out[] = 'lăm';
            } elseif ($one > 0) {
                $out[] = $units[$one];
            }
        } elseif ($ten === 1) {
            $out[] = 'mười';
            if ($one === 5) {
                $out[] = 'lăm';
            } elseif ($one > 0) {
                $out[] = $units[$one];
            }
        } elseif ($one > 0) {
            if ($hundred > 0 || $pad) {
                $out[] = 'lẻ '.$units[$one];
            } else {
                $out[] = $units[$one];
            }
        }

        return implode(' ', $out);
    }
}
