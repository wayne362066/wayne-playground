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
        'description' => '透過引導式問答與三張牌，整理此刻值得留意的方向。',
        'icon' => 'sparkles',
        'route' => '/tarot',
        'enabled' => true,
        'status' => 'active',
        'sort_order' => 2,
    ],
    [
        'key' => 'lab',
        'name' => 'Lab',
        'description' => '各種開發實驗與測試功能的練習場。',
        'icon' => 'flask',
        'route' => '/lab',
        'enabled' => false,
        'status' => 'disabled',
        'sort_order' => 3,
    ],
    [
        'key' => 'wishes',
        'name' => '許願板',
        'description' => '記下希望 Playground 未來出現的功能與點子。',
        'icon' => 'wish',
        'route' => '/wishes',
        'enabled' => true,
        'status' => 'active',
        'sort_order' => 4,
    ],
];
