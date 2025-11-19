<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services;

use PromotionsBundle\Entities\BargainPromotions;
use PromotionsBundle\Entities\UserBargains;
use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Traits\CheckPromotionsValid;
use PromotionsBundle\Traits\CheckPromotionsRules;
use GoodsBundle\Services\ItemsService;

class BargainPromotionsService
{
    use CheckPromotionsValid;
    use CheckPromotionsRules;

    /**
     * BargainPromotions Repository类
     */
    public $bargainPromotionsRepository = null;

    public function __construct()
    {
        $this->bargainPromotionsRepository = app('registry')->getManager('default')->getRepository(BargainPromotions::class);
    }

    public function createBargain($params)
    {
        // 检查是否有冲突的会员优先购活动
        //$this->checkMarketingActivity($params['company_id'], $params['item_id'], $params['begin_time'], $params['end_time'], '', ['member_preference']);
        $this->checkActivityValidByBargain($params['company_id'], $params['item_id'], $params['begin_time'], $params['end_time'], 0);

        // 检查商品是否为赠品
        $itemsService = new ItemsService();
        if ($itemsService->__checkIsGiftItem($params['company_id'], [$params['item_id']])) {
            throw new ResourceException(trans('PromotionsBundle.gift_product_check_error'));
        }
        return $this->bargainPromotionsRepository->create($params);
    }

    public function updateBargain($bargainId, $data)
    {
        // 检查是否有冲突的会员优先购活动
        //$this->checkMarketingActivity($data['company_id'], $data['item_id'], $data['begin_time'], $data['end_time'], '', ['member_preference']);
        $this->checkActivityValidByBargain($data['company_id'], $data['item_id'], $data['begin_time'], $data['end_time'], $bargainId);
        // 检查商品是否为赠品
        $itemsService = new ItemsService();
        if ($itemsService->__checkIsGiftItem($data['company_id'], [$data['item_id']])) {
            throw new ResourceException(trans('PromotionsBundle.gift_product_check_error'));
        }
        $userBargainsRepository = app('registry')->getManager('default')->getRepository(UserBargains::class);
        $userBargainInfo = $userBargainsRepository->get(['bargain_id' => $bargainId]);
        if ($userBargainInfo) {
            throw new ResourceException(trans('PromotionsBundle.bargain_activity_has_user_cannot_edit'));
        }
        $bargainInfo = $this->getBargain($bargainId);
        if ($bargainInfo['end_time'] < time()) {
            throw new ResourceException(trans('PromotionsBundle.bargain_activity_ended_cannot_edit'));
        }
        return $this->bargainPromotionsRepository->update($bargainId, $data);
    }

    public function updateBargainOrderNum($bargainId)
    {
        $bargain = $this->bargainPromotionsRepository->get($bargainId);
        if (!$bargain) {
            throw new ResourceException(trans('PromotionsBundle.bargain_activity_not_exist'));
        }
        $orderNum = intval($bargain['order_num']) + 1;
        return $this->bargainPromotionsRepository->update($bargainId, ['order_num' => $orderNum]);
    }

    public function getBargainList($filter, $offset = 0, $limit = -1, $orderBy = ['created' => 'DESC'])
    {
        return $this->bargainPromotionsRepository->getList($filter, $offset, $limit, $orderBy);
    }

    public function getBargain($bargainId)
    {
        return $this->bargainPromotionsRepository->get($bargainId);
    }

    /**
     * 终止助力活动
     *
     * @param bargainid
     * @return array
     */
    public function terminateBargain($bargainId)
    {
        $bargain = $this->bargainPromotionsRepository->get($bargainId);
        if (!$bargain) {
            throw new ResourceException(trans('PromotionsBundle.bargain_activity_not_exist'));
        }
        if ($bargain['end_time'] > time()) {
            $bargain = $this->bargainPromotionsRepository->update($bargainId, ['end_time' => time()]);
        }
        return $bargain;
    }

    /**
     * 删除助力活动
     *
     * @param array filter
     * @return bool
     */
    public function deleteBargain($filter)
    {
        $bargainInfo = $this->bargainPromotionsRepository->get($filter['bargain_id']);

        if ($filter['company_id'] != $bargainInfo['company_id']) {
            throw new ResourceException(trans('PromotionsBundle.delete_bargain_info_error'));
        }
        if (!$filter['bargain_id']) {
            throw new ResourceException(trans('PromotionsBundle.bargain_activity_id_required'));
        }

        $userBargainsRepository = app('registry')->getManager('default')->getRepository(UserBargains::class);
        $userBargainInfo = $userBargainsRepository->get(['bargain_id' => $filter['bargain_id']]);
        if ($userBargainInfo) {
            throw new ResourceException(trans('PromotionsBundle.bargain_activity_has_user_cannot_delete'));
        }

        return $this->bargainPromotionsRepository->delete($filter['bargain_id']);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->bargainPromotionsRepository->$method(...$parameters);
    }
}
