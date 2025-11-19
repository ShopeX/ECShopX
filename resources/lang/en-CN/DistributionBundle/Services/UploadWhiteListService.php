<?php

// Field Titles
$fields = [
    'mobile' => 'Mobile',
    'username' => 'Name',
    'distributor_no' => 'Store Code',
];

// Error Messages
$error = [
    'excel_format_only' => 'Only Excel file format is supported for employee purchase activity products',
    'distributor_not_found' => 'Error, store not found',
];

return array_merge($fields, $error); 