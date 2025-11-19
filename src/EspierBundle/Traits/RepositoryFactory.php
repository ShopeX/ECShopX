<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Traits;

trait RepositoryFactory
{
    public static function instance($manager = 'default')
    {
        if (is_null(self::$entityClass)) {
            throw new \Exception('entity class not null');
        }
        $manager = app('registry')->getManager($manager);
        $classMetadata = $manager->getClassMetadata(self::$entityClass);
        return new self($manager, $classMetadata);
    }
}
