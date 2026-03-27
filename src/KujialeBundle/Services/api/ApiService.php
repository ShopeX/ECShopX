<?php

namespace KujialeBundle\Services\api;

use GuzzleHttp\Client;
use KujialeBundle\Traits\AbstractApiTraits;

class ApiService
{
    use AbstractApiTraits;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var array
     */
    private $apiParams = [];

    /**
     * @var string
     */
    private $appKey;

    /**
     * @var string
     */
    private $appSecret;

    /**
     * @var string
     */
    private $appUid;

    /**
     * @var Client
     */
    private $client;

    /**
     * 获取商品类目
     * @param $index
     * @return bool|mixed
     */
    public function getCat($index)
    {
        $apiUrl = config('kujiale.apiUrl.cat');
        return $this->getData(['index'=>$index],$apiUrl,'get');
    }

    /**
     * 获取商品标签列表
     * @return bool|mixed
     */
    public function getTag()
    {
        $apiUrl = config('kujiale.apiUrl.tag');
        return $this->getData([],$apiUrl,'get');
    }

    /**
     * 商品搜索
     * @return bool|mixed
     */
    public function search($params)
    {
        $apiUrl = config('kujiale.apiUrl.search');
        return $this->getData($params,$apiUrl,'post');
    }

    /**
     * 获取品牌
     * @return bool|mixed
     */
    public function getBrand()
    {
        $apiUrl = config('kujiale.apiUrl.brand');
        return $this->getData([],$apiUrl,'get');
    }

    /**
     * 获取商品详情
     * @param $obsbgid
     * @return bool|mixed
     */
    public function getDetail($obsbgid)
    {
        $apiUrl = config('kujiale.apiUrl.detail');
        return $this->getData(['obsbgid'=>$obsbgid],$apiUrl,'get');
    }

    /**
     * 获取设计师方案作品
     * @param int $start 分页查询起始位置，从0开始。与页数关系：start=(page-1)*num
     * @param int $num 分页窗口大小，最大50
     */
    public function getDesignerWorks($start, $num){
        $apiUrl = config('kujiale.apiUrl.designer_works');
        return $this->getData(['start' => $start, 'num' => $num], $apiUrl, 'get');
    }

    /**
     * 获取设计师方案作品详情
     * @param $designId
     * @return bool|mixed
     */
    public function getDesignerWorksDetail($designId){
        $apiUrl = config('kujiale.apiUrl.designer_works_detail');
        $apiUrl = str_replace('{designId}',$designId,$apiUrl);
        return $this->getData(['designId' => $designId],$apiUrl,'get');
    }

    /**
     * 获取指定渲染图所在空间商品id
     * @param $designId
     * @return bool|mixed
     */
    public function getDesignerWorksRoomBrandGoods($picId){
        $apiUrl = config('kujiale.apiUrl.designer_room_brand_goods');
        $apiUrl = str_replace('{picId}',$picId,$apiUrl);
        return $this->getData([],$apiUrl,'get');
    }

    /**
     * 获取设计师方案作品绑定的标签类型
     * @param $designId
     * @return bool|mixed
     */
    public function getDesignerWorksRelTag($designId){
        $apiUrl = config('kujiale.apiUrl.designer_works_tag');
        return $this->getData(['design_id' => $designId , 'appuid' => config('kujiale.appUid')],$apiUrl,'get');
    }

    public function getDesignerWorksPicList($designId, $start = 0, $num = 50){
        $apiUrl = config('kujiale.apiUrl.designer_works_pic');
        return $this->getData(['design_id' => $designId , 'start' => $start, 'num' => $num],$apiUrl,'get');
    }

    /**
     * 优秀方案搜索 POST designex/excellent/search
     * @param array $params 支持: page(必填), pageSize(必填), orderType(必填), tagIds, key, cityIds, searchName, communityName, appuid, nodeId, filterType
     * @return array|false 成功返回接口 d 字段，失败返回 false
     */
    public function excellentSearch(array $params)
    {
        $apiUrl = config('kujiale.apiUrl.excellent_search');
        $body = [
            'page' => (int)($params['page'] ?? 1),
            'pageSize' => (int)($params['pageSize'] ?? 20),
            'orderType' => (int)($params['orderType'] ?? 0),
        ];
        if (isset($params['tagIds']) && is_array($params['tagIds'])) {
            $body['tagIds'] = $params['tagIds'];
        }
        
        $body['filterType'] = 0;
        return $this->getData($body, $apiUrl, 'post');
    }

    /**
     * 获取方案标签列表
     * @return bool|mixed
     */
    public function getDesignerTags(){
        $apiUrl = config('kujiale.apiUrl.designer_tags_list');
        return $this->getData(['is_disabled'=>false],$apiUrl,'get');
    }

    /**
     * 绑定设计师邮箱
     * @param $params
     * @return bool|mixed
     */
    public function register($params)
    {
        try{
            $bodyParams = array_merge($params, [
                'type'  => 0,
                'maxChildrenCount'  => 0,
                'defaultPassword'   => '123456',
                'creator'   => config('kujiale.appUid'),
            ]);
            $apiUrl = config('kujiale.apiUrl.register_user');
            $this->setApiUrl($apiUrl);
            $this->setApiParams($bodyParams);
            $this->setAppUid($bodyParams['email']);
            $res = $this->post_new([],true);
            if ($res['c'] == 0 || ($res['c'] == -1 && $res['m'] == 'appuid has register or bind to a user'))
            {
                //绑定成功或已经绑定都返回成功
                return true;
            }
            return false;
        }catch (\Exception $e)
        {
            app('log')->info('获取接口数据失败'.$e->getMessage());
            return false;
        }
    }
    /**
     * 获取一次性token
     * @param $email
     * @return bool|mixed
     */

    public function accessToken($appUid, $dest)
    {
        try{
            $bodyParams = [
            ];
            $apiUrl = config('kujiale.apiUrl.access_token');
            $this->setApiUrl($apiUrl);
            $this->setApiParams($bodyParams);
            $this->setAppUid($appUid);
            $res = $this->post_new(['dest' => $dest], true);
            if ($res['c'] == 0)
            {
                if (isset($res['d'])) {
                    return $res['d'];
                }
            }
            return false;
        }catch (\Exception $e)
        {
            app('log')->info('获取接口数据失败'.$e->getMessage());
            return false;
        }
    }

    /**
     * 查找设计师账号-通过邮箱
     * @param $email
     * @return bool|mixed
     */
    public function getAccountByEmail($email)
    {
        try{
            $bodyParams = [
                'email' => $email,
                'start' => 0,
                'num'   => 1000,
            ];
            $apiUrl = config('kujiale.apiUrl.search_account');
            $this->setApiUrl($apiUrl);
            $this->setApiParams($bodyParams);
            $res = $this->post_new([],false);
            if ($res['c'] == 0)
            {
                if (isset($res['d'])) {
                    return $res['d'];
                }
            }
            return false;
        }catch (\Exception $e)
        {
            app('log')->info('获取接口数据失败'.$e->getMessage());
            return false;
        }
    }

    private function getData($params,$url,$method)
    {
        try{
            $this->setApiUrl($url);
            $this->setApiParams($params);
            $appuid = false;
            if(isset($params['appuid']) && !empty($params['appuid'])){
                $appuid = true;
            }
            if ($method == 'get') {
                $res = $this->get($appuid);
            }else{
                $res = $this->post($appuid);
            }
            if ($res) {
                return $res;
            }
            return false;
        }catch (\Exception $e)
        {
            app('log')->info('获取接口数据失败'.$e->getMessage());
            return false;
        }
    }
}