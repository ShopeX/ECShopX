<?php

use PHPUnit\Framework\TestCase;
use NonExistent\Module\SomeClass;

class SingleImportErrorTest extends TestCase
{
    public function testShouldAddNumbersCorrectly(): void
    {
        // This should cause a fatal error due to the non-existent class
        $instance = new SomeClass();
        $this->assertEquals(5, 2 + 3);
    }
}