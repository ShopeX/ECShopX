<?php

use GoodsBundle\Entities\Items as ItemsEntity;
use GoodsBundle\Http\FrontApi\V1\Action\Items;
use Illuminate\Http\Request;

class GoodsBatchItemsActionTest extends TestCase
{
    public function testGetBatchItemsQueriesLightFieldsByItemIds(): void
    {
        $expected = [
            [
                'item_id' => 1,
                'item_name' => '商品A',
                'itemName' => '商品A',
                'price' => 1000,
                'pics' => ['/a.jpg'],
            ],
            [
                'item_id' => 2,
                'item_name' => '商品B',
                'itemName' => '商品B',
                'price' => 2000,
                'pics' => ['/b.jpg'],
            ],
        ];

        $repository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getLists'])
            ->getMock();
        $repository->expects($this->once())
            ->method('getLists')
            ->with(['company_id' => 38, 'item_id' => [1, 2, 3]], 'item_id,item_name,price,pics', 1, -1, [])
            ->willReturn($expected);

        $this->bindItemsRepository($repository);

        $request = Request::create('/h5app/wxapp/goods/items/batch', 'GET', [
            'item_ids' => '1,2,3',
        ]);
        $request->attributes->set('auth', ['company_id' => 38]);

        $response = (new Items())->getBatchItems($request);

        $this->assertSame($expected, $response->getOriginalContent());
    }

    public function testGetBatchItemsReturnsTranslatedItemNameFromRepository(): void
    {
        $translated = [
            [
                'item_id' => 7644,
                'item_name' => 'Certified New Product 05051',
                'itemName' => 'Certified New Product 05051',
                'price' => 90000,
                'pics' => ['/hat.jpg'],
            ],
        ];

        $repository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getLists'])
            ->getMock();
        $repository->expects($this->once())
            ->method('getLists')
            ->with(['company_id' => 38, 'item_id' => [7644]], 'item_id,item_name,price,pics', 1, -1, [])
            ->willReturn($translated);

        $this->bindItemsRepository($repository);

        $request = Request::create('/h5app/wxapp/goods/items/batch', 'GET', [
            'item_ids' => '7644',
            'country_code' => 'en-CN',
        ]);
        $request->attributes->set('auth', ['company_id' => 38]);

        $response = (new Items())->getBatchItems($request);

        $this->assertSame($translated, $response->getOriginalContent());
        $this->assertSame('Certified New Product 05051', $response->getOriginalContent()[0]['item_name']);
    }

    public function testGetBatchItemsReturnsEmptyArrayWhenItemIdsEmpty(): void
    {
        $repository = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getLists'])
            ->getMock();
        $repository->expects($this->never())->method('getLists');

        $this->bindItemsRepository($repository);

        $request = Request::create('/h5app/wxapp/goods/items/batch', 'GET', [
            'item_ids' => '',
        ]);
        $request->attributes->set('auth', ['company_id' => 1]);

        $response = (new Items())->getBatchItems($request);

        $this->assertSame([], $response->getOriginalContent());
    }

    private function bindItemsRepository($repository): void
    {
        $manager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $manager->method('getRepository')
            ->with(ItemsEntity::class)
            ->willReturn($repository);

        $registry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $registry->method('getManager')
            ->with('default')
            ->willReturn($manager);

        $this->app->instance('registry', $registry);
    }
}
