<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Delivery;
use App\Models\Task;
use App\Models\Verify;
use App\Models\Cert;
use App\Models\Item;
use App\Models\Cart;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    /**
     * Get basic dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'broiler' => Company::where('company_type', 'broiler')->count(),
            
            'slaughterhouse' => Company::where('company_type', 'slaughterhouse')->count(),
            
            'sme' => Company::where('company_type', 'sme')->count(),
            
            'logistic' => Company::where('company_type', 'logistic')->count(),
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Get broiler sales data for charts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBroilerSalesData(Request $request)
    {
        $period = $request->input('period', 'month'); // Default to month
        // Define date ranges based on period
        switch ($period) {
            case 'quarter':
                $startDate = Carbon::now()->subMonths(3);
                break;
            case 'year':
                $startDate = Carbon::now()->subYear();
                break;
            case 'month':
            default:
                $startDate = Carbon::now()->subMonth();
                break;
        }
        // Get broiler companies
        $broilerCompanies = Company::where('company_type', 'broiler')->get();
        
        $salesData = [];
        
        foreach ($broilerCompanies as $company) {
            // Get all locations for this company
            $locationIds = Location::where('companyID', $company->companyID)->pluck('locationID');
            
            // Get all items from these locations
            $itemIds = Item::whereIn('locationID', $locationIds)->pluck('itemID');
            
            // Count cart items for these items within the date range
            $salesCount = Cart::whereIn('itemID', $itemIds)
                ->whereHas('order', function($query) use ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                })
                ->count();
            
            if ($salesCount > 0) {
                $salesData[] = [
                    'company' => $company->company_name,
                    'sales' => $salesCount
                ];
            }
        }
        // Sort by sales count in descending order
        usort($salesData, function($a, $b) {
            return $b['sales'] - $a['sales'];
        });
        
        // Take top 5 companies
        $salesData = array_slice($salesData, 0, 5);
        
        return response()->json([
            'period' => $period,
            'data' => $salesData
        ]);
    }
    
    /**
     * Get marketplace activity data (order counts) for line chart
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMarketplaceActivity(Request $request)
    {
        $period = $request->input('period', 'month'); // Default to month
        
        // Define date ranges and grouping format based on period
        switch ($period) {
            case 'quarter':
                $startDate = Carbon::now()->subMonths(12); // Show last 4 quarters
                break;
            case 'year':
                $startDate = Carbon::now()->subYears(5); // Show last 5 years
                break;
            case 'month':
            default:
                $startDate = Carbon::now()->subMonths(12); // Show last 12 months
                $labelFormat = 'M Y'; // e.g., Jan 2023
                break;
        }
        
        try {
            // Get order counts grouped by date
            $query = Order::where('order_timestamp', '>=', $startDate)
                ->where('order_status', 'paid');
                
            if ($period === 'quarter') {
                // For quarters, we need to group by year and quarter
                $orderCounts = $query->select(
                    DB::raw("YEAR(order_timestamp) as year"),
                    DB::raw("QUARTER(order_timestamp) as quarter"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('year', 'quarter')
                ->orderBy('year')
                ->orderBy('quarter')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => sprintf("Q%d %d", $item->quarter, $item->year),
                        'count' => $item->count
                    ];
                });
            } else if ($period === 'year') {
                // For years, we group by year only
                $orderCounts = $query->select(
                    DB::raw("YEAR(order_timestamp) as year"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('year')
                ->orderBy('year')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => (string)$item->year,
                        'count' => $item->count
                    ];
                });
            } else {
                // For months, we group by year and month directly
                $orderCounts = $query->select(
                    DB::raw("YEAR(order_timestamp) as year"),
                    DB::raw("MONTH(order_timestamp) as month"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    // Create a Carbon instance from the year and month
                    $date = Carbon::createFromDate($item->year, $item->month, 1);
                    return [
                        'date' => $date->format('M Y'),
                        'count' => $item->count
                    ];
                });
            }
            
            return response()->json([
                'period' => $period,
                'data' => $orderCounts
            ]);
        } catch (\Exception $e) {
            // Handle any database or processing errors
            return response()->json([
                'period' => $period,
                'error' => 'Error processing data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get company registration trend data for line chart
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompanyRegistrationTrend(Request $request)
    {
        $period = $request->input('period', 'month'); // Default to month
        
        // Define date ranges and grouping format based on period
        switch ($period) {
            case 'quarter':
                $startDate = Carbon::now()->subMonths(12); // Show last 4 quarters
                break;
            case 'year':
                $startDate = Carbon::now()->subYears(5); // Show last 5 years
                break;
            case 'month':
            default:
                $startDate = Carbon::now()->subMonths(12); // Show last 12 months
                break;
        }
        
        try {
            // Get company registration counts grouped by date
            $query = Company::where('created_at', '>=', $startDate);
                
            if ($period === 'quarter') {
                // For quarters, we need to group by year and quarter
                $registrationCounts = $query->select(
                    DB::raw("YEAR(created_at) as year"),
                    DB::raw("QUARTER(created_at) as quarter"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('year', 'quarter')
                ->orderBy('year')
                ->orderBy('quarter')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => sprintf("Q%d %d", $item->quarter, $item->year),
                        'count' => $item->count
                    ];
                });
            } else if ($period === 'year') {
                // For years, we group by year only
                $registrationCounts = $query->select(
                    DB::raw("YEAR(created_at) as year"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('year')
                ->orderBy('year')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => (string)$item->year,
                        'count' => $item->count
                    ];
                });
            } else {
                // For months, we group by year and month directly
                $registrationCounts = $query->select(
                    DB::raw("YEAR(created_at) as year"),
                    DB::raw("MONTH(created_at) as month"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    // Create a Carbon instance from the year and month
                    $date = Carbon::createFromDate($item->year, $item->month, 1);
                    return [
                        'date' => $date->format('M Y'),
                        'count' => $item->count
                    ];
                });
            }
            
            return response()->json([
                'period' => $period,
                'data' => $registrationCounts
            ]);
        } catch (\Exception $e) {
            // Handle any database or processing errors
            return response()->json([
                'period' => $period,
                'error' => 'Error processing data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}