<?php
namespace KujialeBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\ValidationHttpException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

use App\Http\Controllers\Controller as Controller;
use KujialeBundle\Services\KujialeDesignerTagsService;
use KujialeBundle\Services\KujialeDesignerWorksLikeService;
use KujialeBundle\Services\KujialeDesignerWorksService;
use KujialeBundle\Services\KujialeDesignerGoodsRelService;
use KujialeBundle\Services\KujialeDesignerGoodsService;
use KujialeBundle\Services\KujialeDesignerWorksPicService;
use GoodsBundle\Services\ItemsService;
use KujialeBundle\Services\api\ApiService;

class KuController extends Controller
{
    /**
     * @var KujialeDesignerWorksService
     */
    protected $desginService;

    /**
     * @var KujialeDesignerTagsService
     */
    protected $tagsService;

    /**
     * @var KujialeDesignerWorksLikeService
     */
    protected $likeService;

    public  function __construct(){
        $this->desginService = new KujialeDesignerWorksService();
        $this->tagsService = new KujialeDesignerTagsService();
        $this->likeService = new KujialeDesignerWorksLikeService();
    }

    public function desginWorkList(Request $request){

        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);

        $filter = [];
        
        $keyword = $request->input('keywords');
        if(isset($keyword) && !empty($keyword)){
            $filter['keyword'] = $keyword;
        }

        $tags = $request->input('tags_params');
        if(isset($tags) && !empty($tags)){
            if(!is_array($tags)){
                throw new ResourceException('参数无效');
            }
            $filter['tags'] = $tags;
        }
        $cityIds = $request->input('city_id');
        if(isset($cityIds) && !empty($cityIds)){
            // if(!is_array($cityIds)){
            //     throw new ResourceException('参数无效');
            // }
            $filter['cityIds'] = $cityIds;
        }

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 50);
        $sort = $request->input('sort', '');
        $filter['page'] = $page;
        $filter['pageSize'] = $limit;
        $filter['sort'] = $sort;

        //直接打接口
        // $result = (new ApiService)->getDesignerWorks($filter);
        $result = $this->desginService->getDesignerWorksList($filter,$page,$limit);
        return $this->response->array($result);
    }

    public function desginWorkDetail(Request $request){

        $validator = app('validator')->make($request->all(), [
            'design_id' => 'required|string',
            'plan_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('参数错误');
        }

        $design_id = $request->input('design_id');
        $plan_id = $request->input('plan_id');

        $filter['design_id'] = $design_id;
        $filter['plan_id'] = $plan_id;
        $authInfo = $request->get('auth');
        // $userId = $authInfo['user_id'];
        // if (!empty($userId)){
        //     $filter['user_id'] = $userId;
        // }

        $result = $this->desginService->getDesignerWorksDetail($filter);
        return $this->response->array($result);

    }

    public function getDesginTagsList(Request $request){
        $filter = ['is_disabled' => 0];
        $result = $this->tagsService->getTagsList($filter);
        // $result =(new ApiService)->getDesignerTags();
        return $this->response->array($result);
    }

    public function updateDesignViewCount(Request $request){

        $validator = app('validator')->make($request->all(), [
            'design_id' => 'required|string',
            'plan_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('参数错误');
        }

        $design_id = $request->input('design_id');
        $plan_id = $request->input('plan_id');

        $result = $this->desginService->updateViewCount($design_id,$plan_id);
        return $this->response->array($result);
    }

    public function updateDesignLikeCount(Request $request){

        $authInfo = $request->get('auth');
        $userId = $authInfo['user_id'];
        //$userId = 20;

        $validator = app('validator')->make($request->all(), [
            'design_id' => 'required|string',
            'plan_id' => 'required|string',
            'is_like' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('参数错误');
        }

        $design_id = $request->input('design_id');
        $plan_id = $request->input('plan_id');
        $isLike = $request->input('is_like');

        $this->likeService->saveLike($design_id,$plan_id,$userId, $isLike == 'true' ? 'like' : 'unlike');
        return $this->response->array(['status' => true]);
    }

    public function getProductListByPicId(Request $request) {
        $pic_id = $request->input('pic_id');
        $company_id = $request->input('company_id');
        $authInfo = $request->get('auth');

        $kujialeDesignerGoodsRelService = new KujialeDesignerGoodsRelService();
        $kujialeDesignerGoodsService = new KujialeDesignerGoodsService();
        $kujialeDesignerWorksPicService = new KujialeDesignerWorksPicService();

        $filter['pic_id'] = $pic_id;
        $goods_rel = $kujialeDesignerGoodsRelService->lists($filter);
        $goods = [];
        $limitItemIds = [];
        $itemsService = new ItemsService();
        if (!empty($goods_rel['list'])){
            foreach ($goods_rel['list'] as $rel){
                $filter_goods['good_id'] = $rel['obs_brand_good_id'];
                $tempGoods = $kujialeDesignerGoodsService->lists($filter_goods);
                if ($tempGoods['total_count'] > 0){
                    foreach ($tempGoods['list'] as $item){
                        $tempItemInfo = $itemsService->getInfo(['item_bn'=>$item['brand_good_code'], 'audit_status'=>'approved', 'company_id'=>$company_id]);
                        if (empty($tempItemInfo) || !$item['brand_good_code'] ) {
                            continue;
                        }
                        $item_id = $tempItemInfo['item_id'];
                        $goods[] = $itemsService->getItemsDetail($item_id, '', $limitItemIds, $company_id);
                    }
                }
            }
        }
        $picInfo = $kujialeDesignerWorksPicService->getInfo(['pic_id' => $pic_id]);

        $kujiale_pano53 = "pano53.p.kujiale.com";
        $kujiale_www = "www.kujiale.com";
        if(!empty($picInfo['pano_link'])){
            $picInfo['pano_link'] = str_replace($kujiale_www, $kujiale_pano53, $picInfo['pano_link']);
        }
        $result = [
            'goods_list' => $goods,
            'pic_info' => $picInfo
        ];

        return $this->response->array($result);
    }

    public function getDesignerPicById(Request $request) {
        $pic_id = $request->input('pic_id');
        $kujialeDesignerWorksPicService = new KujialeDesignerWorksPicService();
        $picInfo = $kujialeDesignerWorksPicService->getInfo(['pic_id' => $pic_id]);

        return $this->response->array($picInfo);
    }

    /**
     * 获取城市列表
     * 转调酷家乐API获取城市数据
     * 
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function getLocationList(Request $request)
    {
        $apiUrl = 'https://qhstatic-cos.kujiale.com/openapi/cities.json';
        
        try {
            $client = new Client([
                'timeout' => 10,
                'verify' => false
            ]);
            
            $response = $client->request('GET', $apiUrl);
            
            if ($response->getStatusCode() == 200) {
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->response->array($data);
                } else {
                    throw new ResourceException('解析JSON数据失败');
                }
            } else {
                throw new ResourceException('获取城市列表失败');
            }
        } catch (GuzzleException $e) {
            app('log')->error('获取城市列表API失败: ' . $e->getMessage());
            throw new ResourceException('获取城市列表失败: ' . $e->getMessage());
        } catch (\Exception $e) {
            app('log')->error('获取城市列表失败: ' . $e->getMessage());
            throw new ResourceException('获取城市列表失败');
        }
    }

}
