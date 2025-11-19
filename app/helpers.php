<?php

use CompanysBundle\Services\CompanysService;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

if (! function_exists('normalize')) {
    /**
     * 简单的normalize方式, 复杂的可直接使用 app('normalizer.object')
     *
     * @param object $object
     * @return array
     */

    function normalize($object)
    {
        $objectNormalizer =  new ObjectNormalizer();
        return $objectNormalizer->normalize($object);
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return true;
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return rtrim(app()->basePath('public/' . $path), '/');
    }
}

if (!function_exists('ismobile')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function ismobile($mobile)
    {
        if (!preg_match('/^1[3456789]{1}[0-9]{9}$/', trim($mobile))) {
            return false;
        }

        return true;
    }
}

if (!function_exists('istel')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function istel($mobile)
    {
        if (!preg_match('/^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$/', trim($mobile))) {
            return false;
        }

        return true;
    }
}

if (!function_exists('isurl')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function isurl($url)
    {
        if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('isemail')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function isemail($email)
    {
        if (!preg_match('/([a-z0-9]*[-_\\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\\.][a-z]{2,3}([\\.][a-z]{2})?/i', trim($email))) {
            return false;
        }

        return true;
    }
}

if (!function_exists('format_queue_delay')) {
    /**
     * 格式化延时队列时间
     *
     * @param string $delayTime 延时时间 单位/秒
     * @return string
     */
    function format_queue_delay($delayTime)
    {
        //如果小于10分钟，则处理为延时队列，应当使用正常队列
        if ($delayTime < 600) {
            return 0;
        }

        //如果延时小于一个小时，则以10分钟延时一次
        if ($delayTime <= 3600) {
            return floor($delayTime / 600) * 600;
        }

        //如果延时是大于一个小时的，则单位按照小于来算
        return floor($delayTime / 3600) * 3600;
    }
}

if (!function_exists('validator_params')) {
    /**
     * 验证指定参数规则
     *
     * @param array $payload 验证参数
     * @param array $rules 验证规则
     * @return string
     */
    function validator_params($payload, $rules, $returnFirst = true)
    {
        $ruleArr = array();
        $ruleInfoArr = array();
        foreach ($rules as $column => $row) {
            if (isset($row[0]) && $row[0]) {
                $ruleArr[$column] = $row[0];
                $ruleInfoArr[$column . '.*'] = $row[1];
            }
        }

        $validator = app('validator')->make($payload, $ruleArr, $ruleInfoArr);

        $message = false;

        if ($validator->fails()) {
            // 如果只要返回一个错误信息
            if ($returnFirst) {
                $message = $validator->errors()->first();
            } else {
                $messageArr = $validator->errors()->toArray();
                foreach ($messageArr as $col => $errorRow) {
                    $message[$col] = $errorRow[0];
                }
            }
        }

        return $message;
    }
}

if (!function_exists('validation')) {
    /**
     * 参数验证
     * @param array $requestData 需要被验证的数据
     * @param array $rule 验证的规则
     * @param array $alertMessage 对应规则出错时提示的错误
     * @return MessageBag|null
     */
    function validation(array $requestData, array $rule, array $alertMessage = []): ?MessageBag
    {
        $validator = app('validator')->make($requestData, $rule, $alertMessage);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return null;
    }
}

if (!function_exists('format_filesize')) {

    function format_filesize($filesize)
    {
        $bytes = floatval($filesize);
        switch ($bytes) {
            case $bytes < 1024:
                $result = $bytes . 'B';
                break;
            case ($bytes < pow(1024, 2)):
                $result = strval(round($bytes / 1024, 2)) . 'KB';
                break;
            default:
                $result = $bytes / pow(1024, 2);
                $result = strval(round($result, 2)) . 'MB';
                break;
        }
        return $result;
    }
}

if (!function_exists('array_bind_key')) {
    /**
     * 根据传入的数组和数组中值的键值，将对数组的键进行替换
     *
     * @param array $array
     * @param string $key
     */
    function array_bind_key($array, $key)
    {
        foreach ((array)$array as $value) {
            if (!empty($value[$key])) {
                $k = $value[$key];
                $result[$k] = $value;
            }
        }
        return $result ?? [];
    }
}

if (!function_exists('remove_emoji')) {
    /**
     * 去除emoji表情
     *
     * @param string $str
     */
    function remove_emoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str
        );

        return $str;
    }
}

if (!function_exists('substr_cut')) {
    /**
     * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
     * @param string $user_name 姓名
     * @return string 格式化后的姓名
     */
    function substr_cut($user_name)
    {
        $strlen = mb_strlen($user_name, 'utf-8');
        if (1 == $strlen) return $user_name;
        $firstStr = mb_substr($user_name, 0, 1, 'utf-8');
        $lastStr = mb_substr($user_name, -1, 1, 'utf-8');
        return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
    }
}

if (!function_exists('array_merge_deep')) {
    /**
     * 合并多位数组数组
     * @param $arr1
     * @param $arr2
     * @return array
     */
    function array_merge_deep($arr1, $arr2)
    {
        $merged = $arr1;

        foreach ($arr2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = array_merge_deep($merged[$key], $value);
            } elseif (is_numeric($key)) {
                if (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}

if (!function_exists('get_area_by_lat_lng')) {
    /**
    * 根据经纬度获取地区信息
    * @param $lat:腾讯地图经度
    * @param $lng:腾讯地图纬度
    */
    function get_area_by_lat_lng($lat, $lng) {
        try {
            return (new \ThirdPartyBundle\Services\Map\Tencent\MapService())->getPositionByLatAndLng((string)$lat, (string)$lng);
        } catch (Exception $exception) {
            return [];
        }
//        $client = new \GuzzleHttp\Client();
//        $uri = 'https://apis.map.qq.com/ws/geocoder/v1/';
//        $location = $lat . ',' . $lng;
//        $options = [
//            'query' => [
//                'key' => env('TENCENT_LBS_KEY'),
//                'location' => $location,
//                'sig' => md5('/ws/geocoder/v1/?key=' . env('TENCENT_LBS_KEY') . '&location=' . $location . env('TENCENT_LBS_SECERT_KEY'))
//            ],
//            'timeout' => 5
//        ];
//        $retJson = $client->request('GET', $uri, $options);
//        return json_decode($retJson->getBody(), 1);
    }
}


if (!function_exists('get_latlng_by_address')) {
    /**
    * 根据地址获取地区信息
    * @param $address:详细地址  北京市东城区东长安街
    */
    function get_latlng_by_address($address) {
        try {
            return (new \ThirdPartyBundle\Services\Map\Tencent\MapService())->getLatAndLngByPosition([
                "address" => (string)$address
            ]);
        } catch (Exception $exception) {
            return [];
        }
//        $address = str_replace(array("\r\n", "\r", "\n", "null", ' '), "", $address);
//        $client = new \GuzzleHttp\Client();
//        $uri = 'https://apis.map.qq.com/ws/geocoder/v1/';
//        $url = sprintf("/ws/geocoder/v1/?address=%s&key=%s", $address, env('TENCENT_LBS_KEY'));
//        $sig = md5($url.env('TENCENT_LBS_SECERT_KEY'));
//        $options = [
//            'query' => [
//                'key' => env('TENCENT_LBS_KEY'),
//                'address' => $address,
//                'sig' => $sig
//            ],
//            'timeout' => 5
//        ];
//        $retJson = $client->request('GET', $uri, $options);
//        return json_decode($retJson->getBody(), 1);
    }
}

if (!function_exists('config_ext')) {
    /**
     * 配置文件扩展辅助函数
     *
     * 详细使用方法可以参考 filesystems.php 和 filesystems.ext.php
     *
     * @param string $configName 需要扩展的配置文件名
     * @return array             返回 {$configName}.ext.php 中的数组集合
     */
    function config_ext($configName)
    {
        $configPath = base_path('config/' . $configName . '.ext.php');
        if (!file_exists($configPath)) {
            return collect([]);
        }
        $data = require $configPath;
        return collect($data);
    }
}

if (!function_exists('get_distance')) {

    /**
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @param float $radius 星球半径 KM
     * @return float
     */
    function get_distance($lat1, $lon1, $lat2, $lon2, $radius = 6378.137)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;

        $rad = floatval(M_PI / 180.0);

        $lat1 = floatval($lat1) * $rad;
        $lon1 = floatval($lon1) * $rad;
        $lat2 = floatval($lat2) * $rad;
        $lon2 = floatval($lon2) * $rad;

        $theta = $lon2 - $lon1;

        $dist = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($theta));

        if ($dist < 0) {
            $dist += M_PI;
        }
        $dist = $dist * $radius;
        return round($dist, 2);
    }
}

if (!function_exists('array_to_tree')) {
    /**
     * Notes: 把 数组 转换成 树形 结构
     * Author:Michael-Ma
     * Date:  2020年06月04日 19:57:24
     *
     * @param $source
     * @param int $parentid
     *
     * @return array
     */
    function array_to_tree($source, $parentid = 0)
    {
        $trees = [];
        foreach ($source as $key => $item) {
            if ($item['parentid'] == $parentid) {
                $item['children'] = array_to_tree($source, $item['id']);
                $trees[] = $item;
            }
        }
        return $trees;
    }
}

if (!function_exists('make_tree')) {
    /**
     * Notes: 空间复杂度 转化树形结构
     * Author:Michael-Ma
     * Date:  2020年06月04日 21:05:45
     *
     * @param $arr
     *
     * @return array
     */
    function make_tree($arr)
    {
        $items = array_column($arr, null, 'id');
        $tree = [];
        foreach ($items as $k => $v) {
            if (isset($items[$v['parentid']])) {
                $items[$v['parentid']]['children'][] = &$items[$k];
            } else {
                $tree[] = &$items[$k];
            }
        }
        return $tree;
    }
}

if (!function_exists('make_tree_category')) {
    
    function make_tree_category(array $arr = [], string $indexKey = 'category_id', string $parentKey = 'parent_id', string $childrenKey = 'children'): array
    {
        $items = array_column($arr, null, $indexKey);
        $tree  = [];
        foreach ($items as $k => $v) {
            if ($items[$v[$parentKey]] ?? 0) {
                $items[$v[$parentKey]][$childrenKey][] = &$items[$k];
            } else {
                $items[$k][$childrenKey] = [];
                $tree[] = &$items[$k];
            }
        }
        return $tree;
    }
}

if (!function_exists('wlog')) {
    /**
     * Notes: 写日志
     * Author:Michael-Ma
     * Date:  2020年03月06日 23:16:53
     *
     * @param $result
     */
    function wlog($result)
    {
        // 正式环境 不打印日志
        /* !in_array(env('APP_ENV', 'local'), [
             'production',
             'staging',
         ]) && */
        app('log')->debug(PHP_EOL . PHP_EOL . json_encode($result));
        /* 用法复制 或者 制作代码块模板快捷键 wl + tap 即可生成

        wlog([
            'desc' => '描述信息',
            'file'  => __FILE__,
            'class' => __CLASS__,
            'line'  => __LINE__,
            'data'  => $result,
        ]);

        */
    }
}

if (!function_exists('assoc_unique')) {
    function assoc_unique($arr, $key, $sortKey, $sortBy = 'ASC')
    {
        // 取得列的列表
        foreach ($arr as $tempK => $row) {
            $keyarr[$tempK] = $row[$sortKey];
        }
        if ('ASC' == $sortBy) {
            $sort = SORT_ASC;
        } else {
            $sort = SORT_DESC;
        }
        array_multisort($keyarr, $sort, $arr);
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (!isset($v[$key]) || in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;

    }
}

if (!function_exists('esub')) {
    function esub($str, $length = 0)
    {
        if ($length < 1) {
            return $str;
        }

        if (strlen($str) > 128) {
            $str = mb_substr($str, 0, $length, 'utf-8') . '...';
        }

        return $str;
    }
}


if (!function_exists('jsonDecode')) {
    /**
     * 对json字符串做解码，过滤掉语法错误
     * @param mixed $value
     * @return mixed 如果解码失败返回null，解码成功则返回传入的值的类型，除了字符，字符串会被转成数组
     */
    function jsonDecode($value)
    {
        if (is_array($value)) {
            // 数组直接返回
            return $value;
        } elseif (is_object($value)) {
            // 对象不能被解码，直接返回null
            return null;
        } else {
            // value为null或false，返回的是也是null
            $data = json_decode((string)$value, true);
            // 如果解码有错误就返回null
            return json_last_error() == JSON_ERROR_NONE ? $data : null;
        }

    }
}

if (!function_exists('jsonEncode')) {
    /**
     * 对json字符串做编码
     * @param mixed $value
     * @return string|null 如果编码失败返回null，编码成功则返回字符串
     */
    function jsonEncode($value): ?string
    {
        $data = json_encode($value, JSON_UNESCAPED_UNICODE);
        return json_last_error() == JSON_ERROR_NONE ? $data : null;
    }
}

if (!function_exists('getamap_latlng_by_address')) {
    /**
     * 高德-根据地址获取地区信息
     * @param $address :详细地址  北京市东城区东长安街
     */
    function getamap_latlng_by_address($address)
    {
        $address = str_replace(array("\r\n", "\r", "\n", "null", ' '), "", $address);
        $client = new \GuzzleHttp\Client();
        $uri = 'https://restapi.amap.com/v3/geocode/geo?parameters';
        $options = [
            'query' => [
                'address' => $address,
                'key' => env('AMAP_LBS_KEY'),
            ],
            'timeout' => 5
        ];
        $retJson = $client->request('GET', $uri, $options);
        $result = json_decode($retJson->getBody(), 1);
        return $result['geocodes'][0]['location'] ?? '';
    }
}

if (!function_exists('getFirstCharter')) {
    /**
     * 获取首字母
     * @param $str :字符串
     */
    function getFirstCharter($str)
    {
        if (empty($str)) {
            return '';
        }
        if (is_numeric($str[0])) return $str[0];// 如果是数字开头 则返回数字
        $fchar = ord($str[0]);
        if ($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str[0]); //如果是字母则返回字母的大写
        // $s1 = iconv('UTF-8', 'gb2312', $str);
        $s1 = mb_convert_encoding($str, 'GB2312', 'UTF-8');
        // $s2 = iconv('gb2312', 'UTF-8', $s1);
        $s2 = mb_convert_encoding($s1, 'UTF-8', 'GB2312');
        $s = $s2 == $str ? $s1 : $str;
        $asc = ord($s[0]) * 256 + ord($s[1]) - 65536;
        if ($asc >= -20319 && $asc <= -20284) return 'A';//这些都是汉字
        if ($asc >= -20283 && $asc <= -19776) return 'B';
        if ($asc >= -19775 && $asc <= -19219) return 'C';
        if ($asc >= -19218 && $asc <= -18711) return 'D';
        if ($asc >= -18710 && $asc <= -18527) return 'E';
        if ($asc >= -18526 && $asc <= -18240) return 'F';
        if ($asc >= -18239 && $asc <= -17923) return 'G';
        if ($asc >= -17922 && $asc <= -17418) return 'H';
        if ($asc >= -17417 && $asc <= -16475) return 'J';
        if ($asc >= -16474 && $asc <= -16213) return 'K';
        if ($asc >= -16212 && $asc <= -15641) return 'L';
        if ($asc >= -15640 && $asc <= -15166) return 'M';
        if ($asc >= -15165 && $asc <= -14923) return 'N';
        if ($asc >= -14922 && $asc <= -14915) return 'O';
        if ($asc >= -14914 && $asc <= -14631) return 'P';
        if ($asc >= -14630 && $asc <= -14150) return 'Q';
        if ($asc >= -14149 && $asc <= -14091) return 'R';
        if ($asc >= -14090 && $asc <= -13319) return 'S';
        if ($asc >= -13318 && $asc <= -12839) return 'T';
        if ($asc >= -12838 && $asc <= -12557) return 'W';
        if ($asc >= -12556 && $asc <= -11848) return 'X';
        if ($asc >= -11847 && $asc <= -11056) return 'Y';
        if ($asc >= -11055 && $asc <= -10247) return 'Z';
        return null;
    }
}

if (!function_exists('get_user_device')) {
    /**
     * 获取用户设备
     */
    function get_user_device($companyId, $host)
    {
        if ($host == 'miniprogram') {
            return $host;
        }
        // 从 URL 中取得主机名
        preg_match("/^(\w+):\/\/([^\/]+)/i", $host, $matches);
        $host = $matches[2] ?? '';
        if (!$host) {
            return 'miniprogram';
        }

        $companysService = new CompanysService();
        $filter['company_id'] = $companyId;
        $result = $companysService->getDomainInfo($filter);

        if ($host == $result['h5_domain'] || $host == $result['h5_default_domain']) {
            return 'h5';
        }

        if ($host == $result['pc_domain'] || $host == $result['pc_default_domain']) {
            return 'pc';
        }

        return 'miniprogram';
    }
}

if (!function_exists('get_app_pay_type')) {
    /**
     * 获取支付类型
     */
    function get_app_pay_type($payType, $userDevice = '')
    {
        $appPayType = '07'; //微信小程序
        if (empty($userDevice)) {
            return $appPayType;
        }
        switch ($payType) {
            case 'wxpay':
            case 'hfpay':
                if ($userDevice == 'pc') {
                    $appPayType = '01'; //微信正扫
                }
                if ($userDevice == 'h5') {
                    $appPayType = '12'; //微信H5支付(直连)
                }
                if ($userDevice == 'miniprogram') {
                    $appPayType = '07';
                }
                break;
            case 'wxpaypc':
                $appPayType = '01';
                break;
            case 'alipay':
            case 'alipayh5':
            case 'alipayapp':
                $appPayType = '13';
                break;
            case 'wxpayh5':
            case 'wxpayjs':
                $appPayType = '12';
                break;
            case 'wxpayapp':
                $appPayType = '09';
                break;
            default:
                $appPayType = '00';
        }
        return $appPayType;
    }
}

if (!function_exists('randomFromDev')) {
    /**
     * 取随机码，用于生成session
     * @param int $len 随机码的长度
     * @return bool|string
     */
    function randomFromDev(int $len)
    {
        $fp = @fopen('/dev/urandom', 'rb');
        $result = '';
        if ($fp !== FALSE) {
            $result .= @fread($fp, $len);
            @fclose($fp);
        } else {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');
        return substr($result, 0, $len);
    }
}

if (! function_exists('fixedencrypt')) {
    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    function fixedencrypt($value, $keyid='key')
    {
        if (!config('common.encrypt_sensitive_data')) return $value;

        try {
            //防止重复加密
            app('fixedencrypt')->setKey($keyid)->decrypt($value);
            return $value;
        } catch (\Exception $e) {
            return app('fixedencrypt')->setKey($keyid)->encrypt($value);
        }
    }
}


if (! function_exists('fixeddecrypt')) {
    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    function fixeddecrypt($value, $keyid='key')
    {
        if (!config('common.encrypt_sensitive_data')) return $value;

        if (!$value) return $value;
        try {
            return app('fixedencrypt')->setKey($keyid)->decrypt($value);
        } catch (\Exception $e) {
            return $value;
        }
    }
}


if (! function_exists('data_masking')) {
    /**
     * 数据脱敏.
     *
     * @param string $strType 脱敏类型，姓名、生日、手机号……
     * @param string $str 被脱敏文本
     * @return string 脱敏后文本
     */
    function data_masking(string $strType, string $str)
    {
        if (! trim($str) && trim($str) == '') {
            return $str;
        }
        switch ($strType) {
            case 'uname': // 如果是字符和就显示前后各一位，如果是手机号则展示前3位和后4位
                if (preg_match('/^1[3456789]{1}[0-9]{9}$/', $str)) {
                    $maskStr = substr_replace($str, '****', 3, 4);
                } else {
                    //普通账号不做脱敏展示
                    $maskStr = $str;
                    //$maskStr = str_repeat("*", mb_strlen($str)-1) . mb_substr($str, -1, 1);
                }
                break;
            case 'truename': // 只展示最后一个字
                $maskStr = str_repeat('*', mb_strlen($str) - 1) . mb_substr($str, -1, 1);
                break;
            case 'birthday': // 仅展示最后一位
                $maskStr = '****-**-*' . substr($str, -1, 1);
                break;
            case 'bankcard': // 展示前4位和后3位
                $maskStr = substr($str,0,4)."************".substr($str,-4,3);
                break;
            case 'idcard': // 只展示末位
                $maskStr = str_repeat('*', strlen($str) - 1) . substr($str, -1, 1);
                break;
            case 'mobile': // 展示前3位和后4位
                $maskStr = substr_replace($str, '******', 3, 6);
                break;
            case 'email': // 名字部分只展示首位和末位
                $pos = stripos($str, '@', 0);
                $maskStr = substr($str, 0, 1) . str_repeat('*', $pos - 2) . substr($str, $pos - 1, 1) . substr($str, $pos);
                break;
            case 'address': // 地址的详细地址部分
                $maskStr = '******';
                break;
            case 'detailedaddress':// 详细地址，只展示前6位
                $maskStr = mb_substr($str, 0, 6). '******';
                break;
            case 'image':
                $maskStr = 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/10/21/6522d21e446741584632bc04601feb6fBy5oX5syerwAs6FP1cyfOWJd90z5Mb3g';
                break;
            case 'sex':
                $maskStr = '*';
                break;
            default:
                $maskStr = $str;
                break;
        }
        return $maskStr;
    }
}

if (! function_exists('have_special_char')) {
    /**
     * 判断是否含有特殊字符
     * 因数据库存储格式不统一，有些地方判断下是否含有特殊字符
     *
     * @param string $str 待判断的字符串
     * @return boolean 判断结果
     */
    function have_special_char($str)
    {
        $length = mb_strlen($str);
        $array = [];
        for ($i=0; $i<$length; $i++) {
            $array[] = mb_substr($str, $i, 1, 'utf-8');
            if( strlen($array[$i]) >= 4 ){
                return true;

            }
        }
        return false;
    }
}

if (! function_exists('mb_trim')) {
    function mb_trim($string, $trim_chars = '\s')
    {
        return preg_replace('/^['.$trim_chars.']*(?U)(.*)['.$trim_chars.']*$/u', '\\1', $string);
    }
}

if (!function_exists('randValue')) {
    /**
     * 生成随机数
     * @param int $length
     * @return string
     */
    function randValue(int $length) {
        $list = array_merge(range("a", "z"), range("A", "Z"), range(0, 9));
        $count = count($list) - 1;

        $result = "";
        for ($i = 0; $i < $length; $i++) {
            $result .= $list[mt_rand(0, $count)];
        }
        return $result;
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端IP地址
     * @return string
     */
    function get_client_ip()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '0.0.0.0';
        }
        return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : '';
    }

}

// 获取doctrine repository
// 使用代理类，改变默认的repository类的方法 ，依赖于代理类
if (!function_exists('getRepositoryLangue')) {
    function getRepositoryLangue($entityName)
    {
        $repository =  app('registry')->getManager('default')->getRepository($entityName);
        $classObj = new \CompanysBundle\Services\RepositoryInterceptor($repository);

        return $classObj;
    }
}