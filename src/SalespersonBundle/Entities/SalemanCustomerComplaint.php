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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * SalemanCustomerComplaints 导购员客诉表
 *
 * @ORM\Table(name="companys_saleman_customer_complaints", options={"comment":"导购客诉表"})
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalemanCustomerComplaintsRepository")
 */
class SalemanCustomerComplaint
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"comment":"主键id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", options={"comment":"会员id"})
     */
    private $user_id;

    /**
     * @var int
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":""})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string",  options={"comment":"会员名"})
     */
    private $user_name;

    /**
     * @var string
     *
     * @ORM\Column(name="user_mobile", type="string", options={"comment":"会员手机号"})
     */
    private $user_mobile;

    /**
     * @var int
     *
     * @ORM\Column(name="saleman_id", type="integer", options={"comment":"导购员id"})
     */
    private $saleman_id;

    /**
     * @var int
     *
     * @ORM\Column(name="saleman_name", type="string", options={"comment":"导购员名"})
     */
    private $saleman_name;

    /**
     * @var string
     *
     * @ORM\Column(name="saleman_avatar", type="string", nullable=true, options={"comment":"导购员企业微信头像"})
     */
    private $saleman_avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="saleman_mobile", type="string", options={"comment":"导购员手机号"})
     */
    private $saleman_mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id", "default": 0})
     *
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="saleman_distribution_name", type="string", options={"comment":"导购员所在店铺名", "default":""})
     */
    private $saleman_distribution_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="complaints_content", type="string", options={"comment":"投诉内容"})
     */
    private $complaints_content;

    /**
     * @var string
     *
     * @ORM\Column(name="complaints_images", type="text", nullable=true, options={"comment":"投诉图片"})
     */
    private $complaints_images;

    /**
     * @var int
     *
     * @ORM\Column(name="reply_status", type="boolean", options={"comment":"回复状态:0未回复；1已回复", "default":0})
     */
    private $reply_status;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_content", type="text", nullable=true, options={"comment":"回复内容，json数组，内含操作员id，操作员手机号，操作员名称，回复时间，回复内容"})
     */
    private $reply_content;

    /**
     * @var int
     *
     * @ORM\Column(name="reply_time", type="integer", nullable=true, options={"comment":"回复时间", "default": 0})
     */
    private $reply_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="reply_operator_id", type="integer", nullable=true, options={"comment":"回复操作员id", "default": 0})
     */
    private $reply_operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_operator_name", type="string", nullable=true, options={"comment":"回复操作员名称", "default": ""})
     */
    private $reply_operator_name;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_operator_mobile", type="string", nullable=true, options={"comment":"回复操作员手机号", "default": ""})
     */
    private $reply_operator_mobile;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer", options={"comment":""})
     */
    private $created;

    /**
     * @var int
     *
     * @ORM\Column(name="updated", type="integer", options={"comment":""})
     */
    private $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // ShopEx EcShopX Core Module
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return SalemanCustomerComplaint
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return SalemanCustomerComplaint
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set userName
     *
     * @param string $userName
     *
     * @return SalemanCustomerComplaint
     */
    public function setUserName($userName)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set userMobile
     *
     * @param string $userMobile
     *
     * @return SalemanCustomerComplaint
     */
    public function setUserMobile($userMobile)
    {
        $this->user_mobile = $userMobile;

        return $this;
    }

    /**
     * Get userMobile
     *
     * @return string
     */
    public function getUserMobile()
    {
        return $this->user_mobile;
    }

    /**
     * Set salemanId
     *
     * @param integer $salemanId
     *
     * @return SalemanCustomerComplaint
     */
    public function setSalemanId($salemanId)
    {
        $this->saleman_id = $salemanId;

        return $this;
    }

    /**
     * Get salemanId
     *
     * @return integer
     */
    public function getSalemanId()
    {
        return $this->saleman_id;
    }

    /**
     * Set salemanName
     *
     * @param string $salemanName
     *
     * @return SalemanCustomerComplaint
     */
    public function setSalemanName($salemanName)
    {
        $this->saleman_name = $salemanName;

        return $this;
    }

    /**
     * Get salemanName
     *
     * @return string
     */
    public function getSalemanName()
    {
        return $this->saleman_name;
    }

    /**
     * Set salemanMobile
     *
     * @param string $salemanMobile
     *
     * @return SalemanCustomerComplaint
     */
    public function setSalemanMobile($salemanMobile)
    {
        $this->saleman_mobile = $salemanMobile;

        return $this;
    }

    /**
     * Get salemanMobile
     *
     * @return string
     */
    public function getSalemanMobile()
    {
        return $this->saleman_mobile;
    }

    /**
     * Set salemanDistributionName
     *
     * @param string $salemanDistributionName
     *
     * @return SalemanCustomerComplaint
     */
    public function setSalemanDistributionName($salemanDistributionName)
    {
        $this->saleman_distribution_name = $salemanDistributionName;

        return $this;
    }

    /**
     * Get salemanDistributionName
     *
     * @return string
     */
    public function getSalemanDistributionName()
    {
        return $this->saleman_distribution_name;
    }

    /**
     * Set complaintsContent
     *
     * @param string $complaintsContent
     *
     * @return SalemanCustomerComplaint
     */
    public function setComplaintsContent($complaintsContent)
    {
        $this->complaints_content = $complaintsContent;

        return $this;
    }

    /**
     * Get complaintsContent
     *
     * @return string
     */
    public function getComplaintsContent()
    {
        return $this->complaints_content;
    }

    /**
     * Set complaintsImages
     *
     * @param string $complaintsImages
     *
     * @return SalemanCustomerComplaint
     */
    public function setComplaintsImages($complaintsImages)
    {
        $this->complaints_images = $complaintsImages;

        return $this;
    }

    /**
     * Get complaintsImages
     *
     * @return string
     */
    public function getComplaintsImages()
    {
        return $this->complaints_images;
    }

    /**
     * Set replyStatus
     *
     * @param boolean $replyStatus
     *
     * @return SalemanCustomerComplaint
     */
    public function setReplyStatus($replyStatus)
    {
        $this->reply_status = $replyStatus;

        return $this;
    }

    /**
     * Get replyStatus
     *
     * @return boolean
     */
    public function getReplyStatus()
    {
        return $this->reply_status;
    }

    /**
     * Set replyContent
     *
     * @param string $replyContent
     *
     * @return SalemanCustomerComplaint
     */
    public function setReplyContent($replyContent)
    {
        $this->reply_content = $replyContent;

        return $this;
    }

    /**
     * Get replyContent
     *
     * @return string
     */
    public function getReplyContent()
    {
        return $this->reply_content;
    }

    /**
     * Set replyTime
     *
     * @param integer $replyTime
     *
     * @return SalemanCustomerComplaint
     */
    public function setReplyTime($replyTime)
    {
        $this->reply_time = $replyTime;

        return $this;
    }

    /**
     * Get replyTime
     *
     * @return integer
     */
    public function getReplyTime()
    {
        return $this->reply_time;
    }

    /**
     * Set replyOperatorId
     *
     * @param integer $replyOperatorId
     *
     * @return SalemanCustomerComplaint
     */
    public function setReplyOperatorId($replyOperatorId)
    {
        $this->reply_operator_id = $replyOperatorId;

        return $this;
    }

    /**
     * Get replyOperatorId
     *
     * @return integer
     */
    public function getReplyOperatorId()
    {
        return $this->reply_operator_id;
    }

    /**
     * Set replyOperatorName
     *
     * @param string $replyOperatorName
     *
     * @return SalemanCustomerComplaint
     */
    public function setReplyOperatorName($replyOperatorName)
    {
        $this->reply_operator_name = $replyOperatorName;

        return $this;
    }

    /**
     * Get replyOperatorName
     *
     * @return string
     */
    public function getReplyOperatorName()
    {
        return $this->reply_operator_name;
    }

    /**
     * Set replyOperatorMobile
     *
     * @param string $replyOperatorMobile
     *
     * @return SalemanCustomerComplaint
     */
    public function setReplyOperatorMobile($replyOperatorMobile)
    {
        $this->reply_operator_mobile = $replyOperatorMobile;

        return $this;
    }

    /**
     * Get replyOperatorMobile
     *
     * @return string
     */
    public function getReplyOperatorMobile()
    {
        return $this->reply_operator_mobile;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return SalemanCustomerComplaint
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return SalemanCustomerComplaint
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set salemanAvatar.
     *
     * @param string $salemanAvatar
     *
     * @return SalemanCustomerComplaint
     */
    public function setSalemanAvatar($salemanAvatar)
    {
        $this->saleman_avatar = $salemanAvatar;

        return $this;
    }

    /**
     * Get salemanAvatar.
     *
     * @return string
     */
    public function getSalemanAvatar()
    {
        return $this->saleman_avatar;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return SalemanCustomerComplaint
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
}
