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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            'broiler' => User::whereHas('company', function($query) {
                $query->where('company_type', 'broiler');
            })->where('role', 'admin')->count(),
            
            'slaughterhouse' => User::whereHas('company', function($query) {
                $query->where('company_type', 'slaughterhouse');
            })->where('role', 'admin')->count(),
            
            'sme' => User::whereHas('company', function($query) {
                $query->where('company_type', 'sme');
            })->where('role', 'admin')->count(),
            
            'logistic' => User::whereHas('company', function($query) {
                $query->where('company_type', 'logistic');
            })->where('role', 'admin')->count(),
        ];
        
        return response()->json($stats);
    }

    /**
     * Get top performing companies across all metrics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopPerformers()
    {
        $topPerformers = [
            'order_volume' => $this->getTopCompaniesByOrderVolume(),
            'delivery_excellence' => $this->getTopCompaniesByDeliveryPerformance(),
            'quality_assurance' => $this->getTopCompaniesByQuality(),
            'financial_performance' => $this->getTopCompaniesByRevenue(),
            'certification_leaders' => $this->getTopCompaniesByCertifications()
        ];

        return response()->json($topPerformers);
    }

    /**
     * Get comprehensive performance metrics for all companies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerformanceMetrics()
    {
        $companies = Company::with(['users', 'locations'])->get();
        $performanceData = [];

        foreach ($companies as $company) {
            $metrics = $this->calculateCompanyMetrics($company);
            $performanceData[] = [
                'company' => [
                    'id' => $company->companyID,
                    'name' => $company->company_name,
                    'type' => $company->company_type,
                    'image' => $company->company_image
                ],
                'metrics' => $metrics,
                'overall_score' => $this->calculateOverallScore($metrics)
            ];
        }

        // Sort by overall score
        usort($performanceData, function($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });

        return response()->json($performanceData);
    }

    /**
     * Get industry benchmarks
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndustryBenchmarks()
    {
        $benchmarks = [
            'broiler' => $this->calculateIndustryBenchmark('broiler'),
            'slaughterhouse' => $this->calculateIndustryBenchmark('slaughterhouse'),
            'sme' => $this->calculateIndustryBenchmark('sme'),
            'logistic' => $this->calculateIndustryBenchmark('logistic')
        ];

        return response()->json($benchmarks);
    }

    /**
     * Calculate comprehensive metrics for a company
     */
    private function calculateCompanyMetrics($company)
    {
        $companyUsers = $company->users->pluck('userID');
        $companyLocations = $company->locations->pluck('locationID');

        return [
            'order_metrics' => $this->getOrderMetrics($companyUsers, $companyLocations),
            'delivery_metrics' => $this->getDeliveryMetrics($companyUsers),
            'quality_metrics' => $this->getQualityMetrics($companyUsers, $companyLocations),
            'financial_metrics' => $this->getFinancialMetrics($companyUsers, $companyLocations),
            'certification_metrics' => $this->getCertificationMetrics($company->companyID)
        ];
    }

    /**
     * Get order-related metrics
     */
    private function getOrderMetrics($userIds, $locationIds)
    {
        $totalOrders = Order::whereIn('userID', $userIds)
            ->orWhereIn('locationID', $locationIds)
            ->count();

        $completedOrders = Order::whereIn('userID', $userIds)
            ->orWhereIn('locationID', $locationIds)
            ->where('order_status', 'completed')
            ->count();

        $avgFulfillmentTime = Order::whereIn('userID', $userIds)
            ->orWhereIn('locationID', $locationIds)
            ->whereNotNull('order_timestamp')
            ->whereNotNull('deliver_timestamp')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, order_timestamp, deliver_timestamp)) as avg_time')
            ->value('avg_time');

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'success_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0,
            'avg_fulfillment_hours' => round($avgFulfillmentTime ?? 0, 2)
        ];
    }

    /**
     * Get delivery-related metrics
     */
    private function getDeliveryMetrics($userIds)
    {
        $totalDeliveries = Delivery::whereIn('userID', $userIds)->count();
        
        $completedDeliveries = Delivery::whereIn('userID', $userIds)
            ->whereNotNull('end_timestamp')
            ->count();

        $onTimeDeliveries = Delivery::whereIn('userID', $userIds)
            ->whereNotNull('end_timestamp')
            ->whereRaw('DATE(end_timestamp) <= scheduled_date')
            ->count();

        $avgDeliveryTime = Delivery::whereIn('userID', $userIds)
            ->whereNotNull('start_timestamp')
            ->whereNotNull('end_timestamp')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, start_timestamp, end_timestamp)) as avg_time')
            ->value('avg_time');

        return [
            'total_deliveries' => $totalDeliveries,
            'completed_deliveries' => $completedDeliveries,
            'on_time_deliveries' => $onTimeDeliveries,
            'success_rate' => $totalDeliveries > 0 ? round(($completedDeliveries / $totalDeliveries) * 100, 2) : 0,
            'on_time_rate' => $completedDeliveries > 0 ? round(($onTimeDeliveries / $completedDeliveries) * 100, 2) : 0,
            'avg_delivery_hours' => round($avgDeliveryTime ?? 0, 2)
        ];
    }

    /**
     * Get quality-related metrics
     */
    private function getQualityMetrics($userIds, $locationIds)
    {
        $totalTasks = Task::whereIn('userID', $userIds)->count();
        $completedTasks = Task::whereIn('userID', $userIds)
            ->where('task_status', 'completed')
            ->count();

        $totalVerifications = Verify::whereHas('delivery', function($query) use ($userIds) {
            $query->whereIn('userID', $userIds);
        })->count();

        $successfulVerifications = Verify::whereHas('delivery', function($query) use ($userIds) {
            $query->whereIn('userID', $userIds);
        })->where('verify_status', 'verified')->count();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'task_completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'total_verifications' => $totalVerifications,
            'successful_verifications' => $successfulVerifications,
            'verification_success_rate' => $totalVerifications > 0 ? round(($successfulVerifications / $totalVerifications) * 100, 2) : 0
        ];
    }

    /**
     * Get financial metrics
     */
    private function getFinancialMetrics($userIds, $locationIds)
    {
        $orders = Order::whereIn('userID', $userIds)
            ->orWhereIn('locationID', $locationIds)
            ->with('payment')
            ->get();

        $totalRevenue = $orders->sum(function($order) {
            return $order->payment ? $order->payment->payment_amount : 0;
        });

        $successfulPayments = $orders->filter(function($order) {
            return $order->payment && $order->payment->payment_status === 'completed';
        })->count();

        $totalPayments = $orders->filter(function($order) {
            return $order->payment;
        })->count();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'successful_payments' => $successfulPayments,
            'total_payments' => $totalPayments,
            'payment_success_rate' => $totalPayments > 0 ? round(($successfulPayments / $totalPayments) * 100, 2) : 0,
            'avg_transaction_value' => $totalPayments > 0 ? round($totalRevenue / $totalPayments, 2) : 0
        ];
    }

    /**
     * Get certification metrics
     */
    private function getCertificationMetrics($companyId)
    {
        $certifications = Cert::where('companyID', $companyId)->get();
        $certTypes = $certifications->pluck('cert_type')->unique();

        return [
            'total_certifications' => $certifications->count(),
            'certification_types' => $certTypes->count(),
            'cert_details' => $certTypes->toArray()
        ];
    }

    /**
     * Calculate overall performance score
     */
    private function calculateOverallScore($metrics)
    {
        $weights = [
            'order_success_rate' => 0.25,
            'delivery_success_rate' => 0.25,
            'verification_success_rate' => 0.20,
            'payment_success_rate' => 0.20,
            'certification_bonus' => 0.10
        ];

        $score = 0;
        $score += ($metrics['order_metrics']['success_rate'] ?? 0) * $weights['order_success_rate'];
        $score += ($metrics['delivery_metrics']['success_rate'] ?? 0) * $weights['delivery_success_rate'];
        $score += ($metrics['quality_metrics']['verification_success_rate'] ?? 0) * $weights['verification_success_rate'];
        $score += ($metrics['financial_metrics']['payment_success_rate'] ?? 0) * $weights['payment_success_rate'];
        
        // Certification bonus
        $certBonus = min(($metrics['certification_metrics']['total_certifications'] ?? 0) * 10, 100);
        $score += $certBonus * $weights['certification_bonus'];

        return round($score, 2);
    }

    /**
     * Get top companies by order volume
     */
    private function getTopCompaniesByOrderVolume($limit = 5)
    {
        return Company::withCount(['users as total_orders' => function($query) {
            $query->join('orders', 'users.userID', '=', 'orders.userID');
        }])
        ->orderBy('total_orders', 'desc')
        ->limit($limit)
        ->get(['companyID', 'company_name', 'company_type', 'total_orders']);
    }

    /**
     * Get top companies by delivery performance
     */
    private function getTopCompaniesByDeliveryPerformance($limit = 5)
    {
        return Company::select('companies.*')
            ->join('users', 'companies.companyID', '=', 'users.companyID')
            ->join('deliveries', 'users.userID', '=', 'deliveries.userID')
            ->whereNotNull('deliveries.end_timestamp')
            ->groupBy('companies.companyID', 'companies.company_name', 'companies.company_type')
            ->orderByRaw('COUNT(deliveries.deliveryID) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top companies by quality metrics
     */
    private function getTopCompaniesByQuality($limit = 5)
    {
        return Company::select('companies.*')
            ->join('users', 'companies.companyID', '=', 'users.companyID')
            ->join('tasks', 'users.userID', '=', 'tasks.userID')
            ->where('tasks.task_status', 'completed')
            ->groupBy('companies.companyID', 'companies.company_name', 'companies.company_type')
            ->orderByRaw('COUNT(tasks.taskID) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top companies by revenue
     */
    private function getTopCompaniesByRevenue($limit = 5)
    {
        return Company::select('companies.*')
            ->join('users', 'companies.companyID', '=', 'users.companyID')
            ->join('orders', 'users.userID', '=', 'orders.userID')
            ->join('payments', 'orders.paymentID', '=', 'payments.paymentID')
            ->where('payments.payment_status', 'completed')
            ->groupBy('companies.companyID', 'companies.company_name', 'companies.company_type')
            ->orderByRaw('SUM(payments.payment_amount) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top companies by certifications
     */
    private function getTopCompaniesByCertifications($limit = 5)
    {
        return Company::withCount('certs')
            ->orderBy('certs_count', 'desc')
            ->limit($limit)
            ->get(['companyID', 'company_name', 'company_type', 'certs_count']);
    }

    /**
     * Calculate industry benchmark for a company type
     */
    private function calculateIndustryBenchmark($companyType)
    {
        $companies = Company::where('company_type', $companyType)->get();
        $totalMetrics = [
            'avg_order_success_rate' => 0,
            'avg_delivery_success_rate' => 0,
            'avg_verification_success_rate' => 0,
            'avg_payment_success_rate' => 0,
            'avg_certifications' => 0
        ];

        if ($companies->count() === 0) {
            return $totalMetrics;
        }

        foreach ($companies as $company) {
            $metrics = $this->calculateCompanyMetrics($company);
            $totalMetrics['avg_order_success_rate'] += $metrics['order_metrics']['success_rate'] ?? 0;
            $totalMetrics['avg_delivery_success_rate'] += $metrics['delivery_metrics']['success_rate'] ?? 0;
            $totalMetrics['avg_verification_success_rate'] += $metrics['quality_metrics']['verification_success_rate'] ?? 0;
            $totalMetrics['avg_payment_success_rate'] += $metrics['financial_metrics']['payment_success_rate'] ?? 0;
            $totalMetrics['avg_certifications'] += $metrics['certification_metrics']['total_certifications'] ?? 0;
        }

        $count = $companies->count();
        return [
            'avg_order_success_rate' => round($totalMetrics['avg_order_success_rate'] / $count, 2),
            'avg_delivery_success_rate' => round($totalMetrics['avg_delivery_success_rate'] / $count, 2),
            'avg_verification_success_rate' => round($totalMetrics['avg_verification_success_rate'] / $count, 2),
            'avg_payment_success_rate' => round($totalMetrics['avg_payment_success_rate'] / $count, 2),
            'avg_certifications' => round($totalMetrics['avg_certifications'] / $count, 2)
        ];
    }
}