# Zendo API Documentation

This document describes the HTTP APIs used by the mobile/front-end applications.

## Base URL

- **Base:** `{APP_URL}/api`
- Example: `https://your-domain.com/api`

## Authentication

Most endpoints require an access token.

- **Header:** `Authorization: Bearer {token}`
- **Content-Type:** `application/json`

Example headers:

```http
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json
Accept: application/json
```

Middleware frequently applied:

- `auth:api`
- `maintenance_mode`

If an endpoint is marked as **Public**, it does not require `Authorization`.

## Common Response Format

Response formats can vary by controller, but typically:

- Success responses return HTTP `200`/`201` with JSON.
- Validation errors return HTTP `422`.
- Unauthorized returns HTTP `401`.
- Forbidden/blocked (maintenance mode or permissions) may return HTTP `403`.

Many controllers use a `responseFormatter(...)` helper. When you receive an error response, always check:

- `response_code`
- `message`
- `errors` (validation)

## Apps

This API is consumed by two mobile/front-end apps:

- **Customer App** (routes prefixed with `/customer/...`)
- **Driver App** (routes prefixed with `/driver/...`)

## Modules / Endpoints

### Auth Management

#### Customer Auth

- **POST** `/customer/auth/registration`
  - **Name:** `customer-registration`
  - **Auth:** Public

Example request (typical):

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "2557XXXXXXXX",
  "password": "********",
  "referral_code": "OPTIONAL"
}
```

- **POST** `/customer/auth/login`
  - **Name:** `customer-login`
  - **Auth:** Public

Example request:

```json
{
  "phone": "2557XXXXXXXX",
  "password": "********"
}
```

Notes:

- This endpoint may respond with HTTP `202` if phone verification is required.
- On success it returns authentication data from `authenticate(...)`.

- **POST** `/customer/auth/social-login`
  - **Auth:** Public

- **POST** `/customer/auth/otp-login`
  - **Auth:** Public

- **POST** `/customer/auth/check`
  - **Auth:** Public

- **POST** `/customer/auth/forget-password`
  - **Auth:** Public

- **POST** `/customer/auth/reset-password`
  - **Auth:** Public

- **POST** `/customer/auth/otp-verification`
  - **Auth:** Public

- **POST** `/customer/auth/firebase-otp-verification`
  - **Auth:** Public

- **POST** `/customer/auth/send-otp`
  - **Auth:** Public

- **POST** `/customer/auth/external-registration`
  - **Auth:** Public

- **POST** `/customer/auth/external-login`
  - **Auth:** Public

#### Customer Update

- **PUT** `/customer/update/fcm-token`
  - **Auth:** Bearer token

#### Driver Auth

- **POST** `/driver/auth/registration`
  - **Name:** `driver-registration`
  - **Auth:** Public

- **POST** `/driver/auth/login`
  - **Name:** `driver-login`
  - **Auth:** Public

Example request:

```json
{
  "phone": "2557XXXXXXXX",
  "password": "********"
}
```

- **POST** `/driver/auth/send-otp`
  - **Auth:** Public

- **POST** `/driver/auth/check`
  - **Auth:** Public

- **POST** `/driver/auth/forget-password`
  - **Auth:** Public

- **POST** `/driver/auth/reset-password`
  - **Auth:** Public

- **POST** `/driver/auth/otp-verification`
  - **Auth:** Public

- **POST** `/driver/auth/firebase-otp-verification`
  - **Auth:** Public

#### Driver Update

- **PUT** `/driver/update/fcm-token`
  - **Auth:** Bearer token

#### User Session

- **POST** `/user/logout`
  - **Name:** `logout`
  - **Auth:** Bearer token

- **POST** `/user/delete`
  - **Name:** `delete`
  - **Auth:** Bearer token

- **POST** `/user/change-password`
  - **Auth:** Bearer token

---

### Business Management

#### Public

- **GET** `/configurations`
- **GET** `/get-external-configurations`
- **POST** `/store-configurations`

#### Customer

- **GET** `/customer/configuration`
- **GET** `/customer/pages/{page_name}`

Example response (Customer configuration):

```json
{
  "maintenance_mode": false,
  "business_name": "...",
  "base_url": "https://your-domain.com/api/v1/",
  "currency_code": "USD",
  "image_base_url": {
    "profile_image": "https://.../storage/app/public/customer/profile"
  }
}
```

Customer Config APIs:

- **GET** `/customer/config/get-zone-id`
- **GET** `/customer/config/place-api-autocomplete`
- **GET** `/customer/config/distance-api`
- **GET** `/customer/config/place-api-details`
- **GET** `/customer/config/geocode-api`
- **POST** `/customer/config/get-routes`
- **GET** `/customer/config/get-payment-methods`
- **GET** `/customer/config/cancellation-reason-list`
- **GET** `/customer/config/parcel-cancellation-reason-list`
- **GET** `/customer/config/parcel-refund-reason-list`
- **GET** `/customer/config/other-emergency-contact-list`
- **GET** `/customer/config/safety-alert-reason-list`
- **GET** `/customer/config/safety-precaution-list`
- **GET** `/customer/config/calculate-distance`

Customer Location:

- **POST** `/location/save`
  - **Auth:** Bearer token

#### Driver

- **GET** `/driver/configuration`

Driver Config APIs:

- **GET** `/driver/config/get-zone-id`
- **GET** `/driver/config/place-api-autocomplete`
- **GET** `/driver/config/distance-api`
- **GET** `/driver/config/place-api-details`
- **GET** `/driver/config/geocode-api`
- **GET** `/driver/config/get-payment-methods`
- **GET** `/driver/config/cancellation-reason-list`
- **GET** `/driver/config/parcel-cancellation-reason-list`
- **GET** `/driver/config/predefined-question-answer-list`
- **GET** `/driver/config/other-emergency-contact-list`
- **GET** `/driver/config/safety-alert-reason-list`
- **GET** `/driver/config/safety-precaution-list`

Driver Route:

- **POST** `/driver/get-routes`
  - **Auth:** Bearer token

---

### Trip Management (Ride)

#### Customer

- **GET** `/customer/drivers-near-me`

Customer Ride:

- **POST** `/customer/ride/get-estimated-fare`

Example request:

```json
{
  "type": "ride_request",
  "pickup_coordinates": "[LAT, LNG]",
  "destination_coordinates": "[LAT, LNG]",
  "vehicle_category_id": "UUID_OR_ID",
  "intermediate_coordinates": "[[LAT, LNG]]"
}
```

Notes:

- Some endpoints expect `zoneId` via request header.
- **POST** `/customer/ride/create`

Example request (simplified):

```json
{
  "type": "ride_request",
  "pickup_coordinates": "[LAT, LNG]",
  "destination_coordinates": "[LAT, LNG]",
  "customer_coordinates": "[LAT, LNG]",
  "vehicle_category_id": "UUID_OR_ID",
  "estimated_fare": 0,
  "scheduled_at": null
}
```
- **PUT** `/customer/ride/ignore-bidding`
- **GET** `/customer/ride/bidding-list/{trip_request_id}`
- **PUT** `/customer/ride/update-status/{trip_request_id}`
- **GET** `/customer/ride/details/{trip_request_id}`
- **GET** `/customer/ride/list`
- **GET** `/customer/ride/final-fare`
- **POST** `/customer/ride/trip-action`
- **GET** `/customer/ride/ride-resume-status`
- **PUT** `/customer/ride/arrival-time`
- **PUT** `/customer/ride/coordinate-arrival`
- **GET** `/customer/ride/ongoing-parcel-list`
- **GET** `/customer/ride/unpaid-parcel-list`
- **PUT** `/customer/ride/received-returning-parcel/{trip_request_id}`
- **PUT** `/customer/ride/edit-scheduled-trip/{trip_request_id}`
- **GET** `/customer/ride/pending-ride-list`

Tracking & Payment:

- **POST** `/customer/ride/track-location`
- **GET** `/customer/ride/payment`

Payment endpoint implementation validates:

- `trip_request_id` (required)
- `payment_method` in `wallet,cash`

Example request:

```json
{
  "trip_request_id": "UUID_OR_ID",
  "payment_method": "wallet",
  "tips": 0
}
```
- **GET** `/customer/ride/digital-payment`
  - **Auth:** Public (explicitly without `auth:api`)

Customer Parcel Refund:

- **POST** `/customer/parcel/refund/create`

Customer Safety Alert:

- **POST** `/customer/safety-alert/store`
- **PUT** `/customer/safety-alert/resend/{trip_request_id}`
- **PUT** `/customer/safety-alert/mark-as-solved/{trip_request_id}`
- **GET** `/customer/safety-alert/show/{trip_request_id}`
- **DELETE** `/customer/safety-alert/undo/{trip_request_id}`

#### Driver

- **GET** `/driver/last-ride-details`

Driver Ride:

- **GET** `/driver/ride/final-fare`
- **GET** `/driver/ride/payment`
- **GET** `/driver/ride/show-ride-details`
- **GET** `/driver/ride/all-ride-list`
- **PUT** `/driver/ride/ride-waiting`
- **GET** `/driver/ride/list`
- **PUT** `/driver/ride/arrival-time`
- **PUT** `/driver/ride/coordinate-arrival`
- **GET** `/driver/ride/ongoing-parcel-list`
- **GET** `/driver/ride/unpaid-parcel-list`
- **PUT** `/driver/ride/resend-otp`
- **POST** `/driver/ride/match-otp`
- **POST** `/driver/ride/track-location`
- **GET** `/driver/ride/details/{ride_request_id}`
- **GET** `/driver/ride/pending-ride-list`
- **PUT** `/driver/ride/returned-parcel`
- **GET** `/driver/ride/overview`
- **POST** `/driver/ride/ignore-trip-notification`
- **PUT** `/driver/ride/update-status`
- **POST** `/driver/ride/trip-action`
- **POST** `/driver/ride/bid`
- **PUT** `/driver/ride/update-to-out-for-pickup/{tripId}`

Driver Safety Alert:

- **POST** `/driver/safety-alert/store`
- **PUT** `/driver/safety-alert/resend/{trip_request_id}`
- **PUT** `/driver/safety-alert/mark-as-solved/{trip_request_id}`
- **GET** `/driver/safety-alert/show/{trip_request_id}`
- **DELETE** `/driver/safety-alert/undo/{trip_request_id}`

Other:

- **POST** `/ride/store-screenshot`
  - **Auth:** Bearer token

---

### Parcel Management

#### Customer Parcel

- **GET** `/customer/parcel/category`
- **GET** `/customer/parcel/vehicle`

Notes:

- Requires header `zoneId`.
- Uses `weight_id` to determine vehicle eligibility.

Example headers:

```http
zoneId: <ZONE_ID>
Authorization: Bearer <ACCESS_TOKEN>
```
- **GET** `/customer/parcel/suggested-vehicle-category`

---

### Vehicle Management

#### Customer

- **GET** `/customer/vehicle/category/`

#### Driver

- **POST** `/driver/vehicle/store`
- **POST** `/driver/vehicle/update/{id}`
- **GET** `/driver/vehicle/category/list`
- **GET** `/driver/vehicle/brand/list`
- **GET** `/driver/vehicle/model/list`

---

### Chatting Management

#### Customer

- **GET** `/customer/chat/find-channel`
- **PUT** `/customer/chat/create-channel`
- **PUT** `/customer/chat/send-message`
- **GET** `/customer/chat/conversation`
- **GET** `/customer/chat/channel-list`

#### Driver

- **GET** `/driver/chat/find-channel`
- **PUT** `/driver/chat/create-channel`
- **PUT** `/driver/chat/send-message`
- **GET** `/driver/chat/conversation`
- **GET** `/driver/chat/channel-list`
- **PUT** `/driver/chat/create-channel-with-admin`
- **PUT** `/driver/chat/send-message-to-admin`
- **PUT** `/driver/chat/send-predefined-question-to-admin`

---

### Promotion Management

#### Customer

Banner:

- **GET** `/customer/banner/list`
- **POST** `/customer/banner/update-redirection-count`

Coupon:

- **GET** `/customer/coupon/list`
- **POST** `/customer/coupon/apply`

Discount:

- **GET** `/customer/discount/list`

---

### Review Module

#### Customer

- **GET** `/customer/review/list`
- **POST** `/customer/review/store`
- **PUT** `/customer/review/check-submission`

#### Driver

- **GET** `/driver/review/list`
- **POST** `/driver/review/store`
- **PUT** `/driver/review/save/{id}`

---

### Transaction Management

#### Customer

- **GET** `/customer/transaction/list`
- **GET** `/customer/transaction/referral-earning-list`

#### Driver

- **GET** `/driver/transaction/list`
- **GET** `/driver/transaction/referral-earning-list`
- **GET** `/driver/transaction/payable-list`
- **GET** `/driver/transaction/cash-collect-list`
- **GET** `/driver/transaction/wallet-list`

---

### User Management

#### Customer

- **GET** `/customer/loyalty-points/list`
- **POST** `/customer/loyalty-points/convert`
- **GET** `/customer/level/`
- **GET** `/customer/notification-list`
- **PUT** `/customer/update/profile`
- **GET** `/customer/info`
- **POST** `/customer/get-data`
- **POST** `/customer/external-update-data` (Public)
- **POST** `/customer/applied-coupon`
- **POST** `/customer/change-language`
- **GET** `/customer/referral-details`

Customer Address:

- **GET** `/customer/address/all-address`
- **POST** `/customer/address/add`
- **GET** `/customer/address/edit/{id}`
- **PUT** `/customer/address/update`
- **DELETE** `/customer/address/delete`

Customer Wallet:

- **POST** `/customer/wallet/transfer-drivemond-to-mart`
- **POST** `/customer/wallet/transfer-drivemond-from-mart` (Public)
- **GET** `/customer/wallet/bonus-list`
- **GET** `/customer/wallet/add-fund-digitally` (Public)

#### Driver

- **GET** `/driver/time-tracking`
- **POST** `/driver/update-online-status`
- **GET** `/driver/notification-list`

Driver Activity:

- **GET** `/driver/activity/leaderboard`
- **GET** `/driver/activity/daily-income`

Driver Profile:

- **GET** `/driver/my-activity`
- **POST** `/driver/change-language`
- **GET** `/driver/info`
- **GET** `/driver/income-statement`
- **PUT** `/driver/update/profile`
- **GET** `/driver/referral-details`
- **GET** `/driver/pay-digitally` (Public)

Driver Level:

- **GET** `/driver/level/`

Driver Loyalty:

- **GET** `/driver/loyalty-points/list`
- **POST** `/driver/loyalty-points/convert`

Driver Withdraw:

- **GET** `/driver/withdraw/methods`
- **POST** `/driver/withdraw/request`
- **GET** `/driver/withdraw/pending-request`
- **GET** `/driver/withdraw/settled-request`

Withdraw Method Info:

- **GET** `/driver/withdraw-method-info/list`
- **POST** `/driver/withdraw-method-info/create`
- **GET** `/driver/withdraw-method-info/edit/{id}`
- **POST** `/driver/withdraw-method-info/update/{id}`
- **POST** `/driver/withdraw-method-info/delete/{id}`

#### User

Live Location:

- **POST** `/user/store-live-location`
- **POST** `/user/get-live-location`

Notifications:

- **PUT** `/user/read-notification`

---

### Zone Management

#### Driver

- **GET** `/driver/zone/list`

---

### Gateways

- **GET** `/v1/payment-config`

## Developer Details

- Name: Ray Developer
- Phone: +255742710054
