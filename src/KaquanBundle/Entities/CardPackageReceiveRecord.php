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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CardPackageReceiveRecord(等级卡券包领取记录表-防止重复领取)
 *
 * @ORM\Table(name="card_package_receive_record", options={"comment":"等级卡券包领取记录表"},indexes={
 *  @ORM\Index(name="idx_companyid", columns={"company_id"}),
 *  @ORM\Index(name="idx_user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\CardPackageReceiveRecordRepository")
 */
class CardPackageReceiveRecord
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"主键ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     * @ORM\Column(name="user_id", type="bigint", nullable=false, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     * @ORM\Column(name="grade_id", type="bigint", nullable=false, options={"comment":"等级id"})
     */
    private $grade_id;

    /**
     * @var string
     * @ORM\Column(name="trigger_type", type="string", length=20, nullable=false, options={"comment":"领取类型，vip_grade/grade vip等级/等级"})
     */
    private $trigger_type;

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
     * @ORM\Column(type="integer")
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
     * @return CardPackageReceiveRecord
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return CardPackageReceiveRecord
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
     * Set gradeId.
     *
     * @param int $gradeId
     *
     * @return CardPackageReceiveRecord
     */
    public function setGradeId($gradeId)
    {
        $this->grade_id = $gradeId;

        return $this;
    }

    /**
     * Get gradeId.
     *
     * @return int
     */
    public function getGradeId()
    {
        return $this->grade_id;
    }

    /**
     * Set triggerType.
     *
     * @param string $triggerType
     *
     * @return CardPackageReceiveRecord
     */
    public function setTriggerType($triggerType)
    {
        $this->trigger_type = $triggerType;

        return $this;
    }

    /**
     * Get triggerType.
     *
     * @return string
     */
    public function getTriggerType()
    {
        return $this->trigger_type;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CardPackageReceiveRecord
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
     * @param int $updated
     *
     * @return CardPackageReceiveRecord
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
