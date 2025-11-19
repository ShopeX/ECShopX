<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

//笔记-推荐商品
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WsugcPost 笔记
 *
 * @ORM\Table(name="wsugc_post_goods", options={"comment"="笔记"}, indexes={
 *    @ORM\Index(name="idx_post_id", columns={"post_id"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\PostGoodsRepository")
 */
class PostGoods
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="post_goods_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $post_goods_id;

   /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", options={"comment":"笔记id"})
     */
    private $post_id;
   
    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"视频id"})
     */
    private $goods_id;
  

    /**
     * Get postGoodsId.
     *
     * @return int
     */
    public function getPostGoodsId()
    {
        return $this->post_goods_id;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return WsugcPostGoods
     */
    public function setPostId($postId)
    {
        $this->post_id = $postId;

        return $this;
    }

    /**
     * Get postId.
     *
     * @return int
     */
    public function getPostId()
    {
        return $this->post_id;
    }

    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return WsugcPostGoods
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }
}
