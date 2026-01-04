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

namespace DataCubeBundle\Services;

use DataCubeBundle\Entities\Monitors;
use DataCubeBundle\Entities\RelSources;
use Dingo\Api\Exception\ResourceException;
use WechatBundle\Services\OpenPlatform;
use CompanysBundle\Services\RegionauthService;

class MonitorsService
{
    /** @var monitorsRepository */
    private $monitorsRepository;
    private $relSourcesRepository;

    /** @var openPlatform */
    private $openPlatform;

    /**
     * MonitorsService 构造函数.
     */
    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
        $this->monitorsRepository = app('registry')->getManager('default')->getRepository(Monitors::class);
        $this->relSourcesRepository = app('registry')->getManager('default')->getRepository(RelSources::class);
    }

    /**
     * 添加
     *
     * @param array params 跟踪链接数据
     * @return array
     */
    public function addMonitors(array $params)
    {
        $data = [
            'company_id' => $params['company_id'],
            'wxappid' => $params['wxappid'],
            'monitor_path' => $params['monitor_path'],
            'monitor_path_params' => $params['monitor_path_params'],
            'page_name' => $params['page_name'],
            'regionauth_id' => $params['regionauth_id'],
        ];
        $filter = [
            'wxappid' => $params['wxappid'],
            'company_id' => $params['company_id'],
            'monitor_path' => $params['monitor_path'],
            'monitor_path_params' => $params['monitor_path_params'],
            'regionauth_id' => $params['regionauth_id'],
        ];
        $oldInfo = $this->monitorsRepository->findOneBy($filter);
        if ($oldInfo) {
            throw new ResourceException('此链接已经添加过，不能重复添加.');
        }

        $getAuthorizerInfo = $this->openPlatform->getAuthorizerInfo($params['wxappid']);
        $data['nick_name'] = $getAuthorizerInfo['nick_name'];

        $rs = $this->monitorsRepository->create($data);

        return $rs;
    }

    /**
     * 删除
     *
     * @param array filter
     * @return bool
     */
    public function deleteMonitors($filter)
    {
        $monitorsInfo = $this->monitorsRepository->get($filter['monitor_id']);

        if ($filter['company_id'] != $monitorsInfo['company_id']) {
            throw new ResourceException('删除跟踪链接信息有误.');
        }
        if (!$filter['monitor_id']) {
            throw new ResourceException('跟踪链接id不能为空.');
        }

        return $this->monitorsRepository->delete($filter['monitor_id']);
    }

    /**
     * 删除一条来源监控信息
     *
     * @param array filter
     * @return bool
     */
    public function deleteRelSources($filter)
    {
        if (!$filter['company_id']) {
            throw new ResourceException('删除跟踪链接信息有误.');
        }
        if (!$filter['monitor_id']) {
            throw new ResourceException('删除来源监控缺少参数.');
        }
        if (!$filter['source_id']) {
            throw new ResourceException('删除来源监控缺少参数.');
        }

        return $this->relSourcesRepository->deleteOneRelSource($filter['monitor_id'], $filter['source_id'], $filter['company_id']);
    }

    /**
     * 获取
     *
     * @param inteter monitors_id 跟踪链接id
     * @return array
     */
    public function getMonitorsDetail($monitor_id)
    {
        $monitorInfo = $this->monitorsRepository->get($monitor_id);

        return $monitorInfo;
    }

    /**
     * 获取
     *
     * @param array filter
     * @return array
     */
    public function getMonitorsList($filter, $page, $pageSize, $orderBy = ['monitor_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 100) ? 100 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $monitorsList = $this->monitorsRepository->list($filter, $orderBy, $pageSize, $page);
       
        //根据小镇id获取小镇数据
        if(!empty($monitorsList['list']))
        {
            $regionauthId = array_filter(array_column($monitorsList['list'], 'regionauthId'));
            if ($regionauthId) {
                $regionauthService = new RegionauthService();
                $regionauthList = $regionauthService->getLists(['company_id' => $filter['company_id'], 'regionauth_id' => $regionauthId], 'regionauth_id,regionauth_name');
                $regionauthNameMap = array_column($regionauthList, 'regionauth_name', 'regionauth_id');

                foreach ($monitorsList['list'] as $key => $val) {
                    $monitorsList['list'][$key]['regionauth_name'] = $regionauthNameMap[$val['regionauthId']] ?? '';
                }
            }
        }
        
        return $monitorsList;
    }

    /**
     * 修改
     *
     * @param array params 提交的
     * @return array
     */
    public function updateMonitors($params)
    {
        $monitorsInfo = $this->monitorsRepository->get($params['monitor_id']);

        if ($params['company_id'] != $monitorsInfo['company_id']) {
            throw new ResourceException('请确认您的门店信息后再提交.');
        }
        $data = [
            'company_id' => $params['company_id'],
            'wxappid' => $params['wxappid'],
            'nick_name' => $params['nick_name'],
            'monitor_path' => $params['monitor_path'],
            'monitor_path_params' => $params['monitor_path_params'],
        ];

        $rs = $this->monitorsRepository->update($params['monitor_id'], $data);

        return $rs;
    }

    public function relSources($params)
    {
        $conn = app('registry')->getConnection('default');

        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $params['company_id'],
                'monitor_id' => $params['monitor_id'],
            ];
            $conn->delete($this->relSourcesRepository->table, $filter);
            if ($params['sourceIds']) {
                foreach ($params['sourceIds'] as $source_id) {
                    $data = [
                        'company_id' => $params['company_id'],
                        'monitor_id' => $params['monitor_id'],
                        'source_id' => $source_id,
                    ];
                    $conn->insert($this->relSourcesRepository->table, $data);
                }
            }
            $conn->commit();
            return $this->relSourcesRepository->getListByMonitorId($params['monitor_id'], $params['company_id']);
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getRelSources($params)
    {
        $relMonitorsList = $this->relSourcesRepository->getListByMonitorId($params['monitor_id'], $params['company_id']);
        $sourcesService = new SourcesService();
        foreach ($relMonitorsList as &$v) {
            $sourceInfo = $sourcesService->getSourcesDetail($v['source_id']);
            $v['source_name'] = $sourceInfo['source_name'];
        }
        return $relMonitorsList;
    }

    public function getStats($params)
    {
        $dateFilter['date_type'] = $params['date_type'];
        $dateFilter['date_range'] = [];
        if ($params['date_type'] == 'custom') {
            $dateFilter['date_range']['start'] = $params['begin_date'];
            $dateFilter['date_range']['stop'] = $params['end_date'];
        }

        $sourcesService = new SourcesService();
        $relMonitorsList = $this->relSourcesRepository->getListByMonitorId($params['monitor_id'], $params['company_id']);
        $trackService = new TrackService();
        
        //monitor_id 维度汇总
        $statsTotal['total_pv'] = 0;
        $statsTotal['total_uv'] = 0;
        $statsTotal['total_visitor'] = 0;
        $statsTotal['total_member_visitor'] = 0;
        $statsTotal['total_pay_num'] = 0;
        $statsTotal['total_pay_amount'] = 0;

        foreach ($relMonitorsList as &$v) {
            $sourceInfo = $sourcesService->getSourcesDetail($v['source_id']);
            
            $v['source_name'] = $sourceInfo['source_name'];
            $oneFilter = [
                'company_id' => $v['company_id'],
                'monitor_id' => $v['monitor_id'],
                'source_id' => $v['source_id'],
            ];
            $v['register_num'] = $trackService->getRegisterNum(array_merge($dateFilter, $oneFilter));//注册量
            $v['entries_num'] = $trackService->getEntriesNum(array_merge($dateFilter, $oneFilter));//购买量=支付单数

            //获单个sourceid取监控的PV
            $v['total_pv'] = $trackService->getPageView(array_merge($dateFilter, $oneFilter));
            $statsTotal['total_pv'] += $v['total_pv'];
            //获单个sourceid取监控的UV
            $v['total_uv'] = $trackService->getUniqueVisitor(array_merge($dateFilter, $oneFilter));
            $statsTotal['total_uv'] += $v['total_uv'];
            //获单个sourceid取监控的会员访客
            $v['total_member_visitor'] = $trackService->getMemberVisitor(array_merge($dateFilter, $oneFilter));
            $statsTotal['total_member_visitor'] += $v['total_member_visitor'];
            //获单个sourceid取监控的游客访问量 = UV - 会员访客数
            $v['total_visitor'] = $v['total_uv'] - $v['total_member_visitor'];
            $statsTotal['total_visitor'] += $v['total_visitor'];

            //获单个sourceid取监控的支付人数，去orders表根据source_id和company_id获取支付人数，按时间范围
            $paiedOrderAggs = $trackService->getPaiedOrderAggs(array_merge($dateFilter, $oneFilter));
            $v['total_pay_num'] = $paiedOrderAggs['pay_num'];
            $statsTotal['total_pay_num'] += $v['total_pay_num'];
            //获单个sourceid取监控的支付总价，去orders表根据source_id和company_id获取支付总价，按时间范围
            $v['total_pay_amount'] = $paiedOrderAggs['pay_amount'];
            $statsTotal['total_pay_amount'] += $v['total_pay_amount'];

            // $v['conversion_rate'] = ($v['view_num'] > 0) ? round( $v['entries_num'] / $v['view_num'] * 100 , 2) . "％" : '0%';
            $v['conversion_rate'] = ($v['total_uv'] > 0) ? round($v['total_pay_num'] / $v['total_uv'] * 100, 2) . "％" : '0%';
        }
        
        $totalFilter = [
            'company_id' => $params['company_id'],
            'monitor_id' => $params['monitor_id'],
        ];

        $statsTotal['total_register_num'] = $trackService->getTotalRegisterNum(array_merge($dateFilter, $totalFilter));//注册量
        $statsTotal['total_entries_num'] = $trackService->getTotalEntriesNum(array_merge($dateFilter, $totalFilter));//购买量=支付单数
        
        // $statsTotal['total_conversion_rate'] = ($statsTotal['total_view_num'] > 0) ? round( $statsTotal['total_entries_num'] / $statsTotal['total_view_num'] * 100 , 2) . "％" : '0%';
        $statsTotal['total_conversion_rate'] = ($statsTotal['total_uv'] > 0) ? round($statsTotal['total_pay_num'] / $statsTotal['total_uv'] * 100, 2) . "％" : '0%';//转化率

        $result = [
            'stats_total' => $statsTotal,
            'stats_list' => $relMonitorsList,
        ];

        return $result;
    }

    public function getMonitorWxaCode($monitorId, $sourceId, $isBase64 = 0)
    {
        $monitorInfo = $this->monitorsRepository->get($monitorId);
        $app = $this->openPlatform->getAuthorizerApplication($monitorInfo['wxappid']);
        $data['page'] = $monitorInfo['monitor_path'];
        $paramsStr = $monitorInfo['monitor_path_params'] . '&s='.$sourceId . '&m=' . $monitorId;
        // $paramsStr = $monitorInfo['monitor_path_params'] . '&ms='.$monitorId .'_'.$sourceId;
        // $paramsStr = trim($paramsStr, '&');
        // parse_str($paramsStr, $scene);
        // $data['scene'] = urlencode($paramsStr);
        if (!$isBase64) {
            $data['width'] = 1280;
        }
        $scene = trim($paramsStr, '&');
        $wxaCode = $app->app_code->getUnlimit($scene, $data);
        if (is_array($wxaCode) && $wxaCode['errcode'] > 0) {
            throw new ResourceException($wxaCode['errmsg']);
        }
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
            return ['base64Image' => $base64];
        } else {
            return $wxaCode;
        }
    }
}
