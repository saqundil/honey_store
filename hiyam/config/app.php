<?php

declare(strict_types=1);

return [
    'name' => 'نظام جداول تقييم الطلاب',
    'base_url' => rtrim(getenv('APP_URL') ?: 'http://localhost/hiyam', '/'),
    'session_name' => 'student_assessment_admin',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Amman',
    'school' => [
        'name_ar' => 'مجموعة مدارس الجامعة',
        'name_en' => "Al-Jami'a Schools Group",
        'logo' => 'assets/images/school-logo.png',
    ],
];