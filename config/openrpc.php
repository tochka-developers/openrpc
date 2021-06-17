<?php

return [
    /** The title of the application */
    'title' => 'Some JsonRpc',
    
    /** Endpoint for OpenRpc schema */
    'endpoint' => '/api/openrpc.json',
    
    /**
     * A verbose description of the application. GitHub Flavored Markdown syntax MAY be used for rich text representation
     * You can use a link to a * .md file with a description (relative to the directory resource/)
     * $views/docs/description.md
     * (for file at resource/views/docs/description.md)
     */
    'description' => 'Description of JsonRpc Server',
    /** The version of the OpenRPC document (which is distinct from the OpenRPC Specification version or the API implementation version) */
    'version' => env('VERSION', '1.0.0'),
    /** A URL to the Terms of Service for the API. MUST be in the format of a URL */
    'termsOfService' => 'http://terms.com',
    /** The contact information for the exposed API */
    'contact' => [
        /** The email address of the contact person/organization. MUST be in the format of an email address */
        'email' => 'contact@mail.com',
        /** The identifying name of the contact person/organization */
        'name' => 'Ivan Ivanov',
        /** The URL pointing to the contact information. MUST be in the format of a URL */
        'url' => 'http://mysite.com',
    ],
    /** The license information for the exposed API */
    'license' => [
        /** The license name used for the API */
        'name' => 'MIT',
        /** A URL to the license used for the API. MUST be in the format of a URL */
        'url' => 'http://mit.com'
    ],
    /** Additional external documentation */
    'externalDocumentation' => [
        /** The URL for the target documentation. Value MUST be in the format of a URL */
        'url' => 'https://google.com',
        /** A verbose explanation of the target documentation. GitHub Flavored Markdown syntax MAY be used for rich text representation */
        'description' => 'Description of external documentation',
    ]
];
