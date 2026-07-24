<?php

return [
    [
        'key' => 'lottery',
        'name' => '威力彩模擬器',
        'description' => '模擬單期開獎、單期獲利與中頭獎所需期數。',
        'icon' => 'dice',
        'route' => '/lottery',
        'enabled' => true,
        'status' => 'active',
        'sort_order' => 1,
    ],
    [
        'key' => 'tarot',
        'name' => '塔羅',
        'description' => '單張牌、三張牌與解讀功能規劃中。',
        'icon' => 'sparkles',
        'route' => '/tarot',
        'enabled' => true,
        'status' => 'coming_soon',
        'sort_order' => 2,
    ],
    [
        'key' => 'lab',
        'name' => 'Lab',
        'description' => '各種開發實驗與測試功能的練習場。',
        'icon' => 'flask',
        'route' => '/lab',
        'enabled' => true,
        'status' => 'active',
        'sort_order' => 3,
    ],
];
