<?php

declare(strict_types=1);

namespace PaymentBundle\Services;

use Dingo\Api\Exception\ResourceException;
use PaymentBundle\Services\Payments\AdaPaymentService;
use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\BsPayService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use PaymentBundle\Services\Payments\DoumenIntlService;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\IcbcPayService;
use PaymentBundle\Services\Payments\OfflinePayService;
use PaymentBundle\Services\Payments\PaypalService;
use PaymentBundle\Services\Payments\WechatPayService;

/**
 * 斗门国际收银台与其它支付方式互斥：启用斗门国际时关闭其它渠道；斗门国际已启用时不允许再开启其它渠道。
 */
class PaymentDoumenIntlMutualExclusionService
{
    private DoumenIntlService $doumenIntlService;

    public function __construct(?DoumenIntlService $doumenIntlService = null)
    {
        $this->doumenIntlService = $doumenIntlService ?? new DoumenIntlService();
    }

    public function isDoumenIntlOpen(int $companyId): bool
    {
        $wrapper = new PaymentsService($this->doumenIntlService);
        $config = $wrapper->getPaymentSetting($companyId);

        return $this->normalizeIsOpen($config['is_open'] ?? false);
    }

    public function validateBeforeSave(int $companyId, string $payType, bool $isOpening): void
    {
        if ($payType === 'doumen_intl' || $payType === 'point_pay' || ! $isOpening) {
            return;
        }

        if ($this->isDoumenIntlOpen($companyId)) {
            throw new ResourceException(trans('payment.close_doumen_intl_first'));
        }
    }

    public function closeAllOtherPaymentMethods(int $companyId): void
    {
        $definitions = [
            [WechatPayService::class, [0, true], 'false'],
            [AlipayService::class, [0, true], false],
            [PaypalService::class, [0], false],
            [ChinaumsPayService::class, [], false],
            [OfflinePayService::class, [], 'false'],
            [BsPayService::class, [], false],
            [HfPayService::class, [], 'false'],
            [AdaPaymentService::class, [], false],
            [IcbcPayService::class, [], 0],
        ];

        foreach ($definitions as [$serviceClass, $constructorArgs, $closedValue]) {
            $this->closePaymentMethodIfOpen($companyId, $serviceClass, $constructorArgs, $closedValue);
        }
    }

    /**
     * @param  class-string  $serviceClass
     * @param  array<int, mixed>  $constructorArgs
     * @param  bool|int|string  $closedValue
     */
    private function closePaymentMethodIfOpen(
        int $companyId,
        string $serviceClass,
        array $constructorArgs,
        $closedValue
    ): void {
        $service = new $serviceClass(...$constructorArgs);
        $wrapper = new PaymentsService($service);
        $config = $wrapper->getPaymentSetting($companyId);

        if ($config === [] || $config === null) {
            return;
        }

        if (! $this->normalizeIsOpen($config['is_open'] ?? false)) {
            return;
        }

        $config['is_open'] = $closedValue;
        $wrapper->setPaymentSetting($companyId, $config);
    }

    /**
     * @param  mixed  $value
     */
    private function normalizeIsOpen($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return false;
    }
}
