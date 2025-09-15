<?php
/**
 * Configuración central del sistema - Clínica Bonsana
 * Sistema de Gestión de Documentos
 */

return [
    // Configuración de usuarios
    'users' => [
        'lector'     => ['password' => '1234', 'role' => 'lectura'],
        'cargas'     => ['password' => 'subir', 'role' => 'subir'],
        'superadmin' => ['password' => 'admin', 'role' => 'super']
    ],

    // Configuración de bases de documentos
    'document_bases' => [
        'docs' => [
            'name' => 'Documentos Antiguos',
            'path' => 'F:\DOCUMENTOSSCANEADOSANTIGUOS'
        ],
        'DOCUMENTOSSCANEADOS' => [
            'name' => 'DOCUMENTOSSCANEADOS',
            'path' => 'F:\DOCUMENTOSSCANEADOS\documentos escaneados'
        ],
        'SCANNER' => [
            'name' => 'SCANNER',
            'path' => 'F:\SCANNER'
        ],
    ],

    // Configuración de la aplicación
    'app' => [
        'name' => 'Sistema de Gestión de Documentos',
        'clinic_name' => 'Clínica Bonsana',
        'clinic_subtitle' => 'Clínica de fracturas',
        'logo_path' => 'source/logo.png',
        'max_upload_size' => '50M',
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    ],

    // Configuración de seguridad
    'security' => [
        'session_timeout' => 3600, // 1 hora
        'max_login_attempts' => 5,
        'allowed_filename_chars' => '/[^A-Za-z0-9\-_\. ()]/',
    ]
];