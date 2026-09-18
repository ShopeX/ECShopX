<?php

declare(strict_types=1);

namespace PaymentBundle\Services;

use Dingo\Api\Exception\ResourceException;
use PaymentBundle\Services\Payments\DoumenIntlService;

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
        $hash = sha1((string) $companyId);
        $lang = $this->resolveOfflineLang();

        $definitions = [
            ['wxPaymentSetting:'.$hash, 'false'],
            ['alipayPaymentSetting:'.$hash, false],
            ['paypalPaymentSetting:'.$hash, false],
            ['chinaumsPaymentSetting:'.$hash, false],
            ['offline_paySetting:'.$hash.':'.$lang, 'false'],
            ['bspaySetting:'.$hash, false],
            ['hfPaymentSetting:'.$hash, 'false'],
            ['adaPaySetting:'.$hash, false],
            ['icbcPaymentSetting:'.$hash, 0],
        ];

        foreach ($definitions as [$redisKey, $closedValue]) {
            $this->closePaymentMethodIfOpen($redisKey, $closedValue);
        }
    }

    /**
     * @param  bool|int|string  $closedValue
     */
    private function closePaymentMethodIfOpen(string $redisKey, $closedValue): void
    {
        $raw = app('redis')->get($redisKey);
        if ($raw === false || $raw === null || $raw === '') {
            return;
        }

        $config = json_decode($raw, true);
        if (! is_array($config)) {
            return;
        }

        if (! $this->normalizeIsOpen($config['is_open'] ?? false)) {
            return;
        }

        $config['is_open'] = $closedValue;
        app('redis')->set($redisKey, json_encode($config));
    }

    private function resolveOfflineLang(): string
    {
        $lang = app('request')->input('country_code', 'zh-CN');
        if (empty($lang)) {
            return 'zh-CN';
        }

        return (string) $lang;
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
