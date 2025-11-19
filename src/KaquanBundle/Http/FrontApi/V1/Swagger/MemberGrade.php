<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace KaquanBundle\Http\FrontApi\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class MemberGrade
{
    /**
     *          @SWG\Property( property="grade_id", type="string", example="27", description="等级ID"),
     *          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *          @SWG\Property( property="grade_name", type="string", example="黄金会员", description="等级名称"),
     *          @SWG\Property( property="default_grade", type="string", example="false", description="是否默认等级"),
     *          @SWG\Property( property="background_pic_url", type="string", example="http://bbctest.aixue7.com/1/2019/12/09/b9c3f00ca85ff4a33e121f7b1a1b5effsqMbkuKCjuFItSsbZrlOXCmra5Vto3ko", description="商家自定义会员卡背景图"),
     *          @SWG\Property( property="promotion_condition", type="object",
     *                  @SWG\Property( property="total_consumption", type="string", example="2000", description="升级条件"),
     *          ),
     *          @SWG\Property( property="privileges", type="object",
     *                  @SWG\Property( property="discount", type="string", example="30", description=""),
     *                  @SWG\Property( property="discount_desc", type="string", example="7", description=""),
     *          ),
     *          @SWG\Property( property="created", type="string", example="1566789746", description="创建时间"),
     *          @SWG\Property( property="updated", type="string", example="1606642797", description="修改时间"),
     *          @SWG\Property( property="third_data", type="string", example="", description="第三方数据"),
     *          @SWG\Property( property="crm_open", type="string", example="false", description="crm开关"),
     */
}
