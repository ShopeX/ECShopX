<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CommentsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use CommentsBundle\Entities\ShopComments;
use Doctrine\Common\Collections\Criteria;

use Dingo\Api\Exception\UpdateResourceFailedException;

class ShopCommentsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    private $table = 'shop_comments';

    public function create($postdata)
    {
        // Built with ShopEx Framework
        $commentEntity = new ShopComments();

        $comment = $this->setCommentData($commentEntity, $postdata);
        $em = $this->getEntityManager();
        $em->persist($comment);
        $em->flush();

        $result = $this->getCommentData($comment);

        return $result;
    }

    public function update($commentId, $postdata)
    {
        $commentEntity = $this->find($commentId);
        if (!$commentEntity) {
            throw new UpdateResourceFailedException("comment_id={$commentId}的评论不存在");
        }

        $comment = $this->setCommentData($commentEntity, $postdata);
        $em = $this->getEntityManager();
        $em->persist($comment);
        $em->flush();

        $result = $this->getCommentData($comment);

        return $result;
    }

    /**
     * 获取满足条件的一条数据
     */
    public function get($filter)
    {
        return $this->findOneBy($filter);
    }

    /**
     * 获取列表
     */
    public function getList($filter, $offset = 0, $limit = 10000, $orderBy = ['created' => 'DESC'])
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                    continue;
                } elseif (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $commentList = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($offset)
                ->setMaxResults($limit);
            $list = $this->matching($criteria);
            $comment = [];
            foreach ($list as $v) {
                $comment['comment_id'] = $v->getCommentId();
                $comment['company_id'] = $v->getCompanyId();
                $comment['user_id'] = $v->getUserId();
                $comment['shop_id'] = $v->getShopId();
                $comment['content'] = $v->getContent();
                $comment['pics'] = $v->getPics();
                $comment['is_reply'] = $v->getIsReply();
                $comment['reply_content'] = $v->getReplyContent();
                $comment['stuck'] = $v->getStuck();
                $comment['hid'] = $v->getHid();
                $comment['reply_time'] = $v->getReplyTime();
                $comment['created'] = $v->getCreated();
                $comment['updated'] = $v->getUpdated();

                $commentList[] = $comment;
            }
        }
        $res['list'] = $commentList;

        return $res;
    }

    private function setCommentData($commentEntity, $postdata)
    {
        if (isset($postdata['company_id'])) {
            $commentEntity->setCompanyId($postdata['company_id']);
        }
        if (isset($postdata['user_id'])) {
            $commentEntity->setUserId($postdata['user_id']);
        }
        if (isset($postdata['shop_id'])) {
            $commentEntity->setShopId($postdata['shop_id']);
        }
        if (isset($postdata['content'])) {
            $commentEntity->setContent($postdata['content']);
        }
        if (isset($postdata['pics'])) {
            $commentEntity->setPics($postdata['pics']);
        }
        if (isset($postdata['is_reply'])) {
            $commentEntity->setIsReply($postdata['is_reply']);
        }
        if (isset($postdata['reply_content'])) {
            $commentEntity->setReplyContent($postdata['reply_content']);
        }
        if (isset($postdata['stuck'])) {
            $commentEntity->setStuck($postdata['stuck']);
        }
        if (isset($postdata['hid'])) {
            $commentEntity->setHid($postdata['hid']);
        }
        if (isset($postdata['reply_time'])) {
            $commentEntity->setReplyTime($postdata['reply_time']);
        }
        if (isset($postdata['source'])) {
            $commentEntity->setSource($postdata['source']);
        }

        return $commentEntity;
    }

    private function getCommentData($comment)
    {
        return [
            'comment_id' => $comment->getCommentId(),
            'company_id' => $comment->getCompanyId(),
            'user_id' => $comment->getUserId(),
            'shop_id' => $comment->getShopId(),
            'content' => $comment->getContent(),
            'pics' => $comment->getPics(),
            'stuck' => $comment->getStuck(),
            'hid' => $comment->getHid(),
            'source' => $comment->getSource(),
            'created' => $comment->getCreated(),
            'updated' => $comment->getUpdated(),
        ];
    }
}
