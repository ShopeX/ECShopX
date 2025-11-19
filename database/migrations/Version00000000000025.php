<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use AftersalesBundle\Services\AftersalesService;

class Version00000000000025 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria = $criteria->from('orders_normal_orders')
                            ->where(
                                $criteria->expr()->orX(
                                    $criteria->expr()->andX(
                                        $criteria->expr()->eq('order_status', $criteria->expr()->literal('WAIT_BUYER_CONFIRM')),
                                    ),
                                    $criteria->expr()->andX(
                                        $criteria->expr()->eq('order_status', $criteria->expr()->literal('DONE')),
                                        $criteria->expr()->gt('order_auto_close_aftersales_time', time()),
                                    ),
                                )
                            );

        $count = $criteria->select('count(*)')->execute()->fetchColumn();
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $aftersalesService = new AftersalesService();
        $offset = 0;
        $limit = 100;
        do {
            $list = $criteria->select('order_id,receipt_type')->addOrderBy('create_time', 'ASC')->setFirstResult($offset)->setMaxResults($limit)->execute()->fetchAll();
            $offset += $limit;
            foreach ($list as $row) {
                $orderItems = $normalOrdersItemsRepository->getList(['order_id' => $row['order_id']]);
                $leftAftersalesNum = 0;
                foreach ($orderItems['list'] as $item) {
                    $appliedNum = $aftersalesService->getAppliedNum($item['company_id'], $item['order_id'], $item['id']); // 已申请数量
                    //如果是自提订单发货数量等于子订单商品数量
                    $item['delivery_item_num'] = $row['receipt_type'] == 'ziti' ? $item['num'] : ($item['delivery_item_num'] ?? 0);
                    $leftAftersalesNum += $item['delivery_item_num'] + $item['cancel_item_num'] - $appliedNum; // 剩余申请数量
                }
                $leftAftersalesNum = $leftAftersalesNum > 0 ? $leftAftersalesNum : 0;
                $normalOrdersRepository->update(['order_id' => $row['order_id']], ['left_aftersales_num' => $leftAftersalesNum]);
            }
        } while($offset < $count);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
