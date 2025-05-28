<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cert;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CertController extends Controller
{
    /**
     * Get certifications for the authenticated user's company
     */
    public function getCertifications(Request $request)
    {
        $user = $request->user();
        
        // Check if user has a company
        if (!$user->companyID) {
            return response()->json(['message' => 'User does not have a company'], 400);
        }
        
        // Get certifications
        $certifications = Cert::where('companyID', $user->companyID)->get();
        
        // Format the response
        $formattedCertifications = $certifications->map(function ($cert) {
            return [
                'certID' => $cert->certID,
                'cert_type' => $cert->cert_type,
                'cert_file' => $cert->cert_file ? asset('storage/' . $cert->cert_file) : null,
                'created_at' => $cert->created_at,
                'updated_at' => $cert->updated_at
            ];
        });
        
        return response()->json(['certifications' => $formattedCertifications]);
    }
    
    /**
     * Update certifications for the authenticated user's company
     */
    public function updateCertifications(Request $request)
    {
        $user = $request->user();
        
        // Check if user is admin
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check if user has a company
        if (!$user->companyID) {
            return response()->json(['message' => 'User does not have a company'], 400);
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'certifications' => 'required|array',
            'certifications.*.cert_type' => 'required|string|max:255',
            'certifications.*.cert_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Make nullable
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Get company
        $company = Company::find($user->companyID);
        
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }
        
        // Process certifications
        $updatedCertifications = [];
        
        foreach ($request->certifications as $index => $certData) {
            // If certID is provided, update existing certification
            if (isset($certData['certID'])) {
                $cert = Cert::find($certData['certID']);
                
                // Check if certification belongs to the company
                if (!$cert || $cert->companyID !== $user->companyID) {
                    continue;
                }
                
                $cert->cert_type = $certData['cert_type'];
                
                // Handle file upload if provided
                if ($request->hasFile('certifications.' . $index . '.cert_file')) {
                    // Delete old file if exists
                    if ($cert->cert_file) {
                        Storage::disk('public')->delete($cert->cert_file);
                    }
                    
                    // Store new file
                    $file = $request->file('certifications.' . $index . '.cert_file');
                    $filename = 'cert_' . time() . '_' . $cert->certID . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('certifications', $filename, 'public');
                    $cert->cert_file = $path;
                }
                
                $cert->save();
                $updatedCertifications[] = $cert;
            } else {
                // Create new certification
                $newCert = new Cert();
                $newCert->companyID = $user->companyID;
                $newCert->cert_type = $certData['cert_type'];
                
                // Handle file upload
                if ($request->hasFile('certifications.' . $index . '.cert_file')) {
                    $file = $request->file('certifications.' . $index . '.cert_file');
                    $filename = 'cert_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('certifications', $filename, 'public');
                    $newCert->cert_file = $path;
                }
                
                $newCert->save();
                $updatedCertifications[] = $newCert;
            }
        }
        
        // Format the response
        $formattedCertifications = collect($updatedCertifications)->map(function ($cert) {
            return [
                'certID' => $cert->certID,
                'cert_type' => $cert->cert_type,
                'cert_file' => $cert->cert_file ? asset('storage/' . $cert->cert_file) : null,
                'created_at' => $cert->created_at,
                'updated_at' => $cert->updated_at
            ];
        });
        
        return response()->json([
            'message' => 'Certifications updated successfully',
            'certifications' => $formattedCertifications
        ]);
    }
    
    /**
     * Delete a certification
     */
    public function deleteCertification(Request $request, $certID)
    {
        $user = $request->user();
        
        // Check if user is admin
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Get certification
        $cert = Cert::find($certID);
        
        // Check if certification exists and belongs to the user's company
        if (!$cert || $cert->companyID !== $user->companyID) {
            return response()->json(['message' => 'Certification not found'], 404);
        }
        
        // Delete file if exists
        if ($cert->cert_file) {
            try {
                $fileDeleted = Storage::disk('public')->delete($cert->cert_file);
                if (!$fileDeleted) {
                    // Log the failure but continue with record deletion
                    \Log::warning("Failed to delete certification file: {$cert->cert_file}");
                }
            } catch (\Exception $e) {
                // Log the exception but continue with record deletion
                \Log::error("Exception when deleting certification file: {$e->getMessage()}");
            }
        }
        
        // Delete certification
        $cert->delete();
        
        return response()->json(['message' => 'Certification deleted successfully']);
    }
}