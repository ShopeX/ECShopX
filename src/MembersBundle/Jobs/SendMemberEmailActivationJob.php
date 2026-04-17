<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace MembersBundle\Jobs;

use EspierBundle\Jobs\Job;
use MembersBundle\Services\MemberEmailActivationService;

/**
 * 异步发送邮箱激活链接（注册成功后派发；payload 不含 token）。
 */
class SendMemberEmailActivationJob extends Job
{
    /** @var int */
    private $companyId;

    /** @var string */
    private $email;

    /** @var string */
    private $clientIp;

    /** @var string|null */
    private $deviceId;

    /** @var string */
    private $activationBaseUrl;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        int $companyId,
        string $email,
        string $clientIp,
        ?string $deviceId,
        string $activationBaseUrl
    ) {
        $this->companyId = $companyId;
        $this->email = $email;
        $this->clientIp = $clientIp;
        $this->deviceId = $deviceId;
        $this->activationBaseUrl = $activationBaseUrl;
    }

    public function handle(): void
    {
        (new MemberEmailActivationService())->sendActivationLinkEmail(
            $this->companyId,
            $this->email,
            $this->clientIp,
            $this->deviceId,
            $this->activationBaseUrl
        );
    }
}
