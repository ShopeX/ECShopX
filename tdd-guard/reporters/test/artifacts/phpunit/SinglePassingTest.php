<?php

use PHPUnit\Framework\TestCase;

class SinglePassingTest extends TestCase
{
    public function testShouldAddNumbersCorrectly(): void
    {
        $this->assertEquals(5, 2 + 3);
    }
}