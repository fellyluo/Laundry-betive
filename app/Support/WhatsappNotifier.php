<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim notifikasi WhatsApp via gateway Fonnte (https://fonnte.com).
 * Token disimpan per-member di pengaturan, sehingga tiap laundry memakai
 * nomor pengirimnya sendiri. Kegagalan gateway tidak boleh mengganggu alur
 * order — semua error ditangkap & dicatat ke log, fungsi hanya mengembalikan bool.
 */
class WhatsappNotifier
{
    private const ENDPOINT = 'https://api.fonnte.com/send';

    /** Kirim notifikasi "cucian selesai" untuk sebuah order. true bila terkirim. */
    public static function sendOrderDone(Order $order): bool
    {
        $wa = Settings::whatsapp($order->user_id);
        if (! $wa['enabled'] || $wa['token'] === '') {
            return false;
        }

        $order->loadMissing('customer');
        $phone = self::normalizePhone($order->customer->no_hp ?? '');
        if ($phone === '') {
            return false;
        }

        $branding = Settings::get($order->user_id)['branding'] ?? [];
        $paid = (int) $order->payments()->sum('jumlah');
        $sisa = max(0, $order->netTotal() - $paid);

        $message = self::fill($wa['template_selesai'], [
            'nama' => $order->customer->nama ?? 'Pelanggan',
            'nota' => $order->nomor_nota,
            'laundry' => $branding['nama_laundry'] ?? 'Laundry',
            'total' => format_rupiah($order->netTotal()),
            'sisa' => format_rupiah($sisa),
        ]);

        try {
            $res = Http::timeout(10)
                ->withHeaders(['Authorization' => $wa['token']])
                ->asForm()
                ->post(self::ENDPOINT, [
                    'target' => $phone,
                    'message' => $message,
                ]);

            if ($res->successful()) {
                return true;
            }

            Log::warning('Notifikasi WA gagal', ['order_id' => $order->id, 'status' => $res->status()]);
        } catch (\Throwable $e) {
            Log::warning('Notifikasi WA error: '.$e->getMessage(), ['order_id' => $order->id]);
        }

        return false;
    }

    /** Ganti placeholder {nama}, {nota}, dst pada template. */
    private static function fill(string $tpl, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $tpl = str_replace('{'.$k.'}', (string) $v, $tpl);
        }

        return $tpl;
    }

    /** Normalisasi nomor ke format internasional 62xxxx (kosong jika tak valid). */
    public static function normalizePhone(string $raw): string
    {
        $c = preg_replace('/[^0-9]/', '', $raw);
        if ($c === '' || strlen($c) < 8) {
            return '';
        }
        if (str_starts_with($c, '0')) {
            $c = '62'.substr($c, 1);
        } elseif (str_starts_with($c, '8')) {
            $c = '62'.$c;
        }

        return $c;
    }
}
