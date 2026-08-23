<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Facades\Log;

class CategoryRepository
{
    public function getAllCategories($parentId = null)
    {
        $query = Category::orderBy('sort2', 'asc');
            
        if ($parentId != null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        
        return $query;
    }

    /**
     * @deprecated استفاده از getAllCategories()->get() بهتر است
     * برای backward compatibility نگه داشته شده
     */
    public function getAllCategoriesCollection($parentId = null)
    {
        return $this->getAllCategories($parentId)->get();
    }

    public function getAllCategoriesPaginated($parentId = null, $perPage = 20)
    {
        $query = Category::orderBy('sort2', 'asc')
            ->with(['children' => function ($q) {
                $q->select('id', 'parent_id', 'title', 'slug', 'des', 'has_subcategory', 'image_path');
            }]);
            
        if ($parentId != null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        
        return $query->paginate($perPage);
    }

    public function getCategoriesTree()
    {
        return Category::with(['children'])
            ->whereNull('parent_id')
            ->orderBy('sort2', 'asc')
            ->get();
    }

    public function getLeafCategories()
    {
        return Category::where('has_subcategory', 0)
            ->whereHas('category_fields')
            ->orderBy('sort2', 'asc')
            ->get();
    }

    public function findById(int $id)
    {
        return Category::with(['parent', 'children'])
            ->find($id);
    }

    public function getLeafDescendantsOf(int $categoryId)
    {
        $category = Category::find($categoryId);
        return $category ? $category->leafDescendants() : collect();
    }

    /**
     * دریافت دسته‌بندی‌های اصلی (بدون والد)
     */
    public function getMainCategories()
    {
        return Category::whereNull('parent_id')->get();
    }

    /**
     * دریافت دسته‌بندی‌هایی که کلاب دارند
     */
    public function getCategoriesWithClubs()
    {
        return Category::whereHas('clubs')->get();
    }

    /**
     * بررسی اینکه آیا دسته‌بندی یا فرزندانش کلاب دارند
     */
    public function hasClubsInDescendants(Category $category): bool
    {
        $leafDescendants = $category->leafDescendants();
        
        foreach ($leafDescendants as $child) {
            if ($child->clubs()->count() > 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * دریافت دسته‌بندی‌های اصلی که در فرزندانشان کلاب وجود دارد
     */
    public function getMainCategoriesWithClubDescendants()
    {
        $mainCategories = $this->getMainCategories();
        
        return $mainCategories->filter(function ($category) {
            return $this->hasClubsInDescendants($category);
        })->values();
    }

    /**
     * فیلتر کردن دسته‌بندی‌ها بر اساس نوع کاربر
     * @param string|null $accountType - 'individual', 'organization', 'company', یا null
     */
    public function applyTargetTypeFilter($query, ?string $accountType)
    {
        // LOG: ورودی فیلتر
        Log::info('CategoryRepository::applyTargetTypeFilter', [
            'account_type' => $accountType,
            'is_null' => $accountType === null,
        ]);
        
        if (!$accountType) {
            // اگر کاربر احراز هویت نشده، فقط دسته‌بندی‌های 'both' را نشان بده
            Log::info('CategoryRepository: No account_type, filtering for both only');
            return $query->where('target_type', 'both');
        }

        // تبدیل account_type به target_type
        // individual → user
        // organization, company → organization
        $targetType = in_array($accountType, ['organization', 'company', 'g_organization', 's_g_organization']) ? 'organization' : 'user';

        // LOG: تبدیل
        Log::info('CategoryRepository::applyTargetTypeFilter converted', [
            'account_type' => $accountType,
            'target_type' => $targetType,
        ]);

        // فیلتر: یا both یا target_type مطابق با کاربر
        return $query->where(function ($q) use ($targetType) {
            $q->where('target_type', 'both')
              ->orWhere('target_type', $targetType);
        });
    }

    /**
     * دریافت دسته‌بندی‌ها با فیلتر target_type
     */
    public function getAllCategoriesFiltered($parentId = null, ?string $accountType = null)
    {
        Log::info('CategoryRepository::getAllCategoriesFiltered called', [
            'parent_id' => $parentId,
            'account_type' => $accountType,
        ]);
        
        $query = Category::orderBy('sort2', 'asc');
        
        // اعمال فیلتر target_type
        $query = $this->applyTargetTypeFilter($query, $accountType);
            
        if ($parentId != null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        
        // LOG: SQL query
        Log::info('CategoryRepository::getAllCategoriesFiltered SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        
        return $query;
    }

    /**
     * دریافت tree دسته‌بندی‌ها با فیلتر
     */
    public function getCategoriesTreeFiltered(?string $accountType = null)
    {
        $query = Category::with(['children' => function ($q) use ($accountType) {
            if ($accountType) {
                $targetType = in_array($accountType, ['organization', 'company']) ? 'organization' : 'user';
                $q->where(function ($subQ) use ($targetType) {
                    $subQ->where('target_type', 'both')
                         ->orWhere('target_type', $targetType);
                });
            } else {
                $q->where('target_type', 'both');
            }
        }])->whereNull('parent_id')->orderBy('sort2', 'asc');

        $query = $this->applyTargetTypeFilter($query, $accountType);

        return $query->get();
    }

    /**
     * دریافت leaf categories با فیلتر
     */
    public function getLeafCategoriesFiltered(?string $accountType = null)
    {
        $query = Category::where('has_subcategory', 0)
            ->whereHas('category_fields')
            ->orderBy('sort2', 'asc');

        $query = $this->applyTargetTypeFilter($query, $accountType);

        return $query->get();
    }
}