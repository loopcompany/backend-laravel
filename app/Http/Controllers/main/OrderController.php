<?php

namespace App\Http\Controllers\main;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function order_form(Request $request, $category_id = null)
    {
        $cityName = 'tehran';
        $categoryId = $category_id ?? $request->get('category_id');
        $category = Category::findOrFail($categoryId);
        
        // Debug category time settings
        Log::info('Category Time Settings:', [
            'category_id' => $category->id,
            'title' => $category->title,
            'start_at' => $category->start_at,
            'end_at' => $category->end_at,
            'duration' => $category->duration,
            'start_at_type' => gettype($category->start_at),
            'end_at_type' => gettype($category->end_at),
            'duration_type' => gettype($category->duration),
        ]);
        
        // if ($category->category_fields->count() < 1) {
        //     return redirect()->back()->with('error', 'ثبت سفارش در این دسته بندی مقدور نمی‌باشد.');
        // }

        return view('main.form', compact('category', 'cityName'));
    }
}
