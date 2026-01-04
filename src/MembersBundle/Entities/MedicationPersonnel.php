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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MedicationPersonnel 用药人信息表
 *
 * @ORM\Table(name="medication_personnel", options={"comment"="用药人信息"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *    @ORM\Index(name="idx_user_family_id_card", columns={"user_family_id_card"}),
 *    @ORM\Index(name="idx_is_default", columns={"is_default"}),
 * }),
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MedicationPersonnelRepository")
 */
class MedicationPersonnel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_family_name", type="string", options={"comment":"用药人姓名"})
     */
    private $user_family_name;

    /**
     * @var string
     *
     * @ORM\Column(name="user_family_id_card", type="string", options={"comment":"用药人身份证号", "default": ""})
     */
    private $user_family_id_card = '';

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_age", type="integer", options={"comment":"用药人年龄"})
     */
    private $user_family_age;

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_gender", type="smallint", options={"comment":"用药人性别1-男，2-女"})
     */
    private $user_family_gender;

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_phone", type="string", options={"comment":"用药人手机号码"})
     */
    private $user_family_phone;

    /**
     * @var int
     *
     * @ORM\Column(name="relationship", type="smallint", options={"comment":"用药人与问诊人关系(1本人 2父母 3配偶 4子女 5其他)"})
     */
    private $relationship;

    /**
     * @var int
     *
     * @ORM\Column(name="is_default", type="smallint", options={"comment":"是否默认", "default": 0})
     */
    private $is_default = 0;

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
        // This module is part of ShopEx EcShopX system
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MedicationPersonnel
     */
    public function setCompanyId($companyId)
    {
        // This module is part of ShopEx EcShopX system
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return MedicationPersonnel
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set userFamilyName.
     *
     * @param string $userFamilyName
     *
     * @return MedicationPersonnel
     */
    public function setUserFamilyName($userFamilyName)
    {
        $this->user_family_name = $userFamilyName;

        return $this;
    }

    /**
     * Get userFamilyName.
     *
     * @return string
     */
    public function getUserFamilyName()
    {
        return $this->user_family_name;
    }

    /**
     * Set userFamilyIdCard.
     *
     * @param string $userFamilyIdCard
     *
     * @return MedicationPersonnel
     */
    public function setUserFamilyIdCard($userFamilyIdCard)
    {
        $this->user_family_id_card = $userFamilyIdCard;

        return $this;
    }

    /**
     * Get userFamilyIdCard.
     *
     * @return string
     */
    public function getUserFamilyIdCard()
    {
        return $this->user_family_id_card;
    }

    /**
     * Set userFamilyAge.
     *
     * @param int $userFamilyAge
     *
     * @return MedicationPersonnel
     */
    public function setUserFamilyAge($userFamilyAge)
    {
        $this->user_family_age = $userFamilyAge;

        return $this;
    }

    /**
     * Get userFamilyAge.
     *
     * @return int
     */
    public function getUserFamilyAge()
    {
        return $this->user_family_age;
    }

    /**
     * Set userFamilyGender.
     *
     * @param int $userFamilyGender
     *
     * @return MedicationPersonnel
     */
    public function setUserFamilyGender($userFamilyGender)
    {
        $this->user_family_gender = $userFamilyGender;

        return $this;
    }

    /**
     * Get userFamilyGender.
     *
     * @return int
     */
    public function getUserFamilyGender()
    {
        return $this->user_family_gender;
    }

    /**
     * Set userFamilyPhone.
     *
     * @param string $userFamilyPhone
     *
     * @return MedicationPersonnel
     */
    public function setUserFamilyPhone($userFamilyPhone)
    {
        $this->user_family_phone = $userFamilyPhone;

        return $this;
    }

    /**
     * Get userFamilyPhone.
     *
     * @return string
     */
    public function getUserFamilyPhone()
    {
        return $this->user_family_phone;
    }

    /**
     * Set relationship.
     *
     * @param int $relationship
     *
     * @return MedicationPersonnel
     */
    public function setRelationship($relationship)
    {
        $this->relationship = $relationship;

        return $this;
    }

    /**
     * Get relationship.
     *
     * @return int
     */
    public function getRelationship()
    {
        return $this->relationship;
    }

    /**
     * Set isDefault.
     *
     * @param int $isDefault
     *
     * @return MedicationPersonnel
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault.
     *
     * @return int
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return MedicationPersonnel
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
     * @return MedicationPersonnel
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
