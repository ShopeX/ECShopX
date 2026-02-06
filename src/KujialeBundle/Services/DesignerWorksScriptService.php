<?php

namespace KujialeBundle\Services;

use KujialeBundle\Services\api\ApiService;

class DesignerWorksScriptService
{
    /**
     * @var ApiService $apiService 
     */
    private $apiService;

    public function __construct(){
        $this->apiService = new ApiService();
    }

    public function preProcDesignerWorks($worksList){
        $worksInfoList = [];
        $workService = new KujialeDesignerWorksService();
        foreach($worksList['result'] as $result){
            if (empty($result['obsDesignId'])){
                continue;
            }

            $tmp = [];
            $tmp['coverPic'] = $result['coverPic'];
            $tmp['designName'] = $result['designName'];
            $tmp['isOrigin'] = (isset($result['isOrigin']) && $result['isOrigin']) == true ? 1 : 0;
            $tmp['isExcellent'] = (isset($result['isExcellent']) && $result['isExcellent'] == true) ? 1 : 0;
            $tmp['isRealExcellent'] = (isset($result['isRealExcellent']) && $result['isRealExcellent'] == true) ? 1 : 0;
            $tmp['isTop'] = (isset($result['isTop']) && $result['isTop'] == true) ? 1 : 0;
            $tmp['submitSampleRoom'] = (isset($result['submitSampleRoom']) && $result['submitSampleRoom'] == true) ? 1 : 0;
            $tmp['designId'] = $result['obsDesignId'];
            // $tmp['city_id_list'] = $result['cityIdList'];
            $tmp['planId'] = !empty($result['obsPlanId'])?$result['obsPlanId']:$result['obsDesignId'];
            $tmp['ku_created'] = $result['created'] *0.001;
            // 获取方案信息
            $detail = $this->apiService->getDesignerWorksDetail($result['obsDesignId']);
            if($detail && $detail['basicInfo']){
                $tmp['commName'] = $detail['basicInfo']['commName'];
                $tmp['city'] = $detail['basicInfo']['city'];
                $tmp['name'] = $detail['basicInfo']['name'];
                $tmp['coverPic'] = $detail['basicInfo']['coverPic'];
            }
            // 楼层信息
            if($detail && $detail['levelInfos']){
                $tmp['level'] = $detail['levelInfos'];
            }

            // 标签信息
            $tags = $this->apiService->getDesignerWorksRelTag($result['obsDesignId']);
            if($tags){
                $tmp['tags'] = $tags;
            }

            $tmp = array_merge($tmp,$result['authorVo']);
            $worksInfoList[] = $tmp;
        }
        $workService->saveDesignerWorks($worksInfoList);
    }

    /**
     * 更新设计师方案作品
     */
    public function updateDesignerWorks(){

        try{
            // 更新标签列表
            $tagsList = $this->apiService->getDesignerTags();
            if($tagsList){
                $tagsService = new KujialeDesignerTagsService();
                $tagsService->saveTags($tagsList);
            }

            // 获取设计师方案
            $start = 0;
            $num = 50;
            $worksList = $this->apiService->getDesignerWorks($start,$num);
            app('log')->info('当前处理设计师方案 第 '.($start + 1).' - '.($start + $num).' 条数据。共有：'.$worksList['count'].'条数据');
            if($worksList) {
                // 如果有更多数据的话
                $this->preProcDesignerWorks($worksList);
                // 如果还有数据，继续抓取
                while ($worksList['hasMore']) {
                    $start += $num;
                    $worksList = $this->apiService->getDesignerWorks($start,$num);
                    app('log')->info('当前处理设计师方案 第 '.($start + 1).' - '.($start + $num).' 条数据。共有：'.$worksList['count'].'条数据');
                    if ($worksList) {
                        // 如果有更多数据的话
                        $this->preProcDesignerWorks($worksList);
                    }
                }
            }
        }catch(\Exception $e){
            throw $e;
        }
    }

    public function updateDesignerWorksPic(){

        try{
            $worksService = new KujialeDesignerWorksService();
            $worksList = $worksService->getLists([]);
            if($worksList){
                $picService = new KujialeDesignerWorksPicService();
                foreach($worksList as $work){
                    $picList = $this->apiService->getDesignerWorksPicList($work['design_id']);
                    if($picList && $picList['totalCount'] > 0){
                        foreach($picList['result'] as $pic){
                            $pic['design_id'] = $work['design_id'];
                            $pic['plan_id'] = $work['plan_id'];
                            $result = $picService->savePic($pic);
                        }
                    }
                }
            }

        }catch(\Exception $e){
            throw $e;
        }

    }

    public function updateDesignerPicAndGoods(){

        try{
            $worksService = new KujialeDesignerWorksPicService();
            $worksList = $worksService->getLists([]);
            if($worksList){
                $picService = new KujialeDesignerGoodsRelService();
                foreach($worksList as $work){
                    $picList = $this->apiService->getDesignerWorksRoomBrandGoods($work['pic_id']);
                    if (!empty($picList['obsBrandGoodIdList'])){
                        foreach ($picList['obsBrandGoodIdList'] as $godds){
                            $goods_rel = [
                                'picId' => $work['pic_id'],
                                'obsBrandGoodId' => $godds
                            ]; 
                            $picService->saveGoodsRel($goods_rel);
                        }
                    }
                    //处理完，在获取商品
                }
            }

        }catch(\Exception $e){
            throw $e;
        }

    }

    public function updateDesignerGoods(){

        try{
            $worksService = new KujialeDesignerGoodsRelService();
            $worksList = $worksService->getLists([]);
            if($worksList){
                $picService = new KujialeDesignerGoodsService();
                foreach($worksList as $work){
                    $goodsDetail = $this->apiService->getDetail($work['obs_brand_good_id']);
                    if (!empty($goodsDetail['brandGoodName'])){
                        $goodsDetail['goodsId'] = $work['obs_brand_good_id'];
                        $picService->saveGoods($goodsDetail);
                    }
                    //处理完，在获取商品
                }
            }

        }catch(\Exception $e){
            throw $e;
        }

    }

    /**
     * 定时执行获取设计师方案等数据
     */
    public function scheduleUpdateDesigner()
    {
        $this->updateDesignerWorks();
        $this->updateDesignerWorksPic();
        $this->updateDesignerPicAndGoods();
        $this->updateDesignerGoods();
        return true;
    }
}