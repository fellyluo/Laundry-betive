<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Expense;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'items.service', 'payments'])
            ->orderByDesc('tanggal_masuk')
            ->get();

        $customersCount = Customer::count();
        $services = Service::all();

        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $yesterdayStr = $today->copy()->subDay()->toDateString();
        $thisMonthStr = $today->format('Y-m');
        $lastMonthStr = $today->copy()->startOfMonth()->subMonth()->format('Y-m');

        $dayNamesShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $last7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $last7[$d->toDateString()] = [
                'dateStr' => $d->toDateString(),
                'dayName' => $dayNamesShort[$d->dayOfWeek],
                'total' => 0, 'laundry' => 0, 'sabun' => 0,
            ];
        }

        $revTodayLaundry = $revTodaySabun = 0;
        $revYesterday = $revLast7 = $revThisMonth = $revLastMonth = 0;

        $serviceStats = [];   // name => [count, revenue]
        $soapCount = 0; $soapRevenue = 0;

        foreach ($orders as $o) {
            $totalLaundry = 0; $totalSabun = 0;
            foreach ($o->items as $item) {
                $isSabun = $this->isSabun($item->service);
                if ($isSabun) {
                    $totalSabun += (int) $item->subtotal;
                    $soapCount += (float) $item->qty;
                    $soapRevenue += (int) $item->subtotal;
                } else {
                    $totalLaundry += (int) $item->subtotal;
                    $name = $item->service->nama ?? 'Layanan Tidak Diketahui';
                    if (! isset($serviceStats[$name])) {
                        $serviceStats[$name] = ['count' => 0, 'revenue' => 0];
                    }
                    $serviceStats[$name]['count'] += (float) $item->qty;
                    $serviceStats[$name]['revenue'] += (int) $item->subtotal;
                }
            }
            $totalOrder = $totalLaundry + $totalSabun;

            foreach ($o->payments as $p) {
                if (! $p->created_at) {
                    continue;
                }
                $payDateStr = $p->created_at->toDateString();
                $payMonthStr = $p->created_at->format('Y-m');
                $amt = (int) $p->jumlah;

                $soapPay = 0; $laundryPay = 0;
                if ($totalOrder > 0) {
                    $soapPay = (int) round($amt * ($totalSabun / $totalOrder));
                    $laundryPay = $amt - $soapPay;
                } else {
                    $laundryPay = $amt;
                }

                if ($payDateStr === $todayStr) {
                    $revTodayLaundry += $laundryPay;
                    $revTodaySabun += $soapPay;
                }
                if ($payDateStr === $yesterdayStr) {
                    $revYesterday += $amt;
                }
                if (isset($last7[$payDateStr])) {
                    $last7[$payDateStr]['total'] += $amt;
                    $last7[$payDateStr]['laundry'] += $laundryPay;
                    $last7[$payDateStr]['sabun'] += $soapPay;
                    $revLast7 += $amt;
                }
                if ($payMonthStr === $thisMonthStr) {
                    $revThisMonth += $amt;
                }
                if ($payMonthStr === $lastMonthStr) {
                    $revLastMonth += $amt;
                }
            }
        }

        $activeCount = $orders->whereIn('status', ['diterima', 'diproses'])->count();
        $readyCount = $orders->where('status', 'selesai')->count();

        // Top 3 services by qty
        $popular = collect($serviceStats)
            ->map(fn ($stat, $name) => [
                'name' => $name,
                'count' => round($stat['count'] * 10) / 10,
                'revenue' => $stat['revenue'],
            ])
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->all();

        $belumCount = $orders->whereIn('status_bayar', ['belum', 'dp'])->count();
        $lunasCount = $orders->where('status_bayar', 'lunas')->count();

        // Expenses (pengeluaran) + net profit
        $expensesToday = (int) Expense::whereDate('tanggal', $today)->sum('jumlah');
        $expensesThisMonth = (int) Expense::whereYear('tanggal', $today->year)
            ->whereMonth('tanggal', $today->month)->sum('jumlah');
        $netToday = ($revTodayLaundry + $revTodaySabun) - $expensesToday;
        $netThisMonth = $revThisMonth - $expensesThisMonth;

        return view('dashboard', [
            'orders' => $orders,
            'recentOrders' => $orders->take(5),
            'customersCount' => $customersCount,
            'revTodayLaundry' => $revTodayLaundry,
            'revTodaySabun' => $revTodaySabun,
            'revYesterday' => $revYesterday,
            'revLast7' => $revLast7,
            'revThisMonth' => $revThisMonth,
            'revLastMonth' => $revLastMonth,
            'daily' => array_values($last7),
            'activeCount' => $activeCount,
            'readyCount' => $readyCount,
            'popular' => $popular,
            'soapCount' => round($soapCount * 10) / 10,
            'soapRevenue' => $soapRevenue,
            'belumCount' => $belumCount,
            'lunasCount' => $lunasCount,
            'expensesToday' => $expensesToday,
            'expensesThisMonth' => $expensesThisMonth,
            'netToday' => $netToday,
            'netThisMonth' => $netThisMonth,
        ]);
    }

    private function isSabun($service): bool
    {
        if (! $service) {
            return false;
        }
        if (($service->kategori ?? null) === 'sabun') {
            return true;
        }
        return str_contains(mb_strtolower($service->nama ?? ''), 'sabun');
    }
}
