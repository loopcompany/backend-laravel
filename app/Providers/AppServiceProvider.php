<?php

namespace App\Providers;

use App\Models\DigitalBusinessCard;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Observers\TechnicianObserver;
use App\Observers\OrganizationObserver;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\Contact;
use App\Models\Social;
use App\Models\About;
use App\Models\Blog;
use App\Policies\DigitalBusinessCardPolicy;
use App\Repositories\Eloquent\EloquentLoopLearnRegistrationRepository;
use App\Repositories\Interfaces\LoopLearnRegisterationRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Listeners\LogAdminLogin;
use App\Listeners\LogAdminLogout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\FirebaseNotificationService::class);

        $this->app->bind(
            LoopLearnRegisterationRepositoryInterface::class,
            EloquentLoopLearnRegistrationRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(DigitalBusinessCard::class, DigitalBusinessCardPolicy::class);

        // ثبت Observer برای مدل Order
        Order::observe(OrderObserver::class);
        \App\Models\Technician::observe(TechnicianObserver::class);
        \App\Models\Organization::observe(OrganizationObserver::class);

        // ثبت Observer برای مدل User (تولید خودکار کد کاربر)
        User::observe(UserObserver::class);

        // اشتراک‌گذاری اطلاعات مشترک در تمام view ها
        View::composer('*', function ($view) {
            try {
                $globalContacts = Contact::all();
                $globalSocials = Social::all();
                $globalAbout = About::first();
                $globalRecentBlogs = Blog::latest()->take(2)->get();

                $view->with([
                    'globalContacts' => $globalContacts,
                    'globalSocials' => $globalSocials,
                    'globalAbout' => $globalAbout,
                    'globalRecentBlogs' => $globalRecentBlogs,
                ]);
            } catch (\Exception $e) {
                // در صورت خطا، آرایه‌های خالی ارسال می‌شوند
                $view->with([
                    'globalContacts' => collect(),
                    'globalSocials' => collect(),
                    'globalAbout' => null,
                    'globalRecentBlogs' => collect(),
                ]);
            }
        });


    }
}
