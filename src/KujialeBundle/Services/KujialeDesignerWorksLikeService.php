<?php

namespace KujialeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use KujialeBundle\Entities\KujialeDesignerWorksLike;
use KujialeBundle\Repositories\KujialeDesignerWorksLikeRepository;

class KujialeDesignerWorksLikeService
{
    /**
     * @var KujialeDesignerWorksLikeRepository
     */
    protected $likeRepository;

    public function __construct(){
        $this->likeRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksLike::class);
    }

    public function saveLike($designId, $planId, $userId, $type){

        $filter['design_id'] = $designId;
        $filter['plan_id'] = $planId;
        $filter['user_id'] = $userId;
        $like = $this->likeRepository->getInfo($filter);

        try{
            $saveParams = $filter;
            if($like && $type == 'like'){
                throw new ResourceException('已经点赞过');
            }
            if(!$like && $type == 'like'){
                $saveParams['created'] = time();
                $this->likeRepository->create($saveParams);
                //更新对应的设计方案的点赞量
                $conn = app("registry")->getConnection("default");

                $sql = "UPDATE kujiale_designer_works SET `like_count`=`like_count`+1 WHERE `design_id`='".$designId."' AND `plan_id`='".$planId."'";
                $conn->executeUpdate($sql);
            }
            if($like && $type == 'unlike'){
                $this->likeRepository->deleteBy($filter);
                //更新对应的设计方案的点赞量
                $conn = app("registry")->getConnection("default");

                $sql = "UPDATE kujiale_designer_works SET `like_count`=`like_count`-1 WHERE `design_id`='".$designId."' AND `plan_id`='".$planId."'";
                $conn->executeUpdate($sql);
            }
            return true;
        }catch(\Exception $e){
            throw new ResourceException('点赞异常');
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