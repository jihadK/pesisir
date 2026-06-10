<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    | Nomor WhatsApp admin untuk customer portal (format internasional, tanpa +).
    | Contoh: 6281234567890
    */
    'portal_admin_wa' => env('PORTAL_ADMIN_WA', ''),

    /*
    | Nomor HP toko untuk kop kuitansi/invoice. Bisa format apa saja (62.../0.../+62...).
    | Template akan otomatis menormalkan jadi format 08xx... saat ditampilkan.
    | Kalau STORE_PHONE kosong, fallback ke PORTAL_ADMIN_WA.
    */
    'store_phone' => env('STORE_PHONE', env('PORTAL_ADMIN_WA', '')),

    /*
    | Koordinat lokasi toko (decimal degrees). Dipakai untuk pin presisi di
    | Google Maps + geo schema (LocalBusiness JSON-LD). Default = WH-LAMONGAN.
    */
    'store_lat' => env('STORE_LAT', -7.123056),
    'store_lng' => env('STORE_LNG', 112.380591),

    /*
    | Link sosial media untuk customer portal (banner hero).
    | Kosongkan kalau tidak ingin ditampilkan.
    */
    'portal_social' => [
        'whatsapp'  => env('PORTAL_WA_URL', ''),        // contoh: https://wa.me/6281234567890
        'tiktok'    => env('PORTAL_TIKTOK_URL', ''),    // contoh: https://www.tiktok.com/@pesisirfreshfish
        'instagram' => env('PORTAL_INSTAGRAM_URL', ''), // contoh: https://instagram.com/pesisirfreshfish
        'facebook'  => env('PORTAL_FACEBOOK_URL', ''), // contoh: https://facebook.com/pesisirfreshfish
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
