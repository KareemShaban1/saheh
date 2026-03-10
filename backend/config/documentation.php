<?php

/*
|--------------------------------------------------------------------------
| Documentation Website Configuration
|--------------------------------------------------------------------------
|
| Access the documentation at: /docs
| Set DOCS_PASSWORD in .env (leave empty for no password protection)
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Documentation Password
    |--------------------------------------------------------------------------
    |
    | Password required to access the documentation website.
    | Set via DOCS_PASSWORD in your .env file.
    |
    */
    'password' => env('DOCS_PASSWORD', 'docs123'),

    /*
    |--------------------------------------------------------------------------
    | Documentation Base Path
    |--------------------------------------------------------------------------
    |
    | The directory containing markdown documentation files.
    |
    */
    'path' => base_path('docs'),

    /*
    |--------------------------------------------------------------------------
    | Visible Files
    |--------------------------------------------------------------------------
    |
    | Control which markdown files appear in the documentation website.
    |
    | Options:
    | - 'all' => Show all .md files in the docs directory
    | - ['file1.md', 'file2.md'] => Show only these specific files (in this order)
    |
    */
    'visible_files' => [
        // Format: 'filename.md' => 'Custom Display Name' or just 'filename.md' (auto-derived title)
        // Use 'all' as single entry to show every .md file in the docs directory
        'SOFTWARE_REQUIREMENTS_SPECIFICATION.md' => 'Software Requirements Specification',
        'QUICK_START.md',
        'INSTALLATION_FIXED.md',
        'SETUP_CHECKLIST.md',
        'LANDING_PAGE_CREATION_SUMMARY.md',
        'CONVERSION_SUMMARY.md',
        'START_SERVERS.md',
        'mobile_ui_ux_quick_prompts.md',
        // Use 'all' to show every .md file in the docs directory
        // 'all',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Document
    |--------------------------------------------------------------------------
    |
    | The default document to show when visiting /docs (without a file).
    |
    */
    'default_document' => 'SOFTWARE_REQUIREMENTS_SPECIFICATION.md',

];
