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

namespace PaymentBundle\Services\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use LogicException;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Repositories\NormalOrdersItemsRepository;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Clients\DoumenIntlGatewayClient;
use PaymentBundle\Interfaces\Payment as PaymentInterface;
use PaymentBundle\Support\DoumenIntlSignature;
use PaymentBundle\Support\DoumenIntlStatusMapper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DoumenIntlService implements PaymentInterface
{
    /**
     * 设置斗门国际支付配置（平台级，distributorId 固定为 0）
     */
    public function setPaymentSetting($companyId, $data)
    {
        if (isset($data['X-SecretKey']) && $data['X-SecretKey'] === '') {
            $existing = $this->getRawPaymentSetting($companyId);
            if (!empty($existing['X-SecretKey'])) {
                $data['X-SecretKey'] = $existing['X-SecretKey'];
            }
        }

        return app('redis')->set($this->genRedisId($companyId), json_encode($data));
    }

    /**
     * 获取斗门国际支付配置（SecretKey 脱敏）
     */
    public function getPaymentSetting($companyId)
    {
        $data = $this->getRawPaymentSetting($companyId);
        if (!$data) {
            return [];
        }

        $data['X-SecretKey'] = $this->maskSecret($data['X-SecretKey'] ?? '');

        return $data;
    }

    public function verifyNotifySignature(int $companyId, string $rawBody, string $signature): bool
    {
        $secretKey = (string) ($this->getRawPaymentSetting($companyId)['X-SecretKey'] ?? '');

        return (new DoumenIntlSignature())->verifyNotify($rawBody, $secretKey, $signature);
    }

    /**
     * 配置是否完整且已启用
     */
    public function isConfigured($companyId): bool
    {
        $data = $this->getRawPaymentSetting($companyId);
        if (empty($data) || empty($data['is_open'])) {
            return false;
        }

        return !empty($data['X-AccessCode'])
            && !empty($data['X-SecretKey'])
            && !empty($data['appId'])
            && !empty($data['return_url']);
    }

    /**
     * 获取 redis 存储的 ID（平台级，不含 distributor 前缀）
     */
    private function genRedisId($companyId): string
    {
        return 'doumenIntlPaymentSetting:'.sha1((string) $companyId);
    }

    /**
     * @return array<string, mixed>
     */
    private function getRawPaymentSetting($companyId): array
    {
        $data = app('redis')->get($this->genRedisId($companyId));
        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function maskSecret(?string $secret): string
    {
        if ($secret === null || $secret === '') {
            return '';
        }
        $len = strlen($secret);
        if ($len <= 4) {
            return '****';
        }

        return '****'.substr($secret, -4);
    }

    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        throw new BadRequestHttpException('斗门国际支付不支持预存款充值');
    }

    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        $this->assertNormalTradeSource($data);

        $paymentSetting = $this->getRawPaymentSetting($data['company_id']);
        if (empty($paymentSetting) || empty($paymentSetting['is_open'])) {
            throw new BadRequestHttpException('请检查斗门国际支付配置');
        }

        $returnUrl = $data['return_url'] ?? ($paymentSetting['return_url'] ?? '');
        $products = $this->buildCheckoutProducts((int) $data['company_id'], (string) $data['order_id']);
        if ($products === []) {
            throw new BadRequestHttpException('斗门国际支付缺少订单商品明细');
        }

        $checkoutBody = [
            'checkoutType' => 'DOU_MEN',
            'requestId' => md5(uniqid($data['trade_id'].'-', true)),
            'appId' => $paymentSetting['appId'],
            'merchantOrderId' => (string) $data['trade_id'],
            'amount' => (int) $data['pay_fee'],
            'currency' => $data['fee_type'],
            'successUrl' => $returnUrl,
            'failureUrl' => $returnUrl,
            'cancelUrl' => $returnUrl,
            'notificationUrl' => config('doumen_intl.notify_url'),
            'products' => $products,
        ];

        if (isset($data['auto_cancel_time']) && (int) $data['auto_cancel_time'] > time()) {
            $checkoutBody['validityPeriod'] = (int) $data['auto_cancel_time'] - time();
        }

        $gatewayClient = $this->createGatewayClient($paymentSetting);
        $checkoutResult = $gatewayClient->createCheckout($checkoutBody);

        if (($checkoutResult['status'] ?? '') !== 'REQUEST_CUSTOMER_ACTION') {
            throw new BadRequestHttpException('斗门国际支付创建失败');
        }

        $payUrl = $checkoutResult['nextAction']['url'] ?? '';
        $transactionId = $checkoutResult['id'] ?? '';

        $tradeService = new TradeService();
        $tradeService->updateOneBy(['trade_id' => $data['trade_id']], ['transaction_id' => $transactionId]);

        return ['pay_url' => $payUrl];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildCheckoutProducts(int $companyId, string $orderId): array
    {
        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = app('registry');
        /** @var NormalOrdersItemsRepository $itemsRepository */
        $itemsRepository = $registry->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderItems = $itemsRepository->get($companyId, $orderId);

        $products = [];
        foreach ($orderItems as $item) {
            $products[] = $this->mapOrderItemToCheckoutProduct($item);
        }

        return $products;
    }

    /**
     * @param  array<string, mixed>  $item
     *
     * @return array<string, mixed>
     */
    private function mapOrderItemToCheckoutProduct(array $item): array
    {
        $itemBn = (string) ($item['item_bn'] ?? '');
        $itemName = (string) ($item['item_name'] ?? '');
        $itemSpecDesc = (string) ($item['item_spec_desc'] ?? '');
        if ($itemSpecDesc === '') {
            $itemSpecDesc = $itemName;
        }

        return [
            'type' => '',
            'url' => '',
            'code' => $itemBn,
            'sku' => $itemBn,
            'name' => $itemName,
            'desc' => $itemSpecDesc,
            'quantity' => (int) ($item['num'] ?? 0),
            'unitPrice' => (int) ($item['price'] ?? 0),
            'totalAmount' => (int) ($item['total_fee'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertNormalTradeSource(array $data): void
    {
        $tradeSourceType = $data['trade_source_type'] ?? 'normal';
        if ($tradeSourceType === 'normal' || strpos($tradeSourceType, 'normal_') === 0) {
            return;
        }

        throw new BadRequestHttpException('斗门国际支付不支持该交易场景');
    }

    /**
     * @param  array<string, mixed>  $paymentSetting
     */
    private function createGatewayClient(array $paymentSetting): DoumenIntlGatewayClient
    {
        $baseUri = (string) config('doumen_intl.base_url');

        return new DoumenIntlGatewayClient(
            (string) $paymentSetting['X-AccessCode'],
            (string) $paymentSetting['X-SecretKey'],
            $baseUri,
            $this->resolveHttpClient($baseUri),
            $this->createTokenStore()
        );
    }

    private function resolveHttpClient(string $baseUri): ClientInterface
    {
        if (app()->bound('doumen_intl.http_client')) {
            return app('doumen_intl.http_client');
        }

        return new Client(['base_uri' => $baseUri]);
    }

    private function createTokenStore(): object
    {
        $redis = app('redis');

        return new class($redis) {
            public function __construct(private $redis)
            {
            }

            public function get(string $key): ?array
            {
                $raw = $this->redis->get($key);
                if ($raw === false || $raw === null || $raw === '') {
                    return null;
                }
                $decoded = json_decode((string) $raw, true);
                if (! is_array($decoded) || ! isset($decoded['token'], $decoded['expires_at'])) {
                    return null;
                }
                if ((int) $decoded['expires_at'] <= time()) {
                    return null;
                }

                return [
                    'token' => (string) $decoded['token'],
                    'expires_at' => (int) $decoded['expires_at'],
                ];
            }

            public function set(string $key, string $token, int $ttlSeconds): void
            {
                $this->redis->set($key, json_encode([
                    'token' => $token,
                    'expires_at' => time() + $ttlSeconds,
                ]));
            }
        };
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function query($data)
    {
        $paymentSetting = $this->getRawPaymentSetting($data['company_id']);
        $gatewayClient = $this->createGatewayClient($paymentSetting);
        $gatewayResult = $gatewayClient->queryPayment((string) $data['transaction_id']);
        $mapped = DoumenIntlStatusMapper::mapPaymentQueryStatus((string) ($gatewayResult['status'] ?? ''));

        $result = [
            'status' => $mapped['status'],
            'transaction_id' => $data['transaction_id'],
            'pay_type' => 'doumen_intl',
        ];

        if (isset($mapped['msg'])) {
            $result['msg'] = $mapped['msg'];
        }

        return $result;
    }

    public function getPayOrderInfo($companyId, $trade_id)
    {
        throw new LogicException('Doumen Intl getPayOrderInfo is not implemented');
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        $paymentSetting = $this->getRawPaymentSetting($data['company_id']);
        if (empty($paymentSetting) || empty($paymentSetting['is_open'])) {
            throw new BadRequestHttpException('请检查斗门国际支付配置');
        }

        $refundBody = [
            'requestId' => md5(uniqid((string) ($data['refund_bn'] ?? $data['trade_id']).'-', true)),
            'appId' => $paymentSetting['appId'],
            'merchantOrderId' => $data['refund_bn'] ?? $data['trade_id'],
            'refundTime' => date('Y-m-d H:i:s'),
            'amount' => (int) $data['refund_fee'],
            'currency' => $data['fee_type'],
            'refundReason' => 'Refund',
            'merchantMemo' => (string) ($data['order_id'] ?? ''),
            'notificationUrl' => config('doumen_intl.notify_url'),
        ];

        $gatewayClient = $this->createGatewayClient($paymentSetting);
        app('log')->info('doumen_intl_service_do_refund_refundBody', ['refundBody' => $refundBody]);
        $result = $gatewayClient->refundWithResult((string) $data['transaction_id'], $refundBody);
        app('log')->info('doumen_intl_service_do_refund_result', ['result' => $result]);

        if (! $result['ok']) {
            return [
                'status' => 'FAIL',
                'error_code' => $result['code'],
                'error_desc' => $result['message'],
            ];
        }

        return [
            'status' => 'SUCCESS',
            'refund_id' => $result['data']['id'] ?? '',
        ];
    }

    public function getRefundOrderInfo($companyId, $data)
    {
        return [];
    }
}
