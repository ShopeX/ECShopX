<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 数云线下权益发送批次（单笔/批量共用 requestId）。
 *
 * @ORM\Table(name="shuyun_offline_benefit_send_batch",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uk_shuyun_offline_benefit_batch_company_request", columns={"company_id", "request_id"})
 *     },
 *     options={"comment":"数云线下权益发送批次"}
 * )
 * @ORM\Entity(repositoryClass="ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository")
 */
class ShuyunOfflineBenefitSendBatch
{
    /**
     * @ORM\Column(name="id", type="bigint", options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"unsigned": true})
     */
    private int $company_id;

    /**
     * @ORM\Column(name="request_id", type="string", length=128, nullable=false)
     */
    private string $request_id;

    /**
     * @ORM\Column(name="benefit_id", type="string", length=128, nullable=false)
     */
    private string $benefit_id;

    /**
     * @ORM\Column(name="send_kind", type="string", length=16, nullable=false, options={"comment": "single|batch"})
     */
    private string $send_kind;

    /**
     * @ORM\Column(name="send_time", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $send_time = null;

    /**
     * @ORM\Column(name="expire_time", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $expire_time = null;

    /**
     * @ORM\Column(name="send_remark", type="string", length=512, nullable=true)
     */
    private ?string $send_remark = null;

    /**
     * pending / processing / done / failed
     *
     * @ORM\Column(name="status", type="string", length=32, nullable=false)
     */
    private string $status = 'pending';

    /**
     * @ORM\Column(name="total_count", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $total_count = null;

    /**
     * @ORM\Column(name="success_count", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $success_count = null;

    /**
     * @ORM\Column(name="failure_count", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $failure_count = null;

    /**
     * @ORM\Column(name="report_pushed_at", type="integer", nullable=true, options={"unsigned": true})
     */
    private ?int $report_pushed_at = null;

    /**
     * @ORM\Column(name="report_last_error", type="text", nullable=true)
     */
    private ?string $report_last_error = null;

    /**
     * @ORM\Column(name="report_retry_count", type="smallint", nullable=false, options={"unsigned": true, "default": 0})
     */
    private int $report_retry_count = 0;

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

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id): void
    {
        $this->company_id = $company_id;
    }

    public function getRequestId(): string
    {
        return $this->request_id;
    }

    public function setRequestId(string $request_id): void
    {
        $this->request_id = $request_id;
    }

    public function getBenefitId(): string
    {
        return $this->benefit_id;
    }

    public function setBenefitId(string $benefit_id): void
    {
        $this->benefit_id = $benefit_id;
    }

    public function getSendKind(): string
    {
        return $this->send_kind;
    }

    public function setSendKind(string $send_kind): void
    {
        $this->send_kind = $send_kind;
    }

    public function getSendTime(): ?int
    {
        return $this->send_time;
    }

    public function setSendTime(?int $send_time): void
    {
        $this->send_time = $send_time;
    }

    public function getExpireTime(): ?int
    {
        return $this->expire_time;
    }

    public function setExpireTime(?int $expire_time): void
    {
        $this->expire_time = $expire_time;
    }

    public function getSendRemark(): ?string
    {
        return $this->send_remark;
    }

    public function setSendRemark(?string $send_remark): void
    {
        $this->send_remark = $send_remark;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getTotalCount(): ?int
    {
        return $this->total_count;
    }

    public function setTotalCount(?int $total_count): void
    {
        $this->total_count = $total_count;
    }

    public function getSuccessCount(): ?int
    {
        return $this->success_count;
    }

    public function setSuccessCount(?int $success_count): void
    {
        $this->success_count = $success_count;
    }

    public function getFailureCount(): ?int
    {
        return $this->failure_count;
    }

    public function setFailureCount(?int $failure_count): void
    {
        $this->failure_count = $failure_count;
    }

    public function getReportPushedAt(): ?int
    {
        return $this->report_pushed_at;
    }

    public function setReportPushedAt(?int $report_pushed_at): void
    {
        $this->report_pushed_at = $report_pushed_at;
    }

    public function getReportLastError(): ?string
    {
        return $this->report_last_error;
    }

    public function setReportLastError(?string $report_last_error): void
    {
        $this->report_last_error = $report_last_error;
    }

    public function getReportRetryCount(): int
    {
        return $this->report_retry_count;
    }

    public function setReportRetryCount(int $report_retry_count): void
    {
        $this->report_retry_count = $report_retry_count;
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
