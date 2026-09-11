<?php

declare(strict_types=1);

namespace Tests\Security;

use GoodsBundle\Support\IntroHtmlSanitizer;
use TestCase;

/**
 * cnvd-ome-unauthorized-access TC-XSS-01～07
 */
class IntroHtmlSanitizerTest extends TestCase
{
    /**
     * TC-XSS-01 / AC-XSS-01：HTML intro 含 script 须去除可执行 script。
     * #given 含 <script>alert(1)</script> 的 intro
     * #when 调用 IntroHtmlSanitizer::sanitize
     * #then 输出不含可执行 script 节点/内容
     */
    public function testTcXss01RemovesScriptTags(): void
    {
        #given
        $intro = '<p>before</p><script>alert(1)</script><p>after</p>';

        #when
        $result = IntroHtmlSanitizer::sanitize($intro);

        #then
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script', strtolower($result));
        $this->assertStringNotContainsString('alert(1)', $result);
        $this->assertStringContainsString('before', $result);
        $this->assertStringContainsString('after', $result);
    }

    /**
     * TC-XSS-02 / AC-XSS-02：须去掉 on* 事件属性（含大小写变体），保留合法 img+https src。
     * #given 含 onerror / OnError 的 img
     * #when 调用 IntroHtmlSanitizer::sanitize
     * #then 事件属性被移除；https 图片 src 保留
     */
    public function testTcXss02RemovesEventHandlerAttributes(): void
    {
        #given
        $cases = [
            '<img src="https://x/a.jpg" onerror=alert(1)>',
            '<img src="https://x/a.jpg" OnError="alert(1)">',
        ];

        foreach ($cases as $intro) {
            #when
            $result = IntroHtmlSanitizer::sanitize($intro);

            #then
            $this->assertIsString($result);
            $this->assertStringNotContainsString('onerror', strtolower($result));
            $this->assertStringContainsString('https://x/a.jpg', $result);
            $this->assertStringContainsString('<img', strtolower($result));
        }
    }

    /**
     * TC-XSS-03 / AC-XSS-03：javascript: URL 须被 neutralize。
     * #given 含 javascript: href 的 a 标签
     * #when 调用 IntroHtmlSanitizer::sanitize
     * #then href 中 javascript: 被去除或改为安全值
     */
    public function testTcXss03NeutralizesJavascriptUrls(): void
    {
        #given
        $intro = '<a href="javascript:alert(1)">x</a>';

        #when
        $result = IntroHtmlSanitizer::sanitize($intro);

        #then
        $this->assertIsString($result);
        $this->assertStringNotContainsString('javascript:', strtolower($result));
        $this->assertStringContainsString('>x<', $result);
    }

    /**
     * TC-XSS-04 / AC-XSS-04：合法富文本结构须保留。
     * #given 含 p/strong/img(https) 的 intro
     * #when 调用 IntroHtmlSanitizer::sanitize
     * #then 结构与文本、图片仍在
     */
    public function testTcXss04PreservesLegitimateRichText(): void
    {
        #given
        $intro = '<p><strong>ok</strong><img src="https://cdn.example/a.jpg"></p>';

        #when
        $result = IntroHtmlSanitizer::sanitize($intro);

        #then
        $this->assertIsString($result);
        $this->assertStringContainsString('<p>', strtolower($result));
        $this->assertStringContainsString('<strong>ok</strong>', strtolower($result));
        $this->assertStringContainsString('https://cdn.example/a.jpg', $result);
        $this->assertStringContainsString('<img', strtolower($result));
    }

    /**
     * TC-XSS-05 / AC-XSS-05：装修 JSON 须保留结构，递归净化字符串值。
     * #given 含嵌套 config.content 内嵌危险 HTML 的 JSON intro
     * #when 调用 IntroHtmlSanitizer::sanitize
     * #then JSON 可解码且结构保留；危险片段被净化
     */
    public function testTcXss05SanitizesDecorationJsonRecursively(): void
    {
        #given
        $payload = [
            'type' => 'page',
            'config' => [
                'content' => '<p>safe</p><script>alert(1)</script>',
                'title' => '<img src="https://x/a.jpg" onerror=alert(1)>',
            ],
            'blocks' => [
                ['html' => '<a href="javascript:alert(1)">link</a>'],
            ],
        ];
        $intro = json_encode($payload, JSON_UNESCAPED_UNICODE);

        #when
        $result = IntroHtmlSanitizer::sanitize($intro);

        #then
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertSame('page', $decoded['type']);
        $this->assertArrayHasKey('config', $decoded);
        $this->assertArrayHasKey('blocks', $decoded);
        $this->assertStringNotContainsString('<script', strtolower($decoded['config']['content']));
        $this->assertStringNotContainsString('onerror', strtolower($decoded['config']['title']));
        $this->assertStringContainsString('https://x/a.jpg', $decoded['config']['title']);
        $this->assertStringNotContainsString('javascript:', strtolower($decoded['blocks'][0]['html']));
        $this->assertStringContainsString('safe', $decoded['config']['content']);
    }

    /**
     * TC-XSS-06 / AC-XSS-06：空字符串与非字符串须安全处理。
     * #given 空字符串、null、非字符串标量
     * #when 调用 IntroHtmlSanitizer::sanitize
     * #then 空→空；null→null；非字符串原样返回且不抛异常
     */
    public function testTcXss06HandlesEmptyAndNonStringSafely(): void
    {
        #given / #when / #then
        $this->assertSame('', IntroHtmlSanitizer::sanitize(''));
        $this->assertNull(IntroHtmlSanitizer::sanitize(null));
        $this->assertSame(42, IntroHtmlSanitizer::sanitize(42));
        $this->assertTrue(IntroHtmlSanitizer::sanitize(true));
    }

    /**
     * TC-XSS-07 / AC-XSS-07：实体/嵌套混淆须尽力净化。
     * #given HTML 实体编码 script、嵌套混淆标签
     * #when 调用 IntroHtmlSanitizer::sanitize
     * #then 不以可执行 script 形式残留
     */
    public function testTcXss07SanitizesEntityAndNestedObfuscation(): void
    {
        #given
        $cases = [
            '&lt;script&gt;alert(1)&lt;/script&gt;<p>ok</p>',
            '<<script>script>alert(1)<</script>/script>',
        ];

        foreach ($cases as $intro) {
            #when
            $result = IntroHtmlSanitizer::sanitize($intro);

            #then
            $this->assertIsString($result);
            $this->assertStringNotContainsString('<script', strtolower($result));
            $this->assertStringNotContainsString('alert(1)', $result);
        }
    }
}
