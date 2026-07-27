<?php

return [
    'roles' => [
        'guest' => [
            'name' => '訪客',
            'description' => '尚未登入的公開使用者。',
        ],
        'member' => [
            'name' => '一般會員',
            'description' => '登入後的一般使用者。',
        ],
        'admin' => [
            'name' => '管理員',
            'description' => '依勾選的權限管理系統與內容。',
        ],
    ],
];
