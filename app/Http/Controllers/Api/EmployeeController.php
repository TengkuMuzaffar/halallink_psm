<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Get all employees
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllEmployees()
    {
        $employees = User::with('company')->get();
        return response()->json($employees);
    }
    
    /**
     * Get broiler employees
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBroilerEmployees()
    {
        $employees = User::whereHas('company', function($query) {
            $query->where('company_type', 'broiler');
        })->with('company')->get();
        
        return response()->json($employees);
    }
    
    /**
     * Get slaughterhouse employees
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSlaughterhouseEmployees()
    {
        $employees = User::whereHas('company', function($query) {
            $query->where('company_type', 'slaughterhouse');
        })->with('company')->get();
        
        return response()->json($employees);
    }
    
    /**
     * Get SME employees
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSMEEmployees()
    {
        $employees = User::whereHas('company', function($query) {
            $query->where('company_type', 'SME');
        })->with('company')->get();
        
        return response()->json($employees);
    }
    
    /**
     * Get logistic employees
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLogisticEmployees()
    {
        $employees = User::whereHas('company', function($query) {
            $query->where('company_type', 'logistic');
        })->with('company')->get();
        
        return response()->json($employees);
    }
}