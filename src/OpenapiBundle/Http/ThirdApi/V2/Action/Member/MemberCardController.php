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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Member;

use Illuminate\Http\Request;
use KaquanBundle\Services\MemberCardService;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Traits\Member\MemberCardTrait;
use Swagger\Annotations as SWG;

/**
 * 会员卡基础信息相关
 * Class MemberCardController
 * @package OpenapiBundle\Http\Api\V2\Action\Member
 */
class MemberCardController extends Controller
{
    use MemberCardTrait;

    /**
     * @SWG\Get(
     *     path="/ecx.member_card.detail",
     *     tags={"会员"},
     *     summary="会员卡基础设置 - 查询",
     *     description="会员卡基础设置 - 查询",
     *     operationId="detail",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member_card.detail" ),
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="", required={"brand_name","logo_url","title","color","code_type","background_pic_url","created","updated"},
     *               @SWG\Property(property="brand_name", type="string", default="111", description="商户名称"),
     *               @SWG\Property(property="logo_url", type="string", default="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkrea6fnM3LTSgicDZNfnf6DnpmqgoHL9k1k09oVqlarzOUSBw11h1LiaW1wYazQY1j0KsUm3dbPs9ciaQ/0?wx_fmt=png", description="商户Logo"),
     *               @SWG\Property(property="title", type="string", default="2222", description="会员卡标题"),
     *               @SWG\Property(property="color", type="string", default="#409EFF", description="会员卡背景色"),
     *               @SWG\Property(property="background_pic_url", type="string", default="", description="会员卡背景图"),
     *               @SWG\Property(property="created", type="string", default="2020-11-05 11:06:07", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *               @SWG\Property(property="updated", type="string", default="2020-11-05 11:06:07", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function detail(Request $request)
    {
        $result = (new MemberCardService())->getMemberCard($this->getCompanyId());
        $this->handleResult($result);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member_card.update",
     *     tags={"会员"},
     *     summary="会员卡基础设置 - 修改",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="update",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member_card.update" ),
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="brand_name", in="formData", description="商户名称", required=false, type="string"),
     *     @SWG\Parameter(name="logo_url", in="formData", description="商户Logo", required=false, type="string"),
     *     @SWG\Parameter(name="title", in="formData", description="会员卡标题", required=false, type="string"),
     *     @SWG\Parameter(name="color", in="formData", description="会员卡背景色", required=false, type="string"),
     *     @SWG\Parameter(name="background_pic_url", in="formData", description="会员卡背景图", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", example="success", description=""),
     *             @SWG\Property(property="data", type="array", description="",
     *               @SWG\Items(
     *               ),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function update(Request $request)
    {
        $params = [];
        foreach (["brand_name", "logo_url", "title", "color", "background_pic_url"] as $column) {
            if ($request->has($column)) {
                $params[$column] = (string)$request->input($column, "");
            }
        }
        if (!empty($params)) {
            (new MemberCardService())->setMemberCard($this->getCompanyId(), $params);
        }
        return $this->response->array([]);
    }
}
