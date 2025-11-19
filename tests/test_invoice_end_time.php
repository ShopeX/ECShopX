<?php

require_once __DIR__ . '/vendor/autoload.php';

// 测试发票结束时间更新功能
function testInvoiceEndTimeUpdate() {
    echo "=== 测试发票结束时间更新功能 ===\n";
    
    // 模拟订单完成时间
    $endTime = time();
    $closeAftersalesTime = $endTime + (7 * 24 * 3600); // 7天后售后截止
    
    echo "订单完成时间: " . date('Y-m-d H:i:s', $endTime) . "\n";
    echo "售后截止时间: " . date('Y-m-d H:i:s', $closeAftersalesTime) . "\n";
    
    // 模拟发票数据
    $invoiceData = [
        'id' => 1,
        'invoice_apply_bn' => 'INV' . date('YmdHis'),
        'user_id' => 12345,
        'company_id' => 1,
        'order_id' => 'TEST_ORDER_' . date('YmdHis'),
        'invoice_type' => 'enterprise',
        'company_title' => '测试公司',
        'invoice_status' => 'pending',
        'end_time' => null,
        'close_aftersales_time' => null
    ];
    
    echo "发票数据:\n";
    echo "- 发票申请单号: " . $invoiceData['invoice_apply_bn'] . "\n";
    echo "- 订单号: " . $invoiceData['order_id'] . "\n";
    echo "- 开票状态: " . $invoiceData['invoice_status'] . "\n";
    echo "- 当前结束时间: " . ($invoiceData['end_time'] ? date('Y-m-d H:i:s', $invoiceData['end_time']) : '未设置') . "\n";
    echo "- 当前售后截止时间: " . ($invoiceData['close_aftersales_time'] ? date('Y-m-d H:i:s', $invoiceData['close_aftersales_time']) : '未设置') . "\n";
    
    // 模拟更新后的数据
    $updatedInvoiceData = $invoiceData;
    $updatedInvoiceData['end_time'] = $endTime;
    $updatedInvoiceData['close_aftersales_time'] = $closeAftersalesTime;
    
    echo "\n更新后的发票数据:\n";
    echo "- 结束时间: " . date('Y-m-d H:i:s', $updatedInvoiceData['end_time']) . "\n";
    echo "- 售后截止时间: " . date('Y-m-d H:i:s', $updatedInvoiceData['close_aftersales_time']) . "\n";
    
    echo "\n=== 测试完成 ===\n";
}

// 运行测试
testInvoiceEndTimeUpdate(); 