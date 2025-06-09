<?php

namespace App\Http\Controllers;

use App\Models\ReportValidity;
use App\Models\Report;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportPDFController extends Controller
{
    public function generate(ReportValidity $reportValidity, Request $request)
    {
        // Load relationships
        $reportValidity->load([
            'user',
            'reports.company'
        ]);
        // dd($reportValidity->user);
        // Get companies related to this report validity
        $companies = Company::whereHas('reports', function($query) use ($reportValidity) {
            $query->where('reportValidityID', $reportValidity->reportValidityID);
        })->with('admin')->get();

        // Handle null fullname by using email or a default value
        if (empty($reportValidity->user->fullname)) {
            $reportValidity->user->fullname = $reportValidity->user->email ?? 'Admin User';
        }

        $data = [
            'reportValidity' => $reportValidity,
            'companies' => $companies,
            'logo_path' => public_path('images/HalalLink_v1.png')
        ];

        $pdf = PDF::loadView('pdfs.sme_report', $data);
        
        // Check if download parameter is set
        if ($request->has('download')) {
            return $pdf->download("sme-report-{$reportValidity->reportValidityID}.pdf");
        }
        
        // Default to stream/preview
        return $pdf->stream("sme-report-{$reportValidity->reportValidityID}.pdf");
    }
}