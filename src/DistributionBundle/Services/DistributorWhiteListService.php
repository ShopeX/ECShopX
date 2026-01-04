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

namespace DistributionBundle\Services;

use AliBundle\Entities\AliMiniAppSetting;
use CompanysBundle\Services\SettingService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Entities\DistributorWhiteList;
use DistributionBundle\Repositories\DistributorRepository;
use DistributionBundle\Repositories\DistributorWhiteListRepository;
use MembersBundle\Entities\Members;
use MembersBundle\Repositories\MembersRepository;

class DistributorWhiteListService
{
    /**
     * @var $distributorWhiteListRepository DistributorWhiteListRepository
     */
    private $distributorWhiteListRepository;
    /**
     * @var $distributorRepository DistributorRepository
     */
    private $distributorRepository;

    public function __construct()
    {
        $this->distributorWhiteListRepository = app('registry')->getManager('default')->getRepository(DistributorWhiteList::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);

    }

    public function getWhiteList(array $filter, int $page = 1, int $pageSize = 20, array $orderBy = [])
    {
        if(!empty($filter['shop_code'])){
            $disInfo = $this->distributorRepository->getInfo(['shop_code'=>$filter['shop_code']]);
            $filter['distributor_id'] = $disInfo['distributor_id'];
            unset($filter['distributor_id']);
        }
        if(!empty($filter['distributor_id'])){
            if(!is_array($filter['distributor_id'])){
                $filter['distributor_id'] = [$filter['distributor_id']];
            }
        }

        $conn = app('registry')->getConnection('default');
        $sql = 'select id,company_id,mobile,username,created,updated from distribution_distributor_white_list ';
        $whereSql = " where 1";
        if(!empty($filter['distributor_id'])){
            $idArrStr = implode(",",$filter['distributor_id']);
            $whereSql .= ' and distributor_id in (' . $idArrStr.') ';
        }
        if(!empty($filter['mobile'])){
            if(is_array($filter['mobile'])){
                $mobileArrStr = implode("','",$filter['mobile']);
                $whereSql .= ' and mobile in ('."'" . $mobileArrStr."'".') ';
            }else{
                $whereSql .= ' and mobile = ' ."'". $filter['mobile']."'";
            }

        }
        if(!empty($filter['company_id'])){
            $whereSql .= ' and company_id = ' . $filter['company_id'];
        }
        if(!empty($filter['username'])){
            $whereSql .= ' and username like ' . "'". $filter['username']."%'";
        }
        $start_limit = ($page - 1) * $pageSize;
        $sql .= $whereSql . " group by mobile order by created desc limit {$pageSize}  offset {$start_limit}";
        $list = $conn->fetchAll($sql);
        $countSql = 'select count(distinct mobile) from distribution_distributor_white_list '.$whereSql;
        $count = $conn->fetchColumn($countSql);
        if(empty($list)){
            return ['list'=>[],'total_count'=>$count];
        }
        $totalMobile = array_column($list,'mobile');
        $tmpList = $this->distributorWhiteListRepository->lists(['mobile'=>$totalMobile], '*', 1, -1);
        $tmpProbe = [];
        foreach ($tmpList['list']  as $vP){
            $tmpProbe[$vP['mobile']][] = $vP['distributor_id'];
        }


        foreach ($list as $ri => $rp){
            $mobile = $rp['mobile'];
            $distributorArr = $tmpProbe[$mobile];
            $disList = $this->distributorRepository->getLists(['distributor_id' => $distributorArr],'name,shop_code,distributor_id',1,-1);
            $list[$ri]['distributor_info'] = $disList;
        }

        return ['list'=>$list,'total_count'=>$count];

//        $list = $this->distributorWhiteListRepository->lists($filter, '*', $page, $pageSize);
//        $distributorId = array_column($list, 'distributor_id');
//        $distributorId = array_unique($distributorId);
//        $disList = $this->distributorRepository->getLists(['distributor_id' => $distributorId],'*',1,-1);
//        $disList = array_column($disList,null,'distributor_id');
//        foreach ($list['list'] as $key => $item) {
//            $disTmpId = $item['distributor_id'];
//            if(empty($disList[$disTmpId])){
//                continue;
//            }
//            $list['list'][$key]['name'] = $disList[$disTmpId]['name'];
//            $list['list'][$key]['shop_code'] = $disList[$disTmpId]['shop_code'];
//        }
//        return $list;
    }

    public function addWhiteList(array $data)
    {
        if(isset($data['id'])){
            $id = $data['id'];
            unset($data['id']);
            //判断是否存在
            $exit = $this->distributorWhiteListRepository->getInfo(['id'=>$id]);
            $this->distributorWhiteListRepository->deleteBy(['mobile'=>$exit['mobile']]);
        }
        foreach ($data['distributor_id'] as $v){
            $tmp = $data;
            $tmp['distributor_id'] = $v;
            $ret = $this->distributorWhiteListRepository->create($tmp);
        }

        return $ret ?? [];
    }

    public function deleteOneWhiteList(array $whiteId, int $distributorId = 0)
    {

        $exit = $this->distributorWhiteListRepository->lists(['id'=>$whiteId],'*',1,-1);
        foreach ($exit['list'] as $v){
            $filter =['mobile'=>$v['mobile']];
            if(!empty($distributorId)){
                $filter['distributor_id'] = $distributorId;
            }
            $this->distributorWhiteListRepository->deleteBy($filter);
        }
//        $filter = ['id' => $whiteId];

    }

    public function deleteByDistributorId(array $distributorId)
    {
        $filter = ['distributor_id' => $distributorId];
        $this->distributorWhiteListRepository->deleteBy($filter);
    }

    public function getUserWhiteListDistributor(int $userId,int $companyId)
    {
        $mobile = $this->getMobileByUserId($companyId,$userId);
        $list = $this->distributorWhiteListRepository->getLists(['mobile'=>$mobile],'*',1,-1);
        if(empty($list)){
            return [];
        }
        return array_column($list,null,'distributor_id');

    }

    public function getUserWhiteListDistributorList(int $userId,int $companyId)
    {
        $mobile = $this->getMobileByUserId($companyId,$userId);
        $list = $this->distributorWhiteListRepository->getLists(['mobile'=>$mobile],'*',1,-1);
        if(empty($list)){
            return [];
        }
        $distributor_id =  array_column($list,null,'distributor_id');
        $distributorList = $this->distributorRepository->getLists(['distributor_id' => array_keys($distributor_id)], '*', 1, -1);
         if(empty($distributorList)){
            return [];
        }

        return $distributorList;
    }

    public function checkUserValid(int $distributorId, int $userId, int $companyId)
    {
        //看当前店铺开启没开启白名单
        $distributorInfo = $this->distributorRepository->getInfo(['distributor_id'=>$distributorId]);
        if(empty($distributorInfo)){
            return  false;
        }
        if((int)$distributorInfo['open_divided'] === 0){
            return true;
        }
        $mobile = $this->getMobileByUserId($companyId, $userId);
        $whiteInfo = $this->distributorWhiteListRepository->getInfo(['distributor_id' => $distributorId, 'mobile' => $mobile]);
        if (empty($whiteInfo)) {
            return false;
        }
        return true;
    }

    public function checkUserValidCommon(int $distributorId, int $userId, int $companyId)
    {
        $set = (new SettingService())->getOpenDistributorDivided($companyId);
        if($set['status'] === true){
            return $this->checkUserValid($distributorId, $userId, $companyId);
        }
        return true;

    }

    private function getMobileByUserId(int $companyId, int $userId)
    {
        /**
         * @var $membersRepository MembersRepository
         */
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $userInfo = $membersRepository->getMobileByUserIds($companyId, [$userId]);
        return $userInfo[$userId];
    }

    public function getWhiteListDistributor(int $userId,int $companyId,int $distributorId = 0,$lng = 0, $lat = 0, $showType = '')
    {
        $mobile = $this->getMobileByUserId($companyId, $userId);
        $list = $this->distributorWhiteListRepository->getLists(['mobile'=>$mobile],'*',1,-1,['created'=>'desc']);
        if(empty($list)){
            return [];
        }
        $distributorService = new DistributorService();
        $distributorIdList = array_column($list,'distributor_id');
        if(!empty($distributorId)){
            if(!in_array($distributorId, $distributorIdList)){
                return  [];
            }
            return $distributorService->getInfo(['distributor_id' => $distributorId,'open_divided'=>1]);
        }
        if($lng && $lat){
            if(!empty($distributorId)){
                return $distributorService->getNearShopData(['company_id' => $companyId,'distributor_id' => $distributorId,'open_divided'=>1], $lat, $lng);
            }else{
                $tmpIData = $distributorService->getNearShopData(['company_id' => $companyId,'distributor_id' => $distributorIdList,'open_divided'=>1], $lat, $lng);
                if(!empty($tmpIData['real_default'])){
                    return [];
                }
            }

        }
        if($distributorId === 0){
            $first = $list[0];
//            $distributorId = $first['distributor_id'];
            $dataTmp = $distributorService->lists(['distributor_id' => $distributorIdList,'open_divided'=>1],['created'=>'desc'],2);
            if(empty($dataTmp['list'])){
                return [];
            }
            return  $dataTmp['list'][0];

        }
        return [];
    }

}
