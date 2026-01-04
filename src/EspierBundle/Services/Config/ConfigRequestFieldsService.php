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

namespace EspierBundle\Services\Config;

use Carbon\Carbon;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Entities\ConfigRequestFields;
use EspierBundle\Repositories\ConfigRequestFieldsRepository;
use EspierBundle\Services\Cache\RedisCacheService;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use MembersBundle\Services\MemberRegSettingService;

class ConfigRequestFieldsService
{
    use MagicLangTrait;
    /**
     * 配置请求字段的资源库
     * @var ConfigRequestFieldsRepository
     */
    private $configRequestFieldsRepository;

    /**
     * 模块类型
     */
    public const MODULE_TYPE_MEMBER_INFO = 1;
    public const MODULE_TYPE_CHIEF_INFO = 2;
    public const MODULE_TYPE_MAP = [
        self::MODULE_TYPE_MEMBER_INFO => "会员个人信息",
        self::MODULE_TYPE_CHIEF_INFO => "社区团购团长信息"
    ];

    /**
     * 字段类型
     */
    public const FIELD_TYPE_TEXT = 1;
    public const FIELD_TYPE_NUMBER = 2;
    public const FIELD_TYPE_DATE = 3;
    public const FIELD_TYPE_RADIO = 4;
    public const FIELD_TYPE_CHECKBOX = 5;
    public const FIELD_TYPE_MOBILE = 6;
    public const FIELD_TYPE_IMAGE = 7;
    public const FIELD_TYPE_MAP = [
        self::FIELD_TYPE_TEXT => "文本",
        self::FIELD_TYPE_NUMBER => "数字",
        self::FIELD_TYPE_DATE => "日期",
        self::FIELD_TYPE_RADIO => "单选项",
        self::FIELD_TYPE_CHECKBOX => "多选项",
        self::FIELD_TYPE_MOBILE => "手机号",
        self::FIELD_TYPE_IMAGE => "图片",
    ];
    /**
     * 每个字段类型所对应的元素值
     */
    public const FIELD_TYPE_ELEMENT_MAP = [
        self::FIELD_TYPE_TEXT => "input",
        self::FIELD_TYPE_NUMBER => "numeric",
        self::FIELD_TYPE_DATE => "date",
        self::FIELD_TYPE_RADIO => "select",
        self::FIELD_TYPE_CHECKBOX => "checkbox",
        self::FIELD_TYPE_MOBILE => "mobile",
        self::FIELD_TYPE_IMAGE => "image",
    ];

    public function __construct()
    {
        $this->configRequestFieldsRepository = app('registry')->getManager('default')->getRepository(ConfigRequestFields::class);
    }

    /**
     * 检查是否存在label字段名
     * @param int $companyId 公司id
     * @param int $moduleType 模块类型
     * @param string $label 字段名
     * @param int $neqId 排除自己的数据
     */
    protected function checkLabelExist(int $companyId, int $moduleType, string $label, int $neqId = 0, int $distributorId = 0)
    {
        $labelCount = $this->configRequestFieldsRepository->count([
            "company_id" => $companyId,
            "module_type" => $moduleType,
            "label" => $label,
            "id|neq" => $neqId,
            "distributor_id" => $distributorId,
        ]);
        if ($labelCount > 0) {
            throw new ResourceException(sprintf("操作失败！该模块下【%s】已存在！", $label));
        }
    }

    /**
     * 检查是否存在label字段名
     * @param int $companyId 公司id
     * @param int $moduleType 模块类型
     * @param string $key 字段名的key
     * @param int $neqId 排除自己的数据
     */
    protected function checkKeyExist(int $companyId, int $moduleType, string $key, int $neqId = 0, int $distributorId = 0)
    {
        $keyCount = $this->configRequestFieldsRepository->count([
            "company_id" => $companyId,
            "module_type" => $moduleType,
            "key_name" => $key,
            "id|neq" => $neqId,
            "distributor_id" => $distributorId,
        ]);
        if ($keyCount > 0) {
            throw new ResourceException(sprintf("操作失败！该模块下【%s】已存在！", $key));
        }
    }

    /**
     * 检查是否存在必填切必须开启的字段
     * @param array $filter 过滤条件
     * @return bool true为存在，false为不存在
     */
    public function checkIsNeedInit(array $filter): bool
    {
        // 企业id
        $companyId = (int)($filter["company_id"] ?? 0);
        if ($companyId <= 0) {
            return true;
        }
        // 模块类型
        $moduleType = (int)($filter["module_type"] ?? 0);
        //店铺id
        $distributorId = (int)($filter["distributor_id"] ?? 0);
        // 获取必须开启且必填的字段
        $mustStartAndRequiredFields = $this->getMustStartAndRequiredFieldsFromConfig($companyId, $moduleType);
        // 获取默认的字段
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        // 合并字段集合
        $fields = array_merge($mustStartAndRequiredFields, array_keys($defaultFields));
        // 定义预期的字段数量
        $expectedCount = count($mustStartAndRequiredFields);
        foreach ($defaultFields as $field => $defaultField) {
            if (isset($defaultField[self::SWITCH_COLUMN_DESC_IS_OPEN]) && $defaultField[self::SWITCH_COLUMN_DESC_IS_OPEN] && !in_array($field, $mustStartAndRequiredFields, true)) {
                $expectedCount++;
            }
        }
        // 查询当前的已开启字段数量
        $count = $this->configRequestFieldsRepository->count([
            "company_id" => $companyId,
            "module_type" => $moduleType,
            "key_name" => $fields,
            "distributor_id" => $distributorId,
        ]);
        if ($count !== $expectedCount) {
            $this->init($companyId, $moduleType, $distributorId);
        }
        return true;
    }

    /**
     * 检查该字段是否要必须开启且必须是必填
     * @param int $companyId 公司id
     * @param int $moduleType 模块类型
     * @param string $keyName 字段名的key
     */
    protected function checkKeyNameIsMustStartAndRequired(int $companyId, int $moduleType, string $keyName)
    {
        if (in_array($keyName, $this->getMustStartAndRequiredFieldsFromConfig($companyId, $moduleType), true)) {
            throw new ResourceException(sprintf("操作失败！该模块下【%s】必须开启且必须是必填！", $keyName));
        }
    }

    /**
     * 检查该字段是否是默认项
     * @param int $companyId 公司id
     * @param int $moduleType 模块类型
     * @param string $keyName 字段名的key
     */
    protected function checkKeyNameIsDefault(int $companyId, int $moduleType, string $keyName)
    {
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        if (isset($defaultFields[$keyName])) {
            throw new ResourceException(sprintf("操作失败！该模块下【%s】是默认项，无法删除！", $keyName));
        }
    }

    /**
     * 创建一条请求字段
     * @param int $companyId 公司id
     * @param int $moduleType 模块类型
     * @param array $formData 表单数据
     * @return array 插入完成后的数据
     * @throws \Exception
     */
    public function create(int $companyId, int $moduleType, array $formData)
    {
        // 获取字段内容
        $label = (string)($formData["label"] ?? "");
        // 唯一的key标识符，这里有两种情况，一种是自定义的，一种是预设的
        $keyName = (string)($formData["key_name"] ?? "");
        // 店铺id
        $distributorId = (int)($formData["distributor_id"] ?? 0);
        // 标识符，表示是否是预设字段
        $isPreset = false;
        // 获取默认字段
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        if (empty($keyName)) {
            // 如果key_name为空，则通过label值去匹配出key_name，也能匹配出是否是预设
            foreach ($defaultFields as $fieldKeyName => $info) {
                $fieldLabel = $info["name"] ?? "";
                // 如果匹配到了label，则获取对应的key_name，并且不在需要自动生成key_name
                if ($label == $fieldLabel) {
                    $keyName = $fieldKeyName;
                    $isPreset = true;
                    break;
                }
            }
        } else {
            // 如果key_name存在，则判断是否是预设
            if (isset($defaultFields[$keyName]["name"])) {
                $label = $defaultFields[$keyName]["name"]; // 覆盖label
                $isPreset = true;
            }
        }
        // 判断key_name是否存在
        if ($isPreset) {
            $this->checkKeyExist($companyId, $moduleType, $keyName, 0, $distributorId);
        }

        // 需要被创建的数据格式
        $createData = [
            "company_id" => $companyId,
            "distributor_id" => $distributorId,
            "module_type" => $moduleType,
            "label" => $label,
            "key_name" => $keyName,
            "is_preset" => (int)$isPreset,
            "is_open" => (int)($formData["is_open"] ?? false),
            "is_required" => (int)($formData["is_required"] ?? false),
            "is_edit" => (int)($formData["is_edit"] ?? false),
            "field_type" => (int)($formData["field_type"] ?? self::FIELD_TYPE_TEXT),
            "validate_condition" => "",
            "alert_required_message" => (string)($formData["alert_required_message"] ?? ""),
            "alert_validate_message" => (string)($formData["alert_validate_message"] ?? ""),
        ];
        $createData["validate_condition"] = json_encode($this->makeValidateCondition($createData["field_type"], $formData), JSON_UNESCAPED_UNICODE);
        // 判断label是否重复
        $this->checkLabelExist($companyId, $moduleType, $createData["label"], 0, $distributorId);
        // 判断是否要必填和开启
        try {
            $this->checkKeyNameIsMustStartAndRequired($companyId, $createData["module_type"], $createData["key_name"]);
        } catch (\Exception $exception) {
            $createData["is_open"] = true;
            $createData["is_required"] = true;
        }
        // 开启事务
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建
            $result = $this->configRequestFieldsRepository->create($createData);
            // 如果不是预设，就自动生成
            if (!$isPreset) {
                // 获取主键id
                $id = (int)($result["id"]);
                $result["key_name"] = md5("config_request_field_". $id);
                $this->checkKeyExist($companyId, $moduleType, $result["key_name"], $id, $distributorId);
                // 根据id做md5加密
                $this->configRequestFieldsRepository->updateBy(["id" => $id], [
                    "key_name" => $result["key_name"]
                ]);
            }
            $conn->commit();
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
        // 创建数据
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", $moduleType, $distributorId)))->delete();
        return $this->handleData($result);
    }


    /**
     * 返回的数据做统一处理
     * @param array $data
     * @return array
     */
    protected function handleData(array $data): array
    {
        // 获取模块类型描述
        if (isset($data["field_type"])) {
            $data["field_type_desc"] = self::FIELD_TYPE_MAP[$data["field_type"]] ?? "";
        }
        // 获取在验证时的条件数据
        if (isset($data["validate_condition"])) {
            $data["validate_condition"] = (array)jsonDecode($data["validate_condition"] ?? "");
        }
        $this->makeFieldTypeContent($data);
        // 新增时间的描述
        if (isset($data["created"])) {
            $data["created_desc"] = Carbon::createFromTimestamp($data["created"])->toDateTimeString();
        }
        // 更新时间的描述
        if (isset($data["updated"])) {
            $data["updated_desc"] = Carbon::createFromTimestamp($data["updated"])->toDateTimeString();
        }

        // 设置字段是否是必须开启且必须填写
        $data["is_must_start_required"] = 0;
        // 设置字段是否是默认字段
        $data["is_default"] = 0;
        // 模块类型
        if (isset($data["module_type"])) {
            $data["module_type_desc"] = self::MODULE_TYPE_MAP[$data["module_type"]] ?? "";
            $keyName = (string)($data["key_name"] ?? "");
            if (in_array($keyName, $this->getMustStartAndRequiredFieldsFromConfig((int)$data["company_id"], (int)$data["module_type"]))) {
                $data["is_must_start_required"] = 1;
            }
            if (isset($this->getDefaultFieldsFromConfig((int)$data["company_id"], (int)$data["module_type"])[$keyName])) {
                $data["is_default"] = 1;
            }
        }
        // 将这些字段转成int类型
        foreach (["field_type", "is_open", "is_required", "is_edit", "is_preset"] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int)$data[$field];
            }
        }
        // 强制转换默认值
        if (isset($data["company_id"]) && isset($data["module_type"]) && isset($data["label"]) && isset($data["key_name"])) {
            $transformDefaultLabels = $this->getDefaultFieldsFromConfig((int)$data["company_id"], (int)$data["module_type"]);
            $data["label"] = $transformDefaultLabels[$data["key_name"]]["name"] ?? $data["label"];
        }
        return $data;
    }

    /**
     * 开关字段的映射表
     */
    public const SWITCH_COLUMN_DESC_IS_OPEN = "is_open";
    public const SWITCH_COLUMN_DESC_IS_REQUIRED = "is_required";
    public const SWITCH_COLUMN_DESC_IS_EDIT = "is_edit";
    public const SWITCH_COLUMN_DESC_IS_PRESET = "is_preset";
    public const SWITCH_COLUMN_IS_OPEN = 1; // 是否开启
    public const SWITCH_COLUMN_IS_REQUIRED = 2; // 是否必填
    public const SWITCH_COLUMN_IS_EDIT = 3; // 是否可编辑
    public const SWITCH_COLUMN_IS_PRESET = 4; // 是否是预设字段
    public const SWITCH_COLUMN_MAP = [
        self::SWITCH_COLUMN_IS_OPEN => self::SWITCH_COLUMN_DESC_IS_OPEN,
        self::SWITCH_COLUMN_IS_REQUIRED => self::SWITCH_COLUMN_DESC_IS_REQUIRED,
        self::SWITCH_COLUMN_IS_EDIT => self::SWITCH_COLUMN_DESC_IS_EDIT,
        self::SWITCH_COLUMN_IS_PRESET => self::SWITCH_COLUMN_DESC_IS_PRESET,
    ];

    public const SWITCH_YES = 1; // 开启
    public const SWITCH_NO = 0; // 关闭

    /**
     * 更新启动状态
     * @param int $companyId 公司id
     * @param int $id 主键id
     * @param int $switchColumnKey 开关列表的key名，取得是SWITCH_COLUMN_MAP的映射表的key
     * @param bool $isOpen true为开启，false为关闭
     * @return bool true为操作成功
     */
    public function updateSwitch(int $companyId, int $id, int $switchColumnKey, bool $isOpen, int $distributorId = 0): bool
    {
        // 获取详情
        $info = $this->getInfo($companyId, ["id" => $id, "distributor_id" => $distributorId]);
        if (empty($info)) {
            throw new ResourceException("无法查询到该数据");
        }
        // 判断更新的字段是否在预设的枚举里
        if (!isset(self::SWITCH_COLUMN_MAP[$switchColumnKey])) {
            throw new ResourceException("操作失败！更新的字段有误！");
        }
        $infoKeyName = (string)($info["key_name"] ?? "");
        $moduleType = (int)($info["module_type"] ?? 0);
        // 如果更新的字段是【是否开启】或【是否必填】且开关最终的值是关闭
        if (!$isOpen && ($switchColumnKey == self::SWITCH_COLUMN_IS_OPEN || $switchColumnKey == self::SWITCH_COLUMN_IS_REQUIRED)) {
            $this->checkKeyNameIsMustStartAndRequired($companyId, $moduleType, $infoKeyName);
        }
        // 需要更新的内容
        $saveData = [
            self::SWITCH_COLUMN_MAP[$switchColumnKey] => (int)$isOpen,
            self::SWITCH_COLUMN_DESC_IS_PRESET => 0, // 默认不是预设
        ];
        // 判断是否是预设字段
        $fields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        if (isset($fields[$infoKeyName])) {
            $saveData[self::SWITCH_COLUMN_DESC_IS_PRESET] = 1; // 设置为预设字段
        }
        // 更新字段状态
        $this->configRequestFieldsRepository->updateBy(["company_id" => $companyId, "id" => $id], $saveData);
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", $moduleType, $distributorId)))->delete();
        return true;
    }

    /**
     * 获取分页数据数据
     * @param int $companyId 公司id
     * @param array $filter 过滤条件
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序方式
     * @param string $cols 查询的列，默认是全部
     * @return array
     */
    public function paginate(int $companyId, array $filter, int $page = 1, int $pageSize = -1, array $orderBy = [], string $cols = "*"): array
    {
        $filter["company_id"] = $companyId;
        $data = $this->configRequestFieldsRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (isset($data["list"]) && is_array($data["list"])) {
            foreach ($data["list"] as &$item) {
                $item = $this->handleData($item);
            }
        }
        return $data;
    }

    /**
     * 获取列表数据
     * @param int $companyId 公司id
     * @param array $filter 过滤条件
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序方式
     * @param string $cols 查询的列，默认是全部
     * @return array 结果
     */
    public function getList(int $companyId, array $filter, int $page = 1, int $pageSize = -1, array $orderBy = ["id" => "DESC"], string $cols = "*"): array
    {
        $filter["company_id"] = $companyId;
        $list = $this->configRequestFieldsRepository->getLists($filter, $cols, $page, $pageSize, $orderBy);
        foreach ($list as &$item) {
            $item = $this->handleData($item);
        }

        return $list;
    }

    /**
     * 获取当前模块下所有字段内容并处理成setting中的配置格式
     * @param int $companyId 企业id
     * @param int $moduleType 模块类型
     * @return array 结果集
     */
    public function getListAndHandleSettingFormat(int $companyId, int $moduleType, int $distributorId = 0): array
    {
        $lang = $this->getLang();
        $lang = strtolower($lang);
        $lang  = str_replace("-", "", $lang);
        return (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d_%s", $moduleType, $distributorId,$lang)))
            ->getByPrevention(function () use ($companyId, $moduleType, $distributorId) {
                // 检查是否已经初始化
                $this->checkIsNeedInit(["company_id" => $companyId, "module_type" => $moduleType, "distributor_id" => $distributorId]);
                // 获取字段列表
                $list = $this->configRequestFieldsRepository->getLists([
                    "company_id" => $companyId,
                    "distributor_id" => $distributorId,
                    "module_type" => $moduleType,
                    self::SWITCH_COLUMN_DESC_IS_OPEN => self::SWITCH_YES,
                ], "*", 1, -1, ["id" => "DESC"]);
                // 转格式
                $fields = (array)array_column($list, null, "key_name");

                $result = [];
                // 将数据库中查询出来的字段信息转换成外部的统一格式
                foreach ($fields as $keyName => $item) {
                    // 设置企业id
                    $item["company_id"] = $companyId;
                    // 处理数据
                    $item = $this->handleData($item);
                    $data = [
                        "name" => (string)($item["label"] ?? ""),
                        "key" => $keyName,
                        "is_open" => (bool)($item["is_open"] ?? 0),
                        "is_required" => (bool)($item["is_required"] ?? 0),
                        "is_edit" => (bool)($item["is_edit"] ?? 0),
                        "is_default" => (bool)($item["is_preset"] ?? 0),
                        "element_type" => "",
                        "field_type" => (int)($item["field_type"] ?? self::FIELD_TYPE_TEXT),
                        "required_message" => (string)($item["alert_required_message"] ?? ""),
                        "validate_message" => (string)($item["alert_validate_message"] ?? ""),
                        //"items"        => [],
                        "range" => [], // 数字、日期的选择范围
                        "select" => [], // 单选项的选择列表 key为索引枚举值，value为枚举值
                        "checkbox" => [], // 复选项的选择列表，二维数组，每个数组下面name为选项名字，ischecked为是否选中
                    ];
                    $data["element_type"] = self::FIELD_TYPE_ELEMENT_MAP[$data["field_type"]] ?? "input";
                    switch ($data["field_type"]) {
                        case self::FIELD_TYPE_NUMBER:
                        case self::FIELD_TYPE_DATE:
                            $data["range"] = (array)($item["range"] ?? []);
                            break;
                        case self::FIELD_TYPE_RADIO:
                            // $data["select"] = array_column((array)($item["radio_list"] ?? []), "value", "label");
                            $data["select"] = array_column((array)($item["radio_list"] ?? []), "label", "value");
                            break;
                        case self::FIELD_TYPE_CHECKBOX:
                            $item["radio_list"] = (array)($item["radio_list"] ?? []);
                            foreach ($item["radio_list"] as $datum) {
                                $data["checkbox"][] = [
                                    "name" => (string)($datum["label"] ?? ""),
                                    "ischecked" => (bool)($datum["is_checked"] ?? false)
                                ];
                            }
                            break;
                    }
                    $result[$keyName] = $data;
                }
                return $result;
            });
    }

    /**
     * 获取单条数据
     * @param int $companyId 公司id
     * @param array $filter 过滤条件
     * @return array 结果
     */
    public function getInfo(int $companyId, array $filter): array
    {
        $list = $this->getList($companyId, $filter, 1, 1);
        return (array)array_shift($list);
    }

    /**
     * 更新数据
     * @param int $companyId 公司id
     * @param int $id 主键id
     * @param array $formData 表单数据
     * @return array
     */
    public function updateInfo(int $companyId, int $id, array $formData): array
    {
        $distributorId = (int)($formData["distributor_id"] ?? 0);
        $info = $this->getInfo($companyId, ["id" => $id, "distributor_id" => $distributorId]);
        if (empty($info)) {
            throw new ResourceException("操作失败！不存在该数据！");
        }
        $updateData = [
            "label" => (string)($formData["label"] ?? ""),
            "field_type" => (int)($formData["field_type"] ?? self::FIELD_TYPE_TEXT),
            "validate_condition" => "",
        ];
        foreach (["alert_required_message", "alert_validate_message"] as $field) {
            if (isset($formData[$field])) {
                $updateData[$field] = $formData[$field];
            }
        }
        $updateData["validate_condition"] = json_encode($this->makeValidateCondition($updateData["field_type"], $formData), JSON_UNESCAPED_UNICODE);
        // 判断label是否重复
        $this->checkLabelExist($companyId, (int)($info["module_type"] ?? 0), $updateData["label"], $id, $distributorId);
        // 更新数据
        $result = $this->configRequestFieldsRepository->updateOneBy(["company_id" => $companyId, "id" => $id], $updateData);
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", (int)($info["module_type"] ?? 0), $distributorId)))->delete();
        return $this->handleData($result);
    }

    /**
     * 删除请求字段
     * @param int $companyId 公司id
     * @param int $id 主键id
     * @return bool 操作的状态
     */
    public function delete(int $companyId, int $id, int $distributorId = 0): bool
    {
        $info = $this->getInfo($companyId, ["id" => $id, "distributor_id" => $distributorId]);
        if (empty($info)) {
            return true;
        }
        $keyName = (string)($info["key_name"] ?? "");
        $moduleType = (int)($info["module_type"] ?? 0);
        $this->checkKeyNameIsDefault($companyId, $moduleType, $keyName);
        $this->configRequestFieldsRepository->deleteBy(["company_id" => $companyId, "id" => $id]);
        (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsNewSetting_%d_%d", $moduleType, $distributorId)))->delete();
        return true;
    }

    /**
     * 获取验证的条件
     * @param int $fieldType
     * @param array $formData
     * @return array|null[]
     */
    protected function makeValidateCondition(int $fieldType, array $formData): array
    {
        switch ($fieldType) {
            case self::FIELD_TYPE_NUMBER:
            case self::FIELD_TYPE_DATE:
                $range = (array)($formData["range"] ?? []);
                $result = [];
                // 这里做了一个新老版本的兼容，如果range是一个数组，则遍历获取里面的对象，如果range本身就是一个对象就直接获取开始和结束
                if (!isset($range["start"]) && !isset($range["end"])) {
                    foreach ($range as $item) {
                        $result[] = [
                            "value" => sprintf("%s,%s", $item["start"] ?? null, $item["end"] ?? null),
                            "label" => "取值范围",
                            "is_checked" => 0
                        ];
                    }
                } else {
                    $result[] = [
                        "value" => sprintf("%s,%s", $range["start"] ?? null, $range["end"] ?? null),
                        "label" => "取值范围",
                        "is_checked" => 0
                    ];
                }
                return $result;
                break;
            case self::FIELD_TYPE_RADIO:
            case self::FIELD_TYPE_CHECKBOX:
                $radioList = (array)($formData["radio_list"] ?? []);
                foreach ($radioList as $key => &$item) {
                    // $item原本是由key、label和is_checked组成，但现在key用不到了，只需要label和is_checked即可
                    if (!isset($item["label"]) || !isset($item["is_checked"])) {
                        throw new ResourceException("操作失败！验证的数据格式有误！");
                    }
                    $item["value"] = $key;
                }
                return $radioList;
            default:
                return [];
        }
    }

    /**
     * 是makeValidateCondition的逆方法，将存进去的值转成前端希望的数据格式
     * @param array $result 结果
     */
    protected function makeFieldTypeContent(array &$result)
    {
        // 初始化取值范围
        if (!isset($result["range"])) {
            $result["range"] = [];
        }
        // 初始单选项列表
        if (!isset($result["radio_list"])) {
            $result["radio_list"] = [];
        }
        // 判断验证条件，如果不存在的话，就不继续执行，直接返回
        if (!isset($result["validate_condition"])) {
            return;
        } else {
            if (is_string($result["validate_condition"])) {
                $validateCondition = jsonDecode($result["validate_condition"]);
            } else {
                $validateCondition = $result["validate_condition"];
            }
        }
        // 获取字段类型
        $fieldType = (int)($result["field_type"] ?? 0);
        switch ($fieldType) {
            case self::FIELD_TYPE_NUMBER:
            case self::FIELD_TYPE_DATE:
                foreach ($validateCondition as $item) {
                    $value = (string)($item["value"] ?? "");
                    $valueArray = explode(",", $value);
                    $start = array_shift($valueArray);
                    $end = array_shift($valueArray);
                    // 目前暂时是一个取值范围
                    //$result["validate_condition_range"][] = [
                    $result["range"] = [
                        "start" => is_numeric($start) ? $start : null,
                        "end" => is_numeric($end) ? $end : null,
                    ];
                }
                break;
            case self::FIELD_TYPE_RADIO:
            case self::FIELD_TYPE_CHECKBOX:
                foreach ($validateCondition as $key => $item) {
                    $result["radio_list"][] = [
                        "key" => (string)($item["key"] ?? ""),
                        "value" => (string)($item["value"] ?? $key),
                        "label" => (string)($item["label"] ?? ""),
                        "is_checked" => (int)($item["is_checked"] ?? 0),
                    ];
                }
                break;
        }
    }

    /**
     * 从配置文件中获取必须请求的字段信息
     * @param int $companyId 企业
     * @param int $moduleType 模块类型
     * @return array 字段信息
     */
    public function getMustStartAndRequiredFieldsFromConfig(int $companyId, int $moduleType): array
    {
        // 数云模式
        if (config('common.oem-shuyun')) {
            $fieldsString = config(sprintf("requestFieldShuyun.%d.must_start_required", $moduleType));
        } else {
            $fieldsString = config(sprintf("requestField.%d.must_start_required", $moduleType));
        }
        return explode(",", $fieldsString);
    }

    /**
     * 从配置文件中获取默认的请求字段信息
     * @param int $companyId 企业
     * @param int $moduleType 模块类型
     * @return array 字段信息, key为key_name, value为字段的内容
     */
    public function getDefaultFieldsFromConfig(int $companyId, int $moduleType): array
    {
        $lang = strtolower($this->getLang());
        $lang = str_replace('-','',$lang);
        // 数云模式
        if (config('common.oem-shuyun')) {
            return (array)config(sprintf("requestFieldShuyun.%d.default", $moduleType));
        } else {
            return (array)config(sprintf("requestField%s.%d.default",$lang, $moduleType));
        }
    }

    /**
     * 获取默认的字段内容
     * @param int $companyId 企业id
     * @param int $moduleType 模块类型
     * @return array
     */
    public function getDefaultFieldContent(int $companyId, int $moduleType): array
    {
        $formData = [];
        // 获取默认字段
        $defaultFields = $this->getDefaultFieldsFromConfig($companyId, $moduleType);
        // 设置某些需要必须开启且是必填的字段
        // 如果在setting中找不到，则直接去配置文件中的默认字段里找
        $mustStartAndRequiredFields = $this->getMustStartAndRequiredFieldsFromConfig($companyId, $moduleType);
        foreach ($mustStartAndRequiredFields as $mustStartAndRequiredField) {
            $defaultFields[$mustStartAndRequiredField][self::SWITCH_COLUMN_DESC_IS_OPEN] = (bool)self::SWITCH_YES;
            $defaultFields[$mustStartAndRequiredField][self::SWITCH_COLUMN_DESC_IS_REQUIRED] = (bool)self::SWITCH_YES;
            $defaultFields[$mustStartAndRequiredField]["name"] = $defaultFields[$mustStartAndRequiredField]["name"] ?? $mustStartAndRequiredField;
            $defaultFields[$mustStartAndRequiredField]["element_type"] = $defaultFields[$mustStartAndRequiredField]["element_type"] ?? "input";
        }

        foreach ($defaultFields as $keyName => $info) {
            $formDatum = [
                "module_type" => $moduleType,
                "label" => (string)($info["name"] ?? ""),
                "key_name" => $keyName,
                "is_open" => (int)($info["is_open"] ?? self::SWITCH_YES),
                "is_required" => (int)($info["is_required"] ?? self::SWITCH_YES),
                "is_edit" => self::SWITCH_YES,
                "field_type" => self::FIELD_TYPE_TEXT,
                "alert_required_message" => (string)($info["prompt"] ?? ""),
                "range" => [],
                "radio_list" => [],
            ];

            // 提示信息
            if (empty($formDatum["alert_required_message"])) {
                $formDatum["alert_required_message"] = sprintf("请输入您的%s", $formDatum["label"]);
            }

            // 元素类型
            $elementType = (string)($info["element_type"] ?? "input");

            // 如果是用户注册模块
            if ($moduleType == self::MODULE_TYPE_MEMBER_INFO) {
                switch ($elementType) {
                    case "select": // 下拉框
                        switch ($keyName) {
                            case "sex": // 性别
                                $formDatum["radio_list"] = [
                                    ["value" => 0, "label" => "未知", "is_checked" => 0],
                                    ["value" => 1, "label" => "男", "is_checked" => 0],
                                    ["value" => 2, "label" => "女", "is_checked" => 0],
                                ];
                                $formDatum["field_type"] = self::FIELD_TYPE_RADIO;
                                break;
                            case "birthday": // 生日
                                $formDatum["range"][] = [
                                    "start" => null,
                                    "end" => null,
                                ];
                                $formDatum["field_type"] = self::FIELD_TYPE_DATE;
                                break;
                            default: // 其他
                                $items = (array)($info["items"] ?? []);
                                foreach ($items as $value => $label) {
                                    $formDatum["radio_list"][] = ["value" => $value, "label" => $label, "is_checked" => 0];
                                }
                                $formDatum["field_type"] = self::FIELD_TYPE_RADIO;
                                break;
                        }
                        break;
                    case "checkbox": // 复选框
                        $items = (array)($info["items"] ?? []);
                        foreach ($items as $value => $item) {
                            $formDatum["radio_list"][] = ["value" => $value, "label" => $item["name"], "is_checked" => (int)($item["ischecked"] ?? 0)];
                        }
                        $formDatum["field_type"] = self::FIELD_TYPE_CHECKBOX;
                        break;
                    case "mobile":
                        $formDatum["field_type"] = self::FIELD_TYPE_MOBILE;
                        break;
                }
            } elseif ($moduleType == self::MODULE_TYPE_CHIEF_INFO) {
                switch ($elementType) {
                    case "mobile":
                        $formDatum["field_type"] = self::FIELD_TYPE_MOBILE;
                        break;
                }
            }

            $formData[$keyName] = $formDatum;
        }
        return $formData;
    }

    /**
     * 初始化数据
     * @param int $companyId 企业id
     * @param int $moduleType 模块类型
     * @return bool true表示初始化成功，false表示初始化失败可能是数据查询不到
     */
    public function init(int $companyId, int $moduleType, int $distributorId = 0): bool
    {
        // 获取默认的字段内容
        $formData = $this->getDefaultFieldContent($companyId, $moduleType);

        // 批量入库，如果存在数据就做更新
        foreach ($formData as $formDatum) {
            try {
                $formDatum["distributor_id"] = $distributorId;
                $this->create($companyId, $moduleType, $formDatum);
            } catch (\Exception $exception) {
                $field = $this->configRequestFieldsRepository->getInfo(["company_id" => $companyId, "module_type" => $moduleType, "key_name" => $formDatum["key_name"]]);
                if (!empty($field)) {
                    try {
                        $this->updateInfo($companyId, $field["id"], $formDatum);
                        $this->updateSwitch($companyId, $field["id"], self::SWITCH_COLUMN_IS_OPEN, (bool)$formDatum["is_open"], $distributorId);
                        $this->updateSwitch($companyId, $field["id"], self::SWITCH_COLUMN_IS_REQUIRED, (bool)$formDatum["is_required"], $distributorId);
                        $this->updateSwitch($companyId, $field["id"], self::SWITCH_COLUMN_IS_EDIT, (bool)$formDatum["is_edit"], $distributorId);
                    } catch (\Throwable $exception) {
                        app('api.exception')->report($exception);
                    }
                }
            }
        }
        return true;
    }

    /**
     * 根据模块类型做初始化
     * @param int $moduleType
     * @return bool
     */
    public function commandInitByModuleType(int $moduleType)
    {
        // 获取默认的字段内容
        $formData = $this->getDefaultFieldContent(0, $moduleType);

        // 批量入库，如果存在数据就做更新
        foreach ($formData as $formDatum) {
            try {
                $page = 1;
                $pageSize = 50;
                do {
                    $list = $this->configRequestFieldsRepository->getLists([
                        "module_type" => $moduleType,
                        "key_name" => $formDatum["key_name"]
                    ], "*", $page, $pageSize, ["id" => "DESC"]);
                    foreach ($list as $item) {
                        $companyId = (int)($item["company_id"] ?? 0);
                        $distributorId = (int)($item["distributor_id"] ?? 0);
                        $formDatum["distributor_id"] = $distributorId;
                        $id = (int)($item["id"] ?? 0);
                        $this->updateInfo($companyId, $id, $formDatum);
                        $this->updateSwitch($companyId, $id, self::SWITCH_COLUMN_IS_OPEN, (bool)$formDatum["is_open"], $distributorId);
                        $this->updateSwitch($companyId, $id, self::SWITCH_COLUMN_IS_REQUIRED, (bool)$formDatum["is_required"], $distributorId);
                        $this->updateSwitch($companyId, $id, self::SWITCH_COLUMN_IS_EDIT, (bool)$formDatum["is_edit"], $distributorId);
                    }
                    $page++;
                } while (count($list) === $pageSize);
            } catch (\Exception $exception) {
                echo sprintf("%s-%s-%s", $exception->getMessage(), $exception->getFile(), $exception->getLine()). PHP_EOL;
            }
        }
        return true;
    }

    /**
     * 缓存配置项
     */
    public const SETTING_SWITCH_FIRST_AUTH_FORCE_VALIDATION = "switch_first_auth_force_validation";
    // value为这个选项的默认值
    public const SETTING_MAP = [
        self::SETTING_SWITCH_FIRST_AUTH_FORCE_VALIDATION => 0
    ];

    /**
     * 更新配置项的内容
     * @param int $companyId 企业id
     * @param int $moduleType 模块id
     * @param array $data 需要更新的数据
     */
    public function updateSetting(int $companyId, int $moduleType, array $data, int $distributorId = 0)
    {
        $cacheService = new RedisCacheService($companyId, sprintf("ConfigRequestFieldsSetting_%d_%d", $moduleType, $distributorId));
        foreach ($data as $key => $value) {
            if (!isset(self::SETTING_MAP[$key])) {
                continue;
            }
            $cacheService->hashSet([$key => $value]);
        }
    }

    /**
     * 获取配置项的信息
     * @param int $companyId 企业id
     * @param int $moduleType 模块id
     * @return array
     */
    public function getSetting(int $companyId, int $moduleType, int $distributorId = 0): array
    {
        $data = (new RedisCacheService($companyId, sprintf("ConfigRequestFieldsSetting_%d_%d", $moduleType, $distributorId)))->hashGet(null);
        // 填充选项
        foreach (self::SETTING_MAP as $key => $default) {
            if (!isset($data[$key])) {
                $data[$key] = $default;
            }
            // 值类型的转换
            switch ($key) {
                case self::SETTING_SWITCH_FIRST_AUTH_FORCE_VALIDATION:
                    $data[$key] = (int)$data[$key];
                    break;
            }
        }
        return $data;
    }

    /**
     * 将数据库从存储的int类型的值做描述输出
     * @param int $companyId 企业id
     * @param int $moduleType 模块类型
     * @param array $dbData 表单数据
     */
    public function transformGetDescByValue(int $companyId, int $moduleType, array &$dbData, int $distributorId = 0): void
    {
        if (empty($dbData)) {
            return;
        }

        // 获取配置的请求字段和字段的枚举值
        $fields = $this->getListAndHandleSettingFormat($companyId, $moduleType, $distributorId);

        // 遍历从数据库中获取的数据参数
        foreach ($dbData as $key => $value) {
            // 不存在配置项中或者不存在字段类型，就直接跳过
            if (empty($fields[$key]) || empty($fields[$key]["field_type"])) {
                continue;
            }

            switch ($fields[$key]["field_type"]) {
                // 单选项类型的字段的值改为 枚举值的描述内容
                case ConfigRequestFieldsService::FIELD_TYPE_RADIO:
                    $dbData[$key] = $fields[$key]["select"][$value] ?? null;
                    break;
            }
        }
    }

    /**
     * 将前端传递的表单值最终转成int类型存入db中
     * @param int $companyId 企业id
     * @param int $moduleType 模块类型
     * @param array $formData 表单数据
     */
    public function transformGetValueByDesc(int $companyId, int $moduleType, array &$formData, int $distributorId = 0): void
    {
        if (empty($formData)) {
            return;
        }

        // 获取配置的请求字段和字段的枚举值
        $fields = $this->getListAndHandleSettingFormat($companyId, $moduleType, $distributorId);

        // 遍历表单数据
        foreach ($formData as $key => $value) {
            // 不存在配置项中或者不存在字段类型，就直接跳过
            if (empty($fields[$key]) || empty($fields[$key]["field_type"])) {
                continue;
            }

            switch ($fields[$key]["field_type"]) {
                // 如果字段类型是单选项，则需要保存int类型
                case ConfigRequestFieldsService::FIELD_TYPE_RADIO:
                    $select = (array)($fields[$key]["select"] ?? []);
                    foreach ($select as $indexValue => $desc) {
                        // 如果表单的值等于枚举的描述值 或 表单的值等于枚举的索引值，则将表单的结果值改为索引值
                        if ($value === $desc || $value === $indexValue) {
                            $formData[$key] = $indexValue;
                            break;
                        }
                    }
                    break;
            }
        }
    }
}
