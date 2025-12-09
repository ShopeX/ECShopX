<?php

namespace ThirdPartyBundle\Services\DmCrm;

use function AlibabaCloud\Client\json;
use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\DmCrm\DmService;
use KaquanBundle\Entities\UserDiscount;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\DiscountCardService as KaquanDiscountCardService;
use KaquanBundle\Services\UserDiscountService;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Entities\MembersInfo;

class DiscountCardService extends DmService
{

    protected $status = 1; // 1:表示正常 2:表示过期 3:表示无库存
    protected $cardApplyChannel = 'ThirdmicroMall'; // 第三方商城
    protected $goodsFilterFlag = 1; // 是否返回适用商品 1:是 0:否 默认不返回
    protected $limitDiscountCoupon = false; // 线下优惠与折扣券互斥标识 默认值:false false:折扣券与线下优惠可共享 true:折扣券与线下优惠不可共享，券不可使用

    /**
     * 达摩CRM消息订阅:卡券模板创建:cardTemplateCreate
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardTemplateCreate($companyId, $msgBody)
    {
        // 处理卡券类型
        $cardType = $this->getCardType($msgBody['cardType'], $msg);
        if (!$cardType) {
            app('log')->error('达摩CRM::卡券模板创建::event=cardTemplateCreate:卡券类型错误::cardType=' . $msgBody['cardType']);
            return false;
        }
        // 处理卡券应用渠道
        $cardApplyChannel = $this->getCardApplyChannel($msgBody['cardApplyChannel'], $msg);
        if (!$cardApplyChannel) {
            app('log')->error('达摩CRM::卡券模板创建::event=cardTemplateCreate:卡券应用渠道错误::cardApplyChannel=' . $msgBody['cardApplyChannel']);
            return false;
        }
        try {
            // 去达摩CRM获取卡券信息
            $cardInfo = $this->getDiscountCardInfo($msgBody['coupCardId']);
            app('log')->info('达摩CRM::卡券模板创建::event=cardTemplateCreate:达摩卡券模板信息::cardInfo=' . json_encode($cardInfo));
            // 转换为本地卡券模板
            $cardTemplate = $this->formatDiscountCardInfo($companyId, $cardInfo, $msg);
            if (empty($cardTemplate)) {
                app('log')->error('达摩CRM::卡券模板创建::event=cardTemplateCreate:卡券模板转换失败::msgBody=' . json_encode($msgBody).',原因:'.$msg);
                return false;
            }
            app('log')->info('达摩CRM::卡券模板创建::event=cardTemplateCreate:转换后的卡券模板信息::cardTemplate=' . json_encode($cardTemplate));
            $discountCardService = new KaquanService(new KaquanDiscountCardService());
            $cardResult = $discountCardService->createKaquan($cardTemplate, '');
            app('log')->info('达摩CRM::卡券模板创建::event=cardTemplateCreate:卡券模板创建成功::msgBody=' . json_encode($msgBody).',cardResult='.json_encode($cardResult));
            return true;
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
            app('log')->error('达摩CRM::卡券模板创建::event=cardTemplateCreate:卡券模板创建失败::msgBody=' . json_encode($msgBody).',原因:'.json_encode($error));
            return false;
        }
    }

    /**
     * 达摩CRM消息订阅:卡券模板修改:cardTemplateModify
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardTemplateModify($companyId, $msgBody)
    {
        // 处理卡券类型
        $cardType = $this->getCardType($msgBody['cardType'], $msg);
        if (!$cardType) {
            return false;
        }
        // 处理卡券应用渠道
        $cardApplyChannel = $this->getCardApplyChannel($msgBody['cardApplyChannel'], $msg);
        if (!$cardApplyChannel) {
            return false;
        }
        try {
            
            // 去达摩CRM获取卡券信息
            $cardInfo = $this->getDiscountCardInfo($msgBody['coupCardId']);
            app('log')->info('达摩CRM::卡券模板更新::event=cardTemplateModify:达摩卡券模板信息::cardInfo=' . json_encode($cardInfo));
            // 转换为本地卡券模板
            $cardTemplate = $this->formatDiscountCardInfo($companyId, $cardInfo, $msg);
            if (empty($cardTemplate)) {
                app('log')->error('达摩CRM::卡券模板更新::event=cardTemplateModify:卡券模板转换失败::msgBody=' . json_encode($msgBody).',原因:'.$msg);
                return false;
            }
            app('log')->info('达摩CRM::卡券模板更新::event=cardTemplateModify:转换后的卡券模板信息::cardTemplate=' . json_encode($cardTemplate));
            $discountCardService = new KaquanService(new KaquanDiscountCardService());
            $cardInfo = $discountCardService->getInfo(['dm_card_id' => $msgBody['coupCardId']]);
            if (empty($cardInfo)) {
                app('log')->error('达摩CRM::卡券模板更新::event=cardTemplateModify:卡券模板不存在::msgBody=' . json_encode($msgBody).':创建卡券模板');
                $cardResult = $discountCardService->createKaquan($cardTemplate, '');
                app('log')->info('达摩CRM::卡券模板更新::event=cardTemplateModify:卡券模板创建成功::msgBody=' . json_encode($msgBody).',cardResult='.json_encode($cardResult));
            } else {
                $cardTemplate['card_id'] = $cardInfo['card_id'];
                $cardResult = $discountCardService->updateKaquan($cardTemplate, '');
                app('log')->info('达摩CRM::卡券模板更新::event=cardTemplateModify:卡券模板更新成功::msgBody=' . json_encode($msgBody).',cardResult='.json_encode($cardResult));
            }
            return true;
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
            app('log')->error('达摩CRM::卡券模板更新::event=cardTemplateModify:卡券模板更新失败::msgBody=' . json_encode($msgBody).',原因:'.json_encode($error));
            return false;
        }
    }

    /**
     * 达摩CRM消息订阅:卡券模板删除:cardTemplateDelete
     * 将已领取的会员卡券先销毁，再删除卡券模板
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardTemplateDelete($companyId, $msgBody)
    {
        $discountCardService = new KaquanService(new KaquanDiscountCardService());
        $cardInfo = $discountCardService->getInfo(['dm_card_id' => $msgBody['coupCardId']]);
        if (empty($cardInfo)) {
            app('log')->error('达摩CRM::卡券模板删除::event=cardTemplateDelete:卡券模板不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        try {
            // 查询已领取的会员卡券
            $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
            $filter = [
                'company_id' => $companyId,
                'card_id' => $cardInfo['card_id'],
            ];
            $userDiscountList = $userDiscountRepository->getUserCardList($filter, 0, 2000);
            if (!empty($userDiscountList)) {
                foreach ($userDiscountList as $userDiscount) {
                    $updateParams = [
                        'status' => 6,
                        'expired_time' => time(),
                    ];
                    $userFilter = [
                        'company_id' => $companyId,
                        'id' => $userDiscount['id'],
                    ];
                    app('log')->info('达摩CRM::卡券模板删除::event=cardTemplateDelete:会员卡券销毁参数::msgBody=' . json_encode($msgBody).',params=' . json_encode($updateParams).',filter='.json_encode($userFilter));
                    $userDiscountRepository->updateUserCard($updateParams, $userFilter);
                }
            }
            // 删除卡券模板
            $discountCardService->deleteKaquan(['company_id' => $companyId, 'card_id' => $cardInfo['card_id']], '', false);
            app('log')->info('达摩CRM::卡券模板删除::event=cardTemplateDelete:卡券模板删除成功::msgBody=' . json_encode($msgBody));
            return true;
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
            app('log')->error('达摩CRM::卡券模板删除::event=cardTemplateDelete:卡券模板删除失败::msgBody=' . json_encode($msgBody).',原因:'.json_encode($error));
            return true;
        }
    }

    /**
     * 达摩CRM消息订阅:卡券模板批量销毁:cardDestroyBatch
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardDestroyBatch($companyId, $msgBody)
    {
        $discountCardService = new KaquanService(new KaquanDiscountCardService());
        $cardInfo = $discountCardService->getInfo(['dm_card_id' => $msgBody['coupCardId']]);
        if (empty($cardInfo)) {
            app('log')->error('达摩CRM::卡券模板批量销毁::event=cardDestroyBatch:卡券模板不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        try {
            $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
            $succData = $failData = [];
            foreach ($msgBody['memberList'] as $data) {
                $params = [
                    'status' => 6,
                    'expired_time' => time(),
                ];
                $filter = [
                    'company_id' => $companyId,
                    'dm_card_code' => $data['cardCode'],
                ];
                app('log')->info('达摩CRM::卡券模板批量销毁::event=cardDestroyBatch:卡券模板销毁成功::msgBody=' . json_encode($msgBody).',params='.json_encode($params).',filter='.json_encode($filter));
                $result = $userDiscountRepository->updateUserCard($params, $filter);
                if ($result['status']) {
                    $succData[] = $data['cardCode'];
                } else {
                    $failData[] = $data['cardCode'];
                }
            }
            app('log')->info('达摩CRM::卡券模板批量销毁::event=cardDestroyBatch:卡券模板销毁成功::msgBody=' . json_encode($msgBody).',succData='.json_encode($succData).',failData='.json_encode($failData));
            return true;
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
            app('log')->error('达摩CRM::卡券模板批量销毁::event=cardDestroyBatch:卡券模板销毁失败::msgBody=' . json_encode($msgBody).',原因:'.json_encode($error));
            return true;
        }
    }

    /**
     * 达摩CRM消息订阅:卡券模板销毁:cardDestroy
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardDestroy($companyId, $msgBody)
    {
        $discountCardService = new KaquanService(new KaquanDiscountCardService());
        $cardInfo = $discountCardService->getInfo(['dm_card_id' => $msgBody['coupCardId']]);
        if (empty($cardInfo)) {
            app('log')->error('达摩CRM::卡券模板销毁::event=cardDestroy:卡券模板不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        try {
            $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
            $params = [
                'status' => 6,
                'expired_time' => time(),
            ];
            $filter = [
                'company_id' => $companyId,
                'dm_card_code' => $msgBody['cardCode'],
            ];
            app('log')->info('达摩CRM::卡券模板销毁::event=cardDestroy:卡券模板销毁参数::msgBody=' . json_encode($msgBody).',params=' . json_encode($params).',filter='.json_encode($filter));
            $result = $userDiscountRepository->updateUserCard($params, $filter);
            app('log')->info('达摩CRM::卡券模板销毁::event=cardDestroy:卡券模板销毁结果::msgBody=' . json_encode($msgBody).',result=' . json_encode($result));
            return true;
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
            app('log')->error('达摩CRM::卡券模板销毁::event=cardDestroy:卡券模板销毁失败::msgBody=' . json_encode($msgBody).',原因:'.json_encode($error));
            return true;
        }
    }

    /**
     * 达摩CRM消息订阅:卡券领取:cardReceive
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardReceive($companyId, $msgBody)
    {
        // 查询会员卡券信息，如果已存在，则不处理
        $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
        $filter = [
            'company_id' => $companyId,
            'dm_card_code' => $msgBody['cardCode'],
        ];
        $userDiscount = $userDiscountRepository->get($filter);
        if (!empty($userDiscount)) {
            app('log')->info('达摩CRM::会员卡券领取::event=cardReceive:会员卡券已存在，无需处理::msgBody=' . json_encode($msgBody));
            return true;
        }
        // 查询本地卡券模板信息
        $discountCardService = new KaquanService(new KaquanDiscountCardService());
        $cardInfo = $discountCardService->getInfo(['dm_card_id' => $msgBody['coupCardId']]);
        if (empty($cardInfo)) {
            app('log')->error('达摩CRM::会员卡券领取::event=cardReceive:卡券模板不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        // 使用cardNo查询会员信息
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $filter = [
            'company_id' => $companyId,
            'dm_card_no' => $msgBody['cardNo'],
        ];
        $memberInfo = $membersInfoRepository->getInfo($filter);
        if (empty($memberInfo)) {
            app('log')->error('达摩CRM::会员卡券领取::event=cardReceive:会员信息不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        // 领取会员卡券
        $userDiscountService = new UserDiscountService();
        $result = $userDiscountService->userGetCard($companyId, $cardInfo['card_id'], $memberInfo['user_id'], '达摩CRM领取事件', 0, '', $msgBody['cardCode']);
        app('log')->info('达摩CRM::会员卡券领取::event=cardReceive:会员卡券领取成功::msgBody=' . json_encode($msgBody).',result='.json_encode($result));
        return true;
    }

    /**
     * 达摩CRM消息订阅:卡券批量投放:cardPutOn
     * 等同于批量领取
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardPutOn($companyId, $msgBody)
    {
        // 查询本地卡券模板信息
        $discountCardService = new KaquanService(new KaquanDiscountCardService());
        $cardInfo = $discountCardService->getInfo(['dm_card_id' => $msgBody['coupCardId']]);
        if (empty($cardInfo)) {
            app('log')->error('达摩CRM::卡券批量投放::event=cardPutOn:卡券模板不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        app('log')->info('达摩CRM::卡券批量投放::event=cardPutOn:卡券模板信息::coupCardId=' . $msgBody['coupCardId'].',cardInfo='.json_encode($cardInfo));
        // 查询会员信息
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $memberList = array_column($msgBody['memberList'], null, 'cardNo');
        $dmCardNoList = array_column($msgBody['memberList'], 'cardNo');
        $filter = [
            'company_id' => $companyId,
            'dm_card_no' => $dmCardNoList,
        ];
        $memberInfoList = $membersInfoRepository->getDataList($filter, 'user_id,username,dm_card_no');
        if (empty($memberInfoList)) {
            app('log')->error('达摩CRM::卡券批量投放::event=cardPutOn:会员信息不存在::coupCardId=' . $msgBody['coupCardId'].',dmCardNoList='.json_encode($dmCardNoList));
            return true;
        }
        app('log')->info('达摩CRM::卡券批量投放::event=cardPutOn:会员信息列表::coupCardId=' . $msgBody['coupCardId'].',memberInfoList='.json_encode($memberInfoList));
        // 领取会员卡券
        $userDiscountService = new UserDiscountService();
        foreach ($memberInfoList as $member) {
            $cardCode = $memberList[$member['dm_card_no']]['cardCode'];
            app('log')->info('达摩CRM::卡券批量投放::event=cardPutOn:会员信息::coupCardId=' . $msgBody['coupCardId'].',member='.json_encode($member).',cardCode='.$cardCode);
            try {
                $userDiscountService->userGetCard($companyId, $cardInfo['card_id'], $member['user_id'], '达摩CRM批量投放事件', 0, '', $cardCode);
                app('log')->info('达摩CRM::卡券批量投放::event=cardPutOn:卡券批量投放成功::coupCardId=' . $msgBody['coupCardId'].',user_id='.$member['user_id'].',cardCode='.$cardCode);
            } catch (\Exception $e) {
                $error = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                ];
                app('log')->error('达摩CRM::卡券批量投放::event=cardPutOn:卡券批量投放失败::coupCardId=' . $msgBody['coupCardId'].',user_id='.$member['user_id'].',cardCode='.$cardCode.',error='.json_encode($error));
            }
        }
        return true;
    }

    /**
     * 达摩CRM消息订阅:卡券占用:cardOccupy
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardOccupy($companyId, $msgBody)
    {
        // 查询会员卡券信息
        $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
        $filter = [
            'company_id' => $companyId,
            'dm_card_code' => $msgBody['cardCode'],
            'status' => 1,// 已领取
        ];
        $userDiscount = $userDiscountRepository->get($filter);
        if (empty($userDiscount)) {
            app('log')->error('达摩CRM::卡券占用::event=cardOccupy:会员卡券不存在或状态非已领取::msgBody=' . json_encode($msgBody));
            return true;
        }
        // 使用cardNo查询会员信息
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $filter = [
            'company_id' => $companyId,
            'dm_card_no' => $msgBody['cardNo'],
        ];
        $memberInfo = $membersInfoRepository->getInfo($filter);
        if (empty($memberInfo)) {
            app('log')->error('达摩CRM::卡券占用::event=cardOccupy:会员信息不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        $params = [
            'user_id' => $memberInfo['user_id'],
            'consume_outer_str' => '达摩CRM卡券占用事件',
        ];
        $userDiscountService = new UserDiscountService();
        $result = $userDiscountService->userConsumeCard($companyId, $userDiscount->getCode(), $params);
        app('log')->info('达摩CRM::卡券占用::event=cardOccupy:卡券占用成功::msgBody=' . json_encode($msgBody).',result='.json_encode($result));
        return true;
    }

    /**
     * 达摩CRM消息订阅:卡券解除占用:cardRelease
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardRelease($companyId, $msgBody)
    {
        // 使用cardNo查询会员信息
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $filter = [
            'company_id' => $companyId,
            'dm_card_no' => $msgBody['cardNo'],
        ];
        $memberInfo = $membersInfoRepository->getInfo($filter);
        if (empty($memberInfo)) {
            app('log')->error('达摩CRM::卡券解除占用::event=cardRelease:会员信息不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        $userDiscountService = new UserDiscountService();
        $userDiscountService->callbackUserCard($companyId, $msgBody['cardCode'], $memberInfo['user_id']);
        app('log')->info('达摩CRM::卡券解除占用::event=cardRelease:卡券解除占用成功::msgBody=' . json_encode($msgBody));
        return true;
    }

    /**
     * 达摩CRM消息订阅:卡券核销:cardConsume
     * @param int $companyId 公司ID
     * @param array $msgBody 卡券模板信息
     * @return bool
     */
    public function syncCardConsume($companyId, $msgBody)
    {
        // 查询会员卡券信息
        $userDiscountService = new UserDiscountService();
        $filter = [
            'company_id' => $companyId,
            'dm_card_code' => $msgBody['cardCode'],
        ];
        $userDiscount = $userDiscountService->getUserCardInfo($filter);
        if (empty($userDiscount)) {
            app('log')->error('达摩CRM::卡券核销::event=cardConsume:会员卡券不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        if ($userDiscount['detail']['status'] == 2) {
            app('log')->info('达摩CRM::卡券核销::event=cardConsume:会员卡券已核销::msgBody=' . json_encode($msgBody));
            $usedTime = $msgBody['usedTime'] ? bcdiv($msgBody['usedTime'], 1000, 0) : time();
            // 更新核销记录
            $conn = app('registry')->getConnection('default');
            $sql = "UPDATE `kaquan_user_discount_logs` SET `shop_name`='" . $msgBody['orderStoreName'] . "',`used_time`='" . $usedTime . "',`used_order`='" . $msgBody['orderNumber'] . "' WHERE `code`='" . $msgBody['cardCode'] . "' AND `company_id`=" . $companyId;
            $conn->executeUpdate($sql);
            app('log')->info('达摩CRM::卡券核销::event=cardConsume:更新核销记录::sql=' . $sql);
            return true;
        }
        // 使用cardNo查询会员信息
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $filter = [
            'company_id' => $companyId,
            'dm_card_no' => $msgBody['cardNo'],
        ];
        $memberInfo = $membersInfoRepository->getInfo($filter);
        if (empty($memberInfo)) {
            app('log')->error('达摩CRM::卡券占用::event=cardOccupy:会员信息不存在::msgBody=' . json_encode($msgBody));
            return true;
        }
        $params = [
            'user_id' => $memberInfo['user_id'],
            'consume_outer_str' => '达摩CRM卡券核销事件',
            'location_name' => $msgBody['orderStoreName'] ?? '',
            'trans_id' => $msgBody['orderNumber'] ?? '',
        ];
        $userDiscountService->userConsumeCard($companyId, $userDiscount['detail']['code'], $params);
        app('log')->info('达摩CRM::卡券核销::event=cardConsume:卡券核销成功::msgBody=' . json_encode($msgBody));
        return true;
    }

    public function getDiscountCardInfo($cardId)
    {
        $worker = '/cgi-api/coupon/get_coupon_template';
        $params = [
            'couponCardId' => $cardId,
            'goodsFilterFlag' => $this->goodsFilterFlag,
        ];
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        if (isset($result['code']) && $result['code'] == 0) {
            return $result['result'];
        } else {
            throw new ResourceException($result['message']);
        }
    }

    private function formatDiscountCardInfo($companyId, $data, &$msg)
    {
        $cardApplyChannel = $this->getCardApplyChannel($data['cardApplyChannel'], $msg);
        if (!$cardApplyChannel) {
            return false;
        }
        $cardType = $this->getCardType($data['couponType'], $msg);
        if (!$cardType) {
            return false;
        }
        $result = [
            'company_id' => $companyId,
            'dm_card_id' => $data['couponCardId'],
            'card_type' => $cardType,
            'title' => $data['couponName'],
            'description' => $data['useDescript'],
            'least_cost' => $data['useCondition']['saleLimit']['fee'],
            'get_limit' => $data['cardLimit'],
            'dm_use_channel' => $cardApplyChannel,
            'quantity' => $data['couponStock'] ?? 999999,
            'most_cost' => 999999,
            'color' => '#000000',
            'source_type' => 'dmcrm',
            'use_platform' => '',
            'receive' => 'false',
            'use_scenes' => '',
        ];
        // 卡券应用渠道
        if (in_array('ThirdmicroMall', $cardApplyChannel)) {
            $result['use_platform'] = 'mall';
            $result['use_scenes'] = 'ONLINE';
            $result['receive'] = 'true';
        } else {
            $result['use_platform'] = 'store';
            $result['use_scenes'] = 'SWEEP';
        }
        if ($result['card_type'] == 'cash') {
            $result['reduce_cost'] = $data['cardDenomination'];
        }
        if ($result['card_type'] == 'discount') {
            $result['discount'] = $data['cardDenomination'];
        }
        switch ($data['cardEffectiveMode']) {
            case '0':// 固定日期
                $result['date_type'] = 'DATE_TYPE_FIX_TIME_RANGE';
                $result['begin_time'] = bcdiv($data['beginDate'], 1000, 0);
                $result['end_time'] = bcdiv($data['endDate'], 1000, 0);
                break;
            case '1':// 领取后生效
                $result['date_type'] = 'DATE_TYPE_FIX_TERM';
                $result['begin_time'] = $data['startDay'];
                $result['days'] = $data['limitDay'];
                $result['end_time'] = 0;
                break;
            case '2':// 投放当月有效
                $result['date_type'] = 'DATE_TYPE_FIX_MONTH';
                $result['begin_time'] = $data['startDay'];
                $result['end_time'] = 0;
                break;
            default:
                $msg = '卡券有效期类型错误: ' . $data['cardEffectiveMode'];
                return false;
                break;
        }
        // 适用商品
        if (!$data['goodsFilter']) {
            $result['use_all_items'] = 'true';
        } else {
            $result['use_all_items'] = 'false';
            $result['use_bound'] = 1;
            $result['rel_item_ids'] = $this->formatGoodsList($data['goodsFilter']);
        }
        // 适用门店
        if ($data['storeMode'] == '0') {
            $result['use_all_shops'] = 'true';
        } else {
            $result['use_all_shops'] = 'false';
            $result['distributor_id'] = $this->formatShopList($data['storeCodes']);
        }
        return $result;
    }

    private function getCardType($couponType, &$msg)
    {
        $cardType = [
            '0' => 'cash', // 0:抵金券
            '1' => 'discount', // 1:折扣券
        ];
        if (!isset($cardType[$couponType])) {
            $msg = '卡券类型错误: ' . $couponType;
            return false;
        }
        return $cardType[$couponType];
    }

    private function getCardApplyChannel($cardApplyChannel, &$msg)
    {
        $_cardApplyChannel = explode(',', $cardApplyChannel);
        // if (empty($_cardApplyChannel) || !in_array('ThirdmicroMall', $_cardApplyChannel)) {
        //     $msg = '卡券应用渠道错误: ' . $cardApplyChannel;
        //     return false;
        // }
        return $_cardApplyChannel;
    }

    public function formatGoodsList($goodsFilter)
    {
        $itemIds = [];
        $goodsFilter = $goodsFilter[0][0];
        $itemsService = new ItemsService();
        switch ($goodsFilter['type']) {
            case 'goods':// SPU
                $datalist = $itemsService->getItemsLists(['goods_bn' => $goodsFilter['childCodes']], 'default_item_id,item_id');
                $itemIds = array_column($datalist, 'item_id');
            break;
            case 'sku':// SKU
                $datalist = $itemsService->getItemsLists(['item_bn' => $goodsFilter['childCodes']], 'default_item_id,item_id');
                $itemIds = array_column($datalist, 'item_id');
            break;
        }
        return json_encode($itemIds);
    }

    public function formatShopList($storeCodes)
    {
        $storeCodes = explode(',', $storeCodes);
        $distributorService = new DistributorService();
        $shopList = $distributorService->getDistributorOriginalList(['shop_code' => $storeCodes], 1, -1);
        return array_column($shopList['list'], 'distributor_id');
    }

    /**
     * 用于对指定会员进行卡券投放
     * @param string $dmCardId 达摩CRM卡券模板ID
     * @param string $mobile 会员手机号
     */
    public function sendCoupon($dmCardId, $mobile)
    {
        if (empty($dmCardId) || empty($mobile)) {
            throw new ResourceException('卡券模板ID或手机号不能为空');
        }
        $worker = '/cgi-api/coupon/send_coupon';
        $params = [
            'mobile' => $mobile,
            'couponCardId' => $dmCardId,
        ];
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        if (isset($result['code']) && $result['code'] == 0) {
            return $result['result'];
        } else {
            throw new ResourceException($result['message']);
        }
    }

    /**
     * 通过卡券模板id和卡券券码进行卡券占用/解除
     * @param string $mobile 会员手机号
     * @param string $dmCouponCode 达摩CRM卡券券码
     * @param int $occupyType 占用类型 0:解除 1:占用 
     */
    public function occupyCoupon($mobile, $dmCouponCode, $occupyType = 1)
    {
        $worker = '/cgi-api/coupon/occupy_coupon';
        $params = [
            'mobile' => $mobile,
            'couponCode' => $dmCouponCode,
            'occupyType' => $occupyType,
        ];
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        if (isset($result['code']) && $result['code'] == 0) {
            return true;
        } else {
            throw new ResourceException($result['message']);
        }
    }

    /**
     * 查询订单卡券适用信息
     * 
     * @param array $paramsData
     * @return array
     */
    public function queryCouponLogForOrder($paramsData)
    {
        $worker = '/cgi-api/coupon/query_coupon_log_for_order';
        $orderItemList = $this->formatOrderItemList($paramsData);
        $params = [
            'mobile' => $paramsData['mobile'],
            'storeCode' => $paramsData['shop_code'],
            'totalPrice' => bcdiv($paramsData['item_fee'], 100, 2),
            'payPrice' => bcdiv($paramsData['total_fee'], 100, 2),
            'limitDiscountCoupon' => $this->limitDiscountCoupon,
            'orderItemList' => $orderItemList,
        ];
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        if (isset($result['code']) && $result['code'] == 0) {
            return $result['result'];
        } else {
            return [];
        }
    }

    private function formatOrderItemList($paramsData)
    {
        $result = [];
        foreach ($paramsData['items'] as $item) {
            $result[] = [
                'goodsCode' => $item['item_bn'],
                'skuCode' => $item['item_bn'],
                'goodsPrice' => bcdiv($item['price'], 100, 2),
                'goodsNum' => $item['num'],
                'totalPrice' => bcdiv($item['item_fee'], 100, 2),
                'payPrice' => bcdiv($item['total_fee'], 100, 2),
            ];
        }
        return $result;
    }

    /**
     * 我的优惠券列表-转换条件
     * @param string $filter
     * @return string
     */
    public function myUserCardStatusFilter($filter, $status = '1')
    {
        // 状态 1:可使用、未到使用期优惠券 2:已使用 3:已过期、作废
        switch ($status) {
            case '1':
                break;
            case '2':
                $filter['status'] = 5;
                break;
            case '3':
                $filter['status'] = 6;
                break;
            default:
                # code...
                break;
        }
        return $filter;
    }

    public function getMyUserCardList($filter)
    {
        $worker = '/cgi-api/coupon/query_coupon_log';
        $params = [
            'mobile' => $filter['mobile'],
        ];
        if (isset($filter['status']) && $filter['status'] != '') {
            $params['status'] = $filter['status'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        if (isset($result['code']) && $result['code'] == 0) {
            return $result['result'];
        } else {
            return ['list' => [], 'total_count' => 0];
        }
    }
    
}