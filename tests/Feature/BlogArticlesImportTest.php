<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ورود مقالات «مقالات در سایت ۱ و ۲» به بخش مقالات سایت.
 */
class BlogArticlesImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_all_articles_with_images_and_is_idempotent(): void
    {
        Storage::fake('public');

        $this->artisan('blog:import-articles')->assertSuccessful();

        $this->assertSame(28, Blog::count());
        $blog = Blog::where('slug', 'why-learn-computer')->sole();
        $this->assertSame('چرا باید کامپیوتر یاد بگیریم؟', $blog->title);
        $this->assertSame('blog/articles/why-learn-computer-1.jpg', $blog->image_path);
        Storage::disk('public')->assertExists($blog->image_path);
        $this->assertStringNotContainsString('{{IMG:', Blog::pluck('des')->implode(''));
        // کاور در بدنه تکرار نمی‌شود
        $this->assertStringNotContainsString('why-learn-computer-1.jpg', $blog->des);
        $this->assertStringContainsString('<h3>فرصت‌های شغلی و حرفه‌ای</h3>', $blog->des);

        $history = Blog::where('slug', 'what-is-a-computer-history')->sole();
        $this->assertStringContainsString('storage/blog/articles/what-is-a-computer-history-2.jpg', $history->des);

        $this->artisan('blog:import-articles')->assertSuccessful();
        $this->assertSame(28, Blog::count());

        $this->get(route('blog.detail', ['id' => $blog->id, 'slug' => $blog->slug]))->assertOk()->assertSee('فرصت‌های شغلی و حرفه‌ای');
    }
}
