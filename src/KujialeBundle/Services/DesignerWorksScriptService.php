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

    /**
     * 预处理并保存本批设计师作品，返回本批已保存的 (design_id, plan_id) 列表
     * @param array $worksList API 返回的作品列表
     * @return array 已保存的 [['design_id' => xx, 'plan_id' => xx], ...]
     */
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
        if (!empty($worksInfoList)) {
            $workService->saveDesignerWorks($worksInfoList);
        }
        return array_map(function ($row) {
            return ['design_id' => $row['designId'], 'plan_id' => $row['planId']];
        }, $worksInfoList);
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

            // 获取设计师方案，并收集本次拉取到的 (design_id, plan_id)
            // API 分页：start=起始位置(0,50,100...)，num=每页条数(最大50)，start=(page-1)*num
            $start = 0;
            $num = 50;
            $fetchedKeys = []; // 本次拉取到的 design_id|plan_id 集合，用于无数据删除
            $worksList = $this->apiService->getDesignerWorks($start, $num);
            if($worksList) {
                $batch = $this->preProcDesignerWorks($worksList);
                foreach ($batch as $pair) {
                    $fetchedKeys[$pair['design_id'] . '|' . $pair['plan_id']] = true;
                }
                while (!empty($worksList['hasMore'])) {
                    $start += $num;
                    $worksList = $this->apiService->getDesignerWorks($start, $num);
                    if (!$worksList || empty($worksList['result'])) {
                        break; // 本页无数据则结束，避免 API 异常导致死循环
                    }
                    $batch = $this->preProcDesignerWorks($worksList);
                    foreach ($batch as $pair) {
                        $fetchedKeys[$pair['design_id'] . '|' . $pair['plan_id']] = true;
                    }
                }
            }

            // 无数据删除：仅当本次有拉取到数据时，删除库中有但本次未拉到的作品
            if (!empty($fetchedKeys)) {
                $workService = new KujialeDesignerWorksService();
                $dbWorks = $workService->getLists([]);
                foreach ($dbWorks as $row) {
                    $key = $row['design_id'] . '|' . $row['plan_id'];
                    if (empty($fetchedKeys[$key])) {
                        $workService->deleteWorkAndRelated($row['design_id'], $row['plan_id']);
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
