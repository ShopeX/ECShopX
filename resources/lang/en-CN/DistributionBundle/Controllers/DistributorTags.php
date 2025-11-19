<?php

// Validation Messages
$validation = [
    'tag_name_required' => 'Tag name cannot be empty',
    'tag_color_required' => 'Tag color is required',
    'font_color_required' => 'Tag font color is required',
    'front_show_invalid' => 'Front display type error',
    'page_required' => 'Page is required',
    'pagesize_required' => 'PageSize is required',
    'distributor_id_required' => 'Store ID is required',
    'distributor_ids_required' => 'Store IDs cannot be empty',
    'tag_ids_required' => 'Tag IDs cannot be empty',
    'tag_id_required' => 'Tag ID cannot be empty',
];

// Error Messages
$error = [
    'select_distributor_first' => 'Please select a store',
    'select_tag_first' => 'Please select a tag',
];

return array_merge($validation, $error); 