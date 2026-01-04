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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use EspierBundle\Services\UploadFileService;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\UploadTokenFactoryService;
use Swagger\Annotations as SWG;

class UploadFile extends Controller
{
    /**
     * @SWG\Post(
     *     path="/espier/upload_file",
     *     summary="上传文件",
     *     tags={"系统"},
     *     description="上传文件",
     *     operationId="handleUploadFile",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="file_type", in="query", description="模板类型 【update_distribution_item 更新门店商品的模板】", required=true, type="string"),
     *     @SWG\Parameter( name="file", in="query", description="上传的文件", required=true, type="string"),
     *     @SWG\Parameter( name="should_queue", in="query", description="是否需要实时处理，【1 异步处理】【0 实时处理】", required=false, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="file_name", type="string"),
     *                     @SWG\Property(property="file_type", type="string"),
     *                     @SWG\Property(property="file_size", type="string"),
     *                     @SWG\Property(property="handle_status", type="string"),
     *                     @SWG\Property(property="handle_line_num", type="string"),
     *                     @SWG\Property(property="finish_time", type="string"),
     *                     @SWG\Property(property="handle_message", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function handleUploadFile(Request $request)
    {
        $uploadFileService = new uploadFileService();

        $companyId = app('auth')->user()->get('company_id');
        $operatorId = app('auth')->user()->get('operator_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = $request->input('distributor_id', 0);
        $fileType = $request->input('file_type');
        $fileObject = $request->file('file');
        $shouldQueue = (bool)$request->input("should_queue", 1);
        if (env('APP_ENV') == 'local_dev') {
            $shouldQueue = 0;//本地开发环境，不走队列
        }
        $supplierId = 0;
        if ($operatorType == 'supplier') {
            $supplierId = $operatorId;
            //todo 因为前端传参不对，把商品上传切换到供应商商品上传
//            if ($fileType == 'normal_goods') {
//                $fileType = 'supplier_goods';
//            }
        }
        $result = $uploadFileService->uploadFile($companyId, $operatorId, $distributorId, $supplierId, $fileType, $fileObject, $shouldQueue);

        return $this->response->array(['data' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/espier/upload_files",
     *     summary="获取上传文件列表",
     *     tags={"系统"},
     *     description="获取上传文件列表",
     *     operationId="getUploadLists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="file_type", in="query", description="模板类型 【update_distribution_item 更新门店商品的模板】", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="distributor_id", type="stirng"),
     *                     @SWG\Property(property="name", type="stirng"),
     *                     @SWG\Property(property="address", type="stirng"),
     *                     @SWG\Property(property="mobile", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getUploadLists(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1', '分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50', '每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        $filter['company_id'] = $companyId;
        $filter['file_type'] = $request->input('file_type', 'member_info');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $filter['supplier_id'] = 0;
        }
        $distributor_id = $request->input('distributor_id', 0);
        if (!empty($distributor_id)) {
            $filter['distributor_id'] = $distributor_id;
        }

        $distributorService = new uploadFileService();
        $data = $distributorService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);

        foreach ($data['list'] as $key => $val) {
            if (isset($data['list'][$key]['handle_message']['errorlog'])) {
                unset($data['list'][$key]['handle_message']['errorlog']);
            }
        }

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/espier/upload_template",
     *     summary="获取上传文件模版",
     *     tags={"系统"},
     *     description="获取上传文件模版",
     *     operationId="exportUploadTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="file_type", in="query", description="模板类型 【update_distribution_item 更新门店商品的模板】", required=true, type="string"),
     *     @SWG\Parameter( name="file_name", in="query", description="模板名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(required={"data"},
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items(type="object",required={"name","file"},
     *                     @SWG\Property(property="name", type="string", description="文件名称"),
     *                     @SWG\Property(property="file", type="string", description="文件的二进制内容"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function exportUploadTemplate(Request $request)
    {
        $uploadFileService = new uploadFileService();

        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $fileType = $request->input('file_type');
        if (empty($fileType)) {
            throw new ResourceException("文件类型不能为空！");
        }
        $fileName = $request->input('file_name');
        if (empty($fileName)) {
            throw new ResourceException("文件名称不能为空！");
        }

        if ($operatorType == 'supplier') {
            if ($fileType == 'normal_goods') {
                $fileType = 'supplier_goods';
            }
        }

        $response = [
            'name' => $fileName . '.xlsx', //no extention needed
            'file' => "data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64," . base64_encode($uploadFileService->uploadTemplate($fileType, $fileName, $companyId, $operatorType))
        ];
        return response()->json($response);
    }

    /**
     * @SWG\Get(
     *     path="/espier/upload_error_file_export/{id}",
     *     summary="上传文件执行后错误信息",
     *     tags={"系统"},
     *     description="上传文件执行后错误信息",
     *     operationId="exportUploadErrorFile",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="file_type", in="query", description="模板类型 【update_distribution_item 更新门店商品的模板】", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="file", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function exportUploadErrorFile($id, Request $request)
    {
        $uploadFileService = new uploadFileService();

        $companyId = app('auth')->user()->get('company_id');
        $fileType = $request->input('file_type');

        $content = $uploadFileService->getErrorFile($id, $fileType);

        $response = array(
            'name' => 'error.xlsx', //no extention needed
            'file' => "data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64," . base64_encode($content)
        );
        return response()->json($response);
    }

    /**
     * @SWG\Post(
     *     path="/espier/file_upload_token",
     *     summary="获取上传图片token",
     *     tags={"系统"},
     *     description="获取上传图片token",
     *     operationId="getPicUploadToken",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="filesystem", in="query", description="文件系统名称", required=true, type="string"),
     *     @SWG\Parameter( name="filename", in="query", description="上传文件名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="region", type="string"),
     *                     @SWG\Property(property="uptoken", type="string"),
     *                     @SWG\Property(property="domain", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getPicUploadToken(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $filesystem = 'image';
        $filename = $request->input('filename');
        $result = UploadTokenFactoryService::create($filesystem)->getToken($companyId, '', $filename);
        return $this->response->array($result['token']);
    }
}
