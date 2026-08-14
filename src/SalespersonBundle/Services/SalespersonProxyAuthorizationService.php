<?php

declare(strict_types=1);

namespace SalespersonBundle\Services;

use Dingo\Api\Exception\ResourceException;
use SalespersonBundle\Entities\ShopSalesperson;
use SalespersonBundle\Entities\ShopsRelSalesperson;

class SalespersonProxyAuthorizationService
{
    /** @var object */
    private $salespersonRepository;

    /** @var object|null */
    private $shopsRelSalespersonRepository;

    public function __construct($salespersonRepository = null, $shopsRelSalespersonRepository = null)
    {
        if ($salespersonRepository === null) {
            $salespersonRepository = app('registry')->getManager('default')->getRepository(ShopSalesperson::class);
        }
        if ($shopsRelSalespersonRepository === null) {
            $shopsRelSalespersonRepository = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
        }

        $this->salespersonRepository = $salespersonRepository;
        $this->shopsRelSalespersonRepository = $shopsRelSalespersonRepository;
    }

    /**
     * 校验当前登录用户是否有权以导购身份代客操作。
     *
     * @param int|string      $companyId
     * @param int|string      $authUserId
     * @param int|string|null $buyUserId
     * @param int|string|null $distributorId
     */
    /**
     * 解析代客场景下的 acting user_id。
     *
     * @param array<string, mixed> $authInfo 须含 user_id、company_id
     * @param array<string, mixed> $input    可含 promoter_user_id、buy_user_id、distributor_id、shop_id
     */
    public function resolveActingUserId(array $authInfo, array $input, $distributorId = null)
    {
        $authUserId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];

        $promoterUserId = $input['promoter_user_id'] ?? null;
        $buyUserId = $input['buy_user_id'] ?? null;

        if ($promoterUserId === null || $promoterUserId === '' || $promoterUserId === 0 || $promoterUserId === '0') {
            return (int) $authUserId;
        }

        if ((int) $promoterUserId !== (int) $authUserId) {
            return (int) $authUserId;
        }

        if ($this->isSelfPurchase($authUserId, $buyUserId)) {
            return (int) $authUserId;
        }

        if ($distributorId === null) {
            $distributorId = $input['distributor_id'] ?? $input['shop_id'] ?? null;
        }

        $this->assertCanProxyAsSalesperson($companyId, $authUserId, $buyUserId, $distributorId);

        return (int) $buyUserId;
    }

    public function assertCanProxyAsSalesperson(
        $companyId,
        $authUserId,
        $buyUserId,
        $distributorId = null
    ): void {
        if ($this->isSelfPurchase($authUserId, $buyUserId)) {
            return;
        }

        $filter = [
            'company_id' => (int) $companyId,
            'user_id' => (int) $authUserId,
            'is_valid' => 'true',
            'salesperson_type' => 'shopping_guide',
        ];

        $salespersonInfo = $this->salespersonRepository->getInfo($filter);
        if (empty($salespersonInfo) || empty($salespersonInfo['user_id'])) {
            throw new ResourceException('无权代客下单');
        }

        if ($this->hasDistributorContext($distributorId)
            && !$this->salespersonMatchesShop($salespersonInfo, $companyId, $distributorId)
        ) {
            throw new ResourceException('无权代客下单');
        }
    }

    private function hasDistributorContext($distributorId): bool
    {
        if ($distributorId === null || $distributorId === '' || $distributorId === 0 || $distributorId === '0') {
            return false;
        }

        return (int) $distributorId > 0;
    }

    private function salespersonMatchesShop(array $salespersonInfo, $companyId, $distributorId): bool
    {
        $distributorId = (int) $distributorId;

        if (isset($salespersonInfo['shop_id']) && (int) $salespersonInfo['shop_id'] === $distributorId) {
            return true;
        }

        if ($this->shopsRelSalespersonRepository === null) {
            return false;
        }

        $relInfo = $this->shopsRelSalespersonRepository->getInfo([
            'company_id' => (int) $companyId,
            'salesperson_id' => $salespersonInfo['salesperson_id'],
            'shop_id' => $distributorId,
        ]);

        return !empty($relInfo);
    }

    private function isSelfPurchase($authUserId, $buyUserId): bool
    {
        if ($buyUserId === null || $buyUserId === '' || $buyUserId === 0 || $buyUserId === '0') {
            return true;
        }

        return (int) $authUserId === (int) $buyUserId;
    }
}
