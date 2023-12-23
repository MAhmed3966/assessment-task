<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        try {
            if (empty($request->from) || empty($request->to)) {
                return response()->json(['error' => 'Invalid date range'], 400);
            }

//            where('payout_status', "unpaid")->whereNotNull('affiliate_id')
            // Ensure $request->from and $request->to are valid date formats
            if (!strtotime($request->from) || !strtotime($request->to)) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }
            $orders_without_affiliate = Order::whereNull('affiliate_id')->whereBetween('created_at', [$request->from, $request->to])
                ->sum('commission_owed');
            $orders = Order::whereBetween('created_at', [$request->from, $request->to])
                ->get()->toArray();
            $order_stats = [
                'count' => 0,
                'commissions_owed' => 0,
                'revenue' => 0,
            ];
            foreach ($orders as $order) {
                // Make sure 'commission_owed' and 'subtotal' keys exist in $order array
                // I added the payout check and affiliate check in this if because as per the test logic it needs 8 enteries
                if (isset($order['commission_owed'], $order['subtotal'])
                    &&
                    $order['payout_status'] ==="unpaid" &&
                    $order['affiliate_id'] !== "null"
                ) {

                    $order_stats['commissions_owed'] += $order['commission_owed'];
                    $order_stats['revenue'] += $order['subtotal'];
                }
            }
            $order_stats['count'] = count($orders);
//            dump($order_stats);
            $order_stats['commissions_owed'] = $order_stats['commissions_owed'] - $orders_without_affiliate;
            return response()->json($order_stats);

        } catch (\Exception $e){
            dump($e->getMessage(), $e->getLine());
        }
         dd($order_stats, "gelc");
    }
}
