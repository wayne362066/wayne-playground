<?php

return [
    'permissions' => [
        ['key' => 'access.manage', 'module' => 'access', 'name' => '管理角色與成員'],
        ['key' => 'access.audit.view', 'module' => 'access', 'name' => '檢視權限異動紀錄'],
        ['key' => 'modules.lottery.view', 'module' => 'lottery', 'name' => '檢視威力彩模組'],
        ['key' => 'lottery.generate', 'module' => 'lottery', 'name' => '產生威力彩號碼'],
        ['key' => 'lottery.simulate', 'module' => 'lottery', 'name' => '執行威力彩模擬'],
        ['key' => 'modules.tarot.view', 'module' => 'tarot', 'name' => '檢視塔羅模組'],
        ['key' => 'tarot.read', 'module' => 'tarot', 'name' => '進行塔羅解讀'],
        ['key' => 'modules.wishes.view', 'module' => 'wishes', 'name' => '檢視許願板模組'],
        ['key' => 'wishes.view', 'module' => 'wishes', 'name' => '檢視公開願望'],
        ['key' => 'wishes.create', 'module' => 'wishes', 'name' => '新增願望'],
        ['key' => 'wishes.manage.view', 'module' => 'wishes', 'name' => '檢視願望管理頁'],
        ['key' => 'wishes.update', 'module' => 'wishes', 'name' => '編輯願望內容'],
        ['key' => 'wishes.status.update', 'module' => 'wishes', 'name' => '更新願望狀態'],
        ['key' => 'wishes.moderate', 'module' => 'wishes', 'name' => '審核及調整願望可見性'],
        ['key' => 'wishes.archive', 'module' => 'wishes', 'name' => '封存願望'],
        ['key' => 'wishes.restore', 'module' => 'wishes', 'name' => '恢復願望'],
        ['key' => 'wishes.history.view', 'module' => 'wishes', 'name' => '檢視願望變更紀錄'],
    ],

    'roles' => [
        'guest' => [
            'name' => '訪客',
            'description' => '尚未登入的公開使用者。',
            'permissions' => [
                'modules.lottery.view',
                'lottery.generate',
                'lottery.simulate',
                'modules.tarot.view',
                'tarot.read',
                'modules.wishes.view',
                'wishes.view',
                'wishes.create',
            ],
        ],
        'member' => [
            'name' => '一般會員',
            'description' => '登入後的一般使用者。',
            'permissions' => [
                'modules.lottery.view',
                'lottery.generate',
                'lottery.simulate',
                'modules.tarot.view',
                'tarot.read',
                'modules.wishes.view',
                'wishes.view',
                'wishes.create',
            ],
        ],
        'admin' => [
            'name' => '管理員',
            'description' => '依勾選的權限管理系統與內容。',
            'permissions' => '*',
        ],
    ],
];
