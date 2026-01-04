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
 * Scene 短信场景
 *
 * @ORM\Table(name="aliyunsms_scene", options={"comment":"场景表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AliyunsmsBundle\Repositories\SceneRepository")
 */
class Scene
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
     * @var string
     *
     * @ORM\Column(name="scene_name", type="string", options={"comment":"场景名称"})
     */
    private $scene_name;

    /**
     * @var string
     *
     * @ORM\Column(name="scene_title", nullable=true,type="string", options={"comment":"场景title"})
     */
    private $scene_title;

    /**
     * @var string
     *
     * @ORM\Column(name="template_type", type="string", options={"comment":"短信类型: 0：验证码;1：短信通知;2：推广短信;3：国际/港澳台消息"})
     */
    private $template_type;

    /**
     * @var string enabled|disabled
     *
     * @ORM\Column(name="status", type="string", options={"comment":"状态"})
     */
    private $status = 'disabled';

    /**
     * @var string
     *
     * @ORM\Column(name="default_template", nullable=true, type="string", options={"comment":"默认模板"})
     */
    private $default_template;

    /**
     * @var string
     *
     * @ORM\Column(name="variables", nullable=true, length=500, type="string", options={"comment":"模板变量"})
     */
    private $variables;


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
     * @return Scene
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
     * Set sceneName.
     *
     * @param string $sceneName
     *
     * @return Scene
     */
    public function setSceneName($sceneName)
    {
        $this->scene_name = $sceneName;

        return $this;
    }

    /**
     * Get sceneName.
     *
     * @return string
     */
    public function getSceneName()
    {
        return $this->scene_name;
    }


    /**
     * Set sceneTitle.
     *
     * @param string $sceneTitle
     *
     * @return Scene
     */
    public function setSceneTitle($sceneTitle)
    {
        $this->scene_title = $sceneTitle;

        return $this;
    }

    /**
     * Get sceneTitle.
     *
     * @return string
     */
    public function getSceneTitle()
    {
        return $this->scene_title;
    }

    /**
     * Set templateType
     *
     * @param string $templateType
     *
     * @return Scene
     */
    public function setTemplateType($templateType)
    {
        $this->template_type = $templateType;

        return $this;
    }

    /**
     * Get templateType.
     *
     * @return string
     */
    public function getTemplateType()
    {
        return $this->template_type;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return Scene
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set defaultTemplate.
     *
     * @param string $defaultTemplate
     *
     * @return Scene
     */
    public function setDefaultTemplate($defaultTemplate)
    {
        $this->default_template = $defaultTemplate;

        return $this;
    }

    /**
     * Get defaultTemplate.
     *
     * @return
     */
    public function getDefaultTemplate()
    {
        return $this->default_template;
    }

    /**
     * Set variables.
     *
     * @param string $variables
     *
     * @return Scene
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Get variables.
     *
     * @return
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Scene
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
     * @return Scene
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
