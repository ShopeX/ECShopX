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

namespace KaquanBundle\Interfaces;

interface KaquanInterface
{
    /**
     * add Kaquan
     *
     * @param Datainfo $dataInfo
     * @return
     */
    public function createKaquan(array $dataInfo, $appId = '');

    /**
     * get KaquanData
     *
     * @param filter $filter
     * @return array
     */
    public function getKaquanDetail($filter);

    /**
     * update Kaquan
     *
     * @param data $dataInfo
     * @return filter
     */
    public function updateKaquan($dataInfo, $appId = '');

    /**
     * delete Kaquan
     *
     * @param filter $filter
     * @return
     */
    public function deleteKaquan($filter, $appId = '');

    /**
     *  Kaquan list
     *
     * @param filter $filter
     * @return
     */
    public function getKaquanList($offset, $count, $filter = []);
}
