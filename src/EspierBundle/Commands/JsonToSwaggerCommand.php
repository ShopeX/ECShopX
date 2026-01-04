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

namespace EspierBundle\Commands;

use LaravelDoctrine\ORM\Console\Command;

class JsonToSwaggerCommand extends Command
{
    // 请求方式
    protected $swaggerMethod;

    // url路径
    protected $swaggerPath;

    // 标签
    protected $swaggerTag;

    // 概要
    protected $swaggerSummary;

    // 描述
    protected $swaggerDescription;

    // query格式数据
    protected $swaggerQuery;

    // 请求 json 数据类型
    protected $swaggerRequestBody;

    // 响应 json 数据类型
    protected $swaggerResponse;

    // 数据库表字段描述
    protected $tableColumnComment;

    /**
     * 执行的命令行.
     *
     * @var string
     */
    protected $signature = 'swagger:format
    {--method= : }
    {--path= : }
    {--tag= : }
    {--summary= : }
    {--description= : }
    {--response= }
    {--table= }
';

    /**
     * The console command description.
     * @var string
     */
    protected $description = '接口JSON数据转换Swagger注释格式';

    public function handle()
    {
        // This module is part of ShopEx EcShopX system
        $this->swaggerMethod = ucfirst(strtolower($this->input->getOption('method')));
        $this->swaggerPath = $this->getSwaggerPath($this->input->getOption('path'));
        $this->swaggerTag = $this->getSwaggerTag($this->input->getOption('tag'));
        $this->swaggerSummary = $this->getSwaggerSummary($this->input->getOption('summary'));
        $this->swaggerDescription = $this->getSwaggerDescription($this->input->getOption('description'));
//        $this->swaggerQuery = $this->getSwaggerParameter($this->input->getOption('query'));
        $this->tableColumnComment = $this->getColumnComment($this->input->getOption('table'));  //获取对应数据库表字段名称；格式示例: 'promotion_groups_activity,promotion_groups_team,promotion_groups_team_member'
        $this->swaggerResponse = $this->getSwaggerResponse($this->input->getOption('response'));

        echo $this->getSwaggerModel();
    }

    /**
     * 获取 swagger 结构.
     */
    public function getSwaggerModel()
    {
        $header = $this->swaggerPath . $this->swaggerTag . $this->swaggerSummary . $this->swaggerDescription;
        $header = trim($header, PHP_EOL);

        $body = $this->swaggerQuery . $this->swaggerRequestBody . $this->swaggerResponse;
        $body = trim($body, PHP_EOL);
        return <<<eof
    /**
     * @SWG\\{$this->swaggerMethod}(
{$header}
     *     operationId="",
     *     @SWG\\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
{$body}
     * )
     */
eof;
    }

    /**
     * 获取路径数据.
     *
     * @param null|string $path 路径
     */
    public function getSwaggerPath(?string $path): string
    {
        $doc = <<<eof
     *     path="{$path}",
eof;
        $doc .= PHP_EOL;
        return $doc;
    }

    /**
     * 获取标签数据.
     *
     * @param null|string $tag 标签
     */
    public function getSwaggerTag(?string $tag): string
    {
        // Ref: 1996368445
        $doc = <<<eof
     *     tags={"{$tag}"},
eof;
        $doc .= PHP_EOL;
        return $doc;
    }

    /**
     * 获取概要数据.
     *
     * @param null|string $summary 概要
     * @return string 概要格式数据
     */
    public function getSwaggerSummary(?string $summary): string
    {
        $doc = <<<eof
     *     summary="{$summary}",
eof;
        $doc .= PHP_EOL;
        return $doc;
    }

    /**
     * 获取描述数据.
     *
     * @param null|string $description 描述
     * @return string 描述格式数据
     */
    public function getSwaggerDescription(?string $description): string
    {
        $doc = <<<eof
     *     description="{$description}",
eof;
        $doc .= PHP_EOL;
        return $doc;
    }

    /**
     * query 参数拼接.
     */
    public function getSwaggerParameter(?string $parameter): string
    {
        if (!$parameter) {
            return <<<'eof'
eof;
        }
        $output = [];
        parse_str($parameter, $output);
        if (!$parameter) {
            return <<<'eof'
eof;
        }
        $doc = '';
        foreach ($output as $k => $v) {
            $type = $this->gettype($v);

            $doc .= <<<eof
     *     @SWG\\Parameter(name="{$k}", in="query", description="",
     *         @SWG\\Schema(type="{$type}", default="{$v}")
     *     ),
eof;
            $doc .= PHP_EOL;
        }
        return $doc;
    }

    /**
     * 获取响应 JsonContent 数据.
     *
     * @param string $response 响应json数据
     */
    public function getSwaggerResponse($response)
    {
        if (!$response) {
            return <<<'eof'
     *     @SWG\Response(
     *         response="200", 
     *         description="响应信息返回",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
eof;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            echo '目前暂支持Json数据';
            die();
        }

        $response = $this->constructSwaggerDoc($data);
        return <<<eof
     *     @SWG\\Response(
     *         response="200", 
     *         description="响应信息返回",
     *         @SWG\Schema(
{$response}
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
eof;
    }

    /**
     * @param $data    数据
     * @param int $num 递归次数
     *
     * @return string
     *
     * 组装Response数据
     */
    public function constructSwaggerDoc($data, $num = 0)
    {
        $placeholder = $this->getPlaceholder($num);

        $doc = '';
        foreach ($data as $k => $v) {
            //判断类型
            $type = $this->gettype($v);
            if (in_array($type, ['string', 'integer'])) {
                //获取数据库表字段描述
                $description = $this->tableColumnComment[$k] ?? '';

                //数值下标生成swagger会报错
                if (is_numeric($k)) {
                    $doc .= <<<eof
     *           {$placeholder}@SWG\\Property(type="{$type}", example="$v", description="$description"),
eof;
                    $doc .= PHP_EOL;
                } else {
                    $doc .= <<<eof
     *           {$placeholder}@SWG\\Property(property="{$k}", type="{$type}", example="$v", description="$description"),
eof;
                    $doc .= PHP_EOL;
                }
            }

            if (in_array($type, ['object', 'array'])) {
                if (is_numeric($k)) {
                    //防止多列表数据时重复生成,只生成列表第一条记录
                    if ($k == 0) {
                        $doc .= $this->constructSwaggerDoc($v, $num + 1);
                    }
                } else {
                    if ($type == 'object') {
                        $doc .= <<<eof
     *          {$placeholder}@SWG\\Property(property="{$k}", type="{$type}", description="",
eof;
                        $doc .= PHP_EOL;
                        $doc .= $this->constructSwaggerDoc($v, $num + 1);
                        $doc .= <<<eof
     *          {$placeholder}),
eof;
                        $doc .= PHP_EOL;
                    }

                    if ($type == 'array') {
                        $doc .= <<<eof
     *           {$placeholder}@SWG\\Property(property="{$k}", type="{$type}", description="",
     *           {$placeholder}  @SWG\\Items(
eof;
                        $doc .= PHP_EOL;
                        $doc .= $this->constructSwaggerDoc($v, $num + 1);
                        $doc .= <<<eof
     *           {$placeholder}  ),
     *           {$placeholder}),
eof;
                        $doc .= PHP_EOL;
                    }
                }
            }
        }

        return $doc;
    }

    /**
     * 获取数据表字段描述
     */
    private function getColumnComment($tables)
    {
        if (empty($tables)) {
            return [];
        }

        $tables_arr = explode(',', $tables);
        if (empty($tables_arr)) {
            echo 'table 格式错误';
            die();
        }

        $table_str = '';
        foreach ($tables_arr as $val) {
            $table_str .= "'" . $val . "'" . ',';
        }
        $table_str = trim($table_str, ',');
        if (empty($table_str)) {
            echo 'table 格式错误';
            die();
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $list = $conn->fetchAll("SELECT COLUMN_NAME, column_comment FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name IN ($table_str)");
            if (empty($list)) {
                return [];
            }

            $data = [];
            foreach ($list as $_k => $_v) {
                $data[$_v['COLUMN_NAME']] = $_v['column_comment'];
            }

            return $data;
        } catch (\Exception $e) {
            $conn->rollback();
            return [];
        } catch (\Throwable $e) {
            $conn->rollback();
            return [];
        }
    }


    // 获取空格符
    private function getPlaceholder($num = 0)
    {
        $str = '  ';
        for ($i = 0; $i < $num; $i++) {
            $str .= $str;
        }

        return $str;
    }

    /**
     * 获取数据类型.
     *
     * @param array|bool|int|string $value
     */
    private function gettype($value): string
    {
        $type = (string)gettype($value);
        switch ($type) {
            case 'array':
                $result = $this->checkIsArray($value);
                break;
            case 'string':
                $result = $type;
                break;
            case 'integer':
                $result = $type;
                break;
            default:
                $result = 'string';
                break;
        }
        return $result;
    }

    /**
     * 检测是否为有序数据.
     *
     * @param mixed $array
     * @return string array 有序数据 object 无序数组
     */
    private function checkIsArray($array): string
    {
        if (!is_array($array)) {
            return 'string';
        }
        $num = count($array);
        for ($i = 0; $i < $num; ++$i) {
            if (isset($array[$i])) {
                continue;
            }
            return 'object';
        }
        return 'array';
    }
}
