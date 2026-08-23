<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminReportViolationController;
use App\Http\Controllers\ArchiveImageController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DebtRequestController;
use App\Http\Controllers\DeliveryReportController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\EducationRegisterationController;
use App\Http\Controllers\LoopLearnRegistrationController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\TechnicianEducationRegisterationController;
use App\Http\Controllers\TechnicianOrganizationOrderController;
use App\Http\Controllers\TechnicianTicketController;
use App\Http\Controllers\UserTicketController;
use App\Http\Controllers\EducationRequestController;
use App\Http\Controllers\ExpertiseController;
use App\Http\Controllers\ExtraServiceController;
use App\Http\Controllers\FaultReportController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GemController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LetterRateController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ManpowerRequestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\PollApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportViolationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StepController;
use App\Http\Controllers\TechnicianChatController;
use App\Http\Controllers\TechnicianForgotPasswordController;
use App\Http\Controllers\TechnicianIncentivePlanController;
use App\Http\Controllers\TechnicianNoteController;
use App\Http\Controllers\TechnicianOrderReportController;
use App\Http\Controllers\TechnicianPollController;
use App\Http\Controllers\TechnicianProfileController;
use App\Http\Controllers\TechnicianRegistrationController;
use App\Http\Controllers\TechnicianTransactionController;
use App\Http\Controllers\TerminationRequestController;
use App\Http\Controllers\TransferRequestController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\UserGemDiscountController;
use App\Http\Controllers\UserNoteController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\OrganizationRegistrationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\OrganizationContractController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ShahkarController;
use App\Http\Middleware\SetLocaleFromApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => SetLocaleFromApi::class], function () {
    // Location routes (public - no auth required)
    Route::post('/loop-learn-api-submit', [LoopLearnRegistrationController::class, 'apiStore'])->name('loop.learn.apiSubmit');

    Route::prefix('locations')->group(function () {
        Route::get('/provinces', [LocationController::class, 'getProvinces'])->name('api.locations.provinces');
        Route::get('/provinces/{provinceId}/cities', [LocationController::class, 'getCitiesByProvince'])->name('api.locations.cities');
        Route::get('/cities/{cityId}/regions', [LocationController::class, 'getRegionsByCity'])->name('api.locations.regions');
        Route::get('/search', [LocationController::class, 'search'])->name('api.locations.search');
        Route::get('/details', [LocationController::class, 'getLocationDetails'])->name('api.locations.details');
        Route::get('/radii', [LocationController::class, 'getMapRadii'])->name('api.locations.radii');
    });

    // Contact routes (public - no auth required)
    Route::get('/contact/phone', [ContactController::class, 'getPhoneContact'])->name('api.contact.phone');
    Route::get('/pdf-documents', [MainController::class, 'pdf_documents'])->name('api.pdf.documents');
    Route::get('/min-price', [MainController::class, 'min_price'])->name('api.min.price');

    // Shahkar inquiry (mobile <-> national code match) - standalone, usable from any registration/verification flow
    Route::post('/shahkar/inquiry', [ShahkarController::class, 'inquiry'])->name('api.shahkar.inquiry');

    // Authentication routes (no middleware required)
    Route::prefix('auth')->group(function () {
        // Registration
        Route::post('/register', [RegistrationController::class, 'register'])->name('api.auth.register');
        Route::post('/verify-phone', [PhoneVerificationController::class, 'verify'])->name('api.auth.verify-phone');
        Route::post('/resend-code', [PhoneVerificationController::class, 'resend'])->name('api.auth.resend-code');

        // Login
        Route::post('/login', [LoginController::class, 'login'])->name('api.auth.login');

        // Forgot Password
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetCode'])->name('api.auth.forgot-password');
        Route::post('/verify-reset-code', [PhoneVerificationController::class, 'verifyForPasswordReset'])->name('api.auth.verify-reset-code');
        Route::post('/resend-reset-code', [PhoneVerificationController::class, 'resendForPasswordReset'])->name('api.auth.resend-reset-code');
    });

    // Technician registration routes (no middleware required)
    Route::prefix('technician')->group(function () {
        Route::post('/register', [TechnicianRegistrationController::class, 'register'])->name('api.technician.register');
        Route::post('/verify-phone', [TechnicianRegistrationController::class, 'verifyPhone'])->name('api.technician.verify-phone');
        Route::post('/resend-code', [TechnicianRegistrationController::class, 'resendVerificationCode'])->name('api.technician.resend-code');
        Route::post('/validate-referral', [TechnicianRegistrationController::class, 'validateReferralCode'])->name('api.technician.validate-referral');
        Route::post('/login', [TechnicianRegistrationController::class, 'login'])->name('api.technician.login');

        // Forgot Password
        Route::post('/forgot-password', [TechnicianForgotPasswordController::class, 'sendResetCode'])->name('api.technician.forgot-password');
        Route::post('/verify-reset-code', [TechnicianForgotPasswordController::class, 'verifyResetCode'])->name('api.technician.verify-reset-code');
        Route::post('/reset-password', [TechnicianForgotPasswordController::class, 'resetPassword'])->name('api.technician.reset-password');
    });

    // دانلود فاکتور بدون نیاز به احراز هویت (عمومی)
    Route::get('/orders/{orderId}/invoice', [InvoiceController::class, 'downloadInvoice'])->name('api.orders.invoice.public');

    // Organization registration routes (no middleware required)
    Route::prefix('organization')->group(function () {
        // Registration
        Route::post('/register', [OrganizationRegistrationController::class, 'register'])->name('api.organization.register');
        Route::post('/verify-phone', [OrganizationRegistrationController::class, 'verifyPhone'])->name('api.organization.verify-phone');
        Route::post('/resend-code', [OrganizationRegistrationController::class, 'resendVerificationCode'])->name('api.organization.resend-code');

        // Login
        Route::post('/login', [OrganizationRegistrationController::class, 'login'])->name('api.organization.login');

        // Forgot Password
        Route::post('/forgot-password', [OrganizationRegistrationController::class, 'forgotPassword'])->name('api.organization.forgot-password');
        Route::post('/verify-reset-code', [OrganizationRegistrationController::class, 'verifyResetCode'])->name('api.organization.verify-reset-code');
        Route::post('/reset-password', [OrganizationRegistrationController::class, 'resetPassword'])->name('api.organization.reset-password');

        // Protected routes for organizations
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/validate-token', [OrganizationRegistrationController::class, 'validateToken'])->name('api.organization.validate-token');
            Route::post('/logout', [OrganizationRegistrationController::class, 'logout'])->name('api.organization.logout');
            Route::post('/logout-all', [OrganizationRegistrationController::class, 'logoutFromAllDevices'])->name('api.organization.logout-all');

            // Organization profile management
            Route::get('/profile', [OrganizationController::class, 'show'])->name('api.organization.profile.show');
            Route::post('/update-profile', [OrganizationController::class, 'update'])->name('api.organization.profile.update');
            Route::get('/profile/status', [OrganizationController::class, 'getStatus'])->name('api.organization.profile.status');

            // Organization contracts management
            Route::get('/contracts', [OrganizationContractController::class, 'index'])->name('api.organization.contracts.index');
            Route::post('/contracts/upload', [OrganizationContractController::class, 'upload'])->name('api.organization.contracts.upload');
        });
    });

    // Contracts routes (public template contract for organizations)
    Route::prefix('contracts')->middleware('auth:sanctum')->group(function () {
        Route::get('/latest', [ContractController::class, 'getLatest'])->name('api.contracts.latest');
        Route::get('/submit-contract-request', [ContractController::class, 'submitRequest'])->name('api.contracts.submitRequest');
        Route::post('/submit-information-for-request', [ContractController::class, 'submitInformationForRequest'])->name('api.contracts.submitInformationForRequest');
        Route::post('/delete-gallery', [ContractController::class, 'deleteGalleryItem'])->name('api.contracts.delete.gallery');
    });

    // Public information routes (no authentication required)
    Route::prefix('info')->group(function () {
        Route::get('/faqs', [InfoController::class, 'faqs'])->name('api.info.faqs');
        Route::get('/blogs', [InfoController::class, 'blogs'])->name('api.info.blogs');
        Route::get('/terms', [InfoController::class, 'terms'])->name('api.info.terms');
        Route::get('/privacy', [InfoController::class, 'privacy'])->name('api.info.privacy');
        Route::get('/warranties', [InfoController::class, 'warranties'])->name('api.info.warranties');
        Route::get('/organization-terms', [InfoController::class, 'organizationTerms'])->name('api.info.organization-terms');

        // Letter rates routes
        Route::get('/letter-rates', [LetterRateController::class, 'index'])->name('api.info.letter-rates');
        Route::get('/letter-rate-categories', [LetterRateController::class, 'categories'])->name('api.info.letter-rate-categories');
        Route::get('/letter-rates/category/{categoryId?}', [LetterRateController::class, 'byCategory'])->name('api.info.letter-rates-by-category');
    });

    // Category routes (public access with optional authentication for filtering)
    Route::prefix('categories')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('api.categories.index');
        Route::get('/tree', [CategoryController::class, 'tree'])->name('api.categories.tree');
        Route::get('/leaves', [CategoryController::class, 'leaves'])->name('api.categories.leaves');
        Route::get('/{id}', [CategoryController::class, 'show'])->name('api.categories.show');
    });

    // Expertise routes (public access)
    Route::prefix('expertises')->group(function () {
        Route::get('/', [ExpertiseController::class, 'index'])->name('api.expertises.index');
    });

    // Step routes (requires authentication to detect user type)
    Route::prefix('steps')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/fetch', [StepController::class, 'fetch_steps'])->name('api.steps.fetch');
        Route::post('/fetch-conditional', [StepController::class, 'fetch_conditional_steps'])->name('api.steps.fetch-conditional');
    });

    // Reviews - public access (دریافت نظرات تکنسین برای همه قابل دسترسی است)
    Route::get('/reviews/technician/{technicianId}', [ReviewController::class, 'getTechnicianReviews'])->name('api.reviews.technician');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // User info
        Route::get('/user', [LoginController::class, 'me'])->name('api.user.me');

        // Profile management
        Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
        Route::patch('/profile/password', [ProfileController::class, 'changePassword'])->name('api.profile.change-password');

        // Token validation
        Route::post('/auth/validate-token', [LoginController::class, 'validateToken'])->name('api.auth.validate-token');

        // Logout
        Route::post('/auth/logout', [LoginController::class, 'logout'])->name('api.auth.logout');
        Route::post('/auth/logout-all', [LoginController::class, 'logoutFromAllDevices'])->name('api.auth.logout-all');

        // Poll Application
        Route::prefix('poll')->group(function () {
            Route::post('/', [PollApplicationController::class, 'store'])->name('api.poll.store');
            Route::get('/my-poll', [PollApplicationController::class, 'show'])->name('api.poll.show');
            Route::get('/can-participate', [PollApplicationController::class, 'canParticipate'])->name('api.poll.can-participate');
            Route::get('/statistics', [PollApplicationController::class, 'statistics'])->name('api.poll.statistics');
        });

        // Report Violations
        Route::prefix('report-violations')->group(function () {
            Route::post('/', [ReportViolationController::class, 'store'])->name('api.report-violations.store');
            Route::get('/', [ReportViolationController::class, 'index'])->name('api.report-violations.index');
            Route::get('/search', [ReportViolationController::class, 'search'])->name('api.report-violations.search');
            Route::get('/{id}', [ReportViolationController::class, 'show'])->name('api.report-violations.show');
            Route::put('/{id}', [ReportViolationController::class, 'update'])->name('api.report-violations.update');
            Route::delete('/{id}', [ReportViolationController::class, 'destroy'])->name('api.report-violations.destroy');
        });

        // Reviews (نظرات)
        Route::prefix('reviews')->group(function () {
            Route::post('/', [ReviewController::class, 'submitReview'])->name('api.reviews.submit');
            Route::get('/my-reviews', [ReviewController::class, 'getMyReviews'])->name('api.reviews.my-reviews');
        });

        // User Addresses
        Route::prefix('addresses')->group(function () {
            Route::get('/', [UserAddressController::class, 'index'])->name('api.addresses.index');
            Route::post('/', [UserAddressController::class, 'store'])->name('api.addresses.store');
            Route::get('/count', [UserAddressController::class, 'count'])->name('api.addresses.count');
            Route::get('/{id}', [UserAddressController::class, 'show'])->name('api.addresses.show');
            Route::put('/{id}', [UserAddressController::class, 'update'])->name('api.addresses.update');
            Route::delete('/{id}', [UserAddressController::class, 'destroy'])->name('api.addresses.destroy');
        });

        // Orders - محافظت شده برای سازمان‌های تایید شده
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'getUserOrders'])->name('api.orders.index');
            Route::get('/summary', [OrderController::class, 'getUserOrdersSummary'])->name('api.orders.summary');
            Route::post('/detail', [OrderController::class, 'getOrderDetail'])->name('api.orders.detail');
            Route::post('/upload', [OrderController::class, 'orderUpload'])->name('api.orders.upload');
            Route::post('/uploadMultiple', [OrderController::class, 'uploadMultiple'])->name('api.orders.uploadMultiple');
            Route::post('/check-discount', [OrderController::class, 'checkDiscount'])->name('api.orders.check-discount');
            Route::post('/submit', [OrderController::class, 'submitOrder'])->name('api.orders.submit');
            Route::post('/cancel', [OrderController::class, 'cancelOrder'])->name('api.orders.cancel');
            Route::post('/verify-technician', [OrderController::class, 'verifyTechnician'])->name('api.orders.verify-technician');
            Route::post('/extra-services', [OrderController::class, 'fetchOrderExtraServices'])->name('api.orders.extra-services');
            Route::get('/{orderId}/extra-services', [ExtraServiceController::class, 'showOrderExtraServices'])->name('api.orders.extra-services.show');
            Route::post('/{orderId}/initial-accept', [OrderController::class, 'userInitialAccept'])->name('api.orders.initial-accept');
            Route::post('/{orderId}/decision', [OrderController::class, 'userOrderDecision'])->name('api.orders.decision');
            Route::post('/{orderId}/return-followup', [OrderController::class, 'userReturnFollowup'])->name('api.orders.return-followup');
            Route::post('/{orderId}/final-description', [OrderController::class, 'setUserFinalDescription'])->name('api.orders.final-description');
            Route::post('/{orderId}/in-place-description', [OrderController::class, 'setUserInPlaceDescription'])->name('api.orders.in-place-description');
            Route::get('/{orderId}/delivery-report', [DeliveryReportController::class, 'showForUser'])->name('api.orders.delivery-report');
            Route::post('/{orderId}/delivery-report/verify', [DeliveryReportController::class, 'verifyByUser'])->name('api.orders.delivery-report.verify');

            // پرداخت سفارش از طریق درگاه
            Route::post('/gateway-payment', [OrderController::class, 'gatewayPayment'])->name('api.orders.gateway-payment');
        });

        // Technician protected routes
        Route::prefix('technician')->group(function () {
            Route::post('/validate-token', [TechnicianRegistrationController::class, 'validateToken'])->name('api.technician.validate-token');
            Route::post('/logout', [TechnicianRegistrationController::class, 'logout'])->name('api.technician.logout');
            Route::post('/logout-all', [TechnicianRegistrationController::class, 'logoutFromAllDevices'])->name('api.technician.logout-all');

            // Profile management
            Route::put('/profile/personal-info', [TechnicianProfileController::class, 'updatePersonalInfo'])->name('api.technician.profile.personal-info');
            Route::put('/profile/vehicle-info', [TechnicianProfileController::class, 'updateVehicleInfo'])->name('api.technician.profile.vehicle-info');
            Route::put('/profile/bank-info', [TechnicianProfileController::class, 'updateBankInfo'])->name('api.technician.profile.bank-info');
            Route::patch('/profile/password', [TechnicianProfileController::class, 'updatePassword'])->name('api.technician.profile.password');
            Route::patch('/profile/at-work', [TechnicianProfileController::class, 'updateAtWork'])->name('api.technician.profile.atWork');

            // Orders
            Route::get('/orders', [TechnicianProfileController::class, 'getMyOrders'])->name('api.technician.orders');
            Route::get('/orders/{orderId}/detail', [TechnicianProfileController::class, 'getOrderDetail'])->name('api.technician.orders.detail');
            Route::post('/orders/{orderId}/start', [TechnicianProfileController::class, 'startOrder'])->name('api.technician.orders.start');
            Route::post('/orders/{orderId}/end', [TechnicianProfileController::class, 'endOrder'])->name('api.technician.orders.end');
            Route::post('/orders/{orderId}/cancel', [TechnicianProfileController::class, 'cancelOrder'])->name('api.technician.orders.cancel');
            Route::post('/orders/{orderId}/emergency-help', [TechnicianProfileController::class, 'setEmergencyHelp'])->name('api.technician.orders.emergency-help');
            Route::post('/orders/{orderId}/technician-opinion', [TechnicianProfileController::class, 'setTechnicianOpinion'])->name('api.technician.orders.technician-opinion');
            Route::post('/orders/{orderId}/send-to-loop', [TechnicianOrderReportController::class, 'sendToLoop'])->name('api.technician.orders.send-to-loop');
            Route::post('/orders/{orderId}/done-in-place', [TechnicianOrderReportController::class, 'doneInPlace'])->name('api.technician.orders.done-in-place');
            Route::patch('/orders/{orderId}/loop-info', [TechnicianOrderReportController::class, 'updateLoopInfo'])->name('api.technician.orders.update-loop-info');
            Route::post('/orders/{orderId}/technician-description', [TechnicianOrderReportController::class, 'setTechnicianDescription'])->name('api.technician.orders.set-description');
            Route::post('/orders/{orderId}/set-off', [TechnicianOrderReportController::class, 'setOff'])->name('api.technician.orders.set-off');
            Route::post('/orders/{orderId}/arrive', [TechnicianOrderReportController::class, 'arrive'])->name('api.technician.orders.arrive');

            // Extra Services (خدمات اضافی)
            Route::get('/extra-services', [ExtraServiceController::class, 'getExtraServices'])->name('api.technician.extra-services.index');
            Route::post('/submit-extra-services', [ExtraServiceController::class, 'storeExtraServices'])->name('api.technician.extra-services.store');

            // Delivery Reports (گزارش تحویل)
            Route::prefix('delivery-reports')->group(function () {
                Route::get('/', [DeliveryReportController::class, 'index'])->name('api.technician.delivery-reports.index');
                Route::post('/', [DeliveryReportController::class, 'store'])->name('api.technician.delivery-reports.store');
                Route::get('/order/{orderId}', [DeliveryReportController::class, 'showByOrder'])->name('api.technician.delivery-reports.by-order');
                Route::put('/{reportId}', [DeliveryReportController::class, 'update'])->name('api.technician.delivery-reports.update');
                Route::post('/order/{orderId}/verify-code', [DeliveryReportController::class, 'verifyWithCode'])->name('api.technician.delivery-reports.verify-code');
                Route::post('/order/{orderId}/resend-code', [DeliveryReportController::class, 'resendVerificationCode'])->name('api.technician.delivery-reports.resend-code');
            });

            // Order Reports (تکنسین)
            Route::prefix('order-reports')->group(function () {
                Route::get('/', [TechnicianOrderReportController::class, 'index'])->name('api.technician.order-reports.index');
                Route::post('/', [TechnicianOrderReportController::class, 'store'])->name('api.technician.order-reports.store');
                Route::get('/{id}', [TechnicianOrderReportController::class, 'show'])->name('api.technician.order-reports.show');
                Route::put('/{id}', [TechnicianOrderReportController::class, 'update'])->name('api.technician.order-reports.update');
            });

            // Education Requests (درخواست‌های آموزش/مراجعه)
            Route::prefix('education-requests')->group(function () {
                Route::get('/', [EducationRequestController::class, 'index'])->name('api.technician.education-requests.index');
                Route::post('/', [EducationRequestController::class, 'store'])->name('api.technician.education-requests.store');
                Route::get('/{id}', [EducationRequestController::class, 'show'])->name('api.technician.education-requests.show');
            });

            // Leave Requests (درخواست‌های مرخصی استعلاجی)
            Route::prefix('leave-requests')->group(function () {
                Route::get('/', [LeaveRequestController::class, 'index'])->name('api.technician.leave-requests.index');
                Route::post('/', [LeaveRequestController::class, 'store'])->name('api.technician.leave-requests.store');
                Route::get('/{id}', [LeaveRequestController::class, 'show'])->name('api.technician.leave-requests.show');
            });

            // Debt Requests (درخواست‌های وام)
            Route::prefix('debt-requests')->group(function () {
                Route::get('/', [DebtRequestController::class, 'index'])->name('api.technician.debt-requests.index');
                Route::post('/', [DebtRequestController::class, 'store'])->name('api.technician.debt-requests.store');
                Route::get('/{id}', [DebtRequestController::class, 'show'])->name('api.technician.debt-requests.show');
            });

            // Manpower Requests (درخواست‌های نیروی انسانی)
            Route::prefix('manpower-requests')->group(function () {
                Route::get('/', [ManpowerRequestController::class, 'index'])->name('api.technician.manpower-requests.index');
                Route::post('/', [ManpowerRequestController::class, 'store'])->name('api.technician.manpower-requests.store');
                Route::get('/{id}', [ManpowerRequestController::class, 'show'])->name('api.technician.manpower-requests.show');
            });

            // Transfer Requests (درخواست‌های انتقال/سمت)
            Route::prefix('transfer-requests')->group(function () {
                Route::get('/', [TransferRequestController::class, 'index'])->name('api.technician.transfer-requests.index');
                Route::post('/', [TransferRequestController::class, 'store'])->name('api.technician.transfer-requests.store');
                Route::get('/{id}', [TransferRequestController::class, 'show'])->name('api.technician.transfer-requests.show');
            });

            // Termination Requests (درخواست‌های قطع همکاری)
            Route::prefix('termination-requests')->group(function () {
                Route::get('/', [TerminationRequestController::class, 'index'])->name('api.technician.termination-requests.index');
                Route::post('/', [TerminationRequestController::class, 'store'])->name('api.technician.termination-requests.store');
                Route::get('/{id}', [TerminationRequestController::class, 'show'])->name('api.technician.termination-requests.show');
            });

            // Transactions (تراکنش‌های مالی تکنسین)
            Route::prefix('transactions')->group(function () {
                Route::get('/', [TechnicianTransactionController::class, 'index'])->name('api.technician.transactions.index');
                Route::get('/yearly-income-chart', [TechnicianTransactionController::class, 'yearlyIncomeChart'])->name('api.technician.transactions.yearly-income-chart');
            });

            // Archive Images (آرشیو تصاویر تکنسین)
            Route::prefix('archive-images')->group(function () {
                Route::get('/', [ArchiveImageController::class, 'index'])->name('api.technician.archive-images.index');
                Route::post('/', [ArchiveImageController::class, 'upload'])->name('api.technician.archive-images.upload');
                Route::delete('/{id}', [ArchiveImageController::class, 'destroy'])->name('api.technician.archive-images.destroy');
            });

            // Technician Notes (یادداشت‌های تکنسین)
            Route::prefix('notes')->group(function () {
                Route::post('/', [TechnicianNoteController::class, 'store'])->name('api.technician.notes.store');
                Route::get('/', [TechnicianNoteController::class, 'index'])->name('api.technician.notes.index');
                Route::get('/{id}', [TechnicianNoteController::class, 'show'])->name('api.technician.notes.show');
                Route::put('/{id}', [TechnicianNoteController::class, 'update'])->name('api.technician.notes.update');
                Route::delete('/{id}', [TechnicianNoteController::class, 'destroy'])->name('api.technician.notes.destroy');
            });

            // Incentive Plans (طرح‌های تشویقی تکنسین)
            Route::prefix('incentive-plans')->group(function () {
                Route::get('/', [TechnicianIncentivePlanController::class, 'index'])->name('api.technician.incentive-plans.index');
                Route::get('/{id}', [TechnicianIncentivePlanController::class, 'show'])->name('api.technician.incentive-plans.show');
            });

            // Poll (نظرسنجی و پیشنهادات تکنسین)
            Route::prefix('poll')->group(function () {
                Route::post('/', [TechnicianPollController::class, 'store'])->name('api.technician.poll.store');
                Route::get('/check', [TechnicianPollController::class, 'checkSubmission'])->name('api.technician.poll.check');
            });

            // Admin Report Violations (گزارش تخلفات ادمین به تکنسین)
            Route::prefix('admin-report-violations')->group(function () {
                Route::get('/', [AdminReportViolationController::class, 'index'])->name('api.technician.admin-report-violations.index');
                Route::get('/{id}', [AdminReportViolationController::class, 'show'])->name('api.technician.admin-report-violations.show');
                Route::post('/{id}/reply', [AdminReportViolationController::class, 'reply'])->name('api.technician.admin-report-violations.reply');
            });

            // Tickets (تیکت پشتیبانی تکنسین با ادمین)
            Route::prefix('tickets')->group(function () {
                Route::get('/', [TechnicianTicketController::class, 'index'])->name('api.technician.tickets.index');
                Route::post('/', [TechnicianTicketController::class, 'store'])->name('api.technician.tickets.store');
                Route::get('/unread-count', [TechnicianTicketController::class, 'unreadCount'])->name('api.technician.tickets.unread-count');
            });

            // Education Registrations (ثبت‌نام آموزشی تکنسین‌ها)
            Route::prefix('education-registerations')->group(function () {
                Route::post('/', [TechnicianEducationRegisterationController::class, 'store'])->name('api.technician.education-registerations.store');
                Route::get('/', [TechnicianEducationRegisterationController::class, 'index'])->name('api.technician.education-registerations.index');
                Route::get('/{id}', [TechnicianEducationRegisterationController::class, 'show'])->name('api.technician.education-registerations.show');
            });

            // Organization Orders (سفارشات سازمانی تکنسین)
            Route::prefix('organization-orders')->group(function () {
                Route::get('/organizations', [TechnicianOrganizationOrderController::class, 'getOrganizations'])->name('api.technician.organization-orders.organizations');
                Route::get('/organizations/{organizationId}/orders', [TechnicianOrganizationOrderController::class, 'getOrganizationOrders'])->name('api.technician.organization-orders.organization-orders');
            });

            // Chat (چت تکنسین با کاربران)
            Route::prefix('chats')->group(function () {
                Route::get('/', [TechnicianChatController::class, 'fetchChats'])->name('api.technician.chats.index');
                Route::get('/messages', [TechnicianChatController::class, 'fetchMessages'])->name('api.technician.chats.messages');
                Route::post('/send', [TechnicianChatController::class, 'sendMessage'])->name('api.technician.chats.send');
                Route::post('/mark-read', [TechnicianChatController::class, 'markAsRead'])->name('api.technician.chats.mark-read');
            });
        });
 
        // Order Reports - User Confirmation (کاربر عادی)
        Route::prefix('order-reports')->group(function () {
            Route::get('/by-order/{orderId}', [TechnicianOrderReportController::class, 'showByOrder'])->name('api.order-reports.by-order');
            Route::post('/confirm', [TechnicianOrderReportController::class, 'confirm'])->name('api.order-reports.confirm');
        });

        // Wallet Management
        Route::prefix('wallet')->group(function () {
            Route::post('/charge', [WalletController::class, 'increaseWallet'])->name('api.wallet.charge');
            Route::get('/balance', [WalletController::class, 'getBalance'])->name('api.wallet.balance');
            Route::get('/transactions', [WalletController::class, 'getTransactions'])->name('api.wallet.transactions');
            Route::post('/pay-order', [WalletController::class, 'payOrder'])->name('api.wallet.pay-order');
        });

        // Chat (چت بین کاربر و تکنسین)
        Route::prefix('chats')->group(function () {
            Route::get('/', [ChatController::class, 'fetchChats'])->name('api.chats.index');
            Route::get('/messages', [ChatController::class, 'fetchMessages'])->name('api.chats.messages');
            Route::post('/send', [ChatController::class, 'sendMessage'])->name('api.chats.send');
            Route::post('/mark-read', [ChatController::class, 'markAsRead'])->name('api.chats.mark-read');
        });

        // Gem System (گردونه شانس و امتیازات)
        Route::prefix('gems')->group(function () {
            Route::get('/actions', [GemController::class, 'getActions'])->name('api.gems.actions');
            Route::post('/spin', [GemController::class, 'spinWheel'])->name('api.gems.spin');
            Route::get('/history', [GemController::class, 'getHistory'])->name('api.gems.history');
            Route::get('/can-play', [GemController::class, 'canPlay'])->name('api.gems.can-play');
        });

        // Discount System (سیستم تخفیف و کلاب)
        Route::prefix('discounts')->group(function () {
            Route::get('/offers', [DiscountController::class, 'fetchOffers'])->name('api.discounts.offers');
            Route::get('/categories', [DiscountController::class, 'fetchDiscountCategories'])->name('api.discounts.categories');
            Route::get('/list', [DiscountController::class, 'fetchDiscounts'])->name('api.discounts.list');
            Route::get('/timed', [DiscountController::class, 'fetchDiscountTimed'])->name('api.discounts.timed');
            Route::post('/detail', [DiscountController::class, 'fetchDiscountDetail'])->name('api.discounts.detail');
            Route::post('/claim', [DiscountController::class, 'getDiscount'])->name('api.discounts.claim');
            Route::post('/check', [DiscountController::class, 'checkDiscount'])->name('api.discounts.check');
        });

        // User Gem & Discount Management (مدیریت جم و کدهای تخفیف کاربر)
        Route::prefix('user')->group(function () {
            Route::get('/gem-transactions', [UserGemDiscountController::class, 'fetchGemTransactions'])->name('api.user.gem-transactions');
            Route::get('/discounts', [UserGemDiscountController::class, 'fetchUserDiscounts'])->name('api.user.discounts');
        });

        // Fault Reports (گزارش خرابی)
        Route::prefix('fault-reports')->group(function () {
            Route::post('/', [FaultReportController::class, 'store'])->name('api.fault-reports.store');
            Route::get('/', [FaultReportController::class, 'index'])->name('api.fault-reports.index');
            Route::get('/{id}', [FaultReportController::class, 'show'])->name('api.fault-reports.show');
        });

        // Education Registerations (درخواست آموزش)
        Route::prefix('education-registerations')->group(function () {
            Route::post('/', [EducationRegisterationController::class, 'store'])->name('api.education-registerations.store');
            Route::get('/', [EducationRegisterationController::class, 'index'])->name('api.education-registerations.index');
            Route::get('/{id}', [EducationRegisterationController::class, 'show'])->name('api.education-registerations.show');
        });

        // User Notes (یادداشت‌های کاربر)
        Route::prefix('notes')->group(function () {
            Route::post('/', [UserNoteController::class, 'store'])->name('api.notes.store');
            Route::get('/', [UserNoteController::class, 'index'])->name('api.notes.index');
            Route::get('/{id}', [UserNoteController::class, 'show'])->name('api.notes.show');
            Route::put('/{id}', [UserNoteController::class, 'update'])->name('api.notes.update');
            Route::delete('/{id}', [UserNoteController::class, 'destroy'])->name('api.notes.destroy');
        });

        // User Tickets (تیکت پشتیبانی کاربر با ادمین)
        Route::prefix('tickets')->group(function () {
            Route::get('/', [UserTicketController::class, 'index'])->name('api.user.tickets.index');
            Route::post('/', [UserTicketController::class, 'store'])->name('api.user.tickets.store');
            Route::get('/unread-count', [UserTicketController::class, 'unreadCount'])->name('api.user.tickets.unread-count');
        });
    });

    // Wallet callback (public - برای callback از درگاه)
    Route::get('/wallet/callback', [WalletController::class, 'walletCallback'])->name('wallet.callback');

    // Order payment callback (public - برای callback پرداخت سفارش از درگاه)
    Route::get('/order/payment/callback', [OrderController::class, 'paymentCallback'])->name('order.payment.callback');

});