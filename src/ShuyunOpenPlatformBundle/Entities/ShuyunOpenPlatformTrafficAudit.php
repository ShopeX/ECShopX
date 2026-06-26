<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 数云开放网关出站 / 入站回调轻量审计（完整 URL、query 等见 shuyun_open_platform 日志）。
 * 出站：`action_method` 为数云网关 action；入站：同一列存回调类型（如 token、loyalty_grade、offline_benefit），结合 `direction` 解读。
 *
 * @ORM\Table(name="shuyun_open_platform_traffic_audit",
 *     indexes={
 *         @ORM\Index(name="idx_shuyun_op_traffic_company_created", columns={"company_id", "created"}),
 *         @ORM\Index(name="idx_shuyun_op_traffic_correlation", columns={"correlation_id"})
 *     },
 *     options={"comment":"数云开放网关/回调排障审计（轻量）"}
 * )
 * @ORM\Entity(repositoryClass="ShuyunOpenPlatformBundle\Repositories\ShuyunOpenPlatformTrafficAuditRepository")
 */
class ShuyunOpenPlatformTrafficAudit
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
     * @ORM\Column(name="direction", type="string", length=16, nullable=false)
     */
    private string $direction;

    /**
     * @ORM\Column(name="correlation_id", type="string", length=128, nullable=false)
     */
    private string $correlation_id;

    /**
     * @ORM\Column(name="http_verb", type="string", length=16, nullable=false)
     */
    private string $http_verb;

    /**
     * 出站：数云 action；入站：回调类型标识（与出站共用列名，见类注释）。
     *
     * @ORM\Column(name="action_method", type="string", length=255, nullable=true)
     */
    private ?string $action_method = null;

    /**
     * @ORM\Column(name="http_status", type="smallint", nullable=true, options={"unsigned": true})
     */
    private ?int $http_status = null;

    /**
     * @ORM\Column(name="outcome", type="string", length=32, nullable=false)
     */
    private string $outcome;

    /**
     * @ORM\Column(name="request_headers_json", type="text", nullable=false, columnDefinition="LONGTEXT NOT NULL")
     */
    private string $request_headers_json;

    /**
     * @ORM\Column(name="request_body", type="text", nullable=true, columnDefinition="LONGTEXT")
     */
    private ?string $request_body = null;

    /**
     * @ORM\Column(name="response_body", type="text", nullable=true, columnDefinition="LONGTEXT")
     */
    private ?string $response_body = null;

    /**
     * @ORM\Column(name="error_message", type="string", length=1024, nullable=true)
     */
    private ?string $error_message = null;

    /**
     * 须保持默认 `null`：Gedmo Timestampable 仅在「当前值为 null」时才写入 on="create"（见 gedmo AbstractTrackingListener::prePersist）；`int` 默认 `0` 会导致永远不填、落库为 0。
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned": true, "comment": "添加时间"})
     */
    private ?int $created = null;

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

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): void
    {
        $this->direction = $direction;
    }

    public function getCorrelationId(): string
    {
        return $this->correlation_id;
    }

    public function setCorrelationId(string $correlation_id): void
    {
        $this->correlation_id = $correlation_id;
    }

    public function getHttpVerb(): string
    {
        return $this->http_verb;
    }

    public function setHttpVerb(string $http_verb): void
    {
        $this->http_verb = $http_verb;
    }

    public function getActionMethod(): ?string
    {
        return $this->action_method;
    }

    public function setActionMethod(?string $action_method): void
    {
        $this->action_method = $action_method;
    }

    public function getHttpStatus(): ?int
    {
        return $this->http_status;
    }

    public function setHttpStatus(?int $http_status): void
    {
        $this->http_status = $http_status;
    }

    public function getOutcome(): string
    {
        return $this->outcome;
    }

    public function setOutcome(string $outcome): void
    {
        $this->outcome = $outcome;
    }

    public function getRequestHeadersJson(): string
    {
        return $this->request_headers_json;
    }

    public function setRequestHeadersJson(string $request_headers_json): void
    {
        $this->request_headers_json = $request_headers_json;
    }

    public function getRequestBody(): ?string
    {
        return $this->request_body;
    }

    public function setRequestBody(?string $request_body): void
    {
        $this->request_body = $request_body;
    }

    public function getResponseBody(): ?string
    {
        return $this->response_body;
    }

    public function setResponseBody(?string $response_body): void
    {
        $this->response_body = $response_body;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(?string $error_message): void
    {
        $this->error_message = $error_message;
    }
}
