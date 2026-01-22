<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace WsugcBundle\Services;

use WsugcBundle\Entities\Post;
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\CompanysService;
use GoodsBundle\Services\ItemsService;
use WsugcBundle\Services\BadgeService;
use WsugcBundle\Services\TopicService;
use WsugcBundle\Services\TagService;
use WsugcBundle\Services\SettingService;

use MembersBundle\Services\WechatUserService;
use WsugcBundle\Services\PostFavoriteService;
use MembersBundle\Entities\WechatUserInfo;
use MembersBundle\Entities\MembersAssociations;
use PointBundle\Services\PointMemberService;

class PostService
{
    public function __construct()
    {
        // $this->entityRepository = app('registry')->getManager('default')->getRepository(Post::class);
        $this->entityRepository = getRepositoryLangue(Post::class);
    }

    public function saveData($params, $filter=[])
    {
        // 0x456353686f7058
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
    public function getPostList($filter,$cols="*", $page = 1, $pageSize = -1, $orderBy=[],$fromAdmin=false,$user_id_auth=0)
    {
        $postService = new PostService();
        $defaultOrderBy=[
            'p_order' => 'asc',
            'created' => 'desc',
            'mobile'  => 'asc'
        ];
        if(!$orderBy){
            //按排序，小的在前。
            //排序从小到大，置顶是-1，然后创建时间新的在前，然后手机号
            $orderBy=$defaultOrderBy;
        }
        else{
            $tmpOrderBy['p_order']='asc';
            foreach($orderBy as $kb=>$vb){
                $tmpOrderBy[$kb]=$vb;
            }
            if(!array_key_exists('created',$tmpOrderBy)){
                $tmpOrderBy['created']='desc';//防止时间还有正序。。
            }
            $tmpOrderBy['mobile']='asc';
            $orderBy=$tmpOrderBy;//array_merge($orderBy,$defaultOrderBy);
        }
        //print_r($orderBy);exit;
        //print_r($filter);exit;
        $lists = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
        $wechatUserService = new WechatUserService();

        foreach ($lists['list'] as &$v) {
            $v['user_id_auth']=$user_id_auth;
            $v=$this->formatPost($v,false,$wechatUserService,$fromAdmin);
            ksort($v);
        }
        if($v??null){
            unset($v);
            //防止&引用影响到下面的循环
        }
        return $lists;
    }
    /**
     * [formatPost 格式化活动数据]
     * @Author   sksk
     * @DateTime 2021-07-14T10:14:36+0800
     * @param    [type]                   $v [description]
     * @return   [type]                      [description]
     */
    function formatPost($v,$fromdetail=false,$wechatUserService=null,$fromAdmin=false){
        $v['created_text'] = date('Y-m-d H:i:s', $v['created']);
        $v['status']=$this->getPostStatusReal($v);//真正的status
        $v['status_text']=$this->getPostStatusText($v['status'] );//真正的status
        $tagService=new TagService();

        if($v['user_id']??null){
            $filter = ['user_id' => $v['user_id'], 'company_id' => $v['company_id']];
            $v['userInfo'] = $wechatUserService->getUserInfo($filter);

            $this->memberService=new MemberService();
            $memberInfo = $this->memberService->getMemberInfo($filter);
          
            if($memberInfo){
                $v['userInfo']=array_merge( $memberInfo,$v['userInfo']);
                if($fromAdmin){
                    if($v['source']==2){
                        $allow_keys_user=['username','avatar','headimgurl','nickname','user_id'];

                    }
                    else{
                        $allow_keys_user=['username','avatar','headimgurl','nickname','user_id','mobile'];

                    }
                }
                else{
                    if($v['source']==2){

                        //官方需要union_id
                        $allow_keys_user=['username','avatar','headimgurl','nickname','user_id','unionid'];

                    }
                    else{
                        $allow_keys_user=['username','avatar','headimgurl','nickname','user_id'];
                    }
                }
               
                foreach($v['userInfo'] as $km=>$vm){
                    if(!in_array($km,$allow_keys_user)){
                        unset($v['userInfo'][$km]);
                    }
                }
            }
        } else {
            $service = new SettingService();
            $setting = $service->getSettingList(['company_id' => $v['company_id'], 'type' => 'official'],'*');
            if ($setting) {
                $setting = array_column($setting['list'], 'value', 'keyname');
            }
            $v['userInfo'] = [
                'nickname'=>$setting['official.nickname'] ?? '',
                'headimgurl'=>$setting['official.headerimgurl'] ?? '',
            ];
        }
        if($fromdetail || $fromAdmin){
            //相关话题
            if($v['topics']??null){
                $v['topics_origin']=$v['topics'];

                $v['topics']=explode(',',$v['topics']);
                $tmpTopicsArray=$v['topics'];
                if($v['topics']){
                    $topicService=new TopicService();
                    if(!$fromAdmin){
                        $filterTopics=['topic_id'=>$v['topics'],'status'=>1,'company_id'=>$v['company_id']];
                    }
                    else{
                        $filterTopics=['topic_id'=>$v['topics'],'company_id'=>$v['company_id']];
                    }
                    $itemList=$topicService->getTopicList($filterTopics,'topic_id,topic_name,status,created');
                    $v['topics']=$itemList['list']??[];
                    if($v['topics']){
                        //还是得按选择的topics排序
                       $tmpTopicsList=[];
                       foreach($v['topics'] as $ktplist=>$vtplist){
                            $tmpTopicsList[$vtplist['topic_id']]=$vtplist;
                       }
                       $lastTopicsList=[];
                       foreach($tmpTopicsArray as $k_topic_id=>$v_topic_id_v){
                        if($tmpTopicsList[$v_topic_id_v]??null){
                            $lastTopicsList[]=$tmpTopicsList[$v_topic_id_v];
                        }
                       }
                       $v['topics']=$lastTopicsList;
                    }
                }
                else{
                    $v['topics']=[];
                }
              
            }
            //相关商品
            if($v['goods']??null){
                $v['goods']=explode(',',$v['goods']);
                if(is_array($v['goods']) && count($v['goods'])>0){
                    $itemsService = new ItemsService();
                    $result = $itemsService->getItemListData(['item_id'=>$v['goods'],'company_id'=>($v['company_id']??1)], 1, 20, []);//'item_id,goods_id,brief,default_item_id,item_name,itemName,itemBn,price,pics,store'
                    // 如果是推广员不需要计算会员价
                    if ($result['list'] && ($v['user_id']??null)) {
                            // 计算会员价
                            $result = $itemsService->getItemsListMemberPrice($result, $v['user_id'], $v['company_id']??1);
                        }
                    //$itemList = $itemService->getItemsList();
                    //,'item_id,goods_id,brief,default_item_id,item_name,itemName,itemBn,price,pics,store'
                    $v['goods']=$result['list']??[];
                }
                else{
                    $v['goods']=[];
                }
                
            }
        }
        //相关角标
        if($v['badges']??null){
            $v['badges_origin']=$v['badges'];

            $v['badges']=explode(',',$v['badges']);
            if($v['badges']){
                $badgeService = new BadgeService();
                $itemList = $badgeService->getBadgeList(['badge_id'=>$v['badges'],'status'=>1],'badge_id,badge_name,created,status');
                $v['badges']=$itemList['list']??[];
            }
            else{
                $v['badges']=[];
            }
        }
        if($v['mobile']??null){
            unset($v['mobile']);
        }
        if($v['ip']??null){
            unset($v['ip']);
        }
        //收藏，点赞，关注用户 3个状态
        if(!$fromAdmin && $v['user_id_auth']){
            $postLikeService=new PostLikeService();
            $postFavoriteService=new PostFavoriteService();
            $followerService=new FollowerService();

            $v['like_status']=$postLikeService->getPostUserLikeStatus($v['post_id'],$v['user_id_auth']);
            $v['favorite_status']=$postFavoriteService->getPostUserFavorStatus($v['post_id'],$v['user_id_auth']);
            $v['follow_status']=$followerService->getFollowStatus($v['user_id_auth'],$v['user_id']);//粉丝id=>博主id
        }
        else{
            $v['like_status']=0;
            $v['favorite_status']=0;
            $v['follow_status']=0;
        }
        if(!$fromAdmin){
            //收藏总数
            $postFavoriteService=new PostFavoriteService();
            $v['favorite_nums']=$postFavoriteService->getPostFavorites(['post_id'=>$v['post_id']]);
        }
        return $v;
            
    }


    public function likePost($params)
    {
        $postLikeService = new PostLikeService();

        $result=$postLikeService->likePost($params);

        if (isset($result['likes']) && $result['likes']>=0) {
            $res = $this->entityRepository->updateOneBy(['post_id'=>$params['post_id']], ['likes'=>$result['likes']]);
        }

        return $result;
    }
    //收藏笔记
    public function favoritePost($params)
    {
        $postFavorteService = new PostFavoriteService();

        $result=$postFavorteService->favoritePost($params);

        // if (isset($result['likes']) && $result['likes']>=0) {
        //     $res = $this->entityRepository->updateOneBy(['post_id'=>$params['post_id']], ['likes'=>$result['likes']]);
        // }

        return $result;
    }

    /**
     * [getActivityDetail description]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getPostDetail($filter,$user_id="",$fromAdmin=false){
        $postInfo=$this->getInfo($filter);
        if($postInfo && ($postInfo['post_id']??null)){
            $wechatUserService = new WechatUserService();
            $postInfo['user_id_auth']=$user_id;
            $postInfo=$this->formatPost($postInfo,true,$wechatUserService,$fromAdmin);
        }
        ksort($postInfo);
        return $postInfo;
    }
        /**获得活动状态
     * @param string $activity_id
     * Author:sksk
     */
    function getPostStatusReal($activity_info=""){
        //$postService = new PostService();        
        return $activity_info['status'];
    }
    public function getPostStatusText($key=""){
        //(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)
       $rs = trans('WsugcBundle/langue.PostService_getPostStatusText');
       if((string)$key !='' ){
           return $rs[$key];
       }
       else{
           return $rs;
       }
   }
   /**
    * Undocumented function
    *
    * @param [type] $user_id
    * @return void
    */
   public function getNickName($user_id,$company_id){
        $wechatUserService = new WechatUserService();
        $filter = ['user_id' => $user_id, 'company_id' => $company_id];
        $userInfo = $wechatUserService->getUserInfo($filter);
        return $userInfo['nickname']??'-';
   }
   /**
    * 根据昵称查找user_id function
    *
    * @param string $nickname
    * @return void
    */
   function getUserIdByNickName($nickname=""){
        $wechatUserInfoRepository = app('registry')->getManager('default')->getRepository(WechatUserInfo::class);
        $memberInfo = $wechatUserInfoRepository->getAllLists(['nickname|contains'=>$nickname],'unionid');

        if($memberInfo??null){
            $allUnionid=array_column($memberInfo,'unionid');

            if($allUnionid){
                $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
                //
                $memberAdssociationsList = $membersAssociationsRepository->lists(['unionid'=>$allUnionid],'user_id');
                if($memberAdssociationsList??null){
                    $allUserIds=array_column($memberAdssociationsList,'user_id');
                    if($allUserIds){
                        return $allUserIds;
                    }
                }
            }

        }
        return[-1];
        //return $user_id;
   }
   /**
    * Undocumented function
    *
    * @param string $nickname
    * @return void
    */
   function getUserIdByMobile($mobile=""){ 
        $this->memberService=new MemberService();
        $memberInfo = $this->memberService->membersRepository->lists(['mobile'=>$mobile]);
        if($memberInfo['list']??null){
            return [$memberInfo['list'][0]['user_id']];
        }
        return[-1];
    }
    /**
     * 获取openid
     *
     * @param [type] $userId
     * @param [type] $companyId
     * @return void
     */
    function getOpenId($userId,$companyId){
        $wxaappid = app('wxaTemplateMsg')->getWxaAppId($companyId);

        app('log')->debug('getOpenId userId:'.$userId.' \r\n companyId:'.$companyId." \r\n 返回微信appid:".$wxaappid);

        if (!$wxaappid) {
            return '';
        }
        $openid = app('wxaTemplateMsg')->getOpenIdBy($userId, $wxaappid);

        app('log')->debug('getOpenId userId:'.$userId.' \r\n wxaappid:'.$wxaappid." \r\n 返回微信openid:".$openid);

        if ($openid) {
            return $openid;
        }
        return '';
    }
    /**
     * Undocumented function
     *
     * @return void
     */
    function deletePost($filter){
        $result = $this->entityRepository->updateBy($filter, ['disabled'=>1]);
        return $result;
    }
    function updateIsTopPost($post_id){
        //p_order小于0的，都是当前置顶的
        $filter['disabled']=0;
        $filter['is_top']=1;
        //当前置顶的
        $nowTopPostList = $this->entityRepository->lists($filter,'post_id,created',1,-1,['created'=>'desc']);
        $toTopPostList=$this->entityRepository->lists(['post_id'=>$post_id],'post_id,created',1,-1);
        $toTopPostList= $toTopPostList['list'];//
        $max=2;
        //print_r($isTopList);exit;
        if($nowTopPostList['list'] ?? null ){
            $nowTopPostList=$nowTopPostList['list'];
            $lastPostList=array_merge($nowTopPostList,$toTopPostList);
            $all_createtime=[];
            foreach($lastPostList as $k=>$v){
                $all_createtime[]=$v['created'];
            }
            array_multisort($all_createtime,SORT_DESC,$lastPostList);
             foreach($lastPostList as $klast=>$vlast){
                if($klast+1>$max){
                    $result = $this->entityRepository->updateBy(['post_id'=>$vlast['post_id']], ['p_order'=>0,'is_top'=>0]);
                }
                else{
                    $result = $this->entityRepository->updateBy(['post_id'=>$v['post_id']], ['p_order'=>($max-$klast)*(-1),'is_top'=>1]);
                }

            }
            //列表
            //有几个
            // if($isTopList['total_count']<$max){
            //     //有1个

            //     //旧的
            //     //$result1 = $this->entityRepository->updateBy(['post_id'=>$isTopList['list'][0]['post_id']], ['p_order'=>-1]);
            //     foreach($isTopList['list'] as $k=>$v){
            //         if($nowPost_create_time>$)
            //         $result2 = $this->entityRepository->updateBy(['post_id'=>$v['post_id']], ['p_order'=>($max-$k)*(-1),'is_top'=>1]);
            //     }
            //     //当前的
            //     $resultNow = $this->entityRepository->updateBy(['post_id'=>$post_id], ['p_order'=>($max-$isTopList['total_count'])*-1,'is_top'=>1]);
            // }
            // elseif($isTopList['total_count']>=$max){
            //     // foreach($isTopList['list'] as $k=>$v){
            //     //     $result2 = $this->entityRepository->updateBy(['post_id'=>$v['post_id']], ['p_order'=>0,'is_top'=>0]);

            //     // }
            //     //干掉最后一个
            //     $resultLast = $this->entityRepository->updateBy(['post_id'=>$isTopList['list'][$isTopList['total_count']-1]['post_id']], ['p_order'=>0,'is_top'=>0]);

            //     //当前的
            //     $resultNow = $this->entityRepository->updateBy(['post_id'=>$post_id], ['p_order'=>($max-$isTopList['total_count']-1),'is_top'=>1]);

            // }
        }
        else{
            //啥都
            $resultNow = $this->entityRepository->updateBy(['post_id'=>$post_id], ['p_order'=>'-1','is_top'=>1]);

        }
        //$result = $this->entityRepository->updateBy($filter, ['p_order'=>0]);
        return true;
    }
    /**
     * Undocumented function
     *
     * @param [type] $post_id
     * @param [type] $user_id
     * @param [type] $company_id
     * @param [type] $journal_type
     * @return void
     */
    /*{"point_enable":"1","point_max_day":"6","point_post_like_get_once":"1","point_post_like_get_max_times_day":"5","point_post_share_get_once":"3","point_post_share_get_max_times_day":"3","point_post_comment_get_once":"2","point_post_comment_get_max_times_day":"4","point_post_favorite_get_once":"4","point_post_favorite_get_max_times_day":"2","point_post_create_get_once":"5","point_post_create_get_max_times_day":"1"}*/
    function addUgcPoint($post_id,$user_id,$company_id,$journal_type,$add_or_reduce=""){
        $settingService=new SettingService();
        $pointMemberService = new PointMemberService();
        $pointParams=$this->getPointByAction($company_id,$journal_type,$settingService,$add_or_reduce);
        $actionTtitle=$pointParams['title'];

        if(!$add_or_reduce){ 
            //不是拒绝才走下面的
            $point_enable=$settingService->getSetting($company_id, 'point_enable');
            if(!$point_enable){
                app('log')->debug('ugc积分开启状态:未开启');
                return false;
            }
        

            if(!($pointParams && $pointParams['once'])){
                app('log')->debug('ugc积分此动作:未设置积分数量');
                return false;
            }


            $point=$pointParams['once'];//每次活动积分数量
            $get_max_times_day=$pointParams['get_max_times_day'];//每天可获得多少次

            if(!$point){
                app('log')->debug('ugc积分此动作设置的point不大于0,journal_type:'.$journal_type);
                return false;
            }

            //同一post_id,user_id,同一动作，status=true,只能送一次 除了评论和分享
            if($journal_type!=22 && $journal_type!=24){
                $checkSamePostId=$this->checkSamePostId($post_id,$user_id,$journal_type,$settingService,$pointMemberService,$add_or_reduce);
                if(!$checkSamePostId){
                    app('log')->debug('ugc积分此动作此post_id，user_id已存在积分记录 post_id:'.$post_id.'user_id'.$user_id.'|journal_type:'.$journal_type);
                    return false;
                }
            }
    


            //检测当日最高限制
            $checkPointMaxDay=$this->checkPointMaxDay($company_id,$user_id,$point,$settingService,$pointMemberService);
            if(!$checkPointMaxDay){
                return false;
            }

            //检测当前动作每日最高次数限制
            $checkPointActionMaxTimes=$this->checkPointActionMaxTimes($user_id,$journal_type,$get_max_times_day,$settingService,$pointMemberService);
            if(!$checkPointActionMaxTimes){
                return false;
            }
        }
        else{
            //查询这个动作是否存在扣除积分的行为
            $checkSamePostId=$this->checkSamePostId($post_id,$user_id,$journal_type,$settingService,$pointMemberService,$add_or_reduce);
            if(!$checkSamePostId){
                app('log')->debug('扣减积分-ugc积分此动作此post_id，user_id已存在积分记录 post_id:'.$post_id.'user_id'.$user_id.'|journal_type:'.$journal_type);
                return false;
            }
            //然后查出当初这个正向动作给了多少积分
            $oldjournal_type=str_replace('99','',$journal_type);
            $point=$this->checkSamePostId($post_id,$user_id,$oldjournal_type,$settingService,$pointMemberService,false,true);
            
        }
        // 查询会员信息
        $memberService = new MemberService();
        $mobile = $memberService->getMobileByUserId($user_id, $company_id);
        if(!$mobile){
            return false;
        // throw new ResourceException('未查询到相关会员信息');
        }
        $point = intval($point);
        if($point <= 0){
             //throw new ResourceException('积分必填');
             return false;

        }
        $status=true;
        if($add_or_reduce){
            $status=false;//减去
        }
        $postService = new PostService();
        $postInfo=$postService->getInfo(['post_id'=>$post_id]);
        $postTitle=$postInfo['title'];
        $record=$mobile.$actionTtitle.$post_id."【".$postTitle."]";
        $result = $pointMemberService->addPoint($user_id, $company_id, $point, $journal_type, $status, $record,$post_id,['external_id'=>$post_id]);
        return $result;
    }
    /**
     * 每日最大赠送 function
     *
     * @param [type] $user_id
     * @param [type] $point
     * @param [type] $max_day
     * @param [type] $pointMemberService
     * @return void
     */
    function checkPointActionMaxTimes($user_id,$journal_type,$get_max_times_day,$settingService,$pointMemberService){
        $today_begin_time=strtotime(date('Y-m-d'));
        $today_end_time=strtotime(date('Y-m-d').' 23:59:59');
        $filter=[
            'user_id'=>$user_id,
            'journal_type'=>$journal_type,
            'created|gte'=>$today_begin_time,
            'created|lte'=>$today_end_time
        ];
        $count=$pointMemberService->pointMemberLogRepository->count($filter);
        if($count+1>$get_max_times_day){
            app('log')->debug('ugc积分当日journal_type:'.$journal_type.',赠送 超出每日总次数限制：user_id:'.$user_id.'|count:'.$count.'|当前累计积分含本次赠送:'.($count+1).'|get_max_times_day:'.$get_max_times_day);
            return false;
        }
        else{
            app('log')->debug('ugc积分当日journal_type:'.$journal_type.',赠送 没有超出每日总次数限制：user_id:'.$user_id.'|count:'.$count.'|当前累计积分含本次赠送:'.($count+1).'|get_max_times_day:'.$get_max_times_day);
            return true;
        }
    }

    /**
     * 每个动作，每日最大赠送次数 function
     *
     * @param [type] $user_id
     * @param [type] $point
     * @param [type] $max_day
     * @param [type] $pointMemberService
     * @return void
     */
    function checkPointMaxDay($company_id,$user_id,$point,$settingService,$pointMemberService){
        $today_begin_time=strtotime(date('Y-m-d'));
        $today_end_time=strtotime(date('Y-m-d').' 23:59:59');
        $point_max_day=$settingService->getSetting($company_id, 'point_max_day');
        $filter=[
            'user_id'=>$user_id,
            'journal_type'=>array_keys($this->allJournalType()),
            'created|gte'=>$today_begin_time,
            'created|lte'=>$today_end_time,
            'income|gt'=>0,
        ];
        $sumList=$pointMemberService->pointMemberLogRepository->lists($filter,1,999999);
        $sum=0;
        if($sumList['list']??null){
            $sumCols=array_column($sumList['list'],'point');
            $sum=array_sum($sumCols);
        }
        if($sum+$point>$point_max_day){
            app('log')->debug('ugc积分当日已赠送 超出每日总数限制：user_id:'.$user_id.'|sum:'.$sum.'|当前累计积分含本次赠送:'.($sum+$point).'|point_max_day:'.$point_max_day);
            return false;
        }
        else{
            app('log')->debug('ugc积分当日已赠送 还没有每日总数限制：user_id:'.$user_id.'|sum:'.$sum.'|当前累计积分含本次赠送:'.($sum+$point).'|point_max_day:'.$point_max_day);
            return true;
        }
    }


    function checkSamePostId($post_id,$user_id,$journal_type,$settingService,$pointMemberService,$add_or_reduce="",$need_pointreturn=false){
        if(!$add_or_reduce){
            $filter=[
                'user_id'=>$user_id,
                'external_id'=>$post_id,
                'journal_type'=>$journal_type,
                'income|gt'=>0,
            ];
        }
        else{
            //减去
            $filter=[
                'user_id'=>$user_id,
                'external_id'=>$post_id,
                'journal_type'=>$journal_type,
                'outcome|gt'=>0,
            ];
        }
       
        if($need_pointreturn){
            $info=$pointMemberService->pointMemberLogRepository->lists($filter);
            return (isset($info['list'])?$info['list'][0]['point']:0);
        }
        else{
            $exist=$pointMemberService->pointMemberLogRepository->count($filter);

            if($exist){
                app('log')->debug('ugc积分checkSamePostId 存在.user_id：'.$user_id.'|post_id:'.$post_id.'|journal_type:'.$journal_type);
                return false;
            }
            else{
                app('log')->debug('ugc积分checkSamePostId 不存在.user_id：'.$user_id.'|post_id:'.$post_id.'|journal_type:'.$journal_type);
                return true;
            }
        }
       
    }

    function allJournalType($key=""){
        //20 ugc_post_create 发布笔记
        //21 ugc_post_like 笔记点赞 
        //22 ugc_post_comment 评论笔记
        //23 ugc_post_favorite 收藏笔记  
        //24 ugc_post_share 分享笔记 
        //44 拒绝笔记
        /* 
        {"point_enable":"1","point_max_day":"6","point_post_like_get_once":"1","point_post_like_get_max_times_day":"5","point_post_share_get_once":"3","point_post_share_get_max_times_day":"3","point_post_comment_get_once":"2","point_post_comment_get_max_times_day":"4","point_post_favorite_get_once":"4","point_post_favorite_get_max_times_day":"2","point_post_create_get_once":"5","point_post_create_get_max_times_day":"1"} */
        $ret= [
            '20'=>['title'=>'发布笔记','once'=>'point_post_create_get_once','max'=>'point_post_create_get_max_times_day'],

            '21'=>['title'=>'笔记点赞','once'=>'point_post_like_get_once','max'=>'point_post_like_get_max_times_day'],

            '22'=>['title'=>'评论笔记','once'=>'point_post_comment_get_once','max'=>'point_post_comment_get_max_times_day'],

            '23'=>['title'=>'收藏笔记','once'=>'point_post_favorite_get_once','max'=>'point_post_favorite_get_max_times_day'],

            '24'=>['title'=>'分享笔记','once'=>'point_post_share_get_once','max'=>'point_post_share_get_max_times_day'],

            //拒绝的反向操作
            '9920'=>['title'=>'拒绝笔记','once'=>'point.post.refuse.get_once','max'=>'point.post.refuse.get_max_times_day'],
            '9921'=>['title'=>'拒绝点赞笔记','once'=>'point.like.refuse.get_once','max'=>'point.like.refuse.get_max_times_day'],
            '9922'=>['title'=>'拒绝评论笔记','once'=>'point.comment.refuse.get_once','max'=>'point.comment.refuse.get_max_times_day'],


        ];
        if($key!=''){
            return $ret[$key];
        }
        else{
            return $ret;
        }
    }
    /**
     * 赠送的积分 function
     *
     * @param [type] $journal_type
     * @return void
     */
    function getPointByAction($company_id,$journal_type,$settingService,$add_or_reduce=""){
        $ret=$this->allJournalType($journal_type);
        app('log')->debug('ugc积分getPointByAction-allJournalType：'.'|add_or_reduce:'.$add_or_reduce.'|journal_type:'.$journal_type);

        if($ret??null){
            $title=$ret['title'];
            if($add_or_reduce && $journal_type==20){
                $title='拒绝笔记';
            }
            else if($add_or_reduce && $journal_type==22){
                $title='拒绝评论';
            }
            return [
                'title'=>$title,
                'once'=>$settingService->getSetting($company_id, $ret['once']),
                'get_max_times_day'=>$settingService->getSetting($company_id, $ret['max'])
            ];
         
        }
        else{
            return ['title'=>'','once'=>0,'get_max_times_day'=>0];
        }
    }
    /**
     * 媒体检测回调回来的数据,更新图片的审核状态+整个POST或者是否自动审核通过，2022-10-17 10:29:18
     *
     * @param [type] $data
     * @return void
     */
    function doCallbackMediaCheck($data){
        $postRepository=app('registry')->getManager('default')->getRepository(Post::class);
        app('log')->debug('1发布笔记图片的审核回调处理-doCallbackMediaCheck：'.$data['trace_id'].':');
        $existPosts=$postRepository->getLists(['trace_ids|contains'=>','.$data['trace_id'].':']);  // ,aaaabcc:false|,dededdee:true
        app('log')->debug('2发布笔记图片的审核回调处理-doCallbackMediaCheck：'.json_encode($existPosts));
        if($existPosts && $existPosts[0]){
            $existPosts=$postInfo=$existPosts[0];
            $post_id   =  $existPosts['post_id'];
            app('log')->debug('3发布笔记图片的审核回调处理-doCallbackMediaCheck-post_id)：'.($post_id));
            $trace_ids =  $existPosts['trace_ids'];
            app('log')->debug('4发布笔记图片的审核回调处理-doCallbackMediaCheck-trace_ids：'.($trace_ids));
            //拆分trace_ids; ,aaa:false|
            $trace_ids_rs = explode('|',$trace_ids);
            app('log')->debug('5发布笔记图片的审核回调处理-doCallbackMediaCheck-trace_ids分割后数组：'.json_encode($trace_ids));
            //$all_trace_id_one_result=[];
            $i=0;
            foreach($trace_ids_rs as $k=>$v){
                $trace_id_prefix_rs = explode(':',$v);
                app('log')->debug('6发布笔记图片的审核回调处理-doCallbackMediaCheck-trace_id_prefix_rs-冒号前的部分，分割后数组：'.json_encode($trace_id_prefix_rs));
                $trace_id_one=ltrim($trace_id_prefix_rs[0],',');
                app('log')->debug('7发布笔记图片的审核回调处理-doCallbackMediaCheck-trace_id_prefix_rs-得到真正的trace_id'.json_encode($trace_id_one));
                $trace_id_one_result=$trace_id_prefix_rs[1];//false || true
                app('log')->debug('8发布笔记图片的审核回调处理-doCallbackMediaCheck-当前trace_id的审核值'.json_encode($trace_id_one_result));
                //$all_trace_id_one_result[]=$trace_id_one_result;
                if($trace_id_one==$data['trace_id']){
                    app('log')->debug('9发布笔记图片的审核回调处理-doCallbackMediaCheck-trace_id_one匹配'.$data['trace_id']);
                    if($data['result']??null){
                        if($data['result']['suggest']=='pass'){
                            app('log')->debug('10发布笔记图片的审核回调处理-doCallbackMediaCheck-审核结果正确：'.$data['trace_id']);
                            //再拼接回去
                            $trace_ids_rs[$k]=','.$trace_id_one.':true';
                            app('log')->debug('11发布笔记图片的审核回调处理-doCallbackMediaCheck-再拼接回去的更新值：'.$trace_ids_rs[$k]);

                            $trace_id_one_result='true';
                        }
                    }
                }
                if($trace_id_one_result=='true'){
                    $i++;
                }                
            }
            $new_trace_ids=implode('|',$trace_ids_rs);

            app('log')->debug('12发布笔记图片的审核回调处理-doCallbackMediaCheck-最终心的trace_id：'.$new_trace_ids);

            //更新trace_ids的状态
            $updateData['trace_ids']= $new_trace_ids;
            $image_status=$existPosts['image_status'];
            app('log')->debug('12发布笔记图片的审核回调处理-doCallbackMediaCheck-原先的image_status'.$image_status);

            $title_status=$existPosts['title_status'];
            app('log')->debug('13发布笔记图片的审核回调处理-doCallbackMediaCheck-原先的title_status'.$title_status);

            $content_status=$existPosts['content_status'];

            app('log')->debug('14发布笔记图片的审核回调处理-doCallbackMediaCheck-原先的content_status'.$content_status);

            if($i==count($trace_ids_rs)){
                //数量一致，图片全部通过 2022-10-17 10:34:38
                $image_status=1;
                app('log')->debug('15发布笔记图片的审核回调处理-doCallbackMediaCheck-图片全部通过审核'.$image_status);

            }
            if($title_status==1 && $content_status==1 && $image_status==1){
                //3个状态都是通过
                app('log')->debug('15发布笔记图片的审核回调处理-doCallbackMediaCheck-3个都通过status变成1');

                $updateData['status']=1;
            }
            app('log')->debug('16发布笔记图片的审核回调处理-doCallbackMediaCheck-更新一下update'.json_encode($updateData));

            if(isset($updateData['status']) && $updateData['status']==1 && $postInfo['is_draft']==0){
         
            //20送积分给发布笔记
            try{
                 app('log')->debug('addUgcPoint 通过笔记-内容检测回调 发送积分开始:post_id:'.$postInfo['post_id'].'|params'.var_export($postInfo,true));
                 //
                 $this->addUgcPoint($postInfo['post_id'],$postInfo['user_id'], $postInfo['company_id'],20);
            }
            catch(\Exception $e){
                app('log')->debug('addUgcPoint 通过笔记-内容检测回调 发送积分失败:post_id:'.$postInfo['post_id'].'|params'.var_export($postInfo,true)."|失败原因:".$e->getMessage());
            }
        }
            return $this->saveData($updateData,['post_id'=>$post_id]);
        }  
        else{
            app('log')->debug('2.2发布笔记图片的审核回调处理-doCallbackMediaCheck-查询不到匹配的trace_ids：'.json_encode($existPosts));
        }
    }
}
