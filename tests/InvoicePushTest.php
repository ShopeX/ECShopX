<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use OrdersBundle\Jobs\InvoicePushOmsJob;

class InvoicePushTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $id = 7;
        $company_id = 35;
        $gotoJob = (new InvoicePushOmsJob($id,$company_id));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

    }
}
