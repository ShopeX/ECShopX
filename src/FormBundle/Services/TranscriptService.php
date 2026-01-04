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

namespace FormBundle\Services;

use FormBundle\Entities\Transcripts;
use FormBundle\Entities\TranscriptProperties;

class TranscriptService
{
    public $transcriptsRepository;

    public $transcriptPropsRepository;

    public function __construct()
    {
        $this->transcriptsRepository = app('registry')->getManager('default')->getRepository(Transcripts::class);
        $this->transcriptPropsRepository = app('registry')->getManager('default')->getRepository(TranscriptProperties::class);
    }

    /**
     *
     * 创建成绩单
     */
    public function create($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'company_id' => $params['company_id'],
                'transcript_name' => $params['transcript_name'],
                'transcript_status' => isset($params['transcript_status']) ? $params['transcript_status'] : false,
                'template_name' => $params['template_name'],
            ];
            $transcriptResult = $this->transcriptsRepository->create($data);

            $transcriptProps = [];
            if (isset($params['evaluateItems']) && $params['evaluateItems']) {
                foreach ($params['evaluateItems'] as $v) {
                    if (isset($v['prop_name'])) {
                        $tmp = [
                            'transcript_id' => $transcriptResult['transcript_id'],
                            'company_id' => $params['company_id'],
                            'prop_name' => $v['prop_name'],
                            'prop_unit' => $v['prop_unit'],
                        ];
                        $transcriptProps[] = $this->transcriptPropsRepository->create($tmp);
                    }
                }
            }
            $transcriptResult['evaluate_items'] = $transcriptProps;

            $conn->commit();
            return $transcriptResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     *
     * 更新成绩单
     */
    public function update($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'company_id' => $params['company_id'],
                'transcript_name' => $params['transcript_name'],
                'transcript_status' => isset($params['transcript_status']) ? $params['transcript_status'] : 'off',
                'template_name' => $params['template_name'],
            ];
            $transcriptResult = $this->transcriptsRepository->update($params['transcript_id'], $data);
            $oldTtranscriptProps = $this->transcriptPropsRepository->getByTranscriptId($params['company_id'], $params['transcript_id']);
            $oldTtranscriptPropIds = [];
            $transcriptProps = [];
            if (!$oldTtranscriptProps && isset($params['evaluateItems']) && $params['evaluateItems']) {
                foreach ($params['evaluateItems'] as $v) {
                    if (isset($v['prop_name'])) {
                        $tmp = [
                            'transcript_id' => $transcriptResult['transcript_id'],
                            'company_id' => $params['company_id'],
                            'prop_name' => $v['prop_name'],
                            'prop_unit' => $v['prop_unit'],
                        ];
                        $transcriptProps[] = $this->transcriptPropsRepository->create($tmp);
                    }
                }
            } else {
                $newTtranscriptPropIds = array_column($params['evaluateItems'], 'prop_id');
                $oldTtranscriptPropIds = array_column($oldTtranscriptProps, 'prop_id');
                $delTtranscriptPropIds = array_diff($oldTtranscriptPropIds, $newTtranscriptPropIds);
                if ($delTtranscriptPropIds) {
                    foreach ($delTtranscriptPropIds as $propId) {
                        $this->transcriptPropsRepository->delete($propId);
                    }
                }
                if (isset($params['evaluateItems']) && $params['evaluateItems']) {
                    foreach ($params['evaluateItems'] as $v) {
                        $tmp = [
                            'transcript_id' => $transcriptResult['transcript_id'],
                            'company_id' => $params['company_id'],
                            'prop_name' => $v['prop_name'],
                            'prop_unit' => $v['prop_unit'],
                        ];
                        if (isset($v['prop_id']) && $v['prop_id']) {
                            $transcriptProps[] = $this->transcriptPropsRepository->update($v['prop_id'], $tmp);
                        } else {
                            $transcriptProps[] = $this->transcriptPropsRepository->create($tmp);
                        }
                    }
                }
            }
            $transcriptResult['evaluate_items'] = $transcriptProps;
            $conn->commit();
            return $transcriptResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     *
     * 获取用户成绩单
     */
    public function getInfo($companyId, $transcriptId)
    {
        $transcript = $this->transcriptsRepository->get($companyId, $transcriptId);
        $transcriptProps = $this->transcriptPropsRepository->getByTranscriptId($companyId, $transcriptId);

        $result = array_merge($transcript, ['indicators' => $transcriptProps]);

        return $result;
    }

    /**
     *
     * 删除成绩单
     */
    public function delete($transcriptId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->transcriptsRepository->delete($transcriptId);
            $result = $this->transcriptPropsRepository->deleteAllBy($transcriptId);
            $conn->commit();
            return ['status' => $result];
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
