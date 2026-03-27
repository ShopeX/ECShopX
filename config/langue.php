<?php

/**
 * 多语言配置
 * - list: 支持的语言列表
 * - default: 默认语言，须为 list 中的一项
 * 兼容：若仍按纯数组读取，请使用 config('langue.list') 或由 CommonLangModService 解析。
 */
return [
    'list'    => ['zh-CN', 'en-CN', 'ar-SA'],
    'default' => 'zh-CN',
];
