<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * ورود مقالات آموزشی سایت از بسته‌ی database/data/blog-articles (ساخته‌شده با build-blog-articles.py).
 * تکرارپذیر است: هر مقاله با slug شناسایی و در اجرای دوباره به‌روزرسانی می‌شود.
 */
class ImportBlogArticles extends Command
{
    protected $signature = 'blog:import-articles {--path= : مسیر بسته (پیش‌فرض database/data/blog-articles)} {--dry-run : فقط نمایش}';

    protected $description = 'ورود مقالات آموزش کامپیوتر به بخش «مقالات و آموزش» سایت';

    private const STORAGE_DIR = 'blog/articles';

    public function handle(): int
    {
        $path = rtrim($this->option('path') ?: database_path('data/blog-articles'), '/');
        $manifest = "{$path}/articles.json";

        if (!is_file($manifest)) {
            $this->error("فایل {$manifest} پیدا نشد.");

            return self::FAILURE;
        }

        $articles = json_decode(file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
        $disk = Storage::disk('public');
        $created = $updated = 0;

        foreach ($articles as $article) {
            if ($this->option('dry-run')) {
                $this->line("{$article['slug']} — {$article['title']}");
                continue;
            }

            foreach ($article['images'] as $image) {
                $source = "{$path}/images/{$image}";
                if (!is_file($source)) {
                    $this->error("تصویر {$image} پیدا نشد.");

                    return self::FAILURE;
                }
                $disk->put(self::STORAGE_DIR . '/' . $image, file_get_contents($source));
            }

            $des = $article['des'];

            // تصویر کاور بالای صفحه‌ی مقاله نمایش داده می‌شود؛ اگر بدنه هم با همان تصویر شروع شود حذف می‌شود
            if ($article['cover']) {
                $des = preg_replace('/^<figure class="blog-figure"><img src="\{\{IMG:' . preg_quote($article['cover'], '/') . '\}\}"[^>]*><\/figure>\s*/u', '', $des);
            }

            $des = preg_replace_callback(
                '/\{\{IMG:([^}]+)\}\}/u',
                fn ($m) => asset('storage/' . self::STORAGE_DIR . '/' . $m[1]),
                $des
            );

            $blog = Blog::withTrashed()->firstOrNew(['slug' => $article['slug']]);
            $exists = $blog->exists;

            $blog->fill([
                'title' => $article['title'],
                'seo_title' => $article['seo_title'],
                'short_des' => $article['short_des'],
                'meta_description' => $article['meta_description'],
                'des' => $des,
                'image_path' => $article['cover'] ? self::STORAGE_DIR . '/' . $article['cover'] : null,
            ]);
            $blog->deleted_at = null;
            $blog->save();

            $exists ? $updated++ : $created++;
        }

        if (!$this->option('dry-run')) {
            $this->info("مقالات: {$created} مقاله‌ی جدید، {$updated} به‌روزرسانی.");
        }

        return self::SUCCESS;
    }
}
