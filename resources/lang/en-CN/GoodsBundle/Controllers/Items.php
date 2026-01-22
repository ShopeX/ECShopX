<?php


$main = [
    'api_1' => 'Incorrect write-off type parameter',
];

// Service
$itemsService = [
    'items_service_1' => 'Please select the correct product status',
    
    // ItemsService exceptions
    'medical_industry_not_enabled' => 'Medical industry function not enabled',
    'missing_prescription_config' => 'Missing prescription business integration configuration',
    'invalid_spec_data' => 'Please fill in correct specification data',
    'non_gift_price_must_be_positive' => 'Non-gift product sales price must be greater than 0',
    'invalid_item_update' => 'Invalid product update',
    'barcode_exists' => 'Barcode already exists',
    'please_select_correct_item_status' => 'Please select the correct product status',
    'item_bn_exists' => 'This product code already exists: ',
    'selected_category_not_exist' => 'Selected category does not exist or is incorrect',
    'selected_main_category_not_exist' => 'Your selected main category does not exist',
    'selected_brand_not_exist' => 'Your selected brand does not exist',
    'selected_param_not_exist' => 'Your selected parameter does not exist',
    'selected_param_value_not_exist' => 'Your selected parameter value does not exist',
    'selected_spec_not_exist' => 'Your selected specification does not exist',
    'selected_spec_value_not_exist' => 'Your selected specification value does not exist',
    'selected_shipping_template_not_exist' => 'Your selected shipping template does not exist',
    'item_id_cannot_be_empty' => 'Product ID cannot be empty',
    'delete_item_info_error' => 'Error with product deletion information',
    'shop_item_info_error' => 'Error with shop product information, cannot delete',
    'item_not_exists' => 'Product does not exist',
    'please_confirm_item_info' => 'Please confirm your product information before submitting.',
    'supplier_item_not_saleable' => 'Supplier product is not saleable',
    'prescription_drug_requires_medical_config' => 'Prescription drug products require medical industry configuration to be enabled before listing',
    'prescription_drug_requires_audit' => 'Prescription drug product [{0}] must pass audit before listing',
    'zero_price_cannot_be_non_gift' => 'Products with price set to 0 cannot be set as non-gift items, please check and submit again',
    
    // ItemsCategoryService exceptions
    'category_name_exists' => 'Category name already exists',
    
    // ItemsAttributesService exceptions
    'param_spec_value_limit' => 'Parameters or specification values cannot exceed 60',
    'please_add_param_or_spec_value' => 'Please add parameters or specification values',
    'brand_name_duplicate' => 'Brand name cannot be duplicated',
    'param_value_cannot_be_empty' => 'Parameter value cannot be empty',
    'has_associated_items' => 'There are associated products, please handle the associated products first',
    'has_associated_pointsmall_items' => 'There are associated pointsmall products, please handle the associated pointsmall products first',
    'value_has_associated_items' => 'Value has associated products, please handle the associated products first',
    'update_data_not_exists' => 'Update data does not exist',
    
    // KeywordsService exceptions
    'record_not_exists' => 'Record does not exist',
    
    // ItemsTagsService exceptions
    'tag_name_duplicate' => 'Tag name cannot be duplicated',
    'tag_not_exists' => 'Tag does not exist',
    'no_permission_to_edit_tag' => 'No permission to edit this tag',
    
    // ItemsMedicineService exceptions
    'item_not_medicine' => 'Product [{0}] is not a medicine',
    'item_medicine_data_missing' => 'Product [{0}] medicine data is missing',
    'item_medicine_audit_passed' => 'Product [{0}] medicine audit has passed',
    'prescription_drug_requires_audit_to_shelve' => 'Prescription drugs need to pass audit before listing',
    'please_fill_medicine_spec' => 'Please fill in medicine specifications',
    'prescription_drug_usage_tips_required' => 'Prescription drug usage tips cannot be empty',
    'prescription_drug_symptoms_required' => 'Prescription drug symptoms cannot be empty',
    
    // ServiceLabelsService exceptions
    'delete_member_value_attr_info_error' => 'Error deleting member value attribute information.',
    'member_value_attr_id_cannot_be_empty' => 'Member value attribute ID cannot be empty.',
    'please_confirm_member_value_attr' => 'Please confirm your member value attribute information before submitting.',
    
    // ItemsProfitService exceptions
    'item_get_failed' => 'Failed to get product',
    'guide_profit_config_error' => 'Guide profit configuration error',
    'profit_type_error' => 'Profit type error',
    'save_item_guide_profit_config_failed' => 'Failed to save product guide profit configuration',
    
    // ItemsCommissionService exceptions
    'spu_settlement_commission_must_be_non_negative' => 'SPU settlement commission must be a number greater than or equal to 0',
    'sku_settlement_commission_must_be_non_negative' => 'SKU settlement commission must be a number greater than or equal to 0',
    
    // ItemsCategoryProfitService exceptions
    'item_category_get_failed' => 'Failed to get product category',
    'main_category_id_not_exists' => 'Main category ID does not exist',
    'save_item_category_guide_profit_config_failed' => 'Failed to save product category guide profit configuration',
    
    // Items\Services exceptions
    'please_select_item_content' => 'Please select product content',
    
    // Items\Normal exceptions
    'sku_code_duplicate' => 'SKU code duplicate: ',
    'sku_code_cannot_duplicate' => 'SKU code cannot be duplicated: ',
    'supplier_goods_bn_duplicate' => '{0} supplier product code duplicate, please add correct product code',
    'supplier_goods_bn_duplicate_general' => 'Supplier product code duplicate, please add correct product code',
    
    // Upload related exceptions
    'tb_items_upload_excel_only' => 'Taobao product upload only supports Excel file format',
    'purchase_goods_upload_excel_only' => 'Activity product information upload only supports Excel file format (xlsx)',
    'max_items_upload_limit' => 'Maximum {0} products can be uploaded at once... please reduce and submit again',
    'item_bn_cannot_be_empty' => 'Product code cannot be empty... please check the data',
    'column_must_import' => '{0} must be imported',
    'normal_goods_upload_excel_only' => 'Physical product information upload only supports Excel file format (xlsx)',
    'item_code_exists_in_other_shop' => 'Product code already exists in another shop, cannot update',
    'profit_support_param_error' => 'Error in profit support parameter',
    'new_user_profit_amount_required' => 'New user profit amount cannot be empty',
    'promotion_profit_amount_required' => 'Promotion profit amount cannot be empty',
    'item_status_error' => 'Product status error',
    'please_fill_shipping_template' => 'Please fill in product shipping template',
    'shipping_template_not_exists' => 'The specified shipping template does not exist',
    'please_upload_management_category' => 'Please upload management category',
    'management_category_must_be_three_levels' => 'Uploaded management category must be three levels, {0}',
    'management_category_not_exists' => 'Uploaded management category does not exist, {0}',
    'unrecognized_management_category' => 'Unrecognized management category, {0}',
    'please_upload_item_category' => 'Please upload product category',
    'upload_management_category_param_error' => 'Error in uploaded management category parameter',
    'upload_item_category_param_error' => 'Error in uploaded product category parameter',
    'brand_name_not_exists' => '{0} brand name does not exist',
    'item_param_not_exists' => 'Product parameter does not exist',
    'item_spec_parse_error' => 'Error parsing product specification',
    'item_spec_value_parse_error' => 'Error parsing product specification value',
    'item_spec_invalid_values' => 'Product specification [{0}] contains invalid values',
    'item_spec_value_invalid' => 'Product specification value [{0}] is invalid',
    'same_spec_value_item_exists' => 'Product with the same specification value already exists',
    'normal_goods_tag_upload_excel_only' => 'Bulk tagging of physical products only supports Excel file format (xlsx)',
    'item_not_found_query' => 'Corresponding product not found in query',
    'tag_not_found_query' => 'Corresponding tag not found in query',
    'item_tag_conflict_with_activity' => 'Product tag causes activity conflict',
    'update_item_tag_failed' => 'Failed to update product tag data, please re-upload or contact customer service',
    'normal_goods_store_upload_excel_only' => 'Physical product inventory information upload only supports Excel file format (xlsx)',
    'can_only_import_own_shop_item_store' => 'Can only import inventory for products belonging to your shop',
    'can_only_import_dealer_related_shop_item_store' => 'Can only import inventory for products from shops related to your dealer',
    'shop_item_not_exists' => 'Shop product does not exist',
    'store_is_headquarter_store' => 'Store inventory is headquarters inventory',
    'normal_goods_profit_upload_excel_only' => 'Physical product profit information upload only supports Excel file format (xlsx)',
    'marketing_goods_upload_excel_only' => 'Marketing product information upload only supports Excel file format (xlsx)',
    'invalid_item_spec' => 'Invalid product specification exists',
    'invalid_item_spec_value' => 'Invalid product specification value exists',
    'item_code_or_barcode_required' => 'Product code or barcode is required',
    'is_epidemic_item_required' => 'Whether to set as epidemic product is required',
];

$exceptions = [
    'get_items_detail_error' => 'Error getting product details.',
    'get_items_info_error' => 'Error getting product information, please confirm the product ID.',
    'get_items_list_error' => 'Error getting product list.',
    'get_member_price_detail_error' => 'Error getting member price details.',
    'param_error' => 'Parameter error.',
    'item_not_exist_or_offline' => 'Product does not exist or has been removed',
    'item_not_found' => 'Product not found.',
    'special_char_not_supported' => 'The system currently does not support special character search',
    'get_items_intro_error' => 'Error getting product text and image details.',
    
    // API Exception Messages
    'delete_item_error' => 'Error deleting product.',
    'commission_ratio_invalid' => 'Commission ratio must be greater than 0 and less than 100',
    'item_not_exist' => 'Product information does not exist, please confirm the product ID.',
    'min_stock_warning' => 'Warning stock must be at least 1',
    'please_select_shipping_template' => 'Please select a shipping template',
    'please_fill_sort_number' => 'Please fill in the sort number',
    'item_price_format_error' => 'Product price format is incorrect',
    'params_error' => 'Parameter error',
    'please_specify_item' => 'Please specify a product before operation',
    'item_not_specified' => 'Product not specified',
    'please_select_item_and_category' => 'Please select product and category data',
    'get_miniapp_code_params_error' => 'Error getting mini program code parameters, please check.',
    'miniapp_not_opened' => 'This mini program is not enabled, cannot download.',
    'params_required' => 'Parameters required.',
    'supplier_not_support_operation' => 'Supplier does not support this operation',
    'attribute_not_exist' => 'Attribute does not exist attribute_id:',
    'get_items_increment_data_error' => 'Error getting product increment data.',
    
    // Other Controller Exception Messages
    'delete_category_error' => 'Error deleting category.',
    'shop_not_bind_wdt_erp' => 'Shop has not been bound to WDT ERP store',
    'shop_not_bind_jushuitan_erp' => 'Shop has not been bound to Jushuitan ERP store',
    'add_member_value_attr_error' => 'Error adding member value attribute.',
    'update_member_value_attr_error' => 'Error updating member value attribute.',
    'delete_member_value_attr_error' => 'Error deleting member value attribute.',
    'get_member_value_attr_error' => 'Error getting member value attribute.',
    'get_member_value_attr_info_error' => 'Error getting member value attribute information, please confirm your ID.',
    'get_member_value_attr_list_error' => 'Error getting member value attribute list.',
];

return array_merge($main, $itemsService, $exceptions);
