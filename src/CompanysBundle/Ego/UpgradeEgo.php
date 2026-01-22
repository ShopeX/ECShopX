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

namespace CompanysBundle\Ego;

use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use GuzzleHttp\Client;

class UpgradeEgo
{

    public const WORKSPACES = [
        'ecshopx-admin',
        'ecshopx-api',
        'ecshopx-vshop'
    ];

    // 默认升级包地址
    public const UPGRADE_PROJECT = 'upgrade/';
    // 默认解压文件夹地址
    public const UPGRADE_FILES = 'upgrade/files/';
    // 备份目录
    public const UPGRADE_BAKS = 'upgrade/baks/';
    // 默认请求域名
    private const BASE_URI = 'https://www.shopex.cn';
    // private const BASE_URI = 'http://gwucapi.uc.ex-sandbox.com'; // 测试环境
    // 用户活跃度
    private const ECX_ACTIVE_API_URL = '/api/usercenter/open/ecx/active';
    // 补丁包列表
    private const ECX_PARCH_API_URL = '/api/usercenter/open/ecx/patch';
    // 下载包链接
    private const ECX_DOWNLOAD_API_URL = '/api/usercenter/open/ecx/download';
    // 更新日志
    private const ECX_DOCS_API_URL = '/api/usercenter/open/ecx/docs';
    // 获取安装协议
    private const ECX_AGREEMENT_API_URL = '/api/usercenter/open/ecx/agreement';
    // 确认安装协议
    private const ECX_AGREEMENT_CONFIRM_API_URL = '/api/usercenter/open/ecx/agreement/confirm';
    // 补丁包更新文件
    private $newFiles = [];
    // php代码版本
    private $version;
    // 免费版补丁目录名
    private $mark = 'ecshopx2_free_patch';
    // 加密密钥
    private $secret = 'bcyi8N5EQTKG7LcK';

    public function __construct()
    {
        $this->version = '';
    }

    /**
     * 升级脚本
     * @throws \Exception
     */
    public function upgrade($companyId)
    {
        set_time_limit(0);
        // 检测 storage/
        if (!is_writable(storage_path())) {
            throw new \Exception("创建目录失败，目录没有写权限！");
        }
        // 检测目录 storage/upgrade/
        $upgradePath = storage_path(self::UPGRADE_PROJECT);
        if (!is_dir($upgradePath)) {
            mkdir($upgradePath, 0777, true);
        }
        if (!is_writable($upgradePath)) {
            throw new \Exception("创建目录失败，目录没有写权限！");
        }
        // 检测目录 storage/upgrade/files/
        $todir = storage_path(self::UPGRADE_FILES);
        if (!is_dir($todir)) {
            mkdir($todir, 0777, true);
        }
        if (!is_writable($todir)) {
            throw new \Exception("创建目录失败，目录没有写权限！");
        }

        // 检测目录 storage/upgrade/baks/
        $baks = storage_path(self::UPGRADE_BAKS);
        if (!is_dir($baks)) {
            mkdir($baks, 0777, true);
        }
        if (!is_writable($baks)) {
            throw new \Exception("创建目录失败，目录没有写权限！");
        }
        $current_bak = 'bak'.date('YmdHi').'/';
        $current_bak_path = storage_path(self::UPGRADE_BAKS.$current_bak);
        if (is_dir($current_bak_path)) {
            throw new \Exception("备份目录已存在，请稍后再更新");
        }
        mkdir($current_bak_path, 0777, true);

        $operatorsService = new OperatorsService();
        $passportUid = $operatorsService->getPassportUid($companyId);
        $upgradeFileName = $this->getDownloadFile($passportUid);
        $zipFile = $upgradePath . $upgradeFileName;

        $this->unlinkBakFiles();
        if (is_dir($todir)) {
            $this->delDir($todir);
            mkdir($todir, 0777, true);
        }
        // 解压文件
        $this->dealTarGz($zipFile, $todir);
        $this->newFiles = $this->scanDir($todir . $this->mark);
        $this->checkAccess();
        $this->mergeFile($current_bak_path);
        $this->delDir($todir);
        unlink($zipFile);
        return true;
    }

    /**
     * 检测版本升级
     * @return mixed
     */
    public function detectVersion()
    {
        $versionResult = $this->getPatch();
        $packageVersion = $versionResult['data']['package']['version'] ?? '';
        if (!$packageVersion) {
            throw new ResourceException('版本获取失败');
        }
        $result = $versionResult['data']['package'];
        $result['upgrade_status'] = false;
        if (version_compare($packageVersion, $this->getSelfVersion()) > 0) {
            $result['upgrade_status'] = true;
        }
        $result['local_version'] = $this->getSelfVersion() ?? '-';
        return $result;
    }

    /**
     * 获取版本列表
     * @return array 版本列表
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPatch(): array
    {
        $params = [
            'timestamp' => time(),
        ];
        $params['sign'] = $this->getSign($params, $this->secret);
        $client = new Client(['base_uri' => self::BASE_URI]);
        $response = $client->post(self::ECX_PARCH_API_URL, ['verify'=>false, 'form_params' => $params]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        if (isset($result['status']) && $result['status'] !== 'success') {
            throw new ResourceException('获取升级版本失败，请联系管理人员');
        }
        return $result;
    }

    /**
     * 版本活跃度统计
     * @param string $passportUid shopexid
     * @return boolean
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getActive(string $passportUid)
    {
        // 非免费版不做验证
        $license = $this->getSwooleLicense();
        if (!isset($license['Product_type']) || ($license['Product_type'] != 'ECSHOPX2_FREE')) {
            return true;
        }
        if (!$passportUid) {
            return true;
        }
        $params = [
            'shopexid' => $passportUid,
            'product_type' => $license['Product_type'],
            'version' => $this->getSelfVersion() ?? '-',
            'source' => $_SERVER['HTTP_HOST'],
            'ip' => get_client_ip(),
            'timestamp' => time(),
        ];
        $params['sign'] = $this->getSign($params, $this->secret);
        $client = new Client(['base_uri' => self::BASE_URI]);
        $response = $client->post(self::ECX_ACTIVE_API_URL, ['verify'=>false, 'form_params' => $params]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        if (isset($result['data']['agreement_flag']) && !$result['data']['agreement_flag']) {
            throw new \Exception('您还没有同意安装协议！', 400401);
        }
        return true;
    }

    /**
     * 获取更新日志列表
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDocs(): array
    {
        $params = [
            'timestamp' => time(),
        ];
        $params['sign'] = $this->getSign($params, $this->secret);
        $client = new Client(['base_uri' => self::BASE_URI]);
        $response = $client->post(self::ECX_DOCS_API_URL, ['verify'=>false, 'form_params' => $params]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        if (isset($result['status']) && $result['status'] !== 'success') {
            $result = [];
        }
        return $result;
    }

    /**
     * 获取安装协议
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAgreement()
    {
        $params = [
            'timestamp' => time(),
        ];
        $params['sign'] = $this->getSign($params, $this->secret);
        $client = new Client(['base_uri' => self::BASE_URI]);
        $response = $client->post(self::ECX_AGREEMENT_API_URL, ['verify'=>false, 'form_params' => $params]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        $agreement = [
        ];
        if (isset($result['data']['agreement'])) {
            $agreement = $result['data']['agreement'];
        }
        return $agreement;
    }

    /**
     * 确认安装协议
     * @param string $passportUid shopexid
     * @param integer $agreement_id agreement_id
     * @return boolean
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function confirmAgreement(string $passportUid, $agreement_id)
    {
        // 非免费版不做验证
        $license = $this->getSwooleLicense();
        if (!isset($license['Product_type']) || ($license['Product_type'] != 'ECSHOPX2_FREE')) {
            return true;
        }
        if (!$passportUid || !$agreement_id) {
            return true;
        }
        $params = [
            'timestamp' => time(),
            'shopexid' => $passportUid,
            'agreement_id' => $agreement_id,
        ];
        $params['sign'] = $this->getSign($params, $this->secret);
        $client = new Client(['base_uri' => self::BASE_URI]);
        $response = $client->post(self::ECX_AGREEMENT_CONFIRM_API_URL, ['verify'=>false, 'form_params' => $params]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        $return = false;
        if (isset($result['status']) && $result['status'] == 'success') {
            $return = true;
        }
        return $return;
    }

    /**
     * 获取下载文件
     * @param string $passportUid shopexid
     * @return mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDownloadFile(string $passportUid)
    {
        $updateVersion = $this->getNextVersion();
        if (!$updateVersion) {
            throw new ResourceException('已是最新版本，无需升级');
        }
        $params = [
            'timestamp' => time(),
            'shopexid' => $passportUid,
            'product_type' => $updateVersion['product_type'],
            'patch_uuid' => $updateVersion['uuid'],
        ];
        $params['sign'] = $this->getSign($params, $this->secret);
        $client = new Client(['base_uri' => self::BASE_URI]);
        $response = $client->post(self::ECX_DOWNLOAD_API_URL, ['verify'=>false, 'form_params' => $params]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        if (isset($result['status']) && $result['status'] !== 'success') {
            throw new ResourceException('获取升级版本失败，请联系管理人员');
        }
        $downloadUrl = $result['data']['download_url'];
        $basename = pathinfo($downloadUrl)['basename'];
        $upgradeFileName = explode('?', $basename)[0];
        $downloadUrl = sprintf("https:%s", $result['data']['download_url']);
        file_put_contents(storage_path(self::UPGRADE_PROJECT) . $upgradeFileName, file_get_contents($downloadUrl));
        return $upgradeFileName;
    }

    /**
     * 解压缩文件
     * @param string $file 文件路径
     * @param string $todir 解压路径
     * @return bool 是否解压成功
     */
    private function dealZip(string $file, string $todir): bool
    {
        if (trim($file) == '') {
            return false;
        }

        if (trim($todir) == '') {
            return false;
        }
        !is_dir($todir) && mkdir($todir, 0777, true);
        $zip = new \ZipArchive();
        if ($zip->open($file) === true) {
            $zip->extractTo($todir);
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * 解压缩文件
     * @param string $file 文件路径
     * @param string $todir 解压路径
     * @return bool 是否解压成功
     */
    private function dealTarGz(string $file, string $todir): bool
    {
        if (trim($file) == '') {
            return false;
        }

        if (trim($todir) == '') {
            return false;
        }
        !is_dir($todir) && mkdir($todir, 0777, true);

        try {
            $phar = new \PharData($file);
            $phar->extractTo($todir, null, true);
        } catch (\Throwable $throwable) {
            throw new ResourceException("解压失败");
        }
        return true;
    }

    /**
     * 删除文件夹下所有文件
     * @param string $path 文件路径
     */
    private function delDir(string $path, bool $isDirSelf = true): void
    {
        $path = rtrim($path, '/') . '/';
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach ($p as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    //如果是目录则递归子目录，继续操作
                    if (is_dir($path . $val)) {
                        //子目录中操作删除文件夹和文件
                        $this->delDir($path . $val . '/');
                        //目录清空后删除空文件夹
                        @rmdir($path . $val . '/');
                    } else {
                        //如果是文件直接删除
                        unlink($path . $val);
                    }
                }
            }
            if ($isDirSelf) {
                @rmdir($path);
            }
        }
    }

    /**
     * 删除空文件夹
     * @param string $dir
     * @return bool
     */
    private function delEmptyDir(string $dir): void
    {
        $dir = rtrim($dir, '/') . '/';
        if (@rmdir($dir)) {
            $this->delEmptyDir(dirname($dir));
        }
    }

    /**
     * 删除文件以及空文件夹
     * @param string $dir
     * @return bool
     */
    private function delFileAndEmptyDir(string $file): void
    {
        unlink($file);
        $dir = dirname($file);
        if (@rmdir($dir)) {
            $this->delEmptyDir(dirname($dir));
        }
    }

    /**
     * 遍历文件写入
     * @param string $path
     * @return array
     */
    private function scanDir(string $path): array
    {
        $updateFiles = [];
        $path = rtrim($path, '/') . '/';
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach ($p as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    //如果是目录则递归子目录，继续操作
                    $dir = $path . $val;
                    if (is_dir($dir)) {
                        $updateFiles = array_merge_deep($this->scanDir($dir), $updateFiles);
                    } else {
                        $file = $path . $val;
                        if (strstr($file, $this->mark)) {
                            $file = trim(str_replace($this->mark, '', strstr($file, 'ecshopx2_free_patch')), '/');
                            if (strpos($file, 'ecshopx-') === 0 && is_dir(storage_path('../../' . substr($file, 0, strpos($file, '/'))))) {
                                $updateFiles[] = $file;
                            }
                        }
                    }
                }
            }
        }
        return $updateFiles;
    }

    /**
     * 检测写入权限
     * @return array 要写入文件列表
     * @throws Exception
     */
    private function checkAccess(): void
    {
        $errorFiles = [];
        foreach ($this->newFiles as $file) {
            $filePath = storage_path('../../' . $file);
            if (is_file($filePath) && is_writable($filePath)) {
                continue;
            } else {
                $fileDir = dirname($filePath);
                $fileDirStatus = false;
                if (!is_dir($fileDir)) {
                    $fileDirStatus = true;
                    mkdir($fileDir, 0777, true);
                }
                if (is_writable($fileDir)) {
                    if ($fileDirStatus) {
                        $this->delEmptyDir($fileDir);
                    }
                    continue;
                }
            }
            $errorFiles[] = $file;
        }
        if ($errorFiles) {
            $errorMsg = sprintf('error message: \n');
            foreach ($errorFiles as $file) {
                $errorMsg .= sprintf("文件无权限写入，请检查权限: %s\n", $file);
            }
            throw new \Exception($errorMsg);
        }
    }

    /**
     * 合并文件
     * @param array $updateFiles 文件列表
     */
    private function mergeFile($current_bak_path): void
    {
        foreach ($this->newFiles as $file) {
            $newfiles = storage_path(self::UPGRADE_FILES . $this->mark . '/' . $file);
            $oldfiles = storage_path('../../' . $file);
            if (is_file($oldfiles)) {
                $fileDir = dirname($current_bak_path.$file);
                if (!is_dir($fileDir)) {
                    mkdir($fileDir, 0777, true);
                }
                copy($oldfiles, $current_bak_path.$file);
                // file_put_contents($this->getOldFiles(), sprintf("%s.bak" . PHP_EOL, $oldfiles), FILE_APPEND);
                // file_put_contents($oldfiles . '.bak', file_get_contents($oldfiles));
            } else {
                $fileDir = dirname($oldfiles);
                if (!is_dir($fileDir)) {
                    mkdir($fileDir, 0777, true);
                }
                file_put_contents($this->getNewFiles(), sprintf("%s" . PHP_EOL, $oldfiles), FILE_APPEND);
            }
            file_put_contents($oldfiles, file_get_contents($newfiles));
        }
        // $this->unlinkBakUpgradeFile();
        // $this->unlinkBakFiles();
    }

    /**
     * 获取覆盖新增文件路径
     * @return string 文件路径
     */
    private function getOldFiles(): string
    {
        return storage_path(sprintf('upgrade/oldfiles%s.txt', $this->version));
    }

    /**
     * 获取备份新增文件路径
     * @return string 文件路径
     */
    private function getNewFiles(): string
    {
        return storage_path(sprintf('upgrade/newfiles%s.txt', $this->version));
    }

    /**
     * 获取当前版本号
     * @return string
     */
    private function getSelfVersion()
    {
        $version_path = base_path('composer.json');
        $composer = is_file($version_path) ? file_get_contents($version_path) : '-';
        $composer = json_decode($composer, true);
        return $composer['version'] ?? '-';
    }

    /**
     * 获取下一个升级版本
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getNextVersion(): array
    {
        $versionList = $this->getPatch();
        if (!($versionList['data']['patch_list'] ?? '')) {
            throw new ResourceException('获取升级版本列表失败，请联系管理人员');
        }
        $patchList = $versionList['data']['patch_list'];
        $updateVersion = [];
        $versions = array_column($patchList, 'version');
        $versions = $this->sortVersion($versions);
        $patchList = array_column($patchList, null, 'version');
        foreach ($versions as $v) {
            if (version_compare($v, $this->getSelfVersion()) > 0) {
                $updateVersion = $patchList[$v];
                break;
            }
        }
        return $updateVersion;
    }

    /**
     * 删除备份文件
     */
    private function unlinkBakFiles(): void
    {
        // is_file($this->getOldFiles()) && unlink($this->getOldFiles());
        // is_file($this->getNewFiles()) && unlink($this->getNewFiles());
    }

    /**
     * 回滚脚本
     */
    public function rollback(): void
    {
        $this->unlinkBakUpgradeFile(true);
        $this->unlinkNewUpgradeFile();
        $this->unlinkBakFiles();
    }

    /**
     * 恢复更新文件
     * @param bool $isRollback true 恢复并删除bak文件 false 直接删除bak文件
     */
    private function unlinkBakUpgradeFile(bool $isRollback = false)
    {
        if (is_file($this->getOldFiles())) {
            $oldfiles = explode(PHP_EOL, file_get_contents($this->getOldFiles()));
            $oldfiles = array_filter($oldfiles);
            foreach ($oldfiles as $file) {
                $isRollback && file_put_contents(trim($file, '.bak'), file_get_contents($file));
                is_file($file) && unlink($file);
            }
        }
    }

    /**
     * 删除新增文件
     */
    private function unlinkNewUpgradeFile()
    {
        // 在删除新增文件
        if (is_file($this->getNewFiles())) {
            $newfiles = explode(PHP_EOL, file_get_contents($this->getNewFiles()));
            $newfiles = array_filter($newfiles);
            foreach ($newfiles as $file) {
                $this->delFileAndEmptyDir($file);
            }
        }
    }

    /**
     * 生成验证.
     * @param $params
     * @param $secret
     * @return string
     */
    private function getSign($params, $secret)
    {
        return strtoupper(md5($secret . $this->assemble($params) . $secret));
    }

    /**
     * 生成验证.
     * @param array $params 参数验证
     * @return null|string
     */
    private function assemble(array $params): string
    {
        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $val) {
            if (is_null($val)) {
                continue;
            }

            if (is_bool($val)) {
                $val = ($val) ? 1 : 0;
            }
            $sign .= $key . (is_array($val) ? json_encode($val) : $val);
        }

        return $sign;
    }

    /**
     * 版本排序
     * @param array $versions 版本列表
     * @param bool $sortRule 排序规则
     * @return mixed
     */
    private function sortVersion($versions, $sortRule = true)
    {
        foreach ($versions as $key => $value) {
            $firstArr = explode('.', $value);
            $firstArrCount = count($firstArr);
            for ($i = 0; $i < $firstArrCount; $i++) {
                $firstArr[$i] = str_pad($firstArr[$i], 2, 0, STR_PAD_LEFT);

            }
            $versions[$key] = implode('.', $firstArr);

        }
        if ($sortRule) {
            sort($versions);
        } else {
            rsort($versions);
        }
        foreach ($versions as $key => $value) {
            $firstArr = explode('.', $value);
            $firstArrCount = count($firstArr);
            for ($i = 0; $i < $firstArrCount; $i++) {
                $firstArr[$i] = intval($firstArr[$i]);

            }
            $versions[$key] = implode('.', $firstArr);

        }
        return $versions;
    }

    /**
     * 获取swoole配置,如果配置了多个license，目前只获取免费版配置
     * @return array
     */
    public function getSwooleLicense()
    {
        return ['Product_type'=>'ECSHOPX_OPENSOURCE'];
        // return ['Product_type'=>'ECSHOPX2_FREE'];
        if (!function_exists('swoole_get_license')) {
            throw new \Exception('请确认swoole_loader扩展已正确安装！');
        }
        $license_config =  swoole_get_license() ?? [];
        return reset($license_config) ?? [];
    }

}
