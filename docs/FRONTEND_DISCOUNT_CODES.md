# Discount Codes - Frontend Integration

## Concept

The discount system has two entities:

- Club / discount plan: the offer configured by an administrator.
- Discount code: a personal code generated for a user after claiming a plan with gems.

A discount code belongs to the authenticated user who claimed it. It has a percentage, usage count, expiry date, club/category, and optional maximum discount amount.

All discount endpoints are authenticated with Sanctum:

    Authorization: Bearer <sanctum-token>
    Accept: application/json
    Content-Type: application/json

## List available offers

    GET /api/discounts/offers

Returns weekly clubs/offers. The current controller returns the data collection directly, not a success/data wrapper.

## List discount categories

    GET /api/discounts/categories

Returns categories that have discount plans. The response is currently a direct array/collection.

## List all discount plans

    GET /api/discounts/list

Typical plan fields:

    {
      "id": 1,
      "category_id": 10,
      "title": "Summer offer",
      "gems": 100,
      "count": 2,
      "expire": 30,
      "discount_percent": 10,
      "max_price": 200000,
      "expired_at": "2026-10-01T00:00:00.000000Z"
    }

Use id as discountId in detail and claim requests.

## Get plan details

    POST /api/discounts/detail

Body:

    {
      "discountId": 1
    }

Successful response:

    {
      "success": true,
      "data": {
        "id": 1,
        "title": "Summer offer",
        "discount_percent": 10,
        "gems": 100,
        "count": 2,
        "expire": 30,
        "max_price": 200000
      }
    }

## Claim/generate a code

    POST /api/discounts/claim

Body:

    {
      "discountId": 1
    }

Successful response:

    {
      "success": true,
      "message": "Discount code created.",
      "data": {
        "code": "ABC-123456",
        "discount_percent": 10,
        "expiry_date": "2026-10-19 12:00:00",
        "remaining_gems": 350
      }
    }

Claim behavior:

- The user's gem balance must be at least the plan's gems value.
- A user cannot claim the same plan more than once.
- The generated code is personal to the current user.
- The code receives the plan's percentage, usage count, and expiry date.

Common errors:

    404 INVALID_DISCOUNT_ID  Plan does not exist
    409 ALREADY_CLAIMED      User already claimed this plan
    403 INSUFFICIENT_GEMS    User does not have enough gems

## List the current user's codes

    GET /api/user/discounts

The current endpoint returns the user's discount collection directly. A code can include code, discount_percent, count, expiry_date, club, and discount_use relationship data.

Recommended UI states:

    usable  = count > 0 AND expiry_date is in the future
    used_up = count <= 0
    expired = expiry_date is in the past

## Validate a code before order submission

There are two validation endpoints with different field names.

### Generic discount endpoint

    POST /api/discounts/check

Body:

    {
      "discountCode": "ABC-123456",
      "categoryId": 10
    }

Successful response:

    {
      "message": "Discount applied successfully",
      "discount_code_percent": 10
    }

An invalid code returns HTTP 409 with a message. This endpoint does not consume the code.

### Order-specific endpoint

    POST /api/orders/check-discount

Body:

    {
      "discount_code": "ABC-123456",
      "category_id": 10
    }

Successful response:

    {
      "success": true,
      "message": "Discount applied successfully",
      "data": {
        "discount_percent": 10,
        "discount_code_id": 55
      }
    }

## Send the code with the order

Add this optional field to the existing order submission body:

    POST /api/orders/submit

    {
      "address_id": 12,
      "category_id": 10,
      "total_price": 1000000,
      "date": "2026-09-25",
      "time": "10:00",
      "platform": "android",
      "discount_code": "ABC-123456"
    }

The final discount is recalculated server-side from the order total. Never calculate the payable amount only on the client.

The discount is based on the base order price plus extra services and is limited by the plan's max_price when configured. The code's usage count is reduced when the order is submitted.

## Recommended frontend flow

1. Load plans from /discounts/list.
2. Show details and required gems.
3. Call /discounts/claim after the user confirms.
4. Store and display the returned code.
5. On the order screen, call /orders/check-discount for immediate feedback.
6. Send the same code as discount_code in /orders/submit.
7. Use the order/payment response from the backend as the source of truth.

