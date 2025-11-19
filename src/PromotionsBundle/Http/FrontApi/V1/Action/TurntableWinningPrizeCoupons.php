<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use KaquanBundle\Services\DiscountCardService as CardService;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\PackageReceivesService;
use KaquanBundle\Services\UserDiscountService;
use PromotionsBundle\Interfaces\TurntableWinningPrize;

class TurntableWinningPrizeCoupons implements TurntableWinningPrize
{
    private $winning_prize;
    private $user_info;

    public function __construct($winning_prize, $user_info)
    {
        $this->winning_prize = $winning_prize;
        $this->user_info = $user_info;
    }

    //发放奖品操作
    public function grantPrize()
    {
        //检查优惠券包中优惠券余量
//        foreach ($this->winning_prize['prize_value'] as $card_id) {
//            $coupon_surplus = $this->checkCouponsSurplus($card_id, $this->user_info['company_id']);
//            if (!$coupon_surplus) {
//                return false;
//            }
//        }
//        //发放优惠券
//        $user_discount_service = new UserDiscountService();
//        foreach ($this->winning_prize['prize_value'] as $value) {
//            $user_discount_service->userGetCard($this->user_info['company_id'], $value, $this->user_info['user_id'], '大转盘中奖领取');
//        }
        try{
            (new PackageReceivesService())->receivesPackage(
                $this->user_info['company_id'],
                $this->winning_prize['prize_value'],
                $this->user_info['user_id'],
                '大转盘中奖领取'
            );
        }catch(\Exception $e){
            app('log')->debug('david:draw:cpupons:'.$e->getMessage().'@'.$e->getFile().':'.$e->getLine().'--->trace:'.json_encode($e->getTrace()));
            return false;
        }

        return true;
    }

    /**
     * 检查优惠券余量
     * @param $card_id string 优惠券id
     * @param $company_id string 公司id
     * @return bool
     */
    private function checkCouponsSurplus($card_id, $company_id)
    {
        //检查优惠券余量
        $discountCardService = new KaquanService(new CardService());
        $filter['card_id'] = $card_id;
        $filter['company_id'] = $company_id;
        $card_info = $discountCardService->getKaquanDetail($filter);
        $discountCardService = new UserDiscountService();
        $coupon_num = $discountCardService -> getCardGetNum($card_id, $company_id);

        if (!$card_info) { //无优惠券信息
            return false;
        } elseif ($card_info['quantity'] - $coupon_num <= 0) { //优惠券数量不足
            return false;
        }

        return true;
    }
}
