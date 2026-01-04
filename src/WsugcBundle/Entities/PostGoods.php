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
