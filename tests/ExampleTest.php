<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
       $multiLangItem = new \CompanysBundle\MultiLang\MultiLangItem('enCN','aaaa');
       $multiLangItem->createTable();
    }
}
