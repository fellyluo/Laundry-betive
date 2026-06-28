<?php

use Carbon\Carbon;

if (! function_exists('format_rupiah')) {
    /** Format an integer rupiah value, e.g. 12500 => "Rp 12.500". */
    function format_rupiah($value): string
    {
        $value = (int) round((float) $value);

        return 'Rp '.number_format($value, 0, ',', '.');
    }
}

if (! function_exists('format_date')) {
    /** Indonesian short date, optionally with time. */
    function format_date($date, bool $includeTime = false): string
    {
        if (empty($date)) {
            return '-';
        }
        try {
            $c = $date instanceof Carbon ? $date : Carbon::parse($date);
        } catch (Throwable $e) {
            return '-';
        }
        $c->locale('id');

        return $includeTime
            ? $c->translatedFormat('j M Y H:i')
            : $c->translatedFormat('j M Y');
    }
}

if (! function_exists('wa_number')) {
    /** Normalise an Indonesian phone number to wa.me 62 format. */
    function wa_number(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62'.substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62'.$clean;
        }

        return $clean;
    }
}

if (! function_exists('wa_link')) {
    function wa_link(string $phone, string $text): string
    {
        return 'https://wa.me/'.wa_number($phone).'?text='.rawurlencode($text);
    }
}
