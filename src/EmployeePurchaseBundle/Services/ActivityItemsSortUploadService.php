<?php

namespace EmployeePurchaseBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ActivityItemsSortUploadService extends ActivityItemsUploadService
{
    public $header = [
        'SPU编码' => 'goods_bn',
        '排序值' => 'sort',
    ];

    public $headerInfo = [
        'SPU编码' => ['size' => 255, 'remarks' => 'SPU编码', 'is_need' => true],
        '排序值' => ['size' => 10, 'remarks' => '数字越大越靠前，留空默认为0', 'is_need' => false],
    ];

    public $isNeedCols = [
        'SPU编码' => 'goods_bn',
    ];

    public function getHeaderTitle($companyId = 0, $operatorType = '', $relationId = 0)
    {
        $this->getActivity($companyId, $relationId);

        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        $goodsBn = trim((string) ($row['goods_bn'] ?? ''));
        if ($goodsBn === '') {
            throw new BadRequestHttpException('SPU编码不能为空');
        }

        $sort = trim((string) ($row['sort'] ?? ''));
        $sort = $sort === '' ? 0 : $sort;
        if (filter_var($sort, FILTER_VALIDATE_INT) === false || $sort < 0 || $sort > 2147483647) {
            throw new BadRequestHttpException('排序值必须为大于等于0的整数');
        }

        $activity = $this->getActivity($companyId, (int) ($row['relation_id'] ?? 0));
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $goodsId = $qb->select('ai.goods_id')
            ->from('employee_purchase_activity_items', 'ai')
            ->innerJoin('ai', 'items', 'i', 'ai.item_id = i.item_id')
            ->andWhere($qb->expr()->eq('ai.company_id', (int) $companyId))
            ->andWhere($qb->expr()->eq('ai.activity_id', (int) $activity['id']))
            ->andWhere($qb->expr()->eq('i.goods_bn', $qb->expr()->literal($goodsBn)))
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();
        if (!$goodsId) {
            throw new BadRequestHttpException('内购商品不存在:' . $goodsBn);
        }

        $activitiesService = new ActivitiesService();
        $activitiesService->itemsEntityRepository->updateBy([
            'company_id' => $companyId,
            'activity_id' => $activity['id'],
            'goods_id' => $goodsId,
        ], ['sort' => (int) $sort]);
    }

    private function getActivity($companyId, $relationId)
    {
        if (!$relationId) {
            throw new BadRequestHttpException('关联id不能为空');
        }

        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->entityRepository->getInfo([
            'company_id' => $companyId,
            'id' => $relationId,
        ]);
        if (!$activity) {
            throw new BadRequestHttpException('内购活动不存在');
        }

        return $activity;
    }
}
