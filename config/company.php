<?php

return [
    'name' => env('COMPANY_NAME', 'Qualities Uniforms & Gifts Solutions'),
    'phone' => env('COMPANY_PHONE', '+97455221178'),
    'email' => env('COMPANY_EMAIL', 'info@qualitiesqatar.com'),
    'website' => env('COMPANY_WEBSITE', 'qualitiesqatar.com'),

    // Set these only after the client supplies the confirmed document details.
    'address' => env('COMPANY_ADDRESS'),
    'registration_number' => env('COMPANY_REGISTRATION_NUMBER'),
    // Relative to public/, for example branding/company-logo.png (PNG or JPEG).
    'logo_path' => env('COMPANY_LOGO_PATH', 'branding/qualities-logo.png'),
    'quotation_terms' => env('COMPANY_QUOTATION_TERMS'),
    'invoice_terms' => env('COMPANY_INVOICE_TERMS'),
];
