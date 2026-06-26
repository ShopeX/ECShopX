<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 数云线下权益发送批次内单人一行。
 *
 * @ORM\Table(name="shuyun_offline_benefit_send_item",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uk_shuyun_offline_benefit_item_batch_customer", columns={"batch_id", "customer_id"})
 *     },
 *     options={"comment":"数云线下权益发送明细"}
 * )
 * @ORM\Entity(repositoryClass="ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository")
 */
class ShuyunOfflineBenefitSendItem
{
    /**
     * @ORM\Column(name="id", type="bigint", options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch")
     * @ORM\JoinColumn(name="batch_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private ShuyunOfflineBenefitSendBatch $batch;

    /**
     * @ORM\Column(name="customer_id", type="string", length=128, nullable=false)
     */
    private string $customer_id;

    /**
     * @ORM\Column(name="member_user_id", type="bigint", nullable=true, options={"unsigned": true})
     */
    private ?int $member_user_id = null;

    /**
     * @ORM\Column(name="benefit_code", type="string", length=256, nullable=true)
     */
    private ?string $benefit_code = null;

    /**
     * @ORM\Column(name="fail_reason", type="text", nullable=true)
     */
    private ?string $fail_reason = null;

    /**
     * SUCCESS / FAILURE 等与明细推送一致
     *
     * @ORM\Column(name="status", type="string", length=32, nullable=false)
     */
    private string $status = 'FAILURE';

    /**
     * @ORM\Column(name="send_time", type="integer", nullable=true, options={"unsigned": true, "comment": "实际发送时间(秒)"})
     */
    private ?int $send_time = null;

    /**
     * @ORM\Column(name="send_reason", type="string", length=512, nullable=true)
     */
    private ?string $send_reason = null;

    /**
     * @ORM\Column(name="detail_pushed_at", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $detail_pushed_at = null;

    /**
     * @ORM\Column(name="last_consume_status", type="string", length=32, nullable=true, options={"comment": "USED|NOT_USED 等"})
     */
    private ?string $last_consume_status = null;

    /**
     * @ORM\Column(name="last_consume_push_at", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $last_consume_push_at = null;

    /**
     * @ORM\Column(name="local_order_id", type="bigint", nullable=true, options={"unsigned": true})
     */
    private ?int $local_order_id = null;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned": true, "comment": "添加时间"})
     */
    private int $created = 0;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="updated", type="integer", nullable=true, options={"unsigned": true, "comment": "更新时间"})
     */
    private ?int $updated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBatch(): ShuyunOfflineBenefitSendBatch
    {
        return $this->batch;
    }

    public function setBatch(ShuyunOfflineBenefitSendBatch $batch): void
    {
        $this->batch = $batch;
    }

    public function getCustomerId(): string
    {
        return $this->customer_id;
    }

    public function setCustomerId(string $customer_id): void
    {
        $this->customer_id = $customer_id;
    }

    public function getMemberUserId(): ?int
    {
        return $this->member_user_id;
    }

    public function setMemberUserId(?int $member_user_id): void
    {
        $this->member_user_id = $member_user_id;
    }

    public function getBenefitCode(): ?string
    {
        return $this->benefit_code;
    }

    public function setBenefitCode(?string $benefit_code): void
    {
        $this->benefit_code = $benefit_code;
    }

    public function getFailReason(): ?string
    {
        return $this->fail_reason;
    }

    public function setFailReason(?string $fail_reason): void
    {
        $this->fail_reason = $fail_reason;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSendTime(): ?int
    {
        return $this->send_time;
    }

    public function setSendTime(?int $send_time): void
    {
        $this->send_time = $send_time;
    }

    public function getSendReason(): ?string
    {
        return $this->send_reason;
    }

    public function setSendReason(?string $send_reason): void
    {
        $this->send_reason = $send_reason;
    }

    public function getDetailPushedAt(): ?int
    {
        return $this->detail_pushed_at;
    }

    public function setDetailPushedAt(?int $detail_pushed_at): void
    {
        $this->detail_pushed_at = $detail_pushed_at;
    }

    public function getLastConsumeStatus(): ?string
    {
        return $this->last_consume_status;
    }

    public function setLastConsumeStatus(?string $last_consume_status): void
    {
        $this->last_consume_status = $last_consume_status;
    }

    public function getLastConsumePushAt(): ?int
    {
        return $this->last_consume_push_at;
    }

    public function setLastConsumePushAt(?int $last_consume_push_at): void
    {
        $this->last_consume_push_at = $last_consume_push_at;
    }

    public function getLocalOrderId(): ?int
    {
        return $this->local_order_id;
    }

    public function setLocalOrderId(?int $local_order_id): void
    {
        $this->local_order_id = $local_order_id;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function setCreated(int $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): ?int
    {
        return $this->updated;
    }

    public function setUpdated(?int $updated): void
    {
        $this->updated = $updated;
    }
}
