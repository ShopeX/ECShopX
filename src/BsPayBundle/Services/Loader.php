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

declare(strict_types=1);
/**
 * 加载入口
 */
namespace BsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;

use BsPayBundle\Sdk\core\BsPay;
use PaymentBundle\Services\Payments\BsPayService;

class Loader
{

    public static function load($companyId)
    {

        # 以下配置为开发联调时覆盖SDK的配置项，需在引入SDK的init.php之前配置以覆盖SDK初始配置
        # 设置是否调试模式，不配置默认关闭：false
        if (!defined("DEBUG")) {
            define("DEBUG", config('bspay.debug'));
        }

        # 设置调试日志路径，不配置默认为SDK同级的log目录下
        // define("LOG", dirname(__FILE__)."/log");
        if (!defined("LOG")) {
            define("LOG", config('bspay.log_dir'));
        }

        # 设置生产模式，不配置默认生产模式：true
        if (!defined("PROD_MODE")) {
            define("PROD_MODE", config('bspay.prod_mode'));
        }

        # SDK 初始化文件加载
        require_once  dirname(__FILE__). "/../Sdk/init.php";

        # 配置商户信息
    //     $config_str = '{"************* Demo演示生成环境测试商户参数 *************":"",
		  // "sys_id":"6666000108854952",
		  // "product_id":"YYZY",    
		  // "rsa_merch_private_key":"MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCxtfk3rjwdpBV81WBy5jIMcDLFdvHckhjGXkmWfaBn7euPRyetEhS4inpr7EvQ5KDUXNBPljI2NVhG/LEGZKvau1MW8j3t7dJ3gWafuVGsCiLJHU79sIRHf11nKOTykX5WxB/7MMwRnZsECuaZyCk7WPuSAlznqbDJdrZTzHhjQzMhjto1qD6+vc0OxyaBFlOY9piBtEfecsvD+6GfQ8exFqwzblJm9iZPYw02DaeUDLFO9Umn7i7gShlj/1Hh8nEM7YitpF/p26o+MC9LHWbIjgzjvNVhSRVmbvWys+3S11Zm/vux6Yzfk0H3fqrksAKSEkLEtEoYKS4wKjHdecztAgMBAAECggEACy1g4WmqCks5tsJM8K0d1L5x0w2qJK9js4ZWpop8Pk0ulbJqAm6ysvCyxnr0Qc0/eFvmFjtiKRqt1LksATTvwjAqB7Vww7hDlpSi+cTUKDfy/CdFwpsJlt2h6E0gKUmRYq+vO0NUcn8xMs3ktyNpxHvSRtqzMTbxEZrP2PFxWPzUKGNyk53FTlJ64YCoGQqWeGhA5LO6QLPHlAxIrvRf9B5dtXQr5XZXVqS9MwjtsRPvQPWiFXxlzvhJRcL/wXehcNextHzpMMgX/idB3HIpIl6XXLKiFUR4rBDJIMiQjQvS6zz2l1zpiJ0vWujVa3IY+PNefRA2ttg1DeC19GYa2QKBgQDh7AkJ7wut7p4qYAdcFEDVhFgP5mnSRyOBGWmClHYE4RIFplPiv4yO0fttAjFuCg4Zaxq49BuV3zshWOEIr72VK6wMa6Z+QbfXNr/1DT6nW+ktgXTw2G9Ts/nZiMrpcsbl7qvwChfJAPvEwnyP7Ckmd9t2WbQisuYZc+Vu8znO7wKBgQDJXskTiExEipQSOcVH5cX/ExVyj9MoLjmJhy3WTTDzGafgEoOPOfej2ZCgF6gCwugXJr+rtgdOpASk8WPACaCePdjdgQ2NVhSfV3op3TtvhgAPf3iI/zCVkZM4I1iZs6KjdHstLCKyAzCFBsowkPbfZBlFX4eO7Bk6XcIZ6x2h4wKBgQDcH64C5s4bb2beZOhm2Dj/kU54V4l93+CBFjCOkXaYdG+p35DWWspqEcCHSt68l8F7FLdZxEbodTPY3w+L9iejI4UkKPN1CzVD1U2dR4VnbY85zmwRiuCVzsM/KCCE61dOi4ktfbgFGhc1dEYHuROzLo8/tlFkiajW3eyLeSM3MwKBgATL3iw57d8gEeDRQXKx9WJa+QLOjDAD0dkFwEC/e+/+Z3I93qZVsiFT+E7n4VeXfuG2SZB0eH4WCApJuZ+EWzAJtxWnkkQQjdMxyTYgD99bKLs1xRA2S9j0K7aFmQGoNrJ//sMXrwfgbZJtk/lOKqMthjCR0u/DjeJHA22MnRsTAoGADXzJs/of0JExvQWwfdIUnSEPs/PgTrrJpo+CAdXnagYHF+InrmvIcNwx6ZzIs+9aGwUt0d/YsSpJkHMfAtTwZjB7sSw8Cg5DZ179Jy3YkKhFPvZv2ZCANa5J74HZNQUrUUL6O4FouZUiLwFlq8YuUPRtkAjYwyS/jwUbhJzqZhQ=",
		  // "rsa_huifu_public_key":"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkMX8p3GyMw3gk6x72h20NOk3L9+Nn9mOVP6+YoBwCe7Zs4QmYrA/etFRZw2TQrSc51wgtCkJi1/x8Wl7maPL1uH2+77JFlPv7H/F4Lr2I2LXgnllg6PtwOSw/qvGYInVVB4kL85VQl0/8ObyxBUdJ43I0z/u8hJb2gwujSudOGizbeqQXAYrwcNy+e+cjodpPy9unpJjBfa4Wz2eVLLvUYYKZKdRn6pZR2cQsMBvL30K4cFlZqlJ9iP2hTG3gaiZJ9JrjTigwki0g9pbTDXiPACfuF1nOeObvLD22zBbgn1kwgfsqoG67z7g84u2jvfUFCzX1JRgd0xfNorTRkS2RQIDAQAB"}';
		// $config_info = json_decode($config_str, true);
        $service = new BsPayService();
        $setting = $service->getPaymentSetting($companyId);
        if (!$setting) {
            throw new ResourceException('请先配置支付信息');
        }   
        # init方法，从 config.json 加载系统参数
        BsPay::init($setting, true);
    }
}
