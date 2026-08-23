<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Faq;
use App\Models\Term;
use App\Models\Privacy;
use App\Models\Warranty;
use App\Models\OrganizationTerm;
use App\Models\WarrantyCategory;
use Illuminate\Http\JsonResponse;

class InfoController extends Controller
{
    /**
     * Get list of FAQs
     */
    public function faqs(): JsonResponse
    {
        $faqs = Faq::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $faqs
        ]);
    }
    public function blogs(): JsonResponse
    {
        $faqs = Blog::orderByDesc('id')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $faqs
        ]);
    }

    /**
     * Get list of Terms
     */
    public function terms(): JsonResponse
    {
        $terms = Term::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $terms
        ]);
    }

    /**
     * Get list of Privacy policies
     */
    public function privacy(): JsonResponse
    {
        $privacy = Privacy::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $privacy
        ]);
    }

    /**
     * Get list of Warranties
     */
    public function warranties(): JsonResponse
    {
        $warranties = WarrantyCategory::with('warranties')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $warranties
        ]);
    }

    /**
     * Get list of Organization Terms
     */
    public function organizationTerms(): JsonResponse
    {
        $organizationTerms = OrganizationTerm::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $organizationTerms
        ]);
    }
}