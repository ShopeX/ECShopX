<?php

declare(strict_types=1);

namespace Tests\MembersBundle;

use MembersBundle\Events\CreateMemberSuccessEvent;
use PHPUnit\Framework\TestCase;

class CreateMemberSuccessEventShuyunOfflinePlatFlagTest extends TestCase
{
    public function testShuyunOpenPointChangeForceOfflinePlatFromEventData(): void
    {
        $event = new CreateMemberSuccessEvent([
            'user_id' => 1,
            'company_id' => 1,
            'mobile' => '13800138000',
            'openid' => '',
            'wxa_appid' => '',
            'source_id' => 0,
            'monitor_id' => 0,
            'inviter_id' => 0,
            'salesperson_id' => 0,
            'distributor_id' => 5,
            'if_register_promotion' => true,
            'shuyun_open_point_change_force_offline_plat' => true,
        ]);

        $this->assertTrue($event->shuyunOpenPointChangeForceOfflinePlat);
    }

    public function testShuyunOpenPointChangeForceOfflinePlatDefaultsFalse(): void
    {
        $event = new CreateMemberSuccessEvent([
            'user_id' => 1,
            'company_id' => 1,
            'mobile' => '13800138000',
            'openid' => '',
            'wxa_appid' => '',
            'source_id' => 0,
            'monitor_id' => 0,
            'inviter_id' => 0,
            'salesperson_id' => 0,
            'distributor_id' => 5,
            'if_register_promotion' => true,
        ]);

        $this->assertFalse($event->shuyunOpenPointChangeForceOfflinePlat);
    }
}
