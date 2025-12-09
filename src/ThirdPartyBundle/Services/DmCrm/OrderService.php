<?php

namespace ThirdPartyBundle\Services\DmCrm;

use MembersBundle\Services\MemberService;

class OrderService extends DmService
{
    /**
     * Summary of formatData
     * @param mixed $paramsData 订单信息
     * @param mixed $orderStatus 订单状态1 购买 2 退款 4 退换
     * @return array[]
     */
    private function formatData($paramsData, $orderStatus = 1)
    {
        app('log')->debug("Dm OrderService formatData:".var_export($paramsData, true));
        $memberService = new MemberService();
        $filterMember = [
            'company_id' => $paramsData['company_id'],
            'user_id' => $paramsData['user_id'],
        ];
        $memberInfo = $memberService->getMemberInfo($filterMember);
        $params = [
            'mobile' => $memberInfo['mobile'],
            'orderNo' => $paramsData['refund_bn'] ?? $paramsData['order_id'],
            'originalOrderNo' => $paramsData['order_id'],
            'goodsAmount' => bcdiv($paramsData['item_fee'], 100, 2),
            'deliveryPaymentAmount' => bcdiv($paramsData['freight_fee'], 100, 2),
            'paymentAmount' => bcdiv($paramsData['total_fee'], 100, 2),
            'orderStatus' => $orderStatus,
            'channelCode' => 'c_brand_mall', 
//            'userNickname' => $paramsData['userNickname'],
//            'userAccount' => $paramsData['userAccount'],
            'receiverName' => $paramsData['receiver_name'],
            'phoneNumber' => $paramsData['receiver_mobile'],
            'receiverZip' => $paramsData['receiver_zip'],
            'receiverAddress' => $paramsData['receiver_address'],
            'remark' => $paramsData['remark'],
            'orderTime' => date('Y-m-d H:i:s', $paramsData['create_time']),
//            'inviterPhone' => $paramsData['inviterPhone'],
//            'inviterNick' => $paramsData['inviterNick'],
//            'tuikeNick' => $paramsData['tuikeNick'],
//            'tuikePhone' => $paramsData['tuikePhone'],
           'storeName' => $paramsData['storeName'] ?? '',
           'storeCode' => $paramsData['storeCode'] ?? '',
           'clerkCode' => $paramsData['clerkCode'] ?? '',
           'clerkName' => $paramsData['clerkName'] ?? '',
           'usedMemberPoints' => $paramsData['usedMemberPoints'] ?? 0, // bcdiv($paramsData['point_fee'], 100, 2),
            'couponCode' => '-1',
            'item' => [
                'goodsCode' => '',
                'goodsTitle' => '',
                'goodsCategoryName' => '',
                'skuCode' => '',
                'skuName' => '',
                'skuNum' => '',
                'skuAmount' => '',
                'totalAmount' => '',
                'payAmount' => '',
                'price' => '',
                'imageUrl' => '',
                'goodsType' => '',
            ]
        ];
        // 处理优惠券
        if (!empty($paramsData['discount_info'])) {
            $couponCode = array_column($paramsData['discount_info'], 'dm_card_code');
            $params['couponCode'] = !empty($couponCode) ? implode(',', $couponCode) : '-1';
        }
        $items = [];
        foreach ($paramsData['items'] as $v) {
            $items[] = [
                'goodsCode' => $v['goods_bn'],
                'goodsTitle' => $v['item_name'],
                'goodsCategoryName' => '',
                'skuCode' => $v['item_bn'],
                'skuName' => $v['item_name'],
                'skuNum' => $v['num'],
                'skuAmount' => bcdiv($v['item_fee'], 100 ,2),
                'totalAmount' => bcdiv($v['item_fee_t'], 100, 4),
                'payAmount' => bcdiv($v['total_fee'], 100, 2),
                'price' => bcdiv($v['price'], 100 ,2),
                'imageUrl' => $v['pic'] ?? '',
                'goodsType' => isset($v['is_gift']) && $v['is_gift'] ? 2 : 1,
            ];
        }
        $params['item'] = $items;

        return $params;
    }

    /**
     * 新增接口：
     * https://hope-demogic.yuque.com/org-wiki-hope-demogic-dfi2t5/nczfaq/order_add_online_store_order
     */
    public function syncOrder($paramsData)
    {
        $worker = '/cgi-api/order/add_online_store_order';
        $params = $this->formatData($paramsData);
        $headers = [
            'ruid' => $paramsData['order_id'],
        ];
        $result = $this->requestApiPost($worker, $params, $headers);

        return $result ?? [];
    }

    public function syncAfter($paramsData)
    {
        $worker = '/cgi-api/order/add_online_store_order';
        $params = $this->formatData($paramsData, 2);
        $headers = [
            'ruid' => $paramsData['order_id'],
        ];
        $result = $this->requestApiPost($worker, $params, $headers);

        return $result ?? [];
    }

    public function syncForwardAfter($paramsData)
    {
        $worker = '/cgi-api/order/add_online_store_order';
        $params = $this->formatData($paramsData, 5);
        $headers = [
            'ruid' => $paramsData['order_id'],
        ];
        $result = $this->requestApiPost($worker, $params, $headers);

        return $result ?? [];
    }

}
