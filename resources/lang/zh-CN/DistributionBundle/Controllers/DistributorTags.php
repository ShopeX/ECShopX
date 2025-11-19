<?php

// 验证消息
$validation = [
    'tag_name_required' => '标签名称不能为空',
    'tag_color_required' => '标签颜色',
    'font_color_required' => '标签字体颜色',
    'front_show_invalid' => '前台显示类型错误',
    'page_required' => 'page 必填',
    'pagesize_required' => 'pageSize 必填',
    'distributor_id_required' => '店铺id必传',
    'distributor_ids_required' => '店铺ids不能为空',
    'tag_ids_required' => 'tag_ids不能为空',
    'tag_id_required' => 'tag_id不能为空',
];

// 错误消息
$error = [
    'select_distributor_first' => '请选择店铺',
    'select_tag_first' => '请选择标签',
];

return array_merge($validation, $error); 