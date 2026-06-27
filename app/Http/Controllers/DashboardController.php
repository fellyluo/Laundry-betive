<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /** Ekspresi SQL untuk mendeteksi layanan "sabun" (per kategori atau nama). */
    private const SABUN_SQL = "(s.kategori = 'sabun' OR LOWER(COALESCE(s.nama, '')) LIKE '%sabun%')";

    public function index()
    {
        // Super admin: dashboard monitoring semua member (bukan laundry sendiri)
        if (auth()->user()->isSuperAdmin()) {
            return $this->monitor();
        }

        $uid = auth()->id();
        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $yesterdayStr = $today->copy()->subDay()->toDateString();
        $thisMonthStr = $today->format('Y-m');
        $lastMonthStr = $today->copy()->startOfMonth()->subMonth()->format('Y-m');
        // Semua periode revenue (hari ini, kemarin, 7 hari, bulan ini & lalu) tercakup sejak awal bulan lalu.
        $windowStart = $today->copy()->startOfMonth()->subMonth();

        // ---- Hitungan ringkas langsung dari DB (sudah ter-scope tenant via global scope) ----
        $customersCount = Customer::count();
        $activeCount = Order::whereIn('status', ['diterima', 'diproses'])->count();
        $readyCount  = Order::where('status', 'selesai')->count();
        $belumCount  = Order::whereIn('status_bayar', ['belum', 'dp'])->count();
        $lunasCount  = Order::where('status_bayar', 'lunas')->count();

        // ---- Statistik layanan & sabun (akumulasi semua waktu) via agregasi DB ----
        $serviceAgg = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('services as s', 's.id', '=', 'oi.service_id')
            ->where('o.user_id', $uid)
            ->groupBy(DB::raw("COALESCE(s.nama, 'Layanan Tidak Diketahui')"), DB::raw('CASE WHEN ' . self::SABUN_SQL . ' THEN 1 ELSE 0 END'))
            ->selectRaw("COALESCE(s.nama, 'Layanan Tidak Diketahui') as nama,
                         CASE WHEN " . self::SABUN_SQL . " THEN 1 ELSE 0 END as is_sabun,
                         SUM(oi.qty) as qty, SUM(oi.subtotal) as revenue")
            ->get();

        $soapCount = 0.0; $soapRevenue = 0;
        $serviceStats = [];
        foreach ($serviceAgg as $r) {
            if ((int) $r->is_sabun === 1) {
                $soapCount += (float) $r->qty;
                $soapRevenue += (int) $r->revenue;
            } else {
                $serviceStats[$r->nama] = [
                    'count' => ($serviceStats[$r->nama]['count'] ?? 0) + (float) $r->qty,
                    'revenue' => ($serviceStats[$r->nama]['revenue'] ?? 0) + (int) $r->revenue,
                ];
            }
        }

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

        // ---- Revenue per periode (split laundry vs sabun proporsional per pembayaran) ----
        // Hanya muat pembayaran dalam window yang relevan, bukan seluruh riwayat.
        $payments = DB::table('payments as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->where('o.user_id', $uid)
            ->where('p.created_at', '>=', $windowStart)
            ->select('p.order_id', 'p.jumlah', 'p.created_at')
            ->get();

        // Komposisi sabun per order (hanya untuk order yang punya pembayaran di window).
        $orderIds = $payments->pluck('order_id')->unique()->values();
        $comp = collect();
        if ($orderIds->isNotEmpty()) {
            $comp = DB::table('order_items as oi')
                ->leftJoin('services as s', 's.id', '=', 'oi.service_id')
                ->whereIn('oi.order_id', $orderIds)
                ->groupBy('oi.order_id')
                ->selectRaw("oi.order_id,
                             SUM(oi.subtotal) as all_sum,
                             SUM(CASE WHEN " . self::SABUN_SQL . " THEN oi.subtotal ELSE 0 END) as sabun_sum")
                ->get()
                ->keyBy('order_id');
        }

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

        foreach ($payments as $p) {
            $created = Carbon::parse($p->created_at);
            $payDateStr = $created->toDateString();
            $payMonthStr = $created->format('Y-m');
            $amt = (int) $p->jumlah;

            $c = $comp[$p->order_id] ?? null;
            $allSum = $c ? (int) $c->all_sum : 0;
            $sabunSum = $c ? (int) $c->sabun_sum : 0;

            if ($allSum > 0) {
                $soapPay = (int) round($amt * ($sabunSum / $allSum));
                $laundryPay = $amt - $soapPay;
            } else {
                $soapPay = 0;
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

        // Pesanan terbaru untuk panel "Aktivitas Terakhir".
        $recentOrders = Order::with(['customer', 'items.service', 'payments'])
            ->orderByDesc('tanggal_masuk')
            ->take(5)
            ->get();

        // Expenses (pengeluaran) + net profit
        $expensesToday = (int) Expense::whereDate('tanggal', $today)->sum('jumlah');
        $expensesThisMonth = (int) Expense::whereYear('tanggal', $today->year)
            ->whereMonth('tanggal', $today->month)->sum('jumlah');
        $netToday = ($revTodayLaundry + $revTodaySabun) - $expensesToday;
        $netThisMonth = $revThisMonth - $expensesThisMonth;

        return view('dashboard', [
            'recentOrders' => $recentOrders,
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

    /** Dashboard Super Admin: pantau bisnis semua member. */
    private function monitor()
    {
        $members = User::where('role', 'member')->orderBy('username')->get();

        // Agregat per member dalam 2 query (hindari N+1 per member).
        $orderAgg = Order::withoutGlobalScopes()
            ->selectRaw('user_id, COUNT(*) as orders')
            ->groupBy('user_id')
            ->pluck('orders', 'user_id');

        $omzetAgg = DB::table('payments as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->selectRaw('o.user_id as user_id, SUM(p.jumlah) as omzet')
            ->groupBy('o.user_id')
            ->pluck('omzet', 'user_id');

        $custAgg = Customer::withoutGlobalScopes()
            ->selectRaw('user_id, COUNT(*) as customers')
            ->groupBy('user_id')
            ->pluck('customers', 'user_id');

        $rows = [];
        $totOrders = 0; $totOmzet = 0; $totCustomers = 0; $aktif = 0;

        foreach ($members as $m) {
            $orders = (int) ($orderAgg[$m->id] ?? 0);
            $omzet = (int) ($omzetAgg[$m->id] ?? 0);
            $customers = (int) ($custAgg[$m->id] ?? 0);
            $blocked = $m->isBlocked();
            if (! $blocked) {
                $aktif++;
            }
            $rows[] = ['m' => $m, 'orders' => $orders, 'omzet' => $omzet, 'customers' => $customers, 'blocked' => $blocked];
            $totOrders += $orders;
            $totOmzet += $omzet;
            $totCustomers += $customers;
        }

        usort($rows, fn ($a, $b) => $b['omzet'] <=> $a['omzet']);

        return view('superadmin.monitor', [
            'rows' => $rows,
            'totalMembers' => $members->count(),
            'aktif' => $aktif,
            'totOrders' => $totOrders,
            'totOmzet' => $totOmzet,
            'totCustomers' => $totCustomers,
        ]);
    }
}
