<?php

use Illuminate\Support\Facades\Queue;
use MembersBundle\Jobs\SendMemberEmailActivationJob;

class SendMemberEmailActivationJobDispatchTest extends TestCase
{
    public function testDispatchPushesActivationJob(): void
    {
        Queue::fake();
        dispatch(new SendMemberEmailActivationJob(1, 'test@example.com', '127.0.0.1', null, 'https://h5.test'));
        Queue::assertPushed(SendMemberEmailActivationJob::class);
    }
}
