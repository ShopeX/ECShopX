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

namespace MembersBundle\Http\FrontApi\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class MemberSetting
{
    /**
     *                  @SWG\Property( property="username", type="object",
     *                          @SWG\Property( property="name", type="string", example="姓名", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="element_type", type="string", example="input", description="对象类型"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                  ),
     *                  @SWG\Property( property="sex", type="object",
     *                          @SWG\Property( property="name", type="string", example="性别", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="element_type", type="string", example="select", description="对象类型"),
     *                          @SWG\Property( property="is_required", type="string", example="true", description="是否必填"),
     *                  ),
     *                  @SWG\Property( property="birthday", type="object",
     *                          @SWG\Property( property="name", type="string", example="出生年份", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="element_type", type="string", example="select", description="对象类型"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                  ),
     *                  @SWG\Property( property="address", type="object",
     *                          @SWG\Property( property="name", type="string", example="家庭地址", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="element_type", type="string", example="input", description="对象类型"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                  ),
     *                  @SWG\Property( property="email", type="object",
     *                          @SWG\Property( property="name", type="string", example="email", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="element_type", type="string", example="input", description="对象类型"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                  ),
     *                  @SWG\Property( property="industry", type="object",
     *                          @SWG\Property( property="name", type="string", example="行业", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="element_type", type="string", example="select", description="对象类型"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                          @SWG\Property( property="items", type="array",
     *                              @SWG\Items( type="string", example="金融/银行/投资", description="对象类型"),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="income", type="object",
     *                          @SWG\Property( property="name", type="string", example="年收入", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                          @SWG\Property( property="element_type", type="string", example="select", description="对象类型"),
     *                          @SWG\Property( property="items", type="array",
     *                              @SWG\Items( type="string", example="5万以下", description="对象详情"),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="edu_background", type="object",
     *                          @SWG\Property( property="name", type="string", example="学历", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="element_type", type="string", example="select", description="对象类型"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                          @SWG\Property( property="items", type="array",
     *                              @SWG\Items( type="string", example="硕士及以上", description="配置名称"),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="habbit", type="object",
     *                          @SWG\Property( property="name", type="string", example="爱好", description="配置名称"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                          @SWG\Property( property="element_type", type="string", example="checkbox", description="对象类型"),
     *                          @SWG\Property( property="items", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="name", type="string", example="游戏", description="对象详情"),
     *                                  @SWG\Property( property="ischecked", type="string", example="false", description=""),
     *                               ),
     *                          ),
     *                  ),
     *
     */
}
