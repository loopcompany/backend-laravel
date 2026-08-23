<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    /* Important Settings */

    // ======================================================================
    // never remove 'web', just put your middleware like auth or admin (if you have) here. eg: ['web','auth']
    'middlewares' => ['web'],
    // you can change default route from sms-admin to anything you want
    'route' => 'sms-admin',
    // SMS.ir Api Key
    'api-key' => env('SMSIR_API_KEY','Your api key'),
    // Your sms.ir line number
    'line-number' => env('SMSIR_LINE_NUMBER','Your Sms.ir Line Number'),
    // ======================================================================
    // set true if you want log to the database
    'db-log' => env('SMSIR_DB_LOG',false),
    // if you don't want to include admin panel routes set this to false
    'panel-routes' => env('SMSIR_PANEL_ROUTES',true),
    /* Admin Panel Title */
    'title' => 'مدیریت پیامک ها',
    // How many log you want to show in sms-admin panel ?
    'in-page' => env('SMSIR_PANEL_IN_PAGE',true),
    
    // Template IDs for different SMS types
    'verification_template_id' => env('SMSIR_VERIFICATION_TEMPLATE_ID', 100000),
    'forgot_password_template_id' => env('SMSIR_FORGOT_PASSWORD_TEMPLATE_ID', 100000),
    'secure_password_template_id' => env('SMSIR_SECURE_PASSWORD_TEMPLATE_ID', 100000),
    'user_code_template_id' =>  744863,
    'delivery_verification_template_id' => env('SMSIR_DELIVERY_VERIFICATION_TEMPLATE_ID', 100000),
    
    // Order Cancellation Templates
    'order_cancelled_by_admin_to_technician_template_id' => env('SMSIR_ORDER_CANCELLED_BY_ADMIN_TO_TECHNICIAN_TEMPLATE_ID', 482506),
    'order_cancelled_by_admin_to_user_template_id' => env('SMSIR_ORDER_CANCELLED_BY_ADMIN_TO_USER_TEMPLATE_ID', 227977),
    'order_cancelled_by_technician_to_user_template_id' => env('SMSIR_ORDER_CANCELLED_BY_TECHNICIAN_TO_USER_TEMPLATE_ID', 684622),
    'order_cancelled_by_technician_to_admin_template_id' => env('SMSIR_ORDER_CANCELLED_BY_TECHNICIAN_TO_ADMIN_TEMPLATE_ID', 435317),
    'order_done_in_place_by_technician_to_admin_template_id' => 240604,
    
    // Order Management Templates
    'urgent_request_by_technician_to_admin_template_id' => env('SMSIR_URGENT_REQUEST_BY_TECHNICIAN_TO_ADMIN_TEMPLATE_ID', 333169),
    'time_change_or_description_by_technician_to_user_template_id' => env('SMSIR_TIME_CHANGE_OR_DESCRIPTION_BY_TECHNICIAN_TO_USER_TEMPLATE_ID', 575053),
    'order_assigned_by_admin_to_technician_template_id' => env('SMSIR_ORDER_ASSIGNED_BY_ADMIN_TO_TECHNICIAN_TEMPLATE_ID', 797207),
    
    // Order Report Templates
    'order_report_approved_by_user_to_technician_template_id' => env('SMSIR_ORDER_REPORT_APPROVED_BY_USER_TO_TECHNICIAN_TEMPLATE_ID', 807351),
    'product_sent_to_loop_to_user_template_id' => env('SMSIR_PRODUCT_SENT_TO_LOOP_TO_USER_TEMPLATE_ID', 693742),
    'order_report_approved_by_user_to_self_template_id' => env('SMSIR_ORDER_REPORT_APPROVED_BY_USER_TO_SELF_TEMPLATE_ID', 426460),
    
    // Technician Location Templates
    'technician_set_off_to_user_template_id' => env('SMSIR_TECHNICIAN_SET_OFF_TO_USER_TEMPLATE_ID', 607931),
    'technician_arrived_to_user_template_id' => env('SMSIR_TECHNICIAN_ARRIVED_TO_USER_TEMPLATE_ID', 395978),
    
    // Organization Templates
    'organization_welcome_template_id' => env('SMSIR_ORGANIZATION_WELCOME_TEMPLATE_ID', 537149),
    
    // Product Status Templates
    'product_status_submitted_by_technician_to_user_template_id' => env('SMSIR_PRODUCT_STATUS_SUBMITTED_BY_TECHNICIAN_TO_USER_TEMPLATE_ID', 335842),
];
