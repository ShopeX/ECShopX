<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace EspierBundle\Traits;

use DistributionBundle\Services\ExportDistributorWhiteList;
use EspierBundle\Services\Export\DeliveryStaffDataExportService;
use GoodsBundle\Services\EpidemicRegisterExportService;
use OrdersBundle\Services\Export\OfflinePaymentExportService;
use PointBundle\Services\export\PointMemberLogExportService;
use PromotionsBundle\Services\ExportLuckyDrawLog;
use SalespersonBundle\Services\ProfitExportAgentService;
use SalespersonBundle\Services\ProfitExportDistributorService;
use SalespersonBundle\Services\ProfitExportSalespersonService;
use EspierBundle\Services\Export\CashWithdrawalExportService;
use EspierBundle\Services\Export\HfpayTradeRecordExportService;
use EspierBundle\Services\Export\NormalItemsExportService;
use EspierBundle\Services\Export\NormalItemsTagExportService;
use EspierBundle\Services\Export\InvoicesExportService;
use EspierBundle\Services\Export\Items;
use EspierBundle\Services\Export\NormalItemsCodeExportService;
use EspierBundle\Services\Export\PointsmallItems;
use Exception;
use EspierBundle\Services\Export\MemberExportService;
use EspierBundle\Services\Export\ServiceOrderExportService;
use EspierBundle\Services\Export\NormalOrderExportService;
use EspierBundle\Services\Export\RightExportService;
use EspierBundle\Services\Export\TradeExportService;
use EspierBundle\Services\Export\RightConsumeExportService;
use EspierBundle\Services\Export\NormalMasterOrderExportService;
use EspierBundle\Services\Export\RegistrationRecordExportService;
use EspierBundle\Services\Export\TaskBrokerageExportService;
use EspierBundle\Services\Export\AftersalesRecordExportService;
use EspierBundle\Services\Export\RefundRecordExportService;
use EspierBundle\Services\Export\AftersalesFinancialExportService;
use EspierBundle\Services\Export\SalesreportFinancialExportService;
use EspierBundle\Services\Export\DistributorItems;
use EspierBundle\Services\Export\HfpayOrderRecordExportService;
use EspierBundle\Services\Export\HfpayWithdrawRecordExportService;
use PopularizeBundle\Services\PopularizeExportService;
use PopularizeBundle\Services\PopularizeOrderExportService;
use PopularizeBundle\Services\PopularizeStaticExportService;
use AdaPayBundle\Services\Export\AdapayTradeExportService;
use EspierBundle\Services\Export\NormalCommunityOrderExportService;
use EspierBundle\Services\Export\StatementsExportService;
use EspierBundle\Services\Export\StatementDetailsExportService;
use EspierBundle\Services\Export\DivisionExportService;
use EspierBundle\Services\Export\DivisionDetailExportService;
use EspierBundle\Services\Export\PurchaseEmployeesExportService;
use BsPayBundle\Services\Export\BspayTradeExportService;
use SupplierBundle\Services\Export\SupplierGoodsExportService;
use SupplierBundle\Services\Export\SupplierOrderExportService;
use BsPayBundle\Services\Export\WithdrawExportService;

trait GetExportServiceTraits
{
    public function getService($exportType)
    {
        $exportType = strtolower($exportType);
        switch ($exportType) {
            case 'member':
                $exportService = new MemberExportService();
                break;
            case 'service_order':
                $exportService = new ServiceOrderExportService();
                break;
            case 'offline_payment':
                $exportService = new OfflinePaymentExportService();
                break;
            case 'normal_order':
                $exportService = new NormalOrderExportService();
                break;
            case 'right':
                $exportService = new RightExportService();
                break;
            case 'right_consume':
                $exportService = new RightConsumeExportService();
                break;
            case 'tradedata':
                $exportService = new TradeExportService();
                break;
            case 'normal_master_order':
                $exportService = new NormalMasterOrderExportService();
                break;
            case 'supplier_order':
                $exportService = new SupplierOrderExportService();
                break;
            case 'community_withdraw':
                $exportService = new CashWithdrawalExportService();
                break;
            case 'selform_registration_record':
                $exportService = new RegistrationRecordExportService();
                break;
            case 'task_brokerage_count':
                $exportService = new TaskBrokerageExportService();
                break;
            case 'aftersale_record_count':
                $exportService = new AftersalesRecordExportService();
                break;
            case 'profit_distributor':
                $exportService = new ProfitExportDistributorService();
                break;
            case 'profit_salesperson':
                $exportService = new ProfitExportSalespersonService();
                break;
            case 'profit_agent':
                $exportService = new ProfitExportAgentService();
                break;
            case 'refund_record_count':
                $exportService = new RefundRecordExportService();
                break;
            case 'normal_items':
                $exportService = new NormalItemsExportService();
                break;
            case 'supplier_goods':
                $exportService = new SupplierGoodsExportService();
                break;
            case 'items':
                $exportService = new Items();
                break;
            case 'normal_items_tag':
                $exportService = new NormalItemsTagExportService();
                break;
            case 'pointsmallitems':
                $exportService = new PointsmallItems();
                break;
            case 'invoice':
                $exportService = new InvoicesExportService();
                break;
            case 'popularize':
                $exportService = new PopularizeExportService();
                break;
            case 'popularizeorder':
                $exportService = new PopularizeOrderExportService();
                break;
            case 'popularizestatic':
                $exportService = new PopularizeStaticExportService();
                break;
            case 'aftersale_financial':
                $exportService = new AftersalesFinancialExportService();
                break;
            case 'salesreport_financial':
                $exportService = new SalesreportFinancialExportService();
                break;
            case 'hfpay_trade_record':
                $exportService = new HfpayTradeRecordExportService();
                break;
            case 'distributor_items':
                $exportService = new DistributorItems();
                break;
            case 'hfpay_order_record':
                $exportService = new HfpayOrderRecordExportService();
                break;
            case 'hfpay_withdraw_record':
                $exportService = new HfpayWithdrawRecordExportService();
                break;
            case 'adapay_tradedata':
                $exportService = new AdapayTradeExportService();
                break;
            case 'itemcode':
                $exportService = new NormalItemsCodeExportService();
                break;
            case 'epidemic_register':
                $exportService = new EpidemicRegisterExportService();
                break;
            case 'normal_community_order':
                $exportService = new NormalCommunityOrderExportService();
                break;
            case 'statements':
                $exportService = new StatementsExportService();
                break;
            case 'statement_details':
                $exportService = new StatementDetailsExportService();
                break;
            case 'chinaums_division':
                $exportService = new DivisionExportService();
                break;
            case 'chinaums_division_detail':
                $exportService = new DivisionDetailExportService();
                break;
            case 'delivery_staffdata':
                $exportService = new DeliveryStaffDataExportService();
                break;
            case 'bspay_tradedata':
                $exportService = new BspayTradeExportService();
                break;
            case 'distributor_white_list':
                $exportService = new ExportDistributorWhiteList();
                break;
            case 'export_luckdraw_log':
                $exportService = new ExportLuckyDrawLog();
                break;
            case 'employee_purchase_employees':
                $exportService = new PurchaseEmployeesExportService();
                break;
            case 'member_point_logs':
                $exportService = new PointMemberLogExportService();
                break;
            case 'bspay_withdraw':
                $exportService = new WithdrawExportService();
                break;
            default:
                throw new Exception("无此导出类型");
        }
        return $exportService;
    }
}
