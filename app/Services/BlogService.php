<?php

namespace App\Services;

use App\Models\Blog;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class BlogService
{
    /**
     * دریافت لیست مقالات با pagination
     */
    public function getPaginatedBlogs(int $perPage = 12, int $page = 1): array
    {
        try {
            $blogs = Blog::with('category')
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            Log::info('Blog list retrieved successfully', [
                'total' => $blogs->total(),
                'per_page' => $perPage,
                'current_page' => $page
            ]);

            return [
                'success' => true,
                'message' => 'لیست مقالات با موفقیت دریافت شد',
                'blogs' => $blogs
            ];

        } catch (Exception $e) {
            Log::error('Failed to retrieve blog list', [
                'error' => $e->getMessage(),
                'per_page' => $perPage,
                'page' => $page,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست مقالات',
                'error' => $e->getMessage(),
                'blogs' => new LengthAwarePaginator([], 0, $perPage, $page)
            ];
        }
    }

    /**
     * دریافت جزئیات مقاله بر اساس ID
     */
    public function getBlogById(int $id): array
    {
        try {
            $blog = Blog::with('category')
                ->find($id);

            if (!$blog) {
                return [
                    'success' => false,
                    'message' => 'مقاله مورد نظر یافت نشد',
                    'blog' => null
                ];
            }

            Log::info('Blog retrieved successfully', [
                'blog_id' => $id,
                'blog_title' => $blog->title
            ]);

            return [
                'success' => true,
                'message' => 'جزئیات مقاله با موفقیت دریافت شد',
                'blog' => $blog
            ];

        } catch (Exception $e) {
            Log::error('Failed to retrieve blog details', [
                'blog_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات مقاله',
                'error' => $e->getMessage(),
                'blog' => null
            ];
        }
    }

    /**
     * دریافت مقالات اخیر (برای صفحه جزئیات)
     */
    public function getRecentBlogs(int $limit = 5, int $excludeId = null): array
    {
        try {
            $query = Blog::with('category')
                ->latest()
                ->limit($limit);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $recentBlogs = $query->get();

            Log::info('Recent blogs retrieved successfully', [
                'count' => $recentBlogs->count(),
                'limit' => $limit,
                'exclude_id' => $excludeId
            ]);

            return [
                'success' => true,
                'message' => 'مقالات اخیر با موفقیت دریافت شدند',
                'recent_blogs' => $recentBlogs
            ];

        } catch (Exception $e) {
            Log::error('Failed to retrieve recent blogs', [
                'limit' => $limit,
                'exclude_id' => $excludeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت مقالات اخیر',
                'error' => $e->getMessage(),
                'recent_blogs' => collect()
            ];
        }
    }

    /**
     * جستجو در مقالات
     */
    public function searchBlogs(string $query, int $perPage = 12, int $page = 1): array
    {
        try {
            $blogs = Blog::with('category')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('short_des', 'LIKE', "%{$query}%")
                      ->orWhere('des', 'LIKE', "%{$query}%")
                      ->orWhere('meta', 'LIKE', "%{$query}%");
                })
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            Log::info('Blog search completed', [
                'query' => $query,
                'results_count' => $blogs->total(),
                'per_page' => $perPage,
                'current_page' => $page
            ]);

            return [
                'success' => true,
                'message' => 'جستجو با موفقیت انجام شد',
                'blogs' => $blogs,
                'search_query' => $query
            ];

        } catch (Exception $e) {
            Log::error('Failed to search blogs', [
                'query' => $query,
                'per_page' => $perPage,
                'page' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در جستجوی مقالات',
                'error' => $e->getMessage(),
                'blogs' => new LengthAwarePaginator([], 0, $perPage, $page),
                'search_query' => $query
            ];
        }
    }

    /**
     * دریافت مقالات بر اساس دسته‌بندی
     */
    public function getBlogsByCategory(int $categoryId, int $perPage = 12, int $page = 1): array
    {
        try {
            $blogs = Blog::with('category')
                ->where('category_id', $categoryId)
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            Log::info('Category blogs retrieved successfully', [
                'category_id' => $categoryId,
                'total' => $blogs->total(),
                'per_page' => $perPage,
                'current_page' => $page
            ]);

            return [
                'success' => true,
                'message' => 'مقالات دسته‌بندی با موفقیت دریافت شدند',
                'blogs' => $blogs
            ];

        } catch (Exception $e) {
            Log::error('Failed to retrieve category blogs', [
                'category_id' => $categoryId,
                'per_page' => $perPage,
                'page' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت مقالات دسته‌بندی',
                'error' => $e->getMessage(),
                'blogs' => new LengthAwarePaginator([], 0, $perPage, $page)
            ];
        }
    }
}