<?php

use PHPUnit\Framework\TestCase;

class SingleFailingTest extends TestCase
{
    public function testShouldAddNumbersCorrectly(): void
    {
        $this->assertEquals(6, 2 + 3);
    }
}