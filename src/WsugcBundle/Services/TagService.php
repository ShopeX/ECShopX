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

use WsugcBundle\Entities\Tag;
use WsugcBundle\Entities\Post;
use CompanysBundle\Services\CompanysService;
use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\MemberService;

class TagService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Tag::class);

    }

    public function saveData($params, $filter=[])
    {
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

    public function getTagList($filter,$cols,  $page = 1, $pageSize = -1, $orderBy=[],$fromAdmin=false)
    {
        if(!$orderBy){
            //按排序，小的在前。
            $orderBy=[
                'p_order'=>'asc',                'created' => 'desc',
            ];
        }
        $lists = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
        if (!($lists['list'] ?? [])) {
            return [];
        }
        $wechatUserService = new WechatUserService();
        foreach ($lists['list'] as &$v) {
            $v=$this->formatDetail($v,false,$wechatUserService,$fromAdmin);
            ksort($v);
        }
        if($v??null){
            unset($v);
            //防止&引用影响到下面的循环
        }
        if($filter['tag_name|contains']??null){
            //有名称搜索，一模一样的排第一位
            $allRank=[];
            foreach($lists['list'] as $k=>$v){
                if($v['tag_name']==$filter['tag_name|contains']){
                    $lists['list'][$k]['rank']=-1;
                    $allRank[]=-1;

                }
                else{
                    $lists['list'][$k]['rank']=$v['tag_id'];
                    $allRank[]=$v['tag_id'];

                }
            }
            if($allRank){
                array_multisort($allRank,SORT_ASC,$lists['list']);
            }
        }
        return $lists;
    }
    /**
     * [getTagDetail 图片标签详情]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getTagDetail($filter,$user_id="",$fromAdmin=false){
        $tagInfo=$this->getInfo($filter);
        if($tagInfo){
            $wechatUserService = new WechatUserService();
            $tagInfo=$this->formatDetail($tagInfo,true,$wechatUserService,$fromAdmin);
        }
        ksort($tagInfo);
        return $tagInfo;
    }
    /**
     * [formatDetail 格式化标签数据]
     * @Author   sksk
     * @DateTime 2021-07-14T10:14:36+0800
     * @param    [type]                   $v [description]
     * @return   [type]                      [description]
     */
    function formatDetail($v,$fromdetail=false,$wechatUserService=null,$fromAdmin=false){
        $v['created_text'] = date('Y-m-d H:i:s', $v['created']);
        $v['updated_text'] = date('Y-m-d H:i:s', $v['created']);
        if(($v['manual_verify_time']??null) && $v['manual_verify_time']>0){
            $v['manual_verify_time'] = date('Y-m-d H:i:s', $v['manual_verify_time']);
        }
        $v['status']=$this->getTagStatusReal($v);//真正的status
        $v['status_text']=$this->getTagStatusText($v['status']);//真正的status
        //视频完整路径2022-06-01 10:14:58
        if( $v['user_id']??null){
            $filter = ['user_id' => $v['user_id'], 'company_id' => $v['company_id']];
            $v['userInfo'] = $wechatUserService->getUserInfo($filter);
            $this->memberService=new MemberService();
            $memberInfo = $this->memberService->getMemberInfo($filter);
            if($memberInfo){
                $v['userInfo']=array_merge( $memberInfo,$v['userInfo']);
                if($fromAdmin){
                    $allow_keys_user=['username','avatar','headimgurl','nickname','user_id','mobile'];
                }
                else{
                    $allow_keys_user=['username','avatar','headimgurl','nickname','user_id'];
                }
                foreach($v['userInfo'] as $km=>$vm){
                    if(!in_array($km,$allow_keys_user)){
                        unset($v['userInfo'][$km]);
                    }
                }
            }
        }
        if($fromdetail){
           
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
}
?>
