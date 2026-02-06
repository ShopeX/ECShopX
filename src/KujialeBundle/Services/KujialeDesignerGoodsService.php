<?php

namespace KujialeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use KujialeBundle\Entities\KujialeDesignerGoods;
use KujialeBundle\Repositories\KujialeDesignerGoodsRepository;

class KujialeDesignerGoodsService
{
    /**
     * @var KujialeDesignerGoodsRepository
     */
    protected $likeRepository;

    public function __construct(){
        $this->likeRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerGoods::class);
    }

    public function saveGoods($params){
        $conn = app('registry')->getConnection('default');
        try{
            $conn->beginTransaction();
            $filter['good_id'] = $params['goodsId'];

            $save['good_id'] = $params['goodsId'];
            $save['dimensions'] = $params['dimensions'];
            if (!empty($save['description'])){
                $save['description'] = $params['description'];
            }
            if (!empty($params['brandGoodCode'])){
                $save['brand_good_code'] = $params['brandGoodCode'];
            }
            $save['brand_good_name'] = $params['brandGoodName'];
            if (!empty($params['obsBrandId'])){
                $save['brand_id'] = $params['obsBrandId'];
            }
            if (!empty($params['brandName'])){
                $save['brand_name'] = $params['brandName'];
            }
            if (!empty($params['obsSeriesTagId'])){
                $save['series_tag_id'] = $params['obsSeriesTagId'];
            }
            if (!empty($params['seriesTagName'])){
                $save['series_tag_name'] = $params['seriesTagName'];
            }
            if (!empty($params['productNumber'])){
                $save['product_number'] = $params['productNumber'];
            }
            if (!empty($params['customerTexture'])){
                $save['customer_texture'] = $params['customerTexture'];
            }
            if (!empty($params['buyLink'])){
                $save['buy_link'] = $params['buyLink'];
            }  
            if($this->likeRepository->count($filter)){
                $save['updated'] = time();
                $this->likeRepository->updateOneBy($filter, $save);
            }else{
                $save['created'] = time();
                $this->likeRepository->create($save);
            }
            $conn->commit();
        }catch(\Exception $e){
            $conn->rollback();
            throw $e;
        }
        
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->likeRepository->$method(...$parameters);
    }
}