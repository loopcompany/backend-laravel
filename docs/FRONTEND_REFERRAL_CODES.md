# Referral Codes - Frontend Integration

## Concept

Referral codes are different from personal discount codes:

- An administrator creates and assigns a readable code to a referrer user in the admin panel.
- The invited/authenticated user enters that code in the mobile app.
- The code can have an administrator-configured discount percentage.
- The percentage is applied to the order of the user who consumes the code.

Referral code statuses:

    active = usable
    sent   = usable
    used   = already consumed

## Authentication

Referral code consumption requires the authenticated user's Sanctum token:

    Authorization: Bearer <sanctum-token>
    Accept: application/json
    Content-Type: application/json

The token identifies the person consuming the code. The code's assigned user is the referrer and is returned in data.referrer.

## Check and consume a code

    POST /api/referral-codes/check

Body:

    {
      "code": "LOOP-AB7K92"
    }

Successful response:

    {
      "success": true,
      "valid": true,
      "message": "Referral code registered successfully.",
      "data": {
        "code": "LOOP-AB7K92",
        "status": "used",
        "status_label": "Used",
        "discount_percent": 10,
        "source": "managed_referral_code",
        "referrer": {
          "id": 82,
          "name": "Referrer",
          "last_name": "User"
        }
      }
    }

Important: this endpoint consumes the code and changes its status to used. The app must still send the same code in the final order request so the order stores the referral discount.

Calling the endpoint again with the same authenticated user is idempotent and returns the code as valid. A different user receives REFERRAL_CODE_USED.

Possible errors:

    401 Unauthenticated              Missing/invalid Sanctum token
    200 REFERRAL_CODE_NOT_FOUND      Code does not exist
    200 REFERRAL_CODE_OWNER_NOT_FOUND Code has no valid referrer
    200 REFERRAL_CODE_USED            Code was consumed by another user

The current controller returns these business errors as JSON with HTTP 200. The frontend must check both success and error_code, not only the HTTP status.

## Submit an order with a referral code

Add the optional field below to the existing order request:

    POST /api/orders/submit
    Authorization: Bearer <sanctum-token>

    {
      "address_id": 12,
      "category_id": 10,
      "total_price": 1000000,
      "date": "2026-09-25",
      "time": "10:00",
      "platform": "android",
      "referral_code": "LOOP-AB7K92"
    }

The backend stores:

    referral_code_id
    referral_discount_percent

If the code was already consumed by the same user through /referral-codes/check, it remains valid for that user. If the code is invalid or belongs to another consumer, the order response is:

    {
      "success": false,
      "message": "Referral code is invalid.",
      "error_code": "INVALID_REFERRAL_CODE"
    }

The HTTP status for an invalid referral code during order submission is 409.

## Discount calculation

The referral percentage is calculated server-side from the order's final base price plus extra services. Example:

    base price       = 1,000,000
    extra services   =   200,000
    order total      = 1,200,000
    referral percent = 10%
    discount         =   120,000

The final amount can change later if the technician price or extra services change. The frontend must use the amount returned by payment/order endpoints and must not rely only on the initial total_price calculation.

The referral discount is separate from a normal discount_code. Both can be sent and the backend calculates them separately.

## Recommended mobile flow

1. User enters a referral code.
2. Call /api/referral-codes/check with the user's token.
3. If success is true, display discount_percent and referrer information.
4. Keep the normalized returned data.code in the order form state.
5. Send it as referral_code in /api/orders/submit.
6. If the order is retried by the same user, send the same code; do not silently replace it.

Do not treat a successful referral check as a completed order. It consumes the code for that authenticated user; the discount is attached to an order only when the order request includes referral_code.

