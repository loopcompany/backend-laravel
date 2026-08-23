<?php

use App\Http\Controllers\Api\Admin\DigitalBusinessCardEditorController;
use App\Http\Controllers\LoopLearnRegistrationController;
use App\Http\Controllers\DigitalBusinessCardController;
use App\Http\Controllers\main\OrderController;
use App\Http\Controllers\MainController;
use App\Models\DigitalBusinessCard;
use App\Http\Controllers\user\WebAuthController;
use App\Http\Controllers\user\UserDashboardController;
use App\Http\Controllers\Web\WebTechnicianAuthController;
use App\Http\Controllers\Web\WebTechnicianProfileController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MainController::class, 'index'])->name('web.home'); // Alternative name for navigation
Route::get('/reciept/{id}', [MainController::class, 'reciept'])->name('web.reciept'); // Alternative name for navigation

// Authentication redirects for Laravel defaults
Route::get('/login', function () {
    return redirect()->route('web.login');
})->name('login');

Route::get('/home', function () {
    return redirect()->route('web.dashboard');
})->name('home');


Route::get('/redirect', [MainController::class, 'redirect'])->name('redirect');



// Public information pages
Route::get('/terms', [MainController::class, 'terms'])->name('web.terms');
Route::get('/privacy', [MainController::class, 'privacy'])->name('web.privacy');
Route::get('/about', [MainController::class, 'about'])->name('web.about');
Route::get('/contact', [MainController::class, 'contact'])->name('web.contact');
Route::get('/digital-business-card/{slug}', [DigitalBusinessCardController::class, 'digital_business_card_detail'])->name('digital_business_card_detail');
Route::get('/cards/{slug}', [DigitalBusinessCardController::class, 'show'])->name('public.cards.show');
Route::get('/loop-learn', [LoopLearnRegistrationController::class, 'index'])->name('loop.learn');

// Public Technician Profile (for QR Code)
Route::get('/technician/{id}', [\App\Http\Controllers\Web\PublicTechnicianController::class, 'show'])->name('web.technician.public');
Route::post('/contact', [MainController::class, 'submitContact'])->name('submit.contact');
Route::post('/loop-learn-submit', [LoopLearnRegistrationController::class, 'store'])->name('loop.learn.submit');
Route::get('/delete-account-request', [MainController::class, 'deleteAccountRequest'])->name('web.delete-account-request');
Route::post('/delete-account-request', [MainController::class, 'submitDeleteAccountRequest'])->name('submit.delete-account-request');
Route::get('/faqs', [MainController::class, 'faqs'])->name('web.faqs');
Route::get('/blogs', [MainController::class, 'blogs'])->name('web.blogs');
Route::get('/blog/{id}/{slug?}', [MainController::class, 'blogDetail'])->name('blog.detail');

// Categories Routes
Route::get('/categories/{id?}', [MainController::class, 'categoryShow'])->name('web.category.show');

Route::prefix('admin/cards')->middleware(['auth:admin'])->group(function () {
    Route::get('/create', [DigitalBusinessCardEditorController::class, 'create'])
        ->name('admin.cards.create');

    Route::post('/', [DigitalBusinessCardEditorController::class, 'store'])
        ->name('admin.cards.store');

    Route::get('/{card}/editor', function (DigitalBusinessCard $card) {
        return view('admin.cards.editor', [
            'card' => $card,
            'editorDataUrl' => route('admin.cards.editor-data', $card),
            'saveEditorUrl' => route('admin.cards.save-editor', $card),
            'uploadMediaUrl' => route('admin.cards.upload-media', $card),
            'createCardUrl' => route('admin.cards.create'),
        ]);
    })
        ->middleware('can:view,card')
        ->name('admin.cards.editor');

    Route::get('/{card}/editor-data', [DigitalBusinessCardEditorController::class, 'editorData'])
        ->middleware('can:view,card')
        ->name('admin.cards.editor-data');

    Route::post('/{card}/save-editor', [DigitalBusinessCardEditorController::class, 'saveEditor'])
        ->middleware('can:update,card')
        ->name('admin.cards.save-editor');

    Route::post('/{card}/upload-media', [DigitalBusinessCardEditorController::class, 'uploadMedia'])
        ->middleware('can:update,card')
        ->name('admin.cards.upload-media');
});

// Web Authentication Routes
Route::prefix('user')->name('web.')->group(function () {
    // Order form - accessible for all users
    Route::get('/order-form/{category_id?}', [OrderController::class, 'order_form'])->name('order.form');

    // Guest routes (فقط برای کاربران غیر وارد شده)
    Route::middleware('guest')->group(function () {

        // Login
        Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [WebAuthController::class, 'login']);

        // Register  
        Route::get('/register', [WebAuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/register', [WebAuthController::class, 'register']);

        // Phone Verification
        Route::get('/verify-phone', [WebAuthController::class, 'showVerifyPhoneForm'])->name('verify-phone');
        Route::post('/verify-phone', [WebAuthController::class, 'verifyPhone']);
        Route::post('/resend-verification-code', [WebAuthController::class, 'resendVerificationCode'])->name('resend-verification-code');

        // Forgot Password
        Route::get('/forgot-password', [WebAuthController::class, 'showForgotPasswordForm'])->name('forgot-password');
        Route::post('/forgot-password', [WebAuthController::class, 'sendResetCode']);

        // Verify Reset Code  
        Route::get('/verify-reset-code', [WebAuthController::class, 'showVerifyResetCodeForm'])->name('verify-reset-code');
        Route::post('/verify-reset-code', [WebAuthController::class, 'verifyResetCode']);
        Route::post('/resend-reset-code', [WebAuthController::class, 'resendResetCode'])->name('resend-reset-code');
    });



    // Authenticated routes (فقط برای کاربران وارد شده)
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/dashboard', [UserDashboardController::class, 'dashboard'])->name('dashboard');

        // Profile Management
        Route::get('/profile', [UserDashboardController::class, 'showProfile'])->name('profile.show');
        Route::get('/profile/edit', [UserDashboardController::class, 'editProfile'])->name('profile.edit');
        Route::put('/profile', [UserDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/change-password', [UserDashboardController::class, 'changePassword'])->name('profile.change-password');


        // Route::get('/account', [UserAccountController::class, 'account'])->name('user.account');
        // Route::post('/account-update', [UserAccountController::class, 'account_update'])->name('user.account.update');
        // Route::post('/avatar-update', [UserAccountController::class, 'avatar_update'])->name('user.avatar.update');

        // Route::post('/club-generate-code/{id}', [UserAccountController::class, 'club_generate_code'])->name('user.club.generate_code');

        // Route::get('/wallet', [UserWalletController::class, 'wallet'])->name('user.wallet');
        // Route::get('/withdraw', [UserWalletController::class, 'user_withdraw'])->name('user.withdraw');
        // Route::post('/withdraw', [UserWalletController::class, 'withdraw'])->name('user.withdraw2');
        // Route::post('/wallet-charge-request', [UserWalletController::class, 'wallet_charge_request'])->name('user.wallet-charge-request');
        // Route::get('/wallet-charge-callback', [UserWalletController::class, 'wallet_charge_callback'])->name('user.wallet-charge-callback');

        // Route::get('/wishlist', [UserAccountController::class, 'wishlist'])->name('user.wishlist');
        // Route::get('/wishlist-delete/{id}', [UserAccountController::class, 'wishlist_delete'])->name('user.wishlist-delete');
        // Route::get('/wishlist-add/{id}', [UserAccountController::class, 'wishlist_add'])->name('user.wishlist-add');

        // Route::get('/addresses', [AddressController::class, 'addresses'])->name('user.addresses');
        // Route::get('/address-add', [AddressController::class, 'address_add'])->name('user.address.add');
        // Route::post('/address-create', [AddressController::class, 'address_create'])->name('user.address.create');
        // Route::get('/address-delete/{id}', [AddressController::class, 'address_delete'])->name('user.address.delete');
        // Route::get('/address-edit/{id}', [AddressController::class, 'address_edit'])->name('user.address.edit');
        // Route::put('/address-update/{id}', [AddressController::class, 'address_update'])->name('user.address.update');

        // Route::get('/orders', [UserOrderController::class, 'user_orders'])->name('user.orders');
        // Route::get('/order-detail/{id}', [UserOrderController::class, 'order_detail'])->name('user.order.detail');
        // Route::post('/order-cancel', [UserOrderController::class, 'order_cancel'])->name('user.order.cancel.submit');
        // Route::post('/order-extra-submit', [UserOrderController::class, 'storeExtras'])->name('order.extra.submit');
        // Route::get('/order-cancel/{id}', [UserOrderController::class, 'order_cancel'])->name('user.order.cancel'); //////////////?????????????????
        // Route::get('/order-start/{id}', [UserOrderController::class, 'user_start_order'])->name('user.start-order');
        // Route::get('/order-end/{id}', [UserOrderController::class, 'user_end_order'])->name('user.end-order');
        // Route::get('/order-report', [UserOrderController::class, 'order_report'])->name('user.order.report');
        // Route::post('/order-report-status', [UserOrderController::class, 'order_report_status'])->name('user.order.report.status');

        // Route::get('/order/{id}/suggestions', [UserOrderController::class, 'user_suggestions'])->name('user.suggestions');
        // Route::post('order/suggestions/{suggestion}/reject', [UserOrderController::class, 'reject_suggestion'])->name('user.suggestions.reject');
        // Route::post('order/suggestions/{suggestion}/accept', [UserOrderController::class, 'accept_suggestion'])->name('user.suggestions.accept');

        // Route::post('order/rate-submit', [UserOrderController::class, 'rate_submit'])->name('user.rate.submit');
        // Route::post('order/order-payment', [UserOrderController::class, 'order_payment'])->name('user.order.payment');
        // Route::get('order/payment-callback', [UserOrderController::class, 'order_callback'])->name('user.order.payment.callback');

        // Route::get('/ticket/list', [UserAccountController::class, 'user_tickets'])->name('user.tickets');
        // Route::get('/ticket/create', [UserAccountController::class, 'ticket_create'])->name('ticket.create');
        // Route::post('/ticket/store', [UserAccountController::class, 'ticket_store'])->name('ticket.store');
        // Route::get('/ticket/show/{id}', [UserAccountController::class, 'ticket_detail'])->name('user.ticket.detail');
        // Route::post('/ticket/{id}/reply', [UserAccountController::class, 'ticket_reply'])->name('user.ticket.reply');

        // Route::get('/chats', [UserAccountController::class, 'user_chats'])->name('user.chats');
        // Route::get('/chat-detail/{id}', [UserAccountController::class, 'chat_detail'])->name('user.chat.detail');
        // Route::post('/send-message/{id}', [UserAccountController::class, 'chat_send_message'])->name('user.chats.sendMessage');

        // Route::get('/gems', [UserAccountController::class, 'gems'])->name('user.gems');

        // Route::get('/clubs', [UserAccountController::class, 'clubs'])->name('user.clubs');

        // Route::get('/reviews', [UserAccountController::class, 'reviews'])->name('user.reviews');

        // // fatemeh
        // Route::get('/save-technician/{id}', [UserAccountController::class, 'save_technician'])->name('user.save.technician');
        // Route::get('/send-request-technician/{id}', [UserAccountController::class, 'send_request_technician'])->name('user.send.request.technician');
        // Route::post('/submit-request-technician/{id}', [UserAccountController::class, 'submit_request_technician'])->name('user.submit.request.technician');

    });
});

// Technician Web Authentication Routes
Route::prefix('technician')->name('web.technician.')->group(function () {
    // Guest routes (only for non-logged-in technicians)
    Route::middleware('guest:technician')->group(function () {
        // Register
        Route::get('/register', [WebTechnicianAuthController::class, 'showRegisterForm'])->name('register-form');
        Route::post('/register', [WebTechnicianAuthController::class, 'register'])->name('register');

        // Phone Verification
        Route::get('/verify-phone', [WebTechnicianAuthController::class, 'showVerifyPhoneForm'])->name('verify-phone-form');
        Route::post('/verify-phone', [WebTechnicianAuthController::class, 'verifyPhone'])->name('verify-phone');
        Route::post('/resend-verification-code', [WebTechnicianAuthController::class, 'resendVerificationCode'])->name('resend-verification-code');

        // Login
        Route::get('/login', [WebTechnicianAuthController::class, 'showLoginForm'])->name('login-form');
        Route::post('/login', [WebTechnicianAuthController::class, 'login'])->name('login');

        // Forgot Password
        Route::get('/forgot-password', [WebTechnicianAuthController::class, 'showForgotPasswordForm'])->name('forgot-password-form');
        Route::post('/forgot-password', [WebTechnicianAuthController::class, 'sendResetCode'])->name('forgot-password');

        // Verify Reset Code
        Route::get('/verify-reset-code', [WebTechnicianAuthController::class, 'showVerifyResetCodeForm'])->name('verify-reset-code-form');
        Route::post('/verify-reset-code', [WebTechnicianAuthController::class, 'verifyResetCode'])->name('verify-reset-code');

        // Reset Password
        Route::get('/reset-password', [WebTechnicianAuthController::class, 'showResetPasswordForm'])->name('reset-password-form');
        Route::post('/reset-password', [WebTechnicianAuthController::class, 'resetPassword'])->name('reset-password');
    });

    // Authenticated routes (only for logged-in technicians using technician guard)
    Route::middleware(\App\Http\Middleware\TechnicianAuth::class)->group(function () {
        Route::post('/logout', [WebTechnicianAuthController::class, 'logout'])->name('logout');
        Route::get('/', [WebTechnicianAuthController::class, 'dashboard'])->name('dashboard');

        // Profile Management
        Route::get('/profile', [WebTechnicianProfileController::class, 'show'])->name('profile');
        Route::get('/profile/edit-personal-info', [WebTechnicianProfileController::class, 'editPersonalInfo'])->name('profile.edit-personal-info');
        Route::post('/profile/update-personal-info', [WebTechnicianProfileController::class, 'updatePersonalInfo'])->name('profile.update-personal-info');
        Route::get('/profile/edit-vehicle-info', [WebTechnicianProfileController::class, 'editVehicleInfo'])->name('profile.edit-vehicle-info');
        Route::post('/profile/update-vehicle-info', [WebTechnicianProfileController::class, 'updateVehicleInfo'])->name('profile.update-vehicle-info');
        Route::get('/profile/edit-bank-info', [WebTechnicianProfileController::class, 'editBankInfo'])->name('profile.edit-bank-info');
        Route::post('/profile/update-bank-info', [WebTechnicianProfileController::class, 'updateBankInfo'])->name('profile.update-bank-info');
        Route::get('/profile/edit-password', [WebTechnicianProfileController::class, 'editPassword'])->name('profile.edit-password');
        Route::post('/profile/update-password', [WebTechnicianProfileController::class, 'updatePassword'])->name('profile.update-password');

        // Orders
        Route::get('/orders', [WebTechnicianProfileController::class, 'orders'])->name('orders');

        // QR Code
        Route::get('/qrcode', function () {
            return view('technician.qrcode');
        })->name('qrcode');
    });
});




Route::get('/admin/category-fields', function () {
    return redirect()->back();
})->name('filament.admin.resources.category-fields.index');

// Admin Invoice Routes
Route::middleware('auth:admin')->prefix('admin')->group(function () {
    Route::get('/orders/{orderId}/invoice', [InvoiceController::class, 'downloadAdminInvoice'])->name('admin.orders.invoice');
    Route::get('/orders/{orderId}/invoice/preview', [InvoiceController::class, 'previewInvoice'])->name('admin.orders.invoice.preview');
});