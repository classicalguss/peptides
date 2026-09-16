<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | When disabled, checkout falls through to the default Lunar payment driver
    | (offline) exactly as before. Nothing about this integration is reachable
    | until this is switched on, so the storefront is safe to deploy with the
    | code in place but the flag off.
    |
    */
    'enabled' => env('VERIFIED_CRYPTO_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Partner API
    |--------------------------------------------------------------------------
    */
    'api_base' => env('VERIFIED_CRYPTO_API_BASE', 'https://partnerapi.verifiedcryptocheckout.com'),

    'partner_id' => env('VERIFIED_CRYPTO_PARTNER_ID', 'nanochecks'),

    /*
    |--------------------------------------------------------------------------
    | Settlement Wallet
    |--------------------------------------------------------------------------
    |
    | The Polygon (chain 137) address USDC settles to. This doubles as the
    | merchant identifier — the partner API has no API key, so this address is
    | the only thing tying a session to us. It is final and irreversible: USDC
    | sent to the wrong address cannot be recovered.
    |
    */
    'wallet_address' => env('VERIFIED_CRYPTO_WALLET_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | Provider Routing
    |--------------------------------------------------------------------------
    |
    | Null lets VERIFIED auto-route by the customer's region. Set to a hint
    | such as "moonpay", "ramp", "transak" or "stripe" to pin one provider.
    |
    */
    'provider' => env('VERIFIED_CRYPTO_PROVIDER'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Shared secret sent as `webhook_secret` on session creation, used to verify
    | the X-VCC-Signature on inbound callbacks. Callbacks are the authoritative
    | payment signal, so an unsigned or badly signed callback is rejected.
    |
    */
    'webhook_secret' => env('VERIFIED_CRYPTO_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Signature Tolerance
    |--------------------------------------------------------------------------
    |
    | Maximum age, in seconds, of the X-VCC-Timestamp on an inbound callback.
    | Anything older is treated as a replay.
    |
    */
    'signature_tolerance' => (int) env('VERIFIED_CRYPTO_SIGNATURE_TOLERANCE', 300),

    /*
    |--------------------------------------------------------------------------
    | Log Raw Callbacks
    |--------------------------------------------------------------------------
    |
    | Logs the raw body and headers of every inbound callback before the
    | signature is checked. VERIFIED do not publish the string their signature
    | is computed over, so this is how the format gets confirmed on the first
    | live payment. Turn it off once settled — callback bodies contain customer
    | email addresses.
    |
    */
    'log_callbacks' => env('VERIFIED_CRYPTO_LOG_CALLBACKS', false),

    /*
    |--------------------------------------------------------------------------
    | Minimum Order Total
    |--------------------------------------------------------------------------
    |
    | Card-to-crypto on-ramp providers enforce their own minimums — VERIFIED's
    | hosted page limits provider options below $20, and some providers floor
    | higher. Orders below this integer-cent total are rejected at checkout so a
    | customer never reaches a hosted page that cannot process their amount.
    | Set to 0 to disable the check. Default: $30.00.
    |
    */
    'minimum_order' => (int) env('VERIFIED_CRYPTO_MINIMUM_ORDER', 3000),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('VERIFIED_CRYPTO_TIMEOUT', 20),

];
