<?php
return [
    'equipment' => [
        'upload_dir' => __DIR__ . '/../public/uploads/equipment/',
        'max_file_size' => 5242880, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'thumbnails' => [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
            'large' => ['width' => 1200, 'height' => 1200, 'crop' => false],
        ]
    ],
    'generic' => [
        'upload_dir' => __DIR__ . '/../public/uploads/',
        'max_file_size' => 10485760, // 10MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]
    ]
];