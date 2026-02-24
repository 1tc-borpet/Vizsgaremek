<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics (admin only)
     */
    public function stats(Request $request)
    {
        try {
            // Ellenőrzés: admin jogosultság
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Napok száma a statisztikához
            $daysAgo = (int)$request->input('days', 30);

            // Rendelések statisztikái
            $ordersTotal = Order::count();
            $ordersThisMonth = Order::where('created_at', '>=', now()->subDays($daysAgo))->count();
            $ordersCompleted = Order::where('status', 'completed')->count();
            $ordersPending = Order::where('status', 'pending')->count();
            $ordersValue = Order::sum('total_amount');
            $ordersValueThisMonth = Order::where('created_at', '>=', now()->subDays($daysAgo))->sum('total_amount');

            // Foglalások statisztikái
            $reservationsTotal = Reservation::count();
            $reservationsThisMonth = Reservation::where('created_at', '>=', now()->subDays($daysAgo))->count();
            $reservationsConfirmed = Reservation::where('status', 'confirmed')->count();
            $reservationsPending = Reservation::where('status', 'pending')->count();

            // Fizetések statisztikái
            $paymentsTotal = Payment::where('status', 'confirmed')->count();
            $paymentsThisMonth = Payment::where('status', 'confirmed')
                ->where('created_at', '>=', now()->subDays($daysAgo))
                ->count();
            $paymentsAmount = Payment::where('status', 'confirmed')->sum('amount');
            $paymentsAmountThisMonth = Payment::where('status', 'confirmed')
                ->where('created_at', '>=', now()->subDays($daysAgo))
                ->sum('amount');
            $paymentsFailed = Payment::where('status', 'failed')->count();

            // Felhasználók statisztikái
            $usersTotal = User::count();
            $usersThisMonth = User::where('created_at', '>=', now()->subDays($daysAgo))->count();
            $usersActive = User::where('is_active', true)->count();
            $usersInactive = User::where('is_active', false)->count();

            // Átlagok
            $avgOrderValue = $ordersTotal > 0 ? ($ordersValue / $ordersTotal) : 0;
            $avgReservationValue = $reservationsTotal > 0 ? ($reservationsConfirmed / $reservationsTotal) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => [
                        'total' => $ordersTotal,
                        'this_month' => $ordersThisMonth,
                        'completed' => $ordersCompleted,
                        'pending' => $ordersPending,
                        'total_value' => $ordersValue,
                        'total_value_this_month' => $ordersValueThisMonth,
                        'average_value' => round($avgOrderValue, 2),
                    ],
                    'reservations' => [
                        'total' => $reservationsTotal,
                        'this_month' => $reservationsThisMonth,
                        'confirmed' => $reservationsConfirmed,
                        'pending' => $reservationsPending,
                        'confirmation_rate' => round($avgReservationValue, 2),
                    ],
                    'payments' => [
                        'total_successful' => $paymentsTotal,
                        'this_month' => $paymentsThisMonth,
                        'total_amount' => $paymentsAmount,
                        'total_amount_this_month' => $paymentsAmountThisMonth,
                        'failed' => $paymentsFailed,
                    ],
                    'users' => [
                        'total' => $usersTotal,
                        'this_month' => $usersThisMonth,
                        'active' => $usersActive,
                        'inactive' => $usersInactive,
                    ],
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba az irányítópult adatainak lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get recent orders (admin only)
     */
    public function recentOrders(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            $limit = (int)$request->input('limit', 10);
            $status = $request->input('status');

            $query = Order::with('user', 'restaurant')
                ->orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a rendelések lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get recent reservations (admin only)
     */
    public function recentReservations(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            $limit = (int)$request->input('limit', 10);
            $status = $request->input('status');

            $query = Reservation::with('user', 'restaurant', 'table')
                ->orderBy('reservation_time', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $reservations = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $reservations->items(),
                'pagination' => [
                    'total' => $reservations->total(),
                    'per_page' => $reservations->perPage(),
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalások lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get revenue report (admin only)
     */
    public function revenueReport(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            $period = $request->input('period', 'monthly'); // daily, weekly, monthly, yearly
            $months = (int)$request->input('months', 12);

            $startDate = match ($period) {
                'daily' => now()->subDays(30),
                'weekly' => now()->subWeeks(12),
                'yearly' => now()->subYears($months),
                default => now()->subMonths($months),
            };

            // Bevétel adatok
            $revenueData = Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_revenue')
            )
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d")'))
                ->orderBy('date')
                ->get();

            // Fizetési módok szerinti bontás
            $paymentMethods = Payment::select(
                DB::raw('payment_method'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
                ->where('status', 'confirmed')
                ->where('created_at', '>=', $startDate)
                ->groupBy('payment_method')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                    'revenue_data' => $revenueData,
                    'total_revenue' => $revenueData->sum('total_revenue'),
                    'total_orders' => $revenueData->sum('order_count'),
                    'average_order_value' => $revenueData->sum('order_count') > 0 
                        ? round($revenueData->sum('total_revenue') / $revenueData->sum('order_count'), 2)
                        : 0,
                    'payment_methods' => $paymentMethods,
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a bevételi jelentés lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get order status breakdown (admin only)
     */
    public function orderStatusBreakdown(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            $statuses = Order::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            return response()->json([
                'success' => true,
                'data' => [
                    'pending' => $statuses->get('pending')->count ?? 0,
                    'confirmed' => $statuses->get('confirmed')->count ?? 0,
                    'preparing' => $statuses->get('preparing')->count ?? 0,
                    'ready' => $statuses->get('ready')->count ?? 0,
                    'served' => $statuses->get('served')->count ?? 0,
                    'completed' => $statuses->get('completed')->count ?? 0,
                    'cancelled' => $statuses->get('cancelled')->count ?? 0,
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a rendelés státusz bontásának lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get reservation status breakdown (admin only)
     */
    public function reservationStatusBreakdown(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            $statuses = Reservation::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            return response()->json([
                'success' => true,
                'data' => [
                    'pending' => $statuses->get('pending')->count ?? 0,
                    'confirmed' => $statuses->get('confirmed')->count ?? 0,
                    'completed' => $statuses->get('completed')->count ?? 0,
                    'cancelled' => $statuses->get('cancelled')->count ?? 0,
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a foglalás státusz bontásának lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get popular menu items (admin only)
     */
    public function popularMenuItems(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            $limit = (int)$request->input('limit', 10);

            $items = DB::table('order_items')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->select(
                    'menu_items.id',
                    'menu_items.name',
                    'menu_items.price',
                    DB::raw('COUNT(*) as times_ordered'),
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.subtotal) as total_revenue')
                )
                ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.price')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a népszerű ételek lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get top customers (admin only)
     */
    public function topCustomers(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs jogosultsága az admin irányítópulthoz.',
                ], Response::HTTP_FORBIDDEN);
            }

            $limit = (int)$request->input('limit', 10);

            $customers = User::select(
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as total_spent')
            )
                ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
                ->where('users.role', 'user')
                ->groupBy('users.id', 'users.name', 'users.email', 'users.phone')
                ->orderByDesc('total_spent')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $customers,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a legjobb ügyfelek lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
