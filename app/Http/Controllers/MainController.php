<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\Category;
use App\Models\Contact;
use App\Models\MinPrice;
use App\Models\Order;
use App\Models\PdfDocument;
use App\Models\Social;
use App\Models\Term;
use App\Models\Privacy;
use App\Models\Faq;
use App\Http\Requests\ContactFormRequest;
use App\Models\Blog;
use App\Services\ContactService;
use App\Services\BlogService;
use App\Services\CategoryService;
use App\Services\PollApplicationService;
use App\Services\DeleteAccountRequestService;
use App\Http\Requests\DeleteAccountFormRequest;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected BlogService $blogService,
        protected CategoryService $categoryService,
        protected PollApplicationService $pollApplicationService,
        protected DeleteAccountRequestService $deleteAccountRequestService
    ) {
    }

    public function index()
    {
        // دریافت دسته‌بندی‌های نهایی برای نمایش در بخش خدمات
        $leafCategories = $this->categoryService->getLeafCategories();

        // دریافت جدیدترین مقالات برای نمایش در صفحه اصلی
        $recentBlogsResult = $this->blogService->getRecentBlogs(3); // 3 مقاله اخیر
        $recentBlogs = $recentBlogsResult['success'] ? $recentBlogsResult['recent_blogs'] : collect();

        // دریافت نظرات مشتریان از poll_applications
        $testimonialsResult = $this->pollApplicationService->getRecentTestimonials(3);
        $testimonials = $testimonialsResult['success'] ? $testimonialsResult['testimonials'] : [];

        // دریافت اطلاعات تماس برای نمایش در صفحه اصلی (فقط انواع مورد نیاز)
        $contacts = Contact::whereIn('type', ['office', 'email', 'phone'])
            ->orderByRaw("CASE type WHEN 'office' THEN 1 WHEN 'email' THEN 2 WHEN 'phone' THEN 3 ELSE 4 END")
            ->get();

        return view('main.index', compact('leafCategories', 'recentBlogs', 'testimonials', 'contacts'));
    }

    /**
     * نمایش صفحه قوانین و شرایط
     */
    public function redirect(Request $request)
    {
        $status = $request->status;
        $linkingUri = $request->linkingUri;
        return view('api.redirect', compact('linkingUri', 'status'));
    }
    public function terms()
    {
        // استفاده از همان data اپ
        $terms = Term::all();

        return view('main.terms', compact('terms'));
    }

    /**
     * نمایش صفحه حریم خصوصی
     */
    public function privacy()
    {
        // استفاده از همان data اپ
        $privacies = Privacy::all();
        return view('main.privacy', compact('privacies'));
    }

    /**
     * نمایش صفحه درباره ما
     */
    public function about()
    {
        // استفاده از مدل About
        $abouts = About::all();
        $faqs = Faq::orderBy('created_at', 'desc')->take(5)->get();

        return view('main.about', compact('abouts', 'faqs'));
    }

    /**
     * نمایش صفحه تماس با ما
     */
    public function contact()
    {
        // استفاده از مدل Contact واقعی
        $contacts = Contact::all();

        // موقتاً socials استاتیک تا مدل آن هم اضافه شود
        $socials = Social::all();

        return view('main.contact', compact('contacts', 'socials'));
    }

    /**
     * ارسال فرم تماس
     */
    public function submitContact(ContactFormRequest $request)
    {
        // استفاده از Service برای business logic
        $result = $this->contactService->submitContactMessage($request->validated());

        // اگر request از AJAX باشد، JSON response برگردان
        if ($request->ajax()) {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }

        // اگر request معمولی باشد، redirect کن
        if ($result['success']) {
            return redirect()
                ->route('web.contact')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('web.contact')
            ->with('error', $result['message'])
            ->withInput();
    }
    /**
     * نمایش صفحه سوالات متداول
     */
    public function faqs(Request $request)
    {
        $query = Faq::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $faqs = $query->orderBy('created_at', 'desc')->get();

        return view('main.faqs', compact('faqs'));
    }

    /**
     * نمایش لیست مقالات
     */
    public function blogs(Request $request)
    {
        // استفاده از BlogService برای دریافت مقالات
        $perPage = 12;
        $currentPage = $request->get('page', 1);

        $result = $this->blogService->getPaginatedBlogs($perPage, $currentPage);

        if (!$result['success']) {
            // در صورت خطا، پیام خطا نمایش داده می‌شود
            return redirect()->back()->with('error', $result['message']);
        }

        $blogs = $result['blogs'];

        return view('main.blogs', compact('blogs'));
    }

    /**
     * نمایش جزئیات مقاله
     */
    public function blogDetail($id, $slug = null)
    {
        // استفاده از BlogService برای دریافت جزئیات مقاله
        $blogResult = $this->blogService->getBlogById($id);

        if (!$blogResult['success']) {
            return redirect()->route('web.blogs')->with('error', $blogResult['message']);
        }

        $blog = $blogResult['blog'];

        // دریافت مقالات اخیر (به جز مقاله فعلی)
        $recentBlogsResult = $this->blogService->getRecentBlogs(5, $id);
        $recent_blogs = $recentBlogsResult['success'] ? $recentBlogsResult['recent_blogs'] : collect();

        return view('main.blog', compact('blog', 'recent_blogs'));
    }

    /**
     * نمایش جزئیات دسته‌بندی
     */
    // public function categoryShow($id)
    // {
    //     $category = Category::findOrFail($id);
    //     $subcategories = Category::where('parent_id', $id)->get();

    //     return view('main.category', compact('category', 'subcategories'));
    // }


    /**
     * نمایش دسته‌بندی‌ها
     * اگر ID داده نشود، همه دسته‌بندی‌های اصلی نمایش داده می‌شود
     * اگر ID داده شود، زیردسته‌های آن دسته‌بندی نمایش داده می‌شود
     */
    public function categoryShow(Request $request, $id = null)
    {
        // دریافت مقالات اخیر برای کاروسل (مسئولیت جداگانه)
        $recentBlogsResult = $this->blogService->getRecentBlogs(4);
        $carouselBlogs = $recentBlogsResult['success'] ? $recentBlogsResult['recent_blogs'] : collect();

        // اگر ID موجود نباشد، دسته‌بندی‌های اصلی را نمایش دهید
        if (is_null($id)) {
            $result = $this->categoryService->getRootCategories(20);

            if (!$result['success']) {
                return redirect()->back()->with('error', $result['message']);
            }

            return view('main.categories', [
                'sub_categories' => $result['categories'],
                'check' => null,
                'carouselBlogs' => $carouselBlogs,
                'pageTitle' => 'همه دسته‌بندی‌ها'
            ]);
        }

        // اگر ID موجود باشد، زیردسته‌های آن دسته‌بندی را نمایش دهید
        $result = $this->categoryService->getSubcategories($id, 20);

        if (!$result['success']) {
            return redirect()->route('web.category.show')->with('error', $result['message']);
        }

        return view('main.categories', [
            'sub_categories' => $result['categories'],
            'check' => $result['parent'],
            'carouselBlogs' => $carouselBlogs,
            'pageTitle' => $result['parent'] ? 'زیردسته‌های ' . $result['parent']->title : 'دسته‌بندی‌ها'
        ]);
    }

    /**
     * Display the delete account request form
     */
    public function deleteAccountRequest()
    {
        return view('main.delete-account-request');
    }

    /**
     * Handle the delete account request form submission
     */
    public function submitDeleteAccountRequest(DeleteAccountFormRequest $request)
    {
        // Use Service for business logic
        $result = $this->deleteAccountRequestService->submitDeleteAccountRequest($request->validated());

        // If request is AJAX, return JSON response
        if ($request->ajax()) {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }

        // If regular request, redirect
        if ($result['success']) {
            return redirect()
                ->route('web.delete-account-request')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('web.delete-account-request')
            ->with('error', $result['message'])
            ->withInput();
    }

    public function pdf_documents()
    {
        $docs = PdfDocument::orderByDesc('id')->first();
        return response()->json(['data' => $docs]);
    }
    public function min_price()
    {
        $docs = MinPrice::orderByDesc('id')->first();
        return response()->json(['data' => $docs]);
    }

    public function reciept($id)
    {
        $order = Order::with([
            'category.brandCategoryField.field',
            'category.modelCategoryField.field',
            'details.fieldDetail',
            'details.field',
            'user',
            'extra_services',
            'delivery_reports:name,created_at',
            'discountUse.discount_code',
            'user_transaction'
        ])->findOrFail($id);

        $brandField = $order->category?->brandCategoryField?->field;
        $modelField = $order->category?->modelCategoryField?->field;

        $brandDetail = $brandField
            ? $order->details->firstWhere('field_id', $brandField->id)
            : null;

        $modelDetail = $modelField
            ? $order->details->firstWhere('field_id', $modelField->id)
            : null;

        $brandSelectedOption = $brandDetail?->fieldDetail;
        $modelSelectedOption = $modelDetail?->fieldDetail;

        $order_status_label = '';
        $page_title = 'جزئیات سفارش';
        $status_image = 'glass.jpg';
        $user_type = 'کاربر عادی';

        if (($order->status == 0 || $order->status == 1) && $order->payment_status == 0) {
            $order_status_label = 'سفارش شما در انتظار تأیید و پرداخت است';
        } elseif (($order->status == 0 || $order->status == 1) && $order->payment_status == 0) {
            $order_status_label = 'سفارش شما در انتظار پرداخت است';
        } elseif (($order->status == 2 || $order->status == 1) && $order->payment_status == 1) {
            $status_image = 'done.png';
            $page_title = 'انجام شد';
            $order_status_label = 'خدمات شما با موفقیت انجام و تحویل داده شد';
        } else {
            $status_image = 'cancel.png';
            $page_title = 'ناموفق';
            $order_status_label = 'سفارش شما لغو شده است.';
        }

        if ($order->user?->account_type == 'company') {
            $user_type = 'شرکت خصوصی';
        } else if ($order->user?->account_type == 'g_organization') {
            $user_type = 'سازمانی دولتی';
        } else if ($order->user?->account_type == 's_g_organization') {
            $user_type = 'سازمانی نیمه دولتی';
        }

        $shenase_label = 'شماره ملی';
        if ($order->user?->account_type != 'individual') {
            $shenase_label = 'شناسه ملی';
        }

        $shenase_value = $order->user?->melicode;

        $extra_status_label = 'تامین خدمات';
        if (count($order->extra_services) > 0) {
            $extra_status_label = 'تامین کالا / خدمات';
        }

        $extra_services = $order->extra_services;

        $name_label = 'نام کاربر';
        if ($order->user?->account_type == 'company') {
            $name_label = 'نام شرکت';
        } else if ($order->user?->account_type != 'individual') {
            $name_label = 'نام سازمان';
        }
        $payment_status_label = 'در انتظار تأیید و پرداخت';
        $payment_way = 'در انتظار تأیید و پرداخت';

        if ($order->payment_status == 0 && $order->technician_price > 0 && ($order->status == 0 || $order->status == 1 || $order->status == 2)) {

            $payment_status_label = 'در انتظار تأیید و پرداخت';
            $payment_way = 'در انتظار تأیید و پرداخت';

        } else if ($order->payment_status == 0 && ($order->status == 3 || $order->status == 4 || $order->status == 5 || $order->status == 6)) {

            $payment_status_label = 'پرداخت نشده';
            $payment_way = 'پرداخت نشده';

        } else if ($order->payment_status == 1) {
            $payment_status_label = 'پرداخت کامل';
            if ($order->user_transaction) {
                if ($order->user_transaction->type == '2') {
                    $payment_way = 'انتقال بانکی';
                } else if ($order->user_transaction->type == '3') {
                    $payment_way = 'کیف پول لوپ';
                }
            }
        }
        $groupedDetails = $order->details->groupBy('field_id');

        return view('main.reciept', compact(
            'order',
            'order_status_label',
            'page_title',
            'status_image',
            'user_type',
            'shenase_label',
            'shenase_value',
            'extra_services',
            'extra_status_label',
            'name_label',
            'brandField',
            'modelField',
            'brandDetail',
            'modelDetail',
            'brandSelectedOption',
            'modelSelectedOption',
            'payment_status_label',
            'payment_way',
            'groupedDetails'
        ));
    }

}
