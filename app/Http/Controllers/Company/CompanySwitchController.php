<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Traits\CompanyAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    use CompanyAuth;

    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $companyId = $request->integer('company_id');

        // Verify user has access to this company
        $allowedIds = $this->managedCompanyIds();

        if (!in_array($companyId, $allowedIds)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        session(['active_company_id' => $companyId]);

        return response()->json(['success' => true]);
    }
}
