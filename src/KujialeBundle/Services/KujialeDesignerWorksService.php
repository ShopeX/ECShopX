<?php

namespace KujialeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use KujialeBundle\Entities\KujialeDesignerTags;
use KujialeBundle\Entities\KujialeDesignerWorks;
use KujialeBundle\Entities\KujialeDesignerWorksLevel;
use KujialeBundle\Entities\KujialeDesignerWorksLike;
use KujialeBundle\Entities\KujialeDesignerWorksPic;
use KujialeBundle\Entities\KujialeDesignerWorksRelTags;
use KujialeBundle\Repositories\KujialeDesignerWorksRelTagsRepository;
use KujialeBundle\Repositories\KujialeDesignerWorksRelCitiesRepository;
use KujialeBundle\Repositories\KujialeDesignerWorksRepository;
use KujialeBundle\Repositories\KujialeDesignerWorksLevelRepository;
use KujialeBundle\Repositories\KujialeDesignerTagsRepository;
use KujialeBundle\Repositories\KujialeDesignerWorksPicRepository;
use KujialeBundle\Repositories\KujialeDesignerWorksLikeRepository;
use KujialeBundle\Repositories\KujialeDesignerWorksItemRelRepository;
use KujialeBundle\Entities\KujialeDesignerWorksItemRel;
use KujialeBundle\Services\api\ApiService;

class KujialeDesignerWorksService
{
    /**
     * @var KujialeDesignerWorksRepository
     */
    protected $designerRepository;

    /**
     * @var KujialeDesignerWorksLevelRepository
     */
    protected $levelRepository;

    /**
     * @var KujialeDesignerWorksRelTagsRepository
     */
    protected $relTagRepository;

    /**
     * @var KujialeDesignerTagsRepository
     */
    protected $tagRepository;

    /**
     * @var KujialeDesignerWorksPicRepository
     */
    protected $picRepository;

    /**
     * @var KujialeDesignerWorksLikeRepository
     */
    protected $likeRepository;

    /**
     * @var KujialeDesignerWorksRelCitiesRepository
     */
    protected $relCitiesRepository;

    /**
     * @var KujialeDesignerWorksItemRelRepository
     */
    protected $worksItemRelRepository;

    public function __construct(){
        $this->designerRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorks::class);
        $this->levelRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksLevel::class);
        $this->relTagRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksRelTags::class);
        $this->tagRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerTags::class);
        $this->picRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksPic::class);
        $this->likeRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksLike::class);
        $this->relCitiesRepository = app('registry')->getManager('default')->getRepository(\KujialeBundle\Entities\KujialeDesignerWorksRelCities::class);
        $this->worksItemRelRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksItemRel::class);
    }

    /**
     * 保存设计师作品内容
     * @param $tagParams
     */
    public function saveDesignerWorks($params){

        $designerWorks = [];
        $designerTags = [];
        $designerLevel = [];
        $designerCities = [];

        foreach($params as $param){
            $tmp = [];
            $tmp['design_name'] = $param['designName'];
            $tmp['cover_pic'] = $param['coverPic'];
            $tmp['is_origin'] = $param['isOrigin'];
            $tmp['is_excellent'] = $param['isExcellent'];
            $tmp['is_real_excellent'] = $param['isRealExcellent'];
            $tmp['is_top'] = $param['isTop'];
            $tmp['design_id'] = $param['designId'];
            $tmp['plan_id'] = $param['planId'];
            $tmp['comm_name'] = $param['commName'];
            $tmp['city'] = $param['city'];
            $tmp['name'] = $param['name'];
            $tmp['tag_id'] = isset($param['tagId']) && !empty($param['tagId']) ? $param['tagId'] : '';
            $tmp['design_pano_url'] = isset($param['designPanoUrl']) && !empty($param['designPanoUrl']) ? $param['designPanoUrl'] : '';
            $tmp['user_avatar'] = $param['userAvatar'] ?? '';
            $tmp['email'] = $param['email'] ?? '';
            $tmp['user_name'] = $param['userName'];
            $tmp['user_id'] = $param['obsUserId'];
            $tmp['organization_id'] = $param['obsOrganizationId'] ?? '';
            $tmp['ku_created'] = $param['ku_created'];
            $designerWorks[] = $tmp;

            // 处理城市ID列表
            if(isset($param['city_id_list']) && !empty($param['city_id_list']) && is_array($param['city_id_list'])){
                foreach($param['city_id_list'] as $cityId){
                    $cityTmp = [];
                    $cityTmp['design_id'] = $tmp['design_id'];
                    $cityTmp['plan_id'] = $tmp['plan_id'];
                    $cityTmp['city_id'] = $cityId;
                    $designerCities[] = $cityTmp;
                }
            }

            // 是否有楼层信息
            if(isset($param['level']) && !empty($param['level'])){
                foreach($param['level'] as $t){
                    $levelTmp = [];
                    $levelTmp['design_id'] = $tmp['design_id'];
                    $levelTmp['plan_id'] = $tmp['plan_id'];
                    $levelTmp['src_area'] = $t['srcArea'] ?? '';
                    $levelTmp['area'] = $t['area'] ?? '';
                    $levelTmp['real_area'] = $t['realArea'] ?? '';
                    $levelTmp['spec_name'] = $t['specName'];
                    $levelTmp['plan_pic'] = $t['planPic'] ?? '';
                    $levelTmp['level'] = $t['level'];
                    $designerLevel[] = $levelTmp;
                }
            }

            // 是否有标签信息
            if(isset($param['tags']) && !empty($param['tags'])){
                $tagsList = $this->tagRepository->lists(['tag_id' => $param['tags']]);
                if($tagsList['total_count'] == 0){
                   throw new \Exception('标签不存在');
                }
                $newTagsList = [];
                foreach($tagsList['list'] as $tag){
                   $newTagsList[$tag['tag_id']] = $tag;
                }
                foreach($param['tags'] as $t){
                    $tagsTmp = [];
                    $tagsTmp['tag_category_id'] = $newTagsList[$t]['tag_category_id'];
                    $tagsTmp['tag_category_name'] = $newTagsList[$t]['tag_category_name'];
                    $tagsTmp['tag_id'] = $t;
                    $tagsTmp['tag_name'] = $newTagsList[$t]['tag_name'];
                    $tagsTmp['design_id'] = $tmp['design_id'];
                    $tagsTmp['plan_id'] = $tmp['plan_id'];
                    $designerTags[] = $tagsTmp;
                }
            }
        }
        if(!empty($designerWorks)){
            $conn = app('registry')->getConnection('default');
            try{
                $conn->beginTransaction();
                foreach($designerWorks as $save){
                    $filter['design_id'] = $save['design_id'];
                    $filter['plan_id'] = $save['plan_id'];
                    if($this->designerRepository->count($filter)){
                        $save['updated'] = time();
                        app('log')->info('方案重复的数据: '.json_encode($filter));
                        $this->designerRepository->updateOneBy($filter,$save);
                    }else{
                        $save['created'] = time();
                        $this->designerRepository->create($save);
                    }
                }
                // 处理楼层数据
                foreach($designerLevel as $save){
                    $filter['design_id'] = $save['design_id'];
                    $filter['plan_id'] = $save['plan_id'];
                    $filter['plan_id'] = $save['plan_id'];
                    if($this->levelRepository->count($filter)){
                        $save['updated'] = time();
                        $this->levelRepository->updateOneBy($filter,$save);
                    }else{
                        $save['created'] = time();
                        $this->levelRepository->create($save);
                    }
                }

                //标签处理
                foreach($designerTags as $save){
                    $filter['design_id'] = $save['design_id'];
                    $filter['plan_id'] = $save['plan_id'];
                    $filter['tag_category_id'] = $save['tag_category_id'];
                    $filter['tag_id'] = $save['tag_id'];
                    if($this->relTagRepository->count($filter)){
                        $save['updated'] = time();
                        $this->relTagRepository->updateOneBy($filter,$save);
                    }else{
                        $save['created'] = time();
                        $this->relTagRepository->create($save);
                    }
                }

                // 城市关联处理
                // 先删除该方案和户型的所有城市关联记录
                foreach($designerWorks as $work){
                    $this->relCitiesRepository->deleteByDesignAndPlan($work['design_id'], $work['plan_id']);
                }
                // 然后插入新的城市关联记录
                foreach($designerCities as $save){
                    $save['created'] = time();
                    $this->relCitiesRepository->create($save);
                }

                $conn->commit();
            }catch(\Exception $e){
                $conn->rollback();
                throw $e;
            }
        }
        return true;
    }

    /**
     * 获取设计师方案列表
     * @param $filter
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getDesignerWorksList($filter,$page = 1, $pageSize = 20){

        $newFilter = [];
        $sort = $filter['sort'] ?? '';
        unset($filter['sort']);

        $userId = null;
        if(isset($filter['user_id']) && !empty($filter['user_id'])){
            $userId = $filter['user_id'];
            unset($filter['user_id']);
        }

        $orderBy = [
            // 'ku_created' => 'desc'
        ];
        if($sort === 'hot')
        {
            $orderBy = ['view_count' => 'desc']; 
        }
        if($sort === 'latest'){
            $orderBy = ['ku_created' => 'desc'];
        }
        if(isset($filter['new']) && $filter['new'] != ''){
            $orderBy['ku_created'] = $filter['new'] == 0 ? 'asc' : 'desc';
            unset($filter['new']);
        }
        if(isset($filter['viewcount']) && $filter['viewcount'] != ''){
            $orderBy['view_count'] = $filter['viewcount'] == 0 ? 'asc' : 'desc';
            unset($filter['viewcount']);
        }
        if(isset($filter['likecount']) && $filter['likecount'] != ''){
            $orderBy['like_count'] = $filter['likecount'] == 0 ? 'asc' : 'desc';
            unset($filter['likecount']);
        }

        // 如果是关键字搜索 支持户型、产品、风格
        if(isset($filter['keyword']) && !empty($filter['keyword'])){
            $newFilter['design_name|contains'] = $filter['keyword'];
            // 查询户型
            // $planList = $this->levelRepository->lists(['spec_name|contains' => $filter['keyword']]);
            // $designIds = array_column($planList,'design_id');
            
            // if(!empty($designIds)){
            //     $newFilter['design_id'] = $designIds;
            // }else{
            //     $newFilter['design_name|contains'] = $filter['keyword'];
            // }
        }

        // 如果存在标签搜索
        if(isset($filter['tags']) && !empty($filter['tags'])){
            $tagFilter = [];
            $conn = app('registry')->getConnection('default');
            $designerIds = [];
            foreach($filter['tags'] as $k=>$tag){
                // $tagFilter['tag_category_id'][] = $tag['tag_category_id'];
                // $tagFilter['tag_id'][] = $tag['tag_id'];
                $qb = $conn->createQueryBuilder();
                $qb->select('design_id')
                ->from('`kujiale_designer_works_rel_tags`')
                ->where($qb->expr()->eq('tag_category_id', $qb->expr()->literal($tag['tag_category_id'])))
                ->andWhere($qb->expr()->eq('tag_id', $qb->expr()->literal($tag['tag_id'])));
                $tagsList = $qb->execute()->fetchAll();
                foreach($tagsList as $tag1){
                    $designerIds[$k][] = $tag1['design_id'];
                }
            }
            $newFilter['design_id'] = [];
            foreach ($designerIds as $k=>$ids){
                if ($k==0){
                    $newFilter['design_id'] = $ids;
                }else{
                    $newFilter['design_id'] = array_intersect($ids, $newFilter['design_id']);
                }
            }
        }
        if (isset($filter['tags']) && !empty($filter['tags']) && empty($newFilter['design_id'])){
            $newFilter['design_id'] = '';
        }
        if(!empty($filter['cityIds'])){
            $newFilter['city|contains'] = $filter['cityIds'];
        }
        // 查询产品
        $result = $this->designerRepository->lists($newFilter,$page,$pageSize,$orderBy);
        if(empty($result['list'])){
            return $result;
        }
        $newLikeList = [];
        if(!is_null($userId)){
            // 获取用户对应的点赞记录
            unset($filter);
            $filter['user_id'] = $userId;
            $likeList = $this->likeRepository->lists($filter);
            if($likeList['total_count'] > 0){
                foreach($likeList['list'] as $like){
                   $newLikeList[$userId.'_'.$like['design_id'].'_'.$like['plan_id']] = true;
                }
            }
        }
        foreach($result['list'] as &$res){
            $res['is_like'] = false;
        if(!empty($userId) && isset($newLikeList[$userId.'_'.$res['design_id'].'_'.$res['plan_id']]) && !empty($newLikeList[$userId.'_'.$res['design_id'].'_'.$res['plan_id']])){
                $res['is_like'] = true;
            }
        }

        //获取标签
        $designIds = array_column($result['list'], 'design_id');
        $tagList = $this->relTagRepository->lists(['design_id' => $designIds]);
        $newTagList = [];
        if(!empty($tagList['list'])){
            foreach($tagList['list'] as $tag){
                $newTagList[$tag['design_id']][] = $tag;
            }
        }
        foreach($result['list'] as &$resPassBy){
            $resPassBy['taginfo'] = $newTagList[$resPassBy['design_id']] ?? [];
        }
        return $result;
    }

    /**
     * 获取方案详情
     * @param $filter
     */
    public function getDesignerWorksDetail($filter){
        $returnValue = [
            'basicInfo' => [],
            'levelinfo' => [],
            'picinfo' => []
        ];
        $info = $this->designerRepository->getInfo($filter);
        if($info){
            
            $returnValue['basicInfo'] = $info;
            // 查询楼层信息
            $levelInfo = $this->levelRepository->lists($filter);
            if($levelInfo['total_count'] > 0){
                $returnValue['levelinfo'] = $levelInfo['list'];
            }
            //查询全景图信息
            $picList = $this->picRepository->lists($filter);
            if($picList['total_count'] > 0){
                $returnValue['picinfo'] = $picList['list'];

                $returnValue['web_view_url'] = false;

                foreach ($picList['list'] as $key => $v) {
                    if (!empty($v['pano_link'])  && $v['pic_type']  && !$returnValue['web_view_url'] ){
                        $kujiale_pano53 = "pano53.p.kujiale.com";
                        $kujiale_www = "www.kujiale.com";
                        $pano_link = str_replace($kujiale_www, $kujiale_pano53, $v['pano_link']);
                        $returnValue['picinfo'][0]['pano_link']      = $pano_link;
                        $returnValue['basicInfo']['design_pano_url'] = $pano_link;

                        // $returnValue['web_view_url'] = config('kujiale.webViewUrl').'?pic_id='.$v['pic_id'];
                    }
                    
                }
            }

            $returnValue['basicInfo']['design_mesh_url'] = "";
            if(!empty($returnValue['basicInfo']['design_pano_url'])){
                $returnValue['basicInfo']['design_mesh_url'] = "https://pano572.p.kujiale.com/design/".$returnValue['basicInfo']['design_id']."/show";
            }

            $tagList = $this->relTagRepository->lists(['design_id' => $info['design_id']]);
            $returnValue['taginfo'] = [];
            if($tagList['total_count'] > 0){
                $returnValue['taginfo'] = $tagList['list'];
            }
        }
        //更新viewcount
        if($info && !empty($filter)){
            try {
                $this->designerRepository->countView($filter);
            } catch (\Exception $e) {
                // 更新失败不影响主流程，记录日志即可
                app('log')->error('更新view_count失败: ' . $e->getMessage());
            }
        }
        //更新viewcount end

        return $returnValue;
    }

    public function getDesignerWorksDetailByApi(array $params){
        $roomList = (new ApiService())->getDesignerWorksPicList($params['design_id']);
        return ['roomList'=>$roomList['result']];
    }

    public function updateViewCount($designId, $plan_id){

        $filter['design_id'] = $designId;
        $filter['plan_id'] = $plan_id;

        return $this->designerRepository->countView($filter);
    }

    /**
     * 根据商品ID获取关联的设计作品列表
     * 只返回 design_id, design_name, cover_pic 三个字段
     *
     * @param int $itemId 商品ID
     * @return array 设计作品列表
     */
    public function getDesignerWorksByItemId($itemId)
    {
        if (empty($itemId)) {
            return [];
        }

        // 查询商品关联的设计作品绑定关系
        $boundRels = $this->worksItemRelRepository->getLists(['item_id' => $itemId]);
        
        if (empty($boundRels)) {
            return [];
        }

        // 获取所有关联的 design_id
        $designIds = array_column($boundRels, 'design_id');
        if (empty($designIds)) {
            return [];
        }

        // 查询设计作品信息
        $designerWorks = $this->designerRepository->getLists(['design_id' => $designIds]);

        // 只返回需要的字段
        $result = [];
        foreach ($designerWorks as $work) {
            $result[] = [
                'design_id' => $work['design_id'],
                'design_name' => $work['design_name'],
                'cover_pic' => $work['cover_pic'],
                'plan_id' => $work['plan_id'],
            ];
        }

        return $result;
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
        return $this->designerRepository->$method(...$parameters);
    }
}