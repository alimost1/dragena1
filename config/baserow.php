<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Baserow API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Baserow API integration
    |
    */

    'base_url' => env('BASEROW_BASE_URL', 'https://api.baserow.io'),
    
    'token' => env('BASEROW_TOKEN'),
    
    'table_id' => env('BASEROW_TABLE_ID'),
    
    'video_url_column' => env('BASEROW_VIDEO_URL_COLUMN', 'Final Video URL'),
    
    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */
    
    'timeout' => env('BASEROW_TIMEOUT', 30),
    
    'retry_attempts' => env('BASEROW_RETRY_ATTEMPTS', 3),
    
    'page_size' => env('BASEROW_PAGE_SIZE', 200),
];