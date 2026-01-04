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

namespace EspierBundle\Http\Api\V1\Action;

use EspierBundle\Services\SubdistrictService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;

class SubdistrictController extends Controller
{
    // FIXME: check performance
    /**
     * @SWG\Get(
     *     path="/espier/subdistrict",
     *     summary="获取街道社区列表",
     *     tags={"系统"},
     *     description="获取街道社区列表",
     *     operationId="get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="integer", description=""),
     *                  @SWG\Property( property="company_id", type="integer", description=""),
     *                  @SWG\Property( property="label", type="string", description="街道名称"),
     *                  @SWG\Property( property="parent_id", type="integer", description="父级id, 0为顶级0"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="integer", description=""),
     *                          @SWG\Property( property="company_id", type="integer", description=""),
     *                          @SWG\Property( property="label", type="string", description="社区名称"),
     *                          @SWG\Property( property="parent_id", type="integer", description="父级id"),
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function get(Request $request){
        $companyId = app('auth')->user()->get('company_id');

        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
        }

        $subdistrict = app('redis')->connection('espier')->hget('subdistrict', $companyId.'_'.$distributorId);
        if (!$subdistrict) {
            $subdistrictService = new SubdistrictService();
            $subdistrict = $subdistrictService->getSubdistrict($companyId, $distributorId);
            app('redis')->connection('espier')->hset('subdistrict', $companyId.'_'.$distributorId, json_encode($subdistrict));
        } else {
            $subdistrict = json_decode($subdistrict, true);
        }

        if ($label = $request->get('label')) {
            foreach ($subdistrict as $key => $item) {
                $children = array_filter($item['children'], function ($item) use ($label) {
                    return strpos($item['label'], $label) !== false;
                });
                if ($children || (strpos($item['label'], $label) !== false)) {
                    $subdistrict[$key]['children'] = array_values($children);
                } else {
                    unset($subdistrict[$key]);
                }
            }

            $subdistrict = array_values($subdistrict);
        }

        $distributorIds = array_reduce($subdistrict, function($carry, $item) {
            $carry = array_merge($carry, $item['distributor_id']);
            array_unique($carry);
            return $carry;
        }, []);

        if ($distributorIds) {
            $distributorService = new DistributorService();
            $distributor = $distributorService->entityRepository->lists(['distributor_id' => $distributorIds], null, -1, 1, false, 'distributor_id,name', true);
            $distributor = array_column($distributor['list'], 'name', 'distributor_id');
            foreach ($subdistrict as $key => $val) {
                if ($operatorType != 'distributor') {
                    foreach ($val['distributor_id'] as $k => $did) {
                        if ($did > 0) {
                            if (isset($distributor[$did])) {
                                $subdistrict[$key]['distributor'][] = $distributor[$did];
                            }
                        } else {
                            unset($subdistrict[$key]['distributor_id'][$k]);
                        }
                    }
                    $subdistrict[$key]['distributor_id'] = array_values($subdistrict[$key]['distributor_id']);
                } else {
                    $subdistrict[$key]['distributor'] = [$distributor[$distributorId]];
                    $subdistrict[$key]['distributor_id'] = [strval($distributorId)];
                }
            }
        }

        return $this->response->array($subdistrict);
    }

    /**
     * @SWG\Get(
     *     path="/espier/subdistrict/{id}",
     *     summary="获取街道社区",
     *     tags={"系统"},
     *     description="获取街道社区",
     *     operationId="get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="integer", description=""),
     *                  @SWG\Property( property="company_id", type="integer", description=""),
     *                  @SWG\Property( property="label", type="string", description="街道名称"),
     *                  @SWG\Property( property="parent_id", type="integer", description="父级id, 0为顶级0"),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getInfo($id, Request $request){
        $filter = [
            'id' => $id,
            'company_id' => app('auth')->user()->get('company_id'),
        ];
        $subdistrictService = new SubdistrictService();
        $subdistrict = $subdistrictService->getInfo($filter);

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            if ($subdistrict && !in_array($distributorId, $subdistrict)) {
                throw new ResourceException('没有权限查看');
            }

            $subdistrict['distributor_id'] = [strval($distributorId)];
        }

        return $this->response->array($subdistrict ?: []);
    }

    /**
     * @SWG\Delete(
     *     path="/espier/subdistrict/{id}",
     *     summary="删除街道社区",
     *     tags={"系统"},
     *     description="删除街道社区",
     *     operationId="get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property(property="data", type="object", description="", required={"status"},
     *               @SWG\Property(property="status", type="boolean", default="true", description="更新的状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function delete($id, Request $request){
        $filter = [
            'id' => $id,
            'company_id' => app('auth')->user()->get('company_id'),
        ];
        $subdistrictService = new SubdistrictService();

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $subdistrict = $subdistrictService->getInfo($filter);
            $subdistrict['distributor_id'] = array_filter($subdistrict['distributor_id'], function ($did) use ($distributorId) {
                return $did != $distributorId;
            });
            if ($subdistrict['distributor_id']) {
                $subdistrictService->updateOneBy($filter, ['distributor_id' => $subdistrict['distributor_id']]);
            } else {
                $subdistrictService->deleteBy($filter);
                $filter['parent_id'] = $id;
                unset($filter['id']);
                $subdistrictService->deleteBy($filter);
            }
        } else {
            $subdistrictService->deleteBy($filter);
            $filter['parent_id'] = $id;
            unset($filter['id']);
            $subdistrictService->deleteBy($filter);
        }

        app('redis')->connection('espier')->del('subdistrict');

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/espier/subdistrict",
     *     summary="更新街道社区",
     *     tags={"系统"},
     *     description="更新街道社区",
     *     operationId="get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="", required=false, type="integer"),
     *     @SWG\Parameter( name="parent_id", in="query", description="", required=true, type="integer"),
     *     @SWG\Parameter( name="label", in="query", description="", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property(property="data", type="object", description="", required={"status"},
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="integer", description=""),
     *                  @SWG\Property( property="company_id", type="integer", description=""),
     *                  @SWG\Property( property="label", type="string", description="街道名称"),
     *                  @SWG\Property( property="parent_id", type="integer", description="父级id, 0为顶级0"),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function save(Request $request){
        $params = $request->all('id', 'parent_id', 'label', 'distributor_id', 'regions_id', 'province', 'city', 'area');
        $rules = [
            'parent_id' => ['required|integer|min:0', '上级ID必填'],
            'label'     => ['required', '街道/社区名称必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (isset($params['distributor_id']) && !is_array($params['distributor_id'])) {
            $params['distributor_id'] = [$params['distributor_id']];
        }

        $operatorType = app('auth')->user()->get('operator_type');
        $params['company_id'] = app('auth')->user()->get('company_id');

        $subdistrictService = new SubdistrictService();
        if ($params['parent_id'] > 0) {
            $info = $subdistrictService->getInfo(['id' => $params['parent_id'], 'company_id' => $params['company_id']]);
            if (!$info) {
                throw new ResourceException('上级ID错误');
            }

            if ($operatorType != 'distributor' && intval($params['id']) == 0) {
                $params['distributor_id'] = $info['distributor_id'];
            }
        }

        if ($operatorType != 'distributor') {
            if (isset($params['distributor_id'])) {
                if (!in_array('0', $params['distributor_id'])) {
                    array_unshift($params['distributor_id'], 0);
                }
            } else {
                $params['distributor_id'] = [0];   
            }
        }

        if ($params['id'] > 0) {
            $info = $subdistrictService->getInfo(['id' => $params['id'], 'company_id' => $params['company_id']]);
            if (!$info) {
                throw new ResourceException('ID错误');
            }
            $result = $subdistrictService->updateOneBy(['id' => $params['id']], $params);
            if ($operatorType != 'distributor' && intval($params['parent_id']) == 0) {
                $newDistributorId = array_diff($params['distributor_id'], $info['distributor_id']);
                if ($newDistributorId) {
                    $subdistrictList = $subdistrictService->lists(['parent_id' => $params['id'], 'distributor_id|contains' => ',0,']);
                    foreach ($subdistrictList['list'] as $row) {
                        $subdistrictService->updateOneBy(['id' => $row['id']], ['distributor_id' => array_merge($row['distributor_id'], $newDistributorId)]);
                    }
                }
            }
        } else {
            unset($params['id']);
            $result = $subdistrictService->create($params);
        }

        app('redis')->connection('espier')->del('subdistrict');

        return $this->response->array($result);
    }
}