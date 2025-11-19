<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberArticleFav;
use CompanysBundle\Services\ArticleService;
use Dingo\Api\Exception\StoreResourceFailedException;

class MemberArticleFavService
{
    private $memberArticleFavRepository;

    /**
     * MemberAddressService 构造函数.
     */
    public function __construct()
    {
        $this->memberArticleFavRepository = app('registry')->getManager('default')->getRepository(MemberArticleFav::class);
    }

    // 添加收藏商品
    public function addArticleFav($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'article_id' => $params['article_id'],
        ];

        $favInfo = $this->memberArticleFavRepository->getInfo($filter);

        if ($favInfo) {
            return $favInfo;
        }

        $fparams = [
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
            'article_id' => $params['article_id']
        ];

        $result = $this->memberArticleFavRepository->create($fparams);

        return $result;
    }

    // 删除收藏
    public function removeArticleFav($params)
    {
        if (!$params['company_id'] || !$params['user_id'] || !$params['article_id']) {
            throw new StoreResourceFailedException(trans('MembersBundle/Members.param_error_article'));
        }
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'article_id' => $params['article_id'], // 数组
        ];
        return $this->memberArticleFavRepository->deleteBy($filter);
    }

    // 获取文章收藏列表
    public function getArticleFavList($params)
    {
        if (!isset($params['user_id']) || !isset($params['company_id'])) {
            throw new StoreResourceFailedException(trans('MembersBundle/Members.get_user_info_failed_article'));
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $pageSize = isset($params['pageSize']) ? $params['pageSize'] : 20;

        $orderBy = ['fav_id' => 'DESC'];

        $filter = ['user_id' => $params['user_id'], 'company_id' => $params['company_id']];

        $result = $this->memberArticleFavRepository->lists($filter, $page, $pageSize, $orderBy);

        if (!$result['list']) {
            return [];
        }

        $articleIds = [];
        for ($i = count($result['list']) - 1; $i >= 0; $articleIds[] = $result['list'][$i]['article_id'], $i--);

        $result = [];
        if (count($articleIds) > 0) {
            $articleService = new ArticleService();

            $result = $articleService->lists(['article_id' => $articleIds]);

            $praiseParams = [
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
            ];
            foreach ($result['list'] as $key => $row) {
                $praiseParams['article_id'] = $row['article_id'];
                $result['list'][$key]['isPraise'] = $articleService->articlePraiseCheck($praiseParams)['status'];
            }
        }

        return $result;
    }

    // 获取文章收藏详情
    public function getArticleFavInfo($params)
    {
        if (!isset($params['article_id'])) {
            return [];
        }

        if (!isset($params['user_id']) || !isset($params['company_id'])) {
            throw new StoreResourceFailedException(trans('MembersBundle/Members.get_user_info_failed_article'));
        }

        $filter = ['user_id' => $params['user_id'], 'company_id' => $params['company_id'], 'article_id' => $params['article_id']];

        $articleFavInfo = $this->memberArticleFavRepository->getInfo($filter);

        if (!$articleFavInfo) {
            return [];
        }

        $articleService = new ArticleService();

        $result = $articleService->getInfo(['article_id' => $articleFavInfo['article_id'], 'company_id' => $articleFavInfo['company_id']]);

        return $result;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->memberArticleFavRepository->$method(...$parameters);
    }
}
