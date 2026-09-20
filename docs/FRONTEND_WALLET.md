# Wallet API - Frontend Integration

Base URL examples below use /api. Replace it with the deployed API base URL.

## Authentication

All wallet endpoints except the payment callback require the logged-in user's Sanctum token:

    Authorization: Bearer <sanctum-token>
    Accept: application/json
    Content-Type: application/json

Amounts are integer Iranian tomans. Wallet charge validation accepts values from 10,000 to 50,000,000 tomans.

## Get balance

    GET /api/wallet/balance

Response:

    {
      "success": true,
      "data": {
        "wallet_balance": 250000
      }
    }

Refresh the balance after every successful charge or order payment. Do not make payment decisions from a stale client-side balance.

## Charge the wallet

    POST /api/wallet/charge

Body:

    {
      "amount": 500000,
      "linking_url": "myapp://wallet-result"
    }

Successful response:

    {
      "success": true,
      "message": "Payment link created.",
      "data": {
        "payment_url": "https://gateway.example/...",
        "transaction_id": 123,
        "amount": 500000
      }
    }

Frontend flow:

1. Call /wallet/charge.
2. Open data.payment_url in the browser or in-app browser.
3. The backend verifies the Zibal callback and increases the balance only after successful verification.
4. Return to the app using linking_url.
5. Refresh /wallet/balance and /wallet/transactions.

Do not increase the balance locally just because the payment page opened. The callback is a redirect and does not return the normal JSON API response.

## Transaction history

    GET /api/wallet/transactions?from_date=2026-01-01&to_date=2026-01-31&per_page=20

Response shape:

    {
      "success": true,
      "data": {
        "transactions": [
          {
            "id": 123,
            "user_id": 82,
            "order_id": null,
            "price": "500000.00",
            "referenceId": "123456",
            "type": 1,
            "status": 100,
            "description": "Wallet charge",
            "linking_url": "myapp://wallet-result",
            "created_at": "2026-09-19T10:00:00.000000Z",
            "updated_at": "2026-09-19T10:01:00.000000Z"
          }
        ],
        "pagination": {},
        "filters": {
          "from_date": "2026-01-01",
          "to_date": "2026-01-31"
        }
      }
    }

Transaction types:

    1 = Wallet recharge
    2 = Order payment through gateway
    3 = Order payment from wallet

Transaction statuses:

    0    Pending
    100  Successful
    -200 Failed

The backend accepts per_page, but currently returns the collection without real pagination metadata. The frontend should support the current array response and should not depend on populated pagination fields.

## Pay an order from the wallet

    POST /api/wallet/pay-order

Body:

    {
      "orderId": 123
    }

Successful response (HTTP 201):

    {
      "success": true,
      "message": "Order paid successfully.",
      "data": {
        "order_id": 123,
        "paid_amount": 450000,
        "remaining_balance": 50000,
        "transaction_id": 456
      }
    }

The backend recalculates the payable amount from the order. Referral discounts and normal discount codes are included when applicable.

Common errors:

    404 ORDER_NOT_FOUND       Order does not exist or does not belong to the user
    409 ALREADY_PAID          Order has already been paid
    402 INSUFFICIENT_BALANCE  Wallet balance is not enough
    400 INVALID_PRICE         Order price is invalid
    400 PAYMENT_ERROR         Payment could not be completed

## Order payment through gateway

This is separate from charging the wallet:

    POST /api/orders/gateway-payment

Body:

    {
      "order_id": 123,
      "linking_url": "myapp://order-payment-result"
    }

The response contains a gateway payment_url. Open it, then refresh the order after returning to the app. The order gateway callback is handled server-side.

