<?php

declare(strict_types=1);

namespace Tests\DistributionBundle;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response\Factory;
use DistributionBundle\Http\FrontApi\V1\Action\Distributor;
use Illuminate\Http\Request;
use Mockery;

/**
 * TC-LL-08：getAllDistributorList lat/lng 校验与 list 接口一致。
 * 计划：.tasks/plans/fix-distributor-lat-lng-sqli.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GetAllDistributorListLatLngValidationTest extends \TestCase
{
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();
        $app->instance('path.lang', $app->basePath('resources/lang'));
        $app->register(\Illuminate\Translation\TranslationServiceProvider::class);
        $app->register(\Illuminate\Validation\ValidationServiceProvider::class);

        return $app;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-LL-08：非法 lat/lng 在校验层拒绝，不调用 lists。
     */
    public function testTcLl08InvalidLatLngRejectedBeforeLists(): void
    {
        $this->bindResponseFactory();

        $serviceMock = Mockery::mock('overload:DistributionBundle\Services\DistributorService');
        $serviceMock->shouldReceive('lists')->never();

        $request = Request::create('/api/h5app/wxapp/distributor/alllist', 'GET', [
            'lat' => 'bad',
            'lng' => '121',
        ]);
        $request->attributes->set('auth', ['company_id' => 1]);

        $controller = new Distributor();

        try {
            $controller->getAllDistributorList($request);
            $this->fail('Expected ResourceException');
        } catch (ResourceException $e) {
            $this->assertSame('经纬度范围错误.', $e->getMessage());
        }
    }

    private function bindResponseFactory(): void
    {
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->andReturnUsing(static fn (array $payload): array => $payload);
        $this->app->instance(Factory::class, $factory);
    }
}
