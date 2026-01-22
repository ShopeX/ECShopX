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

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Entities\DistributorWhiteList;
use DistributionBundle\Repositories\DistributorRepository;
use DistributionBundle\Repositories\DistributorWhiteListRepository;
use EspierBundle\Entities\Address;
use EspierBundle\Repositories\AddressRepository;
use GoodsBundle\Services\ItemsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;
use ThirdPartyBundle\Services\Map\MapService;

class UploadDistributor
{
    public $header = [
        '店铺类型' => 'distribution_type',
        '店铺号' => 'shop_code',
        '店铺名称' => 'name',
        '联系人姓名' => 'contact',
        '联系方式' => 'contract_phone',
        '店铺所在省市区' => 'addr1',
        '店铺详细地址' => 'addr2',
        '店铺详细地址门牌号' => 'addr3',
        '经营开始时间' => 'hour1',
        '经营结束时间' => 'hour2',
        '开启快递配送' => 'is_delivery',
        '自动同步商品' => 'auto_sync_goods',
        '店铺LOGO' => 'logo',
        '店铺背景' => 'banner',
        '旺店通ERP店铺号' => 'wdt_shop_no',
        '聚水潭店铺编号' => 'jst_shop_id'
    ];

    public $headerInfo = [

        '店铺类型' => ['size' => 255, 'remarks' => '店铺类型,0:自营', 'is_need' => true],
        '店铺号' => ['size' => 255, 'remarks' => '店铺号，仅可填写英文数字的组合，英文不区分大小写', 'is_need' => true],
        '店铺名称' => ['size' => 255, 'remarks' => '店铺名称', 'is_need' => true],
        '联系人姓名' => ['size' => 255, 'remarks' => '联系人姓名', 'is_need' => true],
        '联系方式' => ['size' => 255, 'remarks' => '联系方式', 'is_need' => true],
        '店铺所在省市区' => ['size' => 255, 'remarks' => '店铺所在省市区，省市区以英文逗号分隔，仅接受3个层级，位置1省，位置2市，位置3区', 'is_need' => true],
        '店铺详细地址' => ['size' => 255, 'remarks' => '店铺详细地址', 'is_need' => true],
        '店铺详细地址门牌号' => ['size' => 255, 'remarks' => '店铺详细地址门牌号', 'is_need' => false],
        '经营开始时间' => ['size' => 255, 'remarks' => '经营开始时间，以0点开始，半个小时一分隔,excel文本类型', 'is_need' => false],
        '经营结束时间' => ['size' => 255, 'remarks' => '经营结束时间，以0点开始，半个小时一分隔，结束营业时间需小于开始时间,excel文本类型', 'is_need' => false],
        '开启快递配送' => ['size' => 255, 'remarks' => '开启快递配送，是或否', 'is_need' => true],
        '自动同步商品' => ['size' => 255, 'remarks' => '自动同步商品，是或否', 'is_need' => true],
        '店铺LOGO' => ['size' => 255, 'remarks' => '店铺LOGO', 'is_need' => false],
        '店铺背景' => ['size' => 255, 'remarks' => '店铺背景', 'is_need' => false],
        '旺店通ERP店铺号' => ['size' => 255, 'remarks' => '旺店通ERP店铺号', 'is_need' => false],
        '聚水潭店铺编号' => ['size' => 255, 'remarks' => '聚水潭店铺编号', 'is_need' => false]

    ];

    public $isNeedCols = [
        '店铺类型' => 'distribution_type',
        '店铺号' => 'shop_code',
        '联系人姓名' => 'contact',
        '联系方式' => 'contract_phone',
        '店铺所在省市区' => 'addr1',
        '店铺详细地址' => 'addr2',
        '开启快递配送' => 'is_delivery',
        '自动同步商品' => 'auto_sync_goods',
        '店铺名称' => 'name',
    ];

    /**
     * 验证上传的白名单
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException(trans('DistributionBundle/Services/UploadDistributor.excel_format_only'));
        }
    }

    public $tmpTarget = null;

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        if (env('DISK_DRIVER') == 'local') {
            //本地用这个
            $content = file_get_contents(storage_path('app/public/' . $filePath));
        } else {
            $url = $this->getFileSystem()->privateDownloadUrl($filePath);
            $client = new Client();
            $content = $client->get($url)->getBody()->getContents();
        }

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }


    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    private function _formatData($row)
    {
//        $columns = ['mobile', 'username', 'distributor_no'];
        $columns = array_values($this->header);
        $data = [];
        foreach ($row as $k => $v) {
            if (in_array($k, $columns)) {
                $data[$k] = trim($row[$k]);
            }
        }
        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $data = $this->_formatData($row);
        /**
         * @var $distributorRepository DistributorRepository
         */
        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        /**
         * @var $addressRepository AddressRepository
         */
        $addressRepository = app('registry')->getManager('default')->getRepository(Address::class);


        $regions = explode(',', $data['addr1']);
        $count = count($regions);
        if ($count < 3) {
            throw new ResourceException('错误，省市区错误');
        }
        $fir = $regions[0];
        $sec = $regions[1];
        $thi = $regions[2];
        $firData = $addressRepository->getInfo(['label' => $fir]);
        if (empty($firData)) {
            throw new ResourceException('错误，省市区错误');
        }
        $secProbe = $addressRepository->lists(['parent_id' => $firData['id'], 'label' => $sec], 1, -1);
        $listSec = $secProbe['list'];
        if (empty($listSec)) {
            throw new ResourceException('错误，省市区错误');
        }
        $thiProbe = $addressRepository->lists(['parent_id' => $listSec[0]['id'], 'label' => $thi], 1, -1);
        $listThi = $thiProbe['list'];
        if (empty($listThi)) {
            throw new ResourceException('错误，省市区错误');
        }

        $insertData = [
            'company_id' => $companyId,
            'province' => $fir,
            'city' => $sec,
            'area' => $thi,
            'is_dada' => 1,
            'regions_id' => json_encode([$firData['id'], $listSec[0]['id'], $listThi[0]['id']]),
            'regions' => json_encode([$fir, $sec, $thi]),
            'contact' => $data['contact'] ?? '',
            'mobile' => $data['contract_phone'],
            'contract_phone' => $data['contract_phone'],
            'name' => $data['name'],
            'shop_code' => $data['shop_code'],
            'house_number'=>$data['addr3'],
            'dada_shop_create'=>0,
            'shansong_shop_create'=>0,
            'address' => $data['addr2'] ?? '',

        ];
        if (!empty($data['hour1']) && !empty($data['hour2'])) {
            // 验证时间格式，必须是 HH:MM 格式（如 02:00）
            $hour1 = trim($data['hour1']);
            $hour2 = trim($data['hour2']);
            
            // 验证格式：必须是 HH:MM，且只能有一个冒号
            if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $hour1)) {
                throw new ResourceException('错误，经营开始时间格式错误');
            }
            if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $hour2)) {
                throw new ResourceException('错误，经营结束时间格式错误');
            }
            
            $insertData['hour'] = $hour1 . '-' . $hour2;
        }

        if ($data['is_delivery'] === '是') {
            $insertData['is_delivery'] = 1;
        } else {
            $insertData['is_delivery'] = 0;
        }

        if($data['auto_sync_goods'] === '是'){
            $insertData['auto_sync_goods'] =1;
        }else{
            $insertData['auto_sync_goods'] =0;
        }
        if(!empty($data['logo'])){
            $insertData['logo'] = $data['logo'];
        }

        if(!empty($data['banner'])){
            $insertData['banner'] = $data['banner'];
        }
        if(!empty($data['wdt_shop_no'])){
            $insertData['wdt_shop_no'] = $data['wdt_shop_no'];
        }
        if(!empty($data['jst_shop_id'])){
            $insertData['jst_shop_id'] = $data['jst_shop_id'];
        }

        // 拼接完整地址，与getAreaByAddress API保持一致
        // 如果城市和省相同（如直辖市），只保留一个
        if ($fir === $sec) {
            $fullAddress = $sec . $thi . ($data['addr2'] ?? '');
        } else {
            $fullAddress = $fir . $sec . $thi . ($data['addr2'] ?? '');
        }
        
        // region参数只传城市名（去除"市"、"省"等后缀），用于限制搜索范围
        // 腾讯地图API要求region只传城市名，如"上海"，不能传"上海市"或"上海市上海市徐汇区"
        // 如果城市和省相同，使用省名；否则使用城市名
        $regionName = ($fir === $sec) ? $fir : $sec;
        $cityName = str_replace(['市', '省', '自治区', '特别行政区'], '', $regionName);
        
        // 使用完整地址获取经纬度，region参数传入城市名用于限制搜索范围
        $mapData = MapService::make($companyId)->getLatAndLng($cityName, $fullAddress);
        app('log')->info('uploadDistributor::region::'.$cityName.'::fullAddress::'.$fullAddress.'::mapData:'.json_encode([
            'lat' => $mapData->getLat(),
            'lng' => $mapData->getLng()
        ]));
        $insertData["lat"] = $mapData->getLat(); // 经度
        $insertData["lng"] = $mapData->getLng(); // 纬度
        $distributorData = $distributorRepository->getInfo(['shop_code' => $data['shop_code']]);
        if (!empty($distributorData)) {
            unset($insertData['shop_code']);
            $distributorRepository->updateBy(['shop_code' => $data['shop_code']], $insertData);
//            throw new ResourceException('错误，店铺号已经存在');
        }else{
            $distributorRepository->create($insertData);
        }

    }

}
