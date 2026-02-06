<?php

namespace KujialeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use KujialeBundle\Entities\KujialeDesignerGoodsRel;
use KujialeBundle\Repositories\KujialeDesignerGoodsRelRepository;

class KujialeDesignerGoodsRelService
{
    /**
     * @var KujialeDesignerGoodsRelRepository
     */
    protected $likeRepository;

    public function __construct(){
        $this->likeRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerGoodsRel::class);
    }

    public function saveGoodsRel($params){
        $conn = app('registry')->getConnection('default');
        try{
            $conn->beginTransaction();
            $filter['pic_id'] = $params['picId'];
            $filter['obs_brand_good_id'] = $params['obsBrandGoodId'];

            $save['pic_id'] = $params['picId'];
            $save['obs_brand_good_id'] = $params['obsBrandGoodId'];
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