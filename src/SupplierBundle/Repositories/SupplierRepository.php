<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use SupplierBundle\Entities\Supplier;


class SupplierRepository extends BaseRepository
{
    public $table = "supplier";
    public $cols = ['id', 'company_id', 'supplier_name', 'contact', 'mobile', 'business_license',
        'wechat_qrcode', 'service_tel', 'bank_name', 'bank_account', 'is_check', 'audit_remark',
        'operator_id', 'add_time', 'modify_time'];

    private $multiLangField = [
        'supplier_name',
        'contact',
        'business_license',
        'bank_name'
    ];

    private $prk = 'id';

    public function getLangService()
    {
        return new MultiLangOutsideItemService($this->table,$this->table,$this->multiLangField);
    }
    /**
     * 新增
     *
     * @param array $data
     * @return array
     */
    public function create($data)
    {
        $entity = new Supplier();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $result = $this->getColumnNamesData($entity);

        $this->getLangService()->addMultiLangByParams($result[$this->prk],$data,$this->table);

        return $result;
    }

}
