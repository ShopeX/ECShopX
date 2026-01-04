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

namespace AliyunsmsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 短信场景实例
 *
 * @ORM\Table(name="aliyunsms_scene_item", options={"comment":"场景实例表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AliyunsmsBundle\Repositories\SceneItemRepository")
 */
class SceneItem
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="scene_id", type="integer", options={"comment":"场景ID"})
     */
    private $scene_id;

    /**
     * @var integer
     * @ORM\Column(name="sign_id", type="integer", options={"comment":"签名ID"})
     */
    private $sign_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sign_name", type="string", options={"comment":"签名名称"})
     */
    private $sign_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="template_id", type="integer", options={"comment":"模板ID"})
     */
    private $template_id;

    /**
     * @var text
     *
     * @ORM\Column(name="template_content", type="text", options={"comment":"模板内容"})
     */
    private $template_content;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"0-未启用;1-已启用"})
     */
    private $status = 0;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Sign
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

    /**
     * Set sceneId.
     *
     * @param integer $sceneId
     *
     * @return SceneItem
     */
    public function setSceneId($sceneId)
    {
        $this->scene_id = $sceneId;

        return $this;
    }

    /**
     * Get sceneId.
     *
     * @return integer
     */
    public function getSceneId()
    {
        return $this->scene_id;
    }

    /**
     * Set signId.
     *
     * @param integer $signId
     *
     * @return SceneItem
     */
    public function setSignId($signId)
    {
        $this->sign_id = $signId;

        return $this;
    }

    /**
     * Get signId.
     *
     * @return integer
     */
    public function getSignId()
    {
        return $this->sign_id;
    }

    /**
     * Set signName.
     *
     * @param string $signName
     *
     * @return SceneItem
     */
    public function setSignName($signName)
    {
        $this->sign_name = $signName;

        return $this;
    }

    /**
     * Get signName.
     *
     * @return string
     */
    public function getSignName()
    {
        return $this->sign_name;
    }

    /**
     * Set templateId.
     *
     * @param integer $templateId
     *
     * @return SceneItem
     */
    public function setTemplateId($templateId)
    {
        $this->template_id = $templateId;

        return $this;
    }

    /**
     * Get templateId.
     *
     * @return integer
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }

    /**
     * Set templateContent.
     *
     * @param text $templateContent
     *
     * @return SceneItem
     */
    public function setTemplateContent($templateContent)
    {
        $this->template_content = $templateContent;

        return $this;
    }

    /**
     * Get templateContent.
     *
     * @return text
     */
    public function getTemplateContent()
    {
        return $this->template_content;
    }

    /**
     * Set status.
     *
     * @param integer $status
     *
     * @return SceneItem
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SceneItem
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
     * @return SceneItem
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
}
