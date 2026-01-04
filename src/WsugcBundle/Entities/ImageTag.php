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

//图片tag
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WsugcImageTag 图片tag关联表
 *
 * @ORM\Table(name="wsugc_image_tag", options={"comment"="图片tag关联表"}, indexes={
 *    @ORM\Index(name="idx_image_id", columns={"image_id"}),
 *    @ORM\Index(name="idx_tag_id", columns={"tag_id"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\ImageTagRepository")
 */
class ImageTag
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="image_tag_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $image_tag_id;

   /**
     * @var integer
     *
     * @ORM\Column(name="image_id", type="bigint", options={"comment":"图片id"})
     */
    private $image_id;
   




    /**
     * @var integer
     *
     * @ORM\Column(name="tag_id", type="bigint", options={"comment":"标签id"})
     */
    private $tag_id;

    /**
     * @var string
     *
     * @ORM\Column(name="enabled", type="integer", options={"comment":"是否启用", "default": 1})
     */
    private $enabled = 1;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

  

    /**
     * Get imageTagId.
     *
     * @return int
     */
    public function getImageTagId()
    {
        return $this->image_tag_id;
    }

    /**
     * Set imageId.
     *
     * @param int $imageId
     *
     * @return ImageTag
     */
    public function setImageId($imageId)
    {
        $this->image_id = $imageId;

        return $this;
    }

    /**
     * Get imageId.
     *
     * @return int
     */
    public function getImageId()
    {
        return $this->image_id;
    }

    /**
     * Set tagId.
     *
     * @param int $tagId
     *
     * @return ImageTag
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId.
     *
     * @return int
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set enabled.
     *
     * @param int $enabled
     *
     * @return ImageTag
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return int
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ImageTag
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
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return ImageTag
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ImageTag
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
