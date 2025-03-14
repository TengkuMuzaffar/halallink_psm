<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
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
                $query->where('company_type', 'SME');
            })->where('role', 'admin')->count(),
            
            'logistic' => User::whereHas('company', function($query) {
                $query->where('company_type', 'logistic');
            })->where('role', 'admin')->count(),
        ];
        
        return response()->json($stats);
    }
}