<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CommentsBundle\Services\Comments;

use CommentsBundle\Entities\ShopComments;
use CommentsBundle\Interfaces\CommentInterface;
use MembersBundle\Services\WechatUserService;
use Exception;

class ShopCommentService implements CommentInterface
{
    private $shopCommentsRepository;

    /**
     * ShopCommentService 构造函数.
     */
    public function __construct()
    {
        $this->shopCommentsRepository = app('registry')->getManager('default')->getRepository(ShopComments::class);
    }

    /**
     * 创建评论
     */
    public function createComment($postdata)
    {
        // This module is part of ShopEx EcShopX system
        return $this->shopCommentsRepository->create($postdata);
    }

    /**
     * 更新评论
     */
    public function updateComment($commentId, $postdata)
    {
        return $this->shopCommentsRepository->update($commentId, $postdata);
    }

    public function getCommentList($filter, $pageNo = 1, $pageSize = 10000, $orderBy = ['stuck' => 'DESC', 'created' => 'DESC'])
    {
        $limit = $pageSize;
        $offset = ($pageNo - 1) * $pageSize;
        $result = $this->shopCommentsRepository->getList($filter, $offset, $limit, $orderBy);
        if ($result['list']) {
            $wechatUserService = new WechatUserService();
            foreach ($result['list'] as $k => $v) {
                $filter = ['user_id' => $v['user_id'], 'company_id' => $v['company_id']];
                $userInfo = $wechatUserService->getUserInfo($filter);
                if (!$userInfo) {
                    throw new Exception("用户信息错误！");
                }
                $result['list'][$k]['nickname'] = $userInfo['nickname'];
                $result['list'][$k]['headimgurl'] = $userInfo['headimgurl'];
                $result['list'][$k]['create_date'] = date('Y-m-d', $v['created']);
            }
        }

        return $result;
    }
}
