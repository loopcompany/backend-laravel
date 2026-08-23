<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function __construct(private CategoryRepository $repo)
    {
    }

    public function getCategoriesList($parentId = null)
    {
        $categories = $this->repo->getAllCategories($parentId)->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'parent_id' => $category->parent_id,
                'target_type' => $category->target_type,
                'image_path' => $category->image_path,
                'has_subcategory' => $category->has_subcategory,
                'sort' => $category->sort,
                'sort2' => $category->sort2,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'start_at' => $category->start_at,
                'end_at' => $category->end_at,
                'duration' => $category->duration,
            ];
        });
    }

    public function getCategoriesTree()
    {
        $categories = $this->repo->getCategoriesTree();

        return $categories->map(function ($category) {
            return $this->transformCategoryWithChildren($category);
        });
    }

    public function getLeafCategories()
    {
        $categories = $this->repo->getLeafCategories();

        return $categories->map(function ($category) {
            return $this->transformCategory($category);
        });
    }

    public function getCategoryById(int $id)
    {
        $category = $this->repo->findById($id);

        if (!$category) {
            return null;
        }

        return $this->transformCategoryWithDetails($category);
    }

    private function transformCategory($category)
    {
        return [
            'id' => $category->id,
            'title' => $category->title,
            'parent_id' => $category->parent_id,
            'target_type' => $category->target_type,
            'image_path' => $category->image_path,
            'has_subcategory' => $category->has_subcategory,
            'sort' => $category->sort,
            'sort2' => $category->sort2,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];
    }

    private function transformCategoryWithChildren($category)
    {
        $data = $this->transformCategory($category);

        if ($category->children->isNotEmpty()) {
            $data['children'] = $category->children->map(function ($child) {
                return $this->transformCategoryWithChildren($child);
            });
        }

        return $data;
    }

    private function transformCategoryWithDetails($category)
    {
        $data = $this->transformCategory($category);

        if ($category->parent) {
            $data['parent'] = [
                'id' => $category->parent->id,
                'title' => $category->parent->title,
            ];
        }

        if ($category->children->isNotEmpty()) {
            $data['children'] = $category->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'title' => $child->title,
                    'has_subcategory' => $child->has_subcategory,
                    'image_path' => $child->image_path,
                    'start_at' => $child->start_at,
                    'end_at' => $child->end_at,
                    'duration' => $child->duration,
                ];
            });
        }

        return $data;
    }

    /**
     * دریافت دسته‌بندی‌های اصلی (بدون والد)
     */
    public function getRootCategories(int $perPage = 20): array
    {
        try {
            $categories = $this->repo->getAllCategoriesPaginated(null, $perPage);

            return [
                'success' => true,
                'message' => 'دسته‌بندی‌های اصلی با موفقیت دریافت شدند',
                'categories' => $categories
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در دریافت دسته‌بندی‌های اصلی',
                'error' => $e->getMessage(),
                'categories' => collect()
            ];
        }
    }

    /**
     * دریافت لیست دسته‌بندی‌ها با فیلتر target_type
     */
    public function getCategoriesListFiltered($parentId = null, ?string $accountType = null)
    {
        // LOG: ورودی سرویس
        Log::info('CategoryService::getCategoriesListFiltered called', [
            'parent_id' => $parentId,
            'account_type' => $accountType,
        ]);
        
        $categories = $this->repo->getAllCategoriesFiltered($parentId, $accountType)->get();

        // LOG: نتیجه از Repository
        Log::info('CategoryService::getCategoriesListFiltered repo result', [
            'count' => $categories->count(),
            'first_category' => $categories->first()?->title ?? 'none',
        ]);

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'parent_id' => $category->parent_id,
                'target_type' => $category->target_type,
                'image_path' => $category->image_path,
                'has_subcategory' => $category->has_subcategory,
                'sort' => $category->sort,
                'sort2' => $category->sort2,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'start_at' => $category->start_at,
                'end_at' => $category->end_at,
                'duration' => $category->duration,
                'is_fixed' => $category->is_fixed,
            ];
        });
    }

    /**
     * دریافت tree دسته‌بندی‌ها با فیلتر
     */
    public function getCategoriesTreeFiltered(?string $accountType = null)
    {
        $categories = $this->repo->getCategoriesTreeFiltered($accountType);

        return $categories->map(function ($category) {
            return $this->transformCategoryWithChildren($category);
        });
    }

    /**
     * دریافت leaf categories با فیلتر
     */
    public function getLeafCategoriesFiltered(?string $accountType = null)
    {
        $categories = $this->repo->getLeafCategoriesFiltered($accountType);

        return $categories->map(function ($category) {
            return $this->transformCategory($category);
        });
    }

    /**
     * دریافت زیردسته‌های یک دسته‌بندی خاص
     */
    public function getSubcategories(int $parentId, int $perPage = 20): array
    {
        try {
            // ابتدا بررسی می‌کنیم که دسته‌بندی والد وجود دارد
            $parent = $this->repo->findById($parentId);
            if (!$parent) {
                return [
                    'success' => false,
                    'message' => 'دسته‌بندی مورد نظر یافت نشد',
                    'categories' => collect(),
                    'parent' => null
                ];
            }

            $categories = $this->repo->getAllCategoriesPaginated($parentId, $perPage);

            return [
                'success' => true,
                'message' => 'زیردسته‌ها با موفقیت دریافت شدند',
                'categories' => $categories,
                'parent' => $parent
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در دریافت زیردسته‌ها',
                'error' => $e->getMessage(),
                'categories' => collect(),
                'parent' => null
            ];
        }
    }
}