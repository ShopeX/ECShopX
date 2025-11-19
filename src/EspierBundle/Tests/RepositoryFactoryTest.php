<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Tests;

use EspierBundle\Services\TestBaseService;
use Doctrine\ORM\EntityRepository;
use EspierBundle\Traits\RepositoryFactory;
use EspierBundle\Entities\Address;

class AddressRepositoryNew extends EntityRepository
{
    // Ref: 1996368445
    use RepositoryFactory;
    public static $entityClass = Address::class;
}

class RepositoryFactoryTest extends TestBaseService
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        // Ref: 1996368445
        $addressRepository = AddressRepositoryNew::instance();
        $this->assertEquals('北京市', $addressRepository->findOneByLabel("北京市")->getLabel());
    }
}
