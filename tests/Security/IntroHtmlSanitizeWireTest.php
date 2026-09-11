<?php

declare(strict_types=1);

namespace Tests\Security;

use GoodsBundle\Http\FrontApi\V1\Action\Items;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use Mockery;
use TestCase;

/**
 * cnvd-ome-unauthorized-access TC-XSS-WIRE-01～02
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class IntroHtmlSanitizeWireTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRegistryConnection();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockRegistryConnection(): void
    {
        $defaultRepo = $this->getMockBuilder(\stdClass::class)->addMethods(['getInfo'])->getMock();

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturn($defaultRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConnection', 'getManager'])
            ->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }

    /**
     * TC-XSS-WIRE-01 / AC-XSS-WIRE-01：commonParams 写路径须在落库前净化 intro。
     * #given 含 script 的 intro 参数
     * #when 经 Reflection 调用 ItemsService::commonParams
     * #then 返回 intro 已净化，不含可执行 script
     */
    public function testTcXssWire01CommonParamsSanitizesIntroBeforePersist(): void
    {
        #given
        $dangerousIntro = '<p>before</p><script>alert(1)</script><p>after</p>';
        $ruleMock = Mockery::mock('overload:PointBundle\Services\PointMemberRuleService');
        $ruleMock->shouldReceive('getPointRule')
            ->once()
            ->with(1)
            ->andReturn(['access' => 0]);

        $params = [
            'company_id' => 1,
            'item_name' => 'XSS wire test item',
            'intro' => $dangerousIntro,
            'distributor_id' => 0,
        ];

        $service = new ItemsService();
        $method = new \ReflectionMethod(ItemsService::class, 'commonParams');
        $method->setAccessible(true);

        #when
        $result = $method->invoke($service, $params);

        #then
        $this->assertIsArray($result);
        $this->assertArrayHasKey('intro', $result);
        $this->assertStringNotContainsString('<script', strtolower($result['intro']));
        $this->assertStringNotContainsString('alert(1)', $result['intro']);
        $this->assertStringContainsString('before', $result['intro']);
        $this->assertStringContainsString('after', $result['intro']);
    }

    /**
     * TC-XSS-WIRE-02 / AC-XSS-WIRE-02：getItemsIntro 读路径须净化历史脏 intro。
     * #given getItemsDetail 返回含危险 HTML 的 intro（模拟未净化历史数据）
     * #when 调用 Items::getItemsIntro
     * #then 响应 body 已净化；Content-Type 仍为 text/html
     */
    public function testTcXssWire02GetItemsIntroSanitizesDirtyIntroOnRead(): void
    {
        #given
        $dangerousIntro = '<p>legacy</p><script>alert(1)</script>';
        $itemsMock = Mockery::mock('overload:'.ItemsService::class);
        $itemsMock->shouldReceive('getItemsDetail')
            ->once()
            ->with(42, 'test-woa-appid')
            ->andReturn(['intro' => $dangerousIntro]);

        $request = Request::create('/h5app/wxapp/goods/items/42/intro', 'GET');
        $request->attributes->set('auth', ['woa_appid' => 'test-woa-appid']);

        #when
        $response = (new Items())->getItemsIntro(42, $request);

        #then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->headers->get('content-type'));
        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('<script', strtolower($body));
        $this->assertStringNotContainsString('alert(1)', $body);
        $this->assertStringContainsString('legacy', $body);
    }
}
