<?php

namespace KujialeBundle\Services;

use KujialeBundle\Entities\KujialeDesignerWorksPic;
use KujialeBundle\Repositories\KujialeDesignerWorksPicRepository;

class KujialeDesignerWorksPicService
{
    /**
     * @var KujialeDesignerWorksPicRepository
     */
    protected $picRepository;

    public function __construct(){
        $this->picRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksPic::class);
    }

    public function savePic($params){

        $saveParams = [];
        $saveParams['img'] = $params['img'];
        $saveParams['pic_id'] = $params['picId'];
        $saveParams['pic_type'] = $params['picType'];
        $saveParams['pic_detail_type'] = $params['picDetailType'];
        $saveParams['room_name'] = $params['roomName'];
        $saveParams['pano_link'] = isset($params['panoLink']) && !empty($params['panoLink']) ? $params['panoLink'] : '';
        $saveParams['design_id'] = $params['design_id'];
        $saveParams['plan_id'] = $params['plan_id'];

        $filter['pic_id'] = $params['picId'];
        // $filter['design_id'] = $params['design_id'];
        // $filter['plan_id'] = $params['plan_id'];
        if($this->picRepository->count($filter)){
            $saveParams['updated'] = time();
            return $this->picRepository->updateBy($filter,$saveParams);
        }else{
            $saveParams['created'] = time();
            return $this->picRepository->create($saveParams);
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
        return $this->picRepository->$method(...$parameters);
    }
}