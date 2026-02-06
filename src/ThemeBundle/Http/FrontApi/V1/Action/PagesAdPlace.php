<?php

namespace ThemeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use ThemeBundle\Services\PagesAdPlaceService;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberTagsService;

class PagesAdPlace extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/ad/list",
     *     tags={"模版-前端"},
     *     summary="广告列表",
     *     description="根据广告类型和页面类型获取广告列表",
     *     operationId="getAdList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="query",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="ad_type",
     *         in="query",
     *         description="广告类型：弹窗=>popup，轮播图=>carousel",
     *         required=true,
     *         type="string",
     *         enum={"popup", "carousel"}
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="query",
     *         description="页面类型",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/PagesAdPlace"))
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function getAdList(Request $request)
    {
        $params = $request->all();
        $authInfo = $request->get('auth');

        $validator = app('validator')->make($params, [
            'ad_type'     => 'required|in:popup,carousel',
            'page_type'     => 'required|string',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $filter['company_id'] = $authInfo['company_id'];
        $filter['regionauth_id'] = $params['regionauth_id'] ?? 0;
        $filter['use_bound'] = 0;
        $filter['start_time|lte'] = time();
        $filter['end_time|gte'] = time();
        $filter['ad_type'] = $params['ad_type'];
        $filter['pages'] = $params['page_type'];
        $filter['audit_status'] = 'approved';

        $service = new PagesAdPlaceService();
        $result = $service->getListWithRelTags($filter, 1, -1, ['sort' => 'desc', 'id' => 'DESC']);

        if (isset($params['distributor_id']) && $params['distributor_id'] > 0) {
            $filter['use_bound'] = 1;
            $filter['distributor_id'] = $params['distributor_id'];
            $distributorResult = $service->getListWithRelTags($filter, 1, -1, ['sort' => 'desc', 'id' => 'DESC']);
            $result['list'] = array_merge($result['list'], $distributorResult['list']);
            usort($result['list'], function($left, $right) {
                if ($right['sort'] == $left['sort']) {
                    return $right['id'] - $left['id'];
                }
                return $right['sort'] - $left['sort'];
            });
        }

        $tagRelAdPlaceIds = [];
        foreach ($result['list'] as $key => $val) {
            if ($authInfo['user_id'] > 0) {
                foreach ($val['rel_tags'] as $relTag) {
                    if (!isset($tagRelAdPlaceIds[$relTag['tag_id']])) {
                        $tagRelAdPlaceIds[$relTag['tag_id']] = [$val['id']];
                    } else {
                        if (!in_array($val['id'], $tagRelAdPlaceIds[$relTag['tag_id']])) {
                            $tagRelAdPlaceIds[$relTag['tag_id']][] = $val['id'];
                        }
                    }
                }
            } else {
                if ($val['rel_tags']) {
                    unset($result['list'][$key]);
                }
            }
        }

        if ($tagRelAdPlaceIds) {
            $checkParams = [
                'company_id' => $authInfo['company_id'],
                'user_id' => $authInfo['user_id'],
                'tag_id' => array_keys($tagRelAdPlaceIds),
            ];
            $memberTagsService = new MemberTagsService();
            $bindTags = $memberTagsService->checkAndProcessTag($checkParams);

            $filterAdPlaceIds = [];
            foreach ($bindTags as $tag) {
                if ($tag['related'] && isset($tagRelAdPlaceIds[$tag['tag_id']])) {
                    $filterAdPlaceIds = array_merge($filterAdPlaceIds, $tagRelAdPlaceIds[$tag['tag_id']]);
                }
            }

            foreach ($result['list'] as $key => $val) {
                if ($val['rel_tags'] && !in_array($val['id'], $filterAdPlaceIds)) {
                    unset($result['list'][$key]);
                }
            }
        }

        return $this->response->array(array_values($result['list']));
    }
}

/**
 * @SWG\Definition(
 *     definition="PagesAdPlace",
 *     type="object",
 *     @SWG\Property(property="id", type="integer", description="ID"),
 *     @SWG\Property(property="company_id", type="integer", description="公司ID"),
 *     @SWG\Property(property="regionauth_id", type="integer", description="区域ID"),
 *     @SWG\Property(property="distributor_id", type="integer", description="店铺ID"),
 *     @SWG\Property(property="ad_type", type="string", description="广告类型：弹窗=>popup，轮播图=>carousel"),
 *     @SWG\Property(property="name", type="string", description="广告位名称"),
 *     @SWG\Property(property="pages", type="array", @SWG\Items(type="string"), description="关联页面"),
 *     @SWG\Property(property="start_time", type="integer", description="开始时间"),
 *     @SWG\Property(property="end_time", type="integer", description="结束时间"),
 *     @SWG\Property(property="setting", type="string", description="设置信息,json格式"),
 *     @SWG\Property(property="auto_play", type="integer", description="自动播放"),
 *     @SWG\Property(property="play_interval", type="integer", description="播放间隔时间"),
 *     @SWG\Property(property="auto_close", type="integer", description="自动关闭"),
 *     @SWG\Property(property="close_delay", type="integer", description="关闭延迟时间"),
 *     @SWG\Property(property="created", type="integer", description="创建时间"),
 *     @SWG\Property(property="updated", type="integer", description="更新时间"),
 *     @SWG\Property(property="sort", type="integer", description="排序")
 * )
 */