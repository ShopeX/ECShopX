<?php

use WsugcBundle\Entities\Post;
use WsugcBundle\Services\PostService;
use CompanysBundle\Services\CommonLangModService;

/**
 * 计划：.tasks/plans/ugc-post-list-search-intersect.md
 * 单元测试：PostService::normalizePostListFilterForMultilang（Reflection）
 */
class PostListSearchFilterNormalizeTest extends TestCase
{
    /** @var array<int> */
    private static array $titleIdsReturn = [];

    /** @var array<int> */
    private static array $contentIdsReturn = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::$titleIdsReturn = [];
        self::$contentIdsReturn = [];
        $this->bindRegistryMock();
        $this->bindLangModOverload();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * TODO-1 smoke：Reflection 可调用 private normalize 方法。
     */
    public function testSmokeInvokeNormalizeEarlyReturnWithoutContentContains(): void
    {
        #given filter 无 content|contains
        $filter = ['post_id' => [101, 102], 'company_id' => 1];

        #when
        $result = $this->invokeNormalize($filter);

        #then 早退，filter 原样
        $this->assertSame($filter, $result);
    }

    /**
     * TC-06（AC-1）：existing=[101,102]，mergedIds=[102,103] → intersect 得 [102]。
     */
    public function testTC06TopicsAndContentIntersectReturnsMatchingPostId(): void
    {
        #given 话题限定 post_id，content 在多语言 title/content 命中 102、103
        self::$titleIdsReturn = [102];
        self::$contentIdsReturn = [103];
        $filter = $this->buildFilterWithContentContains('keyword', [], [101, 102]);

        #when
        $result = $this->invokeNormalize($filter);

        #then 取交集，仅返回 102
        $this->assertArrayNotHasKey('content|contains', $result);
        $this->assertSame([102], $result['post_id']);
    }

    /**
     * TC-07（AC-2）：existing=[101,102]，mergedIds=[103] → 关键字在话题外，post_id=[-1]。
     */
    public function testTC07KeywordOutsideTopicsReturnsEmptySentinel(): void
    {
        #given content 仅命中 103，不在话题 post_id 内
        self::$titleIdsReturn = [103];
        self::$contentIdsReturn = [];
        $filter = $this->buildFilterWithContentContains('keyword', [], [101, 102]);

        #when
        $result = $this->invokeNormalize($filter);

        #then 交集为空 → [-1]
        $this->assertSame([-1], $result['post_id']);
    }

    /**
     * TC-08（AC-3）：existing=[101,102]，mergedIds=[] → 关键字无命中，post_id=[-1]。
     */
    public function testTC08NoKeywordMatchReturnsEmptySentinelNotTopicFull(): void
    {
        #given 多语言与 keyword_topics 均无命中
        self::$titleIdsReturn = [];
        self::$contentIdsReturn = [];
        $filter = $this->buildFilterWithContentContains('nohit', [], [101, 102]);

        #when
        $result = $this->invokeNormalize($filter);

        #then 非话题全量，而是 [-1]
        $this->assertSame([-1], $result['post_id']);
    }

    /**
     * TC-04（AC-4）：仅 content，无 existing post_id → post_id = 关键字 OR 合并结果。
     */
    public function testTC04ContentOnlySetsMergedPostIdsWithoutIntersect(): void
    {
        #given 无 post_id，filterByLang title/content 分别命中 101、102
        self::$titleIdsReturn = [101];
        self::$contentIdsReturn = [102];
        $filter = $this->buildFilterWithContentContains('keyword');

        #when
        $result = $this->invokeNormalize($filter);

        #then 直接写入 mergedIds，不走 intersect 分支
        $this->assertSame([101, 102], $result['post_id']);
    }

    /**
     * TC-05（AC-5）：仅 post_id，无 content|contains → filter 不变。
     */
    public function testTC05TopicsOnlyLeavesFilterUnchanged(): void
    {
        #given 仅 topics 写入的 post_id
        $filter = ['post_id' => [101, 102], 'company_id' => 1];

        #when
        $result = $this->invokeNormalize($filter);

        #then 早退，filter 原样
        $this->assertSame($filter, $result);
    }

    /**
     * TC-01（AC-10）：无 content|contains 键 → 早退。
     */
    public function testTC01MissingContentContainsKeyEarlyReturn(): void
    {
        #given filter 无 content|contains
        $filter = ['post_id' => [1], 'company_id' => 1];

        #when
        $result = $this->invokeNormalize($filter);

        #then 原样返回
        $this->assertSame($filter, $result);
    }

    /**
     * TC-02（AC-10）：content|contains 为字符串（Admin 形态）→ 早退。
     */
    public function testTC02ContentContainsAsStringEarlyReturn(): void
    {
        #given Admin 形态字符串
        $filter = [
            'content|contains' => 'plain string',
            'post_id' => [101],
            'company_id' => 1,
        ];

        #when
        $result = $this->invokeNormalize($filter);

        #then 原样返回
        $this->assertSame($filter, $result);
    }

    /**
     * TC-03（AC-10）：array 缺 keyword_topics_post_id → 早退。
     */
    public function testTC03MissingKeywordTopicsPostIdEarlyReturn(): void
    {
        #given content|contains 缺 keyword_topics_post_id
        $filter = [
            'content|contains' => ['content' => 'kw'],
            'post_id' => [101],
            'company_id' => 1,
        ];

        #when
        $result = $this->invokeNormalize($filter);

        #then 原样返回
        $this->assertSame($filter, $result);
    }

    /**
     * TC-09（AC-6）：existing=[-1]，mergedIds=[101] → post_id=[-1]。
     */
    public function testTC09EmptyTopicsWithKeywordMatchReturnsEmptySentinel(): void
    {
        #given 话题无笔记 post_id=[-1]，content 有命中
        self::$titleIdsReturn = [101];
        self::$contentIdsReturn = [];
        $filter = $this->buildFilterWithContentContains('keyword', [], [-1]);

        #when
        $result = $this->invokeNormalize($filter);

        #then intersect([-1],[101]) 为空 → [-1]
        $this->assertSame([-1], $result['post_id']);
    }

    /**
     * TC-10（AC-7）：existing 为 string，mergedIds 为 int → normalize 后 intersect 得 [102]。
     */
    public function testTC10StringExistingPostIdsIntersectWithIntMergedIds(): void
    {
        #given existing 字符串 ID
        self::$titleIdsReturn = [102];
        self::$contentIdsReturn = [103];
        $filter = $this->buildFilterWithContentContains('keyword', [], ['101', '102']);

        #when
        $result = $this->invokeNormalize($filter);

        #then normalizePostIds 后 intersect 得 [102]
        $this->assertSame([102], $result['post_id']);
    }

    /**
     * TC-11（AC-8）：keyword_topics 命中 102，但 post 不在 topics[] → post_id=[-1]。
     */
    public function testTC11KeywordTopicMatchOutsideScopeReturnsEmptySentinel(): void
    {
        #given 话题限定 [101]，keyword_topics_post_id 命中 102
        self::$titleIdsReturn = [];
        self::$contentIdsReturn = [];
        $filter = $this->buildFilterWithContentContains('', [102], [101]);

        #when
        $result = $this->invokeNormalize($filter);

        #then intersect 空 → [-1]
        $this->assertSame([-1], $result['post_id']);
    }

    /**
     * TC-12（AC-9）：favorite existing=[201,202]，mergedIds=[202,203] → [202]。
     */
    public function testTC12FavoriteScopeAndContentIntersect(): void
    {
        #given 收藏集合 post_id
        self::$titleIdsReturn = [202];
        self::$contentIdsReturn = [203];
        $filter = $this->buildFilterWithContentContains('keyword', [], [201, 202]);

        #when
        $result = $this->invokeNormalize($filter);

        #then intersect 得 [202]
        $this->assertSame([202], $result['post_id']);
    }

    /**
     * TC-13：mergedIds 含 -1 与正数 ID，无 existing → normalize 剔除 -1 后 [101]。
     */
    public function testTC13NormalizeStripsSentinelWhenPositiveIdsExist(): void
    {
        #given 无 existing，title 返回 [-1,101]
        self::$titleIdsReturn = [-1, 101];
        self::$contentIdsReturn = [];
        $filter = $this->buildFilterWithContentContains('keyword');

        #when
        $result = $this->invokeNormalize($filter);

        #then normalizePostIds 剔除 -1
        $this->assertSame([101], $result['post_id']);
    }

    /**
     * TC-14（AC-3）：intersect 结果为空 → 输出 [-1]。
     */
    public function testTC14EmptyIntersectOutputsSentinel(): void
    {
        #given existing 与 mergedIds 无交集
        self::$titleIdsReturn = [999];
        self::$contentIdsReturn = [];
        $filter = $this->buildFilterWithContentContains('keyword', [], [101, 102]);

        #when
        $result = $this->invokeNormalize($filter);

        #then
        $this->assertSame([-1], $result['post_id']);
    }

    private function bindRegistryMock(): void
    {
        $mockRepo = new \stdClass();
        $mockRepo->table = 'wsugc_post';
        $mockRepo->langField = ['title', 'address', 'content'];

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')
            ->with(Post::class)
            ->willReturn($mockRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $mockRegistry->method('getManager')
            ->with('default')
            ->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }

    private function bindLangModOverload(): void
    {
        $mock = \Mockery::mock('overload:' . CommonLangModService::class);
        $mock->shouldReceive('getLang')->andReturn('zh');
        $mock->shouldReceive('filterByLang')->andReturnUsing(
            function (string $lang, string $field, string $content, string $tableName, $companyId = 0): array {
                if (strpos($field, 'title') === 0) {
                    return self::$titleIdsReturn;
                }
                if (strpos($field, 'content') === 0) {
                    return self::$contentIdsReturn;
                }

                return [];
            }
        );
    }

    /**
     * @param array<int|string> $postIds
     * @return array<string, mixed>
     */
    private function buildFilterWithContentContains(
        string $searchText,
        array $keywordTopicsPostIds = [],
        array $postIds = []
    ): array {
        $filter = [
            'company_id' => 1,
            'content|contains' => [
                'content' => $searchText,
                'keyword_topics_post_id' => $keywordTopicsPostIds,
            ],
        ];
        if ($postIds !== []) {
            $filter['post_id'] = $postIds;
        }

        return $filter;
    }

    private function invokeNormalize(array $filter): array
    {
        $service = new PostService();
        $ref = new \ReflectionClass(PostService::class);
        $method = $ref->getMethod('normalizePostListFilterForMultilang');
        $method->setAccessible(true);

        return $method->invoke($service, $filter);
    }
}
