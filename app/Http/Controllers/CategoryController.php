<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryListRequest;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(CategoryListRequest $request, CategoryService $service)
    {
        $user = $request->user();
        $accountType = $user ? $user->account_type : null;

        $categories = $service->getCategoriesListFiltered($request->parent_id, $accountType);

        // آیتم جدید
        $trashItem = collect([
            'id' => 'trash',
            'title' => 'Trash',
            'image_path' => 'userfolder/TrashV.png'
        ]);

        // اضافه کردن بعد از اولین آیتم (index = 1)
        $categories->splice(1, 0, [$trashItem]);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function tree(Request $request, CategoryService $service)
    {
        // دریافت account_type کاربر لاگین شده
        $user = $request->user();
        $accountType = $user ? $user->account_type : null;

        $tree = $service->getCategoriesTreeFiltered($accountType);
        return response()->json(['success' => true, 'data' => $tree]);
    }

    public function leaves(Request $request, CategoryService $service)
    {
        // دریافت account_type کاربر لاگین شده
        $user = $request->user();
        $accountType = $user ? $user->account_type : null;

        $leaves = $service->getLeafCategoriesFiltered($accountType);
        return response()->json(['success' => true, 'data' => $leaves]);
    }

    public function show(int $id, CategoryService $service)
    {
        $category = $service->getCategoryById($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $category]);
    }
}