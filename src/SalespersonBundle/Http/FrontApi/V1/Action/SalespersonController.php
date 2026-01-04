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

namespace SalespersonBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use WorkWechatBundle\Entities\WorkWechatRel;
use WorkWechatBundle\Services\WorkWechatRelService;
use SalespersonBundle\Services\SignService;

class SalespersonController extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/salesperson/complaints",
     *     summary="会员发起投诉导购员",
     *     tags={"导购"},
     *     description="会员发起投诉导购员",
     *     operationId="sendSalespersonComplaints",
     *     @SWG\Parameter( in="header", type="string", required=true, name="authorization", description="jwt验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="complaints_content", description="投诉内容" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="complaints_images", description="投诉图片URL地址" ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *          @SWG\Property(
     *              property="data",
     *              ref="#/definitions/SalesPersonComplaint"
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function sendSalespersonComplaints(Request $request)
    {
        $salespersonServer = new SalespersonService();
        $authInfo = $request->get('auth');
        $request_data = $request->all('complaints_content', 'complaints_images');
        $request_data = array_map("trim", $request_data);

        if (mb_strlen($request_data['complaints_content']) > 255) {
            throw new ResourceException('投诉内容不能超过255个字符');
        }

        $rules = [
            'complaints_content' => ['required', '投诉内容不能为空'],
        ];
        $error = validator_params($request_data, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $now_time = time();
        $data = [
            'user_id' => $authInfo['user_id'],
            'user_name' => $authInfo['username'],
            'user_mobile' => $authInfo['mobile'],
            'company_id' => $authInfo['company_id'],
            'complaints_content' => $request_data['complaints_content'],
            'complaints_images' => $request_data['complaints_images'] ?? '',
            'reply_status' => 0,
            'created' => $now_time,
            'updated' => $now_time
        ];

        $filter = [
            'user_id' => $authInfo['user_id'],
            'is_bind' => 1
        ];

        $result = $salespersonServer->sendSalespersonComplaints($filter, $data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/complaintsList",
     *     summary="会员获取投诉列表",
     *     tags={"导购"},
     *     description="会员获取投诉列表",
     *     operationId="getSalespersonComplaintsList",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\Parameter( in="query", type="string", required=true, name="pageSize", description="分页大小" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="页码" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( ref="#/definitions/SalesPersonComplaint" ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getSalespersonComplaintsList(Request $request)
    {
        $salespersonServer = new SalespersonService();
        $authInfo = $request->get('auth');

        $filter = [
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id']
        ];

        $orderBy = ['created' => 'DESC'];
        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $list = $salespersonServer->getSalespersonComplaintsList($filter, $orderBy, $page, $pageSize);

        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/complaintsDetail/{id}",
     *     summary="会员获取投诉详情",
     *     tags={"导购"},
     *     description="会员获取投诉详情",
     *     operationId="getSalespersonComplaintsDetail",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\parameter( name="id", in="path", description="投诉id", type="string", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/SalesPersonComplaint"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getSalespersonComplaintsDetail(Request $request, $id)
    {
        $salespersonServer = new SalespersonService();
        $authInfo = $request->get('auth');

        $filter = [
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
            'id' => $id
        ];

        $result = $salespersonServer->getSalespersonComplaintsDetail($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson",
     *     summary="会员获取导购员信息",
     *     tags={"导购"},
     *     description="会员获取导购员信息",
     *     operationId="getSalespersonInfo",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", ref="#/definitions/SalesPersonInfo" ),
     *     )),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getSalespersonInfo(Request $request)
    {
        $salespersonService = new SalespersonService();
        $workWechatRelService = new WorkWechatRelService();
        $authInfo = $request->get('auth');

        $filter = [
            'user_id' => $authInfo['user_id'],
            'is_bind' => true,
        ];
        $workWechatRelInfo = $workWechatRelService->getInfo($filter);
        $result = [];
        $result['is_show'] = 0;
        if ($workWechatRelInfo['salesperson_id'] ?? 0) {
            $salespersonFilter = [
                'salesperson_id' => $workWechatRelInfo['salesperson_id'],
                'salesperson_type' => 'shopping_guide',
                'is_valid' => 'true',
            ];
            $result = $salespersonService->getSalespersonDetail($salespersonFilter);
            $distributorService = new DistributorService();
            $result['distributor'] = isset($result['distributor_id']) ? $distributorService->getInfo(['distributor_id' => $result['distributor_id']]) : [];
            $result['is_friend'] = $workWechatRelInfo['is_friend'];
            $result['is_show'] = $result['distributor'] ? 1 : 0;
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/nologin",
     *     summary="会员获取导购员信息",
     *     tags={"导购"},
     *     description="会员获取导购员信息",
     *     operationId="getSalespersonInfo",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\parameter( name="salesperson_id", in="query", description="导购员ID", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", ref="#/definitions/SalesPersonInfo" ),
     *     )),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getSalespersonInfoNologin(Request $request)
    {
        $salespersonId = $request->input('salesperson_id');
        if (!$salespersonId) {
            throw new ResourceException('请输入导购员id');
        }
        $salespersonService = new SalespersonService();

        $salespersonFilter = [
            'salesperson_id' => $salespersonId,
            'salesperson_type' => 'shopping_guide',
            'is_valid' => 'true',
        ];
        $result = $salespersonService->getSalespersonDetail($salespersonFilter);
        $distributorService = new DistributorService();
        $result['distributor'] = isset($result['distributor_id']) ? $distributorService->getInfo(['distributor_id' => $result['distributor_id']]) : [];

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/usersalespersonrel",
     *     summary="会员查看与导购员之间的关系",
     *     tags={"导购"},
     *     description="会员查看与导购员之间的关系",
     *     operationId="userSalespersonRelationship",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\parameter( name="salesperson_id", in="query", description="导购员id", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="is_friend", type="string", example="0", description="是否好友"),
     *                  @SWG\Property( property="is_bind", type="string", example="0", description="是否绑定"),
     *                  @SWG\Property( property="salesperson_id", type="string", example="2", description="导购员ID"),
     *                  @SWG\Property( property="name", type="string", example="candy", description="会员名"),
     *                  @SWG\Property( property="mobile", type="string", example="15300532463", description="会员手机号"),
     *                  @SWG\Property( property="created_time", type="string", example="1563778936", description="创建时间"),
     *                  @SWG\Property( property="salesperson_type", type="string", example="admin", description="导购员类型"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                  @SWG\Property( property="user_id", type="string", example="0", description="会员ID"),
     *                  @SWG\Property( property="child_count", type="string", example="0", description="导购员引入的会员数"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                  @SWG\Property( property="shop_id", type="string", example="null", description="门店id"),
     *                  @SWG\Property( property="shop_name", type="string", example="null", description="门店名称"),
     *                  @SWG\Property( property="number", type="string", example="null", description="导购员编号"),
     *                  @SWG\Property( property="friend_count", type="string", example="0", description="导购员会员好友数"),
     *                  @SWG\Property( property="avatar", type="string", example="null", description="企业微信头像"),
     *                  @SWG\Property( property="work_userid", type="string", example="null", description="企业微信userid"),
     *                  @SWG\Property( property="work_configid", type="string", example="null", description="企业微信userid"),
     *                  @SWG\Property( property="work_qrcode_configid", type="string", example="null", description="企业微信userid"),
     *                  @SWG\Property( property="role", type="string", example="null", description="导购权限集合"),
     *                  @SWG\Property( property="created", type="string", example="0", description="created"),
     *                  @SWG\Property( property="updated", type="string", example="0", description="updated"),
     *                  @SWG\Property( property="shop_ids", type="array",
     *                      @SWG\Items( type="string", example="1", description="门店id"),
     *                  ),
     *                  @SWG\Property( property="distributor_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="店铺ID"),
     *                  ),
     *                  @SWG\Property( property="store_type", type="string", example="shop", description="store_type"),
     *                  @SWG\Property( property="distributor_id", type="string", example="false", description="经销商ID"),
     *                  @SWG\Property( property="bound", type="string", example="true", description="是否绑定导购"),
     *                  @SWG\Property( property="bound_salesperson", type="string", example="94", description="绑定导购ID"),
     *          ),
     *     )),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function userSalespersonRelationship(Request $request)
    {
        $authInfo = $request->get('auth');
        $salesperson_id = $request->input('salesperson_id', 0);
        $workWechatRepositories = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        $salespersonService = new SalespersonService();

        $filter = [
            'company_id' => $authInfo['company_id'],
            'salesperson_id' => $salesperson_id,
            'user_id' => $authInfo['user_id']
        ];
        $result = $workWechatRepositories->getLists($filter, 'is_friend, is_bind', 1, 1);
        $filter = [
            'salesperson_id' => $salesperson_id,
            'company_id' => $authInfo['company_id']
        ];
        $salespersonInfo = $salespersonService->getSalespersonDetail($filter);
        if ($result) {
            $result = array_shift($result);
            $result = array_merge($result, $salespersonInfo);
        } else {
            $result = [
                'is_friend' => 0,
                'is_bind' => 0
            ];
            $result = array_merge($result, $salespersonInfo);
        }

        //查找用户已绑定的导购员
        $filter = [
            'user_id' => $authInfo['user_id'],
            'is_bind' => 1,
            'company_id' => $authInfo['company_id']
        ];
        $bound = $workWechatRepositories->getLists($filter, 'salesperson_id', 1, 1);
        if ($bound) {
            $result['bound'] = true;
            $result['bound_salesperson'] = array_shift($bound)['salesperson_id'];
        } else {
            $result['bound'] = false;
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/usersalespersonrel",
     *     summary="会员绑定与导购员之间的关系",
     *     tags={"导购"},
     *     description="会员绑定与导购员之间的关系",
     *     operationId="bindUserSalespersonRelationship",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\parameter( name="salesperson_id", in="query", description="导购员id", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="success", type="string", example="false", description="是否成功"),
     *          ),
     *     )),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function bindUserSalespersonRelationship(Request $request)
    {
        $postdata = $request->input();
        $authInfo = $request->get('auth');
        $salesperson_id = $request->input('salesperson_id', 0);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $workWechatRepositories = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
            $shopRelSalespersonRepositories = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);

            //查找用户已绑定的导购员
            $filter = [
                'user_id' => $authInfo['user_id'],
                'is_bind' => 1,
                'company_id' => $authInfo['company_id']
            ];
            $bound = $workWechatRepositories->getInfo($filter);
//            if ($bound) throw new ResourceException('已绑定导购员，无法绑定');
            if ($bound) {
                return ['success' => false];
            }

            //会员是否已绑定当前导购员
            $filter = [
                'user_id' => $authInfo['user_id'],
                'salesperson_id' => $salesperson_id,
                'company_id' => $authInfo['company_id'],
                'is_bind' => 1
            ];
            $bind = $workWechatRepositories->getInfo($filter);
//            if ($bind) throw new ResourceException('已与当前导购员绑定');
            if ($bind) {
                return ['success' => false];
            }

            $filter = [
                'user_id' => $authInfo['user_id'],
                'company_id' => $authInfo['company_id'],
            ];
            $workWechatRepositories->updateBy($filter, ['is_bind' => 0]); //解绑其他导购员

            $filter = [
                'user_id' => $authInfo['user_id'],
                'company_id' => $authInfo['company_id'],
                'salesperson_id' => $salesperson_id,
            ];
            $isBound = $workWechatRepositories->getInfo($filter);
            if ($isBound) {
                $status = $workWechatRepositories->updateOneBy($filter, ['is_bind' => 1]); //修改
            } else {
                $data = [
                    'user_id' => $authInfo['user_id'],
                    'salesperson_id' => $salesperson_id,
                    'company_id' => $authInfo['company_id'],
                    'is_friend' => 0,
                    'is_bind' => 1,
                    'bound_time' => time(),
                ];
                if ( $authInfo['unionid'] ?? '' ) {
                    $data['unionid'] = $authInfo['unionid'];
                }
                $status = $workWechatRepositories->create($data);
            }

            $filter = [
                'salesperson_id' => $salesperson_id,
                'company_id' => $authInfo['company_id'],
                'store_type' => 'distributor'
            ];
            $shopRelSalesperson = $shopRelSalespersonRepositories->getInfo($filter);
            $conn->commit();
            if ($status) {
                $result = [
                    'success' => true
                ];
            } else {
                $result = [
                    'success' => false
                ];
            }

            return $this->response->array($result);
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
    }
    /**
    * @SWG\Get(
    *     path="/h5app/wxapp/salesperson/signinQrcode",
    *     summary="获取导购签到二维码",
    *     tags={"导购"},
    *     description="获取导购签到二维码",
    *     operationId="getSigninQrcode",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="salesperson_id", in="query", description="导购员ID", required=false, type="string"),
    *     @SWG\Parameter( name="type", in="query", description="操作类型: signin/signout", required=true, type="string"),
    *     @SWG\Parameter( name="distributor_id", in="query", description="分销商ID", required=true, type="string"),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base64Image", type="string", example="data:image/jpg;base64,iVBORw0KGgo...", description="base64编码图片"),
     *                  @SWG\Property( property="access_token", type="string", example="37gLqwNp1mZM", description="access_token"),
     *          ),
     *     )),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
    * )
    */
    //获取导购签到二维码
    public function getSigninQrcode(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'type' => 'required|in:signin,signout',
            'distributor_id' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数错误.', $validator->errors());
        }

        $input = $request->input();
        //$input = $request->all('salesperson_id', 'type', 'distributor_id');
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $salespersonId = $input['salesperson_id'] ?? 0;
        if ($input['type'] == 'signout' && !$salespersonId) {
            throw new ResourceException('导购员id必填');
        }

        $signService = new SignService();
        $access_token = $signService->accessTokenCreated($input['distributor_id'], $salespersonId);

        $input = [
            'type' => $input['type'],
            'cid' => $companyId,
            't' => $access_token,
        ];
        $scene = http_build_query($input);

        $qrcode = app('DNS2D')->getBarcodePNG("pages/index?".$scene, "QRCODE", 120, 120);
        $base64 = 'data:image/jpg;base64,'.$qrcode;

        return $this->response->array(['base64Image' => $base64, 'access_token' => $access_token]);
    }

    // public function getSignoutQrcode(Request $request)
    // {
    //     $input = $request->input();
    //     $authInfo = $request->get('auth');
    //     $companyId = $authInfo['company_id'];

    //     $signService = new SignService();
    //     $access_token = $signService->accessTokenCreated();

    //     $input = [
    //         'type' => 'signout',
    //         'cid' => $companyId,
    //         't' => $access_token,
    //     ];
    //     $scene = http_build_query($input);

    //     $qrcode = app('DNS2D')->getBarcodePNG("pages/index?".$scene, "QRCODE", 120, 120);
    //     $base64 = 'data:image/jpg;base64,'.$qrcode;
    //     return $this->response->array(['base64Image' => $base64, 'access_token' => $access_token]);
    // }

    /**
    * @SWG\Post(
    *     path="/h5app/wxapp/salesperson/signinValid",
    *     summary="大屏端签到验证",
    *     tags={"导购"},
    *     description="description",
    *     operationId="validSignin",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="token", in="query", description="token", required=true, type="string"),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="1", description="扫码结果Code"),
     *                  @SWG\Property( property="msg", type="string", example="扫描成功", description="扫码结果描述"),
     *          ),
     *     )),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
    * )
    */
    public function validSignin(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数错误.', $validator->errors());
        }

        $signService = new SignService();
        $token = $request->input('token');
        $info = $signService->getAccessTokenValid($token);
        return $this->response->array($info);
    }

    /**
     * @SWG\Definition(
     *     definition="SalesPersonComplaint",
     *     type="object",
     *     @SWG\Property( property="id", type="string", example="6", description="投诉ID"),
     *     @SWG\Property( property="user_id", type="string", example="20337", description="会员ID"),
     *     @SWG\Property( property="company_id", type="string", example="1", description="商家ID"),
     *     @SWG\Property( property="user_name", type="string", example="会员", description="会员姓名"),
     *     @SWG\Property( property="user_mobile", type="string", example="13800000000", description="会员手机号"),
     *     @SWG\Property( property="saleman_id", type="string", example="94", description="导购员ID"),
     *     @SWG\Property( property="saleman_name", type="string", example="导购员", description="导购员姓名"),
     *     @SWG\Property( property="saleman_avatar", type="string", example="http://wework.qpic.cn/bizmail/...", description="导购员头像URL"),
     *     @SWG\Property( property="saleman_mobile", type="string", example="13800000000", description="导购员手机号"),
     *     @SWG\Property( property="distributor_id", type="string", example="33", description="店铺ID"),
     *     @SWG\Property( property="saleman_distribution_name", type="string", example="视力康眼镜(中兴路店)", description="店铺名称"),
     *     @SWG\Property( property="complaints_content", type="string", example="投诉内容", description="投诉内容"),
     *     @SWG\Property( property="complaints_images", type="string", example="https://bbctest.aixue7.com/image/...", description="投诉图片地址"),
     *     @SWG\Property( property="reply_status", type="string", example="0", description="回复状态(0,1)"),
     *     @SWG\Property( property="reply_content", type="string", example="回复内容(json编码)", description="回复内容(json编码)"),
     *     @SWG\Property( property="reply_time", type="string", example="1611627758", description="回复时间"),
     *     @SWG\Property( property="reply_operator_id", type="string", example="1", description="回复人ID"),
     *     @SWG\Property( property="reply_operator_name", type="string", example="店员", description="回复人姓名"),
     *     @SWG\Property( property="reply_operator_mobile", type="string", example="13800000000", description="回复人手机号"),
     *     @SWG\Property( property="created", type="string", example="1611572005", description="创建时间"),
     *     @SWG\Property( property="updated", type="string", example="1611572005", description="更新时间"),
     * )
     */

    /**
     * @SWG\Definition(
     *     definition="SalesPersonInfo",
     *     type="object",
     *     @SWG\Property( property="salesperson_id", type="string", example="94", description="导购员ID"),
     *     @SWG\Property( property="name", type="string", example="导购员姓名", description="导购员姓名"),
     *     @SWG\Property( property="mobile", type="string", example="15121097923", description="导购员手机"),
     *     @SWG\Property( property="created_time", type="string", example="1599553899", description="导购员创建时间"),
     *     @SWG\Property( property="salesperson_type", type="string", example="shopping_guide", description="导购员类型"),
     *     @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *     @SWG\Property( property="user_id", type="string", example="0", description="会员ID"),
     *     @SWG\Property( property="child_count", type="string", example="0", description="导购员引入的会员数"),
     *     @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *     @SWG\Property( property="shop_id", type="string", example="0", description="门店ID"),
     *     @SWG\Property( property="shop_name", type="string", example="null", description="门店名称"),
     *     @SWG\Property( property="number", type="string", example="", description="导购员编号"),
     *     @SWG\Property( property="friend_count", type="string", example="0", description="导购员会员好友数"),
     *     @SWG\Property( property="avatar", type="string", example="http://wework.qpic.cn/bizmail/....", description="头像URL"),
     *     @SWG\Property( property="work_userid", type="string", example="XiaoHan", description="企业微信userid"),
     *     @SWG\Property( property="work_configid", type="string", example="dc85ac32bca4de9f8c3ed208821ad3a1", description="企业微信userid"),
     *     @SWG\Property( property="work_qrcode_configid", type="string", example="cd5012b12f5feedb698c7d44e827ba56", description="企业微信userid"),
     *     @SWG\Property( property="role", type="string", example="6", description="导购权限集合"),
     *     @SWG\Property( property="created", type="string", example="1599553899", description="created"),
     *     @SWG\Property( property="updated", type="string", example="1611561588", description="updated"),
     *     @SWG\Property( property="shop_ids", type="array",
     *          @SWG\Items( type="string", example="1", description="门店ID"),
     *     ),
     *     @SWG\Property( property="distributor_ids", type="array",
     *          @SWG\Items( type="string", example="33", description="分销商ID"),
     *     ),
     *     @SWG\Property( property="store_type", type="string", example="distributor", description="store_type"),
     *     @SWG\Property( property="distributor_id", type="string", example="33", description="分销ID"),
     *     @SWG\Property( property="store_name", type="string", example="视力康眼镜(中兴路店)", description="店铺名称"),
     *     @SWG\Property( property="distributor", ref="#/definitions/distributorInfo" ),
     *     @SWG\Property( property="is_friend", type="string", example="false", description="is_friend"),
     *     @SWG\Property( property="is_show", type="string", example="1", description="is_show"),
     * )
     */

     /**
     * @SWG\Definition(
     *     definition="distributorInfo",
     *     type="object",
     *     @SWG\Property( property="shop_id", type="string", example="0", description="店铺ID"),
     *     @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *     @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *     @SWG\Property( property="mobile", type="string", example="13800000000", description="店铺手机号"),
     *     @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description="店铺地址"),
     *     @SWG\Property( property="name", type="string", example="视力康眼镜(中兴路店)", description="店铺名称"),
     *     @SWG\Property( property="auto_sync_goods", type="string", example="true", description="自动同步总部商品"),
     *     @SWG\Property( property="logo", type="string", example="http://bbctest.aixue7.com/1/2019/...", description="LOGO图片地址"),
     *     @SWG\Property( property="contract_phone", type="string", example="15988939258", description="其他联系方式"),
     *     @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *     @SWG\Property( property="contact", type="string", example="林先生", description="联系人名称"),
     *     @SWG\Property( property="is_valid", type="string", example="false", description="店铺是否有效"),
     *     @SWG\Property( property="lng", type="string", example="117.890888", description="腾讯地图纬度"),
     *     @SWG\Property( property="lat", type="string", example="33.144662", description="腾讯地图经度"),
     *     @SWG\Property( property="child_count", type="string", example="0", description="child_count"),
     *     @SWG\Property( property="is_default", type="string", example="0", description="是否默认"),
     *     @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核店铺商品"),
     *     @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *     @SWG\Property( property="regions_id", type="array",
     *          @SWG\Items( type="string", example="340000", description="国家行政区划编码组合，逗号隔开"),
     *     ),
     *     @SWG\Property( property="regions", type="array",
     *          @SWG\Items( type="string", example="安徽省", description="区域"),
     *     ),
     *     @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *     @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *     @SWG\Property( property="province", type="string", example="安徽省", description="省份"),
     *     @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *     @SWG\Property( property="city", type="string", example="蚌埠", description="城市"),
     *     @SWG\Property( property="area", type="string", example="五河县", description="地区"),
     *     @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *     @SWG\Property( property="created", type="string", example="1581244268", description="created"),
     *     @SWG\Property( property="updated", type="string", example="1608798211", description="updated"),
     *     @SWG\Property( property="shop_code", type="string", example="null", description="店铺号"),
     *     @SWG\Property( property="wechat_work_department_id", type="string", example="1", description="企业微信的部门ID"),
     *     @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *     @SWG\Property( property="regionauth_id", type="string", example="1", description="区域id"),
     *     @SWG\Property( property="is_open", type="string", example="false", description="是否开启分账"),
     *     @SWG\Property( property="rate", type="string", example="", description="平台服务费率"),
     *     @SWG\Property( property="store_address", type="string", example="安徽省蚌埠五河县中兴路实验小学楼下", description="店铺地址"),
     *     @SWG\Property( property="store_name", type="string", example="视力康眼镜(中兴路店)", description="店铺名称"),
     *     @SWG\Property( property="phone", type="string", example="15988939258", description="手机号"),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/salesperson/bainfo",
     *     summary="更新导购员敏感信息",
     *     tags={"导购"},
     *     description="更新导购员敏感信息",
     *     operationId="updateBaInfo",
     *     @SWG\Parameter( in="header", type="string", required=true, name="authorization", description="jwt验证token" ),
     *     @SWG\Parameter( in="query", type="string", name="avatar", description="头像" ),
     *     @SWG\Parameter( in="query", type="string", name="qrcode", description="二维码" ),
     *     @SWG\Parameter( in="query", type="string", name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *          @SWG\Property(
     *              property="data",
     *              ref="#/definitions/SalesPersonComplaint"
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function updateBaInfo(Request $request)
    {
        $authInfo = $this->auth->user();
        $request_data = $request->all('avatar', 'qrcode', 'mobile');
        $request_data = array_map("trim", $request_data);

        if (!$request_data['avatar'] && !$request_data['qrcode'] && !$request_data['mobile']) {
            throw new ResourceException('没有要更新的信息');
        }

        $filter = [
            'salesperson_id' => $authInfo['salesperson_id'],
        ];

        $salespersonServer = new SalespersonService();
        $result = $salespersonServer->updateOneBy($filter, $request_data);

        return $this->response->array($result);
    }

}
