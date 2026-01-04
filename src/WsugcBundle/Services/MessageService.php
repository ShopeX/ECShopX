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

use WsugcBundle\Entities\Message;
use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\MemberService;
use WsugcBundle\Services\CommentService;

class MessageService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Message::class);

    }
    /**发送消息 */
    public function sendMessage($params){
        return $this->saveData($params);
    }
    public function saveData($params, $filter=[])
    {
        // Powered by ShopEx EcShopX
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            //print_r($params);exit;
            $result = $this->entityRepository->create($params);
        }
        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function getMessageList($filter,$cols,  $page = 1, $pageSize = -1, $orderBy=[],$fromAdmin=false)
    {
        if(!$orderBy){
            //按排序，小的在前。
            $orderBy=[
                'created' => 'desc',
            ];
        }
        $lists = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
        $wechatUserService = new WechatUserService();
        foreach ($lists['list'] as &$v) {
            $v=$this->formatDetail($v,false,$wechatUserService,$fromAdmin,$filter);
            ksort($v);
        }
        if($v??null){
            unset($v);
            //防止&引用影响到下面的循环
        }
        return $lists;
    }
    /**
     * [getActivityCat 分类详情]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getMessageDetail($filter,$user_id="",$fromAdmin=false){
        $messageInfo=$this->getInfo($filter);
        if($messageInfo && ($messageInfo['message_id']??null)){
            $wechatUserService = new WechatUserService();
            $messageInfo=$this->formatDetail($messageInfo,true,$wechatUserService,$fromAdmin,$filter);
        }
        ksort($messageInfo);
        return $messageInfo;
    }
      /**
     * [formatDetail 格式化标签数据]
     * @Author   sksk
     * @DateTime 2021-07-14T10:14:36+0800
     * @param    [type]                   $v [description]
     * @return   [type]                      [description]
     */
    function formatDetail($v,$fromdetail=false,$wechatUserService=null,$fromAdmin=false,$filterTop=[]){
        $v['created_text'] = date('Y-m-d H:i:s', $v['created']);
       // $v['status']=$this->getTagStatusReal($v);//真正的status
       // $v['status_text']=$this->getTagStatusText($v['status']);//真正的status
       $commentService=new CommentService();
       $followerService = new FollowerService();

        //视频完整路径2022-06-01 10:14:58
        if($v['created']??null){
            $v['created_text']=date('Y-m-d',$v['created']);
            $v['created_moment']=$commentService->formatTime($v['created']);;
        }
        if( $v['from_user_id']??null){
            $filter = ['user_id' => $v['from_user_id'], 'company_id' => $v['company_id']??1];
            $v['from_userInfo'] = $wechatUserService->getUserInfo($filter);
            $this->memberService=new MemberService();
            $memberInfo = $this->memberService->getMemberInfo($filter);
            if($memberInfo){
                $v['from_userInfo']=array_merge( $memberInfo,$v['from_userInfo']);
                $allow_keys_user=['username','avatar','headimgurl','nickname','user_id'];
                foreach($v['from_userInfo'] as $km=>$vm){
                    if(!in_array($km,$allow_keys_user)){
                        unset($v['from_userInfo'][$km]);
                    }
                }
            }
        }
        if( $v['post_id']??null){
            $filter = ['post_id' => $v['post_id'], 'company_id' => $v['company_id']??1];
            $this->postService=new PostService();
            $postInfo = $this->postService->getPostDetail($filter);
            $v['postInfo']=$postInfo;
        }
        if( $v['comment_id']??null){
            $filter = ['comment_id' => $v['comment_id'], 'company_id' => $v['company_id']??1];
            $this->commentService=new CommentService();
            $postInfo = $this->commentService->getCommentDetail($filter);
            $v['commentInfo']=$postInfo;
        }
        if($fromdetail){
           
        }
        if(($filterTop['type']??null) && $filterTop['type']=='followerUser'){
            //关注用户,互关

            $v['mutal_follow']=$followerService->getMutalFollow($v['from_user_id'],$v['to_user_id']);
        }

        if(($filterTop['type']??null) && $filterTop['type']=='system'){
            if($v['sub_type']=='refusePost'){
                //笔记被拒
                $v['title']='笔记暂不被推荐,快来修改';
                if($v['content']==''){
                    $v['content']='您的笔记包含违规内容';
                }
            }
            else if($v['sub_type']=='refuseComment'){
                //笔记被拒
                $v['title']='评论暂不被推荐';
                if($v['content']==''){
                    $v['content']='您的评论包含违规内容';
                }
            }
        }

        return $v;
    }     
    /**获得活动状态
    * @param string $activity_id
    * Author:sksk
    */
   function getTagStatusReal($activity_info=""){
       //$postService = new PostService();        
       return $activity_info['status'];
   }
   public function getTagStatusText($key="",$lang='zh'){
       //(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)
      $rs=array(
          '0'=>array('zh'=>'待审核','en'=>'Comming Soon'),
          '1'=>array('zh'=>'审核通过','en'=>'In Progress'),
          '2'=>array('zh'=>'机器拒绝','en'=>'Closed'),
          '3'=>array('zh'=>'待人工审核','en'=>'Fully Booked'),
          '4'=>array('zh'=>'人工拒绝','en'=>'Fully Booked'),
      );
      if((string)$key!=''){
          return $rs[$key][$lang];
      }
      else{
          return $rs;
      }
  }
  /**
   * 获取消息类型 function
   *
   * @param string $key
   * @return array
   */
  function getMessageType($key=""){
        $result=[
            'system'=>'系统消息',
            'reply'=>'评论笔记/回复评论',
            'like'=>'笔记点赞/评论点赞',
            'favoritePost'=> '笔记收藏',
            'followerUser'=>'关注'
        ];
        return $result;
   }     
  /**
   * 消息桌面，中控台，显示每个类型的最新消息和未读数量。
   *
   * @return void
   */
  function getDashBoard($filter){
     $filter['to_user_id']=$filter['user_id'];
     unset($filter['user_id']);
     $type=$this->getMessageType();
     $result=[];
     $commentService=new CommentService();
     foreach($type as $k=>$v){
        $filter['type']=$k;
        $unread_nums=$this->getUnreadnumsByType($filter);
        $recent_message=$this->entityRepository->lists($filter,'message_id,from_nickname,from_user_id,to_nickname,to_user_id,title,content,created,type',1,1,['created' => 'desc']);
        if($recent_message['list']??null){
            foreach($recent_message['list'] as $kmes=>$vmes){
                $recent_message['list'][$kmes]['created_text']=date('m-d',$vmes['created']);
                $recent_message['list'][$kmes]['created_moment']=$commentService->formatTime($vmes['created']);;
            }
        }
        $result[]=[
            'type'=>$k,
            'unread_nums'=>$unread_nums,
            'recent_message'=>$recent_message,
        ];
     }
     return  $result;
  }
  /**
   * 获取未读数量
   *
   * @param [type] $filter
   * @return void
   */
  function getUnreadnumsByType($filter){
        $filter['hasread']=0;
        return $this->entityRepository->count($filter);
  }
/**
   * 获取未读数量
   *
   * @param [type] $filter
   * @return void
   */
  function getUnreadnumsTotal($user_id){
    $filter['hasread']=0;
    $filter['to_user_id']=$user_id;
    return $this->entityRepository->count($filter);
}
}
