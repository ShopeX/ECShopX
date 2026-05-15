<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use EmployeePurchaseBundle\Entities\ActivityEnterpriseBehaviorLog;
use EmployeePurchaseBundle\Entities\ActivityEnterprises;
use EmployeePurchaseBundle\Entities\Activities;
use EmployeePurchaseBundle\Entities\OrdersRelActivity;
use EmployeePurchaseBundle\Repositories\ActivityEnterpriseBehaviorLogRepository;
use EmployeePurchaseBundle\Repositories\OrdersRelActivityRepository;
use EmployeePurchaseBundle\Services\ActivityEnterpriseBehaviorLogService;
use OrdersBundle\Services\OrderAssociationService;

/**
 * @covers \EmployeePurchaseBundle\Services\ActivityEnterpriseBehaviorLogService::recordEmployeePurchaseOrderPaid
 */
final class ActivityEnterpriseBehaviorLogServiceRecordEmployeePurchaseOrderPaidTest extends TestCase
{
    protected function tearDown(): void
    {
        if (isset($this->app)) {
            $this->app->forgetInstance('registry');
        }
        parent::tearDown();
    }

    /**
     * @param array<int,array{0:class-string,1:ObjectRepository|\PHPUnit\Framework\MockObject\MockObject}> $extraMap
     *
     * @return ActivityEnterpriseBehaviorLogRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private function bindRegistry(
        EntityManagerInterface $em,
        ObjectRepository $dummyRepo,
        ActivityEnterpriseBehaviorLogRepository $logRepo,
        array $extraMap = []
    ): ActivityEnterpriseBehaviorLogRepository {
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);

        $map = [
            [Activities::class, $dummyRepo],
            [ActivityEnterprises::class, $dummyRepo],
            [ActivityEnterpriseBehaviorLog::class, $logRepo],
        ];
        foreach ($extraMap as $pair) {
            $map[] = $pair;
        }
        $em->method('getRepository')->willReturnMap($map);

        return $logRepo;
    }

    public function testInsertsOrderLogWithoutQrBindWhenPaymentSuccessContextOk(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $dummyRepo = $this->createMock(ObjectRepository::class);
        $ordersRelRepo = $this->createMock(OrdersRelActivityRepository::class);
        $ordersRelRepo->method('getInfo')->willReturn([
            'activity_id' => 10,
            'enterprise_id' => 20,
            'user_id' => 30,
        ]);

        $logRepo = $this->createMock(ActivityEnterpriseBehaviorLogRepository::class);
        $this->bindRegistry($em, $dummyRepo, $logRepo, [[OrdersRelActivity::class, $ordersRelRepo]]);

        $logRepo->expects($this->once())->method('getLists')->willReturn([]);
        $logRepo->expects($this->never())->method('existsBindLogWithBindChannel');
        $logRepo->expects($this->once())->method('insertRow')->with($this->callback(function (array $row) {
            return $row['behavior_type'] === ActivityEnterpriseBehaviorLogService::BEHAVIOR_ORDER
                && (int) $row['company_id'] === 1
                && (int) $row['activity_id'] === 10
                && (int) $row['enterprise_id'] === 20
                && (int) $row['user_id'] === 30
                && (int) $row['ref_id'] === 2001;
        }))->willReturn(1);

        $orderSvc = $this->createMock(OrderAssociationService::class);
        $orderSvc->method('getOrder')->with(1, '2001')->willReturn([
            'order_type' => 'normal',
            'order_class' => 'employee_purchase',
        ]);

        $svc = new ActivityEnterpriseBehaviorLogService();
        $svc->recordEmployeePurchaseOrderPaid(1, '2001', $orderSvc);
    }

    public function testSkipsInsertWhenDuplicateOrderLogExists(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $dummyRepo = $this->createMock(ObjectRepository::class);
        $ordersRelRepo = $this->createMock(OrdersRelActivityRepository::class);
        $ordersRelRepo->method('getInfo')->willReturn([
            'activity_id' => 10,
            'enterprise_id' => 20,
            'user_id' => 30,
        ]);

        $logRepo = $this->createMock(ActivityEnterpriseBehaviorLogRepository::class);
        $this->bindRegistry($em, $dummyRepo, $logRepo, [[OrdersRelActivity::class, $ordersRelRepo]]);

        $logRepo->expects($this->once())->method('getLists')->willReturn([['id' => 99]]);
        $logRepo->expects($this->never())->method('insertRow');

        $orderSvc = $this->createMock(OrderAssociationService::class);
        $orderSvc->method('getOrder')->willReturn([
            'order_type' => 'normal',
            'order_class' => 'employee_purchase',
        ]);

        $svc = new ActivityEnterpriseBehaviorLogService();
        $svc->recordEmployeePurchaseOrderPaid(1, '2001', $orderSvc);
    }

    public function testNoInsertWhenNotEmployeePurchaseOrder(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $dummyRepo = $this->createMock(ObjectRepository::class);
        $logRepo = $this->createMock(ActivityEnterpriseBehaviorLogRepository::class);
        $this->bindRegistry($em, $dummyRepo, $logRepo);

        $logRepo->expects($this->never())->method('getLists');
        $logRepo->expects($this->never())->method('insertRow');

        $orderSvc = $this->createMock(OrderAssociationService::class);
        $orderSvc->method('getOrder')->willReturn([
            'order_type' => 'normal',
            'order_class' => 'normal',
        ]);

        $svc = new ActivityEnterpriseBehaviorLogService();
        $svc->recordEmployeePurchaseOrderPaid(1, '2001', $orderSvc);
    }

    public function testNoInsertWhenOrdersRelMissing(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $dummyRepo = $this->createMock(ObjectRepository::class);
        $ordersRelRepo = $this->createMock(OrdersRelActivityRepository::class);
        $ordersRelRepo->method('getInfo')->willReturn([]);

        $logRepo = $this->createMock(ActivityEnterpriseBehaviorLogRepository::class);
        $this->bindRegistry($em, $dummyRepo, $logRepo, [[OrdersRelActivity::class, $ordersRelRepo]]);

        $logRepo->expects($this->never())->method('getLists');
        $logRepo->expects($this->never())->method('insertRow');

        $orderSvc = $this->createMock(OrderAssociationService::class);
        $orderSvc->method('getOrder')->willReturn([
            'order_type' => 'normal',
            'order_class' => 'employee_purchase',
        ]);

        $svc = new ActivityEnterpriseBehaviorLogService();
        $svc->recordEmployeePurchaseOrderPaid(1, '2001', $orderSvc);
    }
}
