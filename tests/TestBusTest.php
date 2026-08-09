<?php

use EspierBundle\Services\Bus\TestBus;

class TestBusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestBus::reset();
    }

    /**
     * T0：连续两次 post 后 getPostCount()===2，reset() 后归零。
     * #given TestBus 已 reset
     * #when 连续两次 post 后 reset
     * #then getPostCount 先为 2 后归零
     */
    public function testPostHistoryCountAndReset(): void
    {
        $bus = new TestBus();

        $bus->post('/uri1', ['a' => 1]);
        $bus->post('/uri2', ['b' => 2]);

        $this->assertSame(2, TestBus::getPostCount());
        $this->assertSame([['a' => 1], ['b' => 2]], TestBus::getPostHistory());

        TestBus::reset();

        $this->assertSame(0, TestBus::getPostCount());
        $this->assertSame([], TestBus::getPostHistory());
        $this->assertNull(TestBus::getLastPostData());
    }
}
