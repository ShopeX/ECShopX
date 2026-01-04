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

namespace EspierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ConfigRequestFields 配置信息, 配置传递的请求字段
 *
 * @ORM\Table(name="config_request_fields", options={"comment":"配置信息, 配置传递的请求字段"}, indexes={
 *    @ORM\Index(name="ix_company_module_open", columns={"company_id", "module_type", "is_open"}),
 * })
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\ConfigRequestFieldsRepository")
 */
class ConfigRequestFields
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", options={"comment":"主键id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示该配置为平台创建", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     * 1 会员注册
     * 2 团长申请
     * @ORM\Column(name="module_type", type="smallint", options={"comment":"模块类型, 【1: 会员注册】【2: 团长申请】"})
     */
    private $module_type;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=50, options={"comment":"该请求字段的标识, 比如是mobile则表示为手机号"})
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="key_name", type="string", length=50, options={"comment":"前后端交互时需要被传递的key名，如果是手机号则是mobile"})
     */
    private $key_name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_open", type="boolean", options={"comment":"是否启用, 【0 关闭】【1 开启】"})
     */
    private $is_open;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_required", type="boolean", options={"comment":"是否必填, 【0 非必填】 【1 必填】"})
     */
    private $is_required;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_preset", type="boolean", options={"comment":"是否是预设字段, 【0 非必填】 【1 必填】"})
     */
    private $is_preset;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_edit", type="boolean", options={"comment":"是否可修改, 【0 不可修改】 【1 可修改】"})
     */
    private $is_edit;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_type", type="smallint", options={"comment":"当前请求字段的类型, [1 文本] [2 数字] [3 日期] [4 单选项]"})
     */
    private $field_type;

    /**
     * @var string
     *
     * @ORM\Column(name="validate_condition", type="text", options={"comment":"验证条件, json存储"})
     */
    private $validate_condition;

    /**
     * @var string
     *
     * @ORM\Column(name="alert_required_message", type="string", options={"comment":"提示必填时的文案"})
     */
    private $alert_required_message;

    /**
     * @var string
     *
     * @ORM\Column(name="alert_validate_message", type="string", options={"comment":"提示验证时时的文案"})
     */
    private $alert_validate_message;

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
     * Set id.
     *
     * @param int $id
     *
     * @return ConfigRequestFields
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * @return ConfigRequestFields
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return ConfigRequestFields
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set moduleType.
     *
     * @param int $moduleType
     *
     * @return ConfigRequestFields
     */
    public function setModuleType($moduleType)
    {
        $this->module_type = $moduleType;

        return $this;
    }

    /**
     * Get moduleType.
     *
     * @return int
     */
    public function getModuleType()
    {
        return $this->module_type;
    }

    /**
     * Set label.
     *
     * @param string $label
     *
     * @return ConfigRequestFields
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set keyName.
     *
     * @param string $keyName
     *
     * @return ConfigRequestFields
     */
    public function setKeyName($keyName)
    {
        $this->key_name = $keyName;

        return $this;
    }

    /**
     * Get keyName.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->key_name;
    }

    /**
     * Set isOpen.
     *
     * @param bool $isOpen
     *
     * @return ConfigRequestFields
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen.
     *
     * @return bool
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set isRequired.
     *
     * @param bool $isRequired
     *
     * @return ConfigRequestFields
     */
    public function setIsRequired($isRequired)
    {
        $this->is_required = $isRequired;

        return $this;
    }

    /**
     * Get isRequired.
     *
     * @return bool
     */
    public function getIsRequired()
    {
        return $this->is_required;
    }

    /**
     * Set isEdit.
     *
     * @param bool $isEdit
     *
     * @return ConfigRequestFields
     */
    public function setIsEdit($isEdit)
    {
        $this->is_edit = $isEdit;

        return $this;
    }

    /**
     * Get isEdit.
     *
     * @return bool
     */
    public function getIsEdit()
    {
        return $this->is_edit;
    }

    /**
     * Set fieldType.
     *
     * @param int $fieldType
     *
     * @return ConfigRequestFields
     */
    public function setFieldType($fieldType)
    {
        $this->field_type = $fieldType;

        return $this;
    }

    /**
     * Get fieldType.
     *
     * @return int
     */
    public function getFieldType()
    {
        return $this->field_type;
    }

    /**
     * Set validateCondition.
     *
     * @param string $validateCondition
     *
     * @return ConfigRequestFields
     */
    public function setValidateCondition($validateCondition)
    {
        $this->validate_condition = $validateCondition;

        return $this;
    }

    /**
     * Get validateCondition.
     *
     * @return string
     */
    public function getValidateCondition()
    {
        return $this->validate_condition;
    }

    /**
     * Set alertRequiredMessage.
     *
     * @param string $alertRequiredMessage
     *
     * @return ConfigRequestFields
     */
    public function setAlertRequiredMessage($alertRequiredMessage)
    {
        $this->alert_required_message = $alertRequiredMessage;

        return $this;
    }

    /**
     * Get alertRequiredMessage.
     *
     * @return string
     */
    public function getAlertRequiredMessage()
    {
        return $this->alert_required_message;
    }

    /**
     * Set alertValidateMessage.
     *
     * @param string $alertValidateMessage
     *
     * @return ConfigRequestFields
     */
    public function setAlertValidateMessage($alertValidateMessage)
    {
        $this->alert_validate_message = $alertValidateMessage;

        return $this;
    }

    /**
     * Get alertValidateMessage.
     *
     * @return string
     */
    public function getAlertValidateMessage()
    {
        return $this->alert_validate_message;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ConfigRequestFields
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
     * @return ConfigRequestFields
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
     * Set isPreset.
     *
     * @param bool $isPreset
     *
     * @return ConfigRequestFields
     */
    public function setIsPreset($isPreset)
    {
        $this->is_preset = $isPreset;

        return $this;
    }

    /**
     * Get isPreset.
     *
     * @return bool
     */
    public function getIsPreset()
    {
        return $this->is_preset;
    }
}
