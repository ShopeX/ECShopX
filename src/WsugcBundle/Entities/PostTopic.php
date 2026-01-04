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

//笔记与话题：关联表。
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WsugcPostTopic
 *
 * @ORM\Table(name="wsugc_post_topic",
 * indexes={
 *    @ORM\Index(name="idx_post_id", columns={"post_id"}),
 *    @ORM\Index(name="idx_topic_id", columns={"topic_id"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\PostTopicRepository")
 *
 */
class PostTopic
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="post_topic_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $post_topic_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", length=50, nullable=false, options={"comment"="笔记id"})
     */
    private $post_id;

    /**
     * @var string
     *
     * @ORM\Column(name="topic_id", type="string", length=50, nullable=false, options={"comment"="话题id"})
     */
    private $topic_id;

    /**
     * @var int
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned"=true,"comment"="添加时间"})
     */
    private $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * Get postTopicId.
     *
     * @return int
     */
    public function getPostTopicId()
    {
        return $this->post_topic_id;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return PostTopic
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
     * Set topicId.
     *
     * @param string $topicId
     *
     * @return PostTopic
     */
    public function setTopicId($topicId)
    {
        $this->topic_id = $topicId;

        return $this;
    }

    /**
     * Get topicId.
     *
     * @return string
     */
    public function getTopicId()
    {
        return $this->topic_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Tag
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PostTopic
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }
}
