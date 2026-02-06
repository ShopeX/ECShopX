<?php
namespace KujialeBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\ValidationHttpException;

use App\Http\Controllers\Controller as Controller;
use kujialeBundle\Services\DesignerWorksScriptService;
use MembersBundle\Services\MemberService;
use MembersBundle\Traits\MemberSearchFilter;
use EspierBundle\Traits\GetExportServiceTraits;
use EspierBundle\Jobs\ExportFileJob;
use MembersBundle\Jobs\BatchActionMembers;
use KaquanBundle\Jobs\batchReceiveMemberCard;
use KaquanBundle\Services\VipGradeService;
use SystemLinkBundle\Http\ThirdApi\V1\Action\Item;
use KujialeBundle\Entities\KujialeDesignerWorksItemRel;
use KujialeBundle\Repositories\KujialeDesignerWorksItemRelRepository;
use KujialeBundle\Repositories\KujialeDesignerWorksRepository;
use KujialeBundle\Entities\KujialeDesignerWorks;
use GoodsBundle\Repositories\ItemsRepository;
use GoodsBundle\Entities\Items;
use KujialeBundle\Services\KujialeDesignerWorksService;

class KuController extends Controller
{
    /**
     * @var KujialeDesignerWorksItemRelRepository
     */
    protected $worksItemRelRepository;

    /**
     * @var ItemsRepository
     */
    protected $itemsRepository;

    /**
     * @var KujialeDesignerWorksService
     */
    protected $desginService;

    /**
     * @var KujialeDesignerWorksRepository
     */
    protected $designerWorksRepository;

    public function __construct()
    {
        $this->worksItemRelRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorksItemRel::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $this->desginService = new KujialeDesignerWorksService();
        $this->designerWorksRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerWorks::class);
    }

    /**
     * 测试方法
     * @param Request $request
     * @route 未配置路由
     */
    public function onTest(Request $request)
    {
       $itemService = new DesignerWorksScriptService();
       $itemService->updateDesignerWorksPic();
    }

    /**
     * 设计师方案列表
     * @param Request $request
     * @route 未配置路由（可能在其他路由文件中）
     */
    public function desginWorkList(Request $request){

        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);

        $filter = [];

        $keyword = $request->input('keywords');
        if(isset($keyword) && !empty($keyword)){
            $filter['keyword'] = $keyword;
        }

        $tags = $request->input('tags_params');
        if(isset($tags) && !empty($tags)){
            if(!is_array($tags)){
                throw new ResourceException('参数无效');
            }
            $filter['tags'] = $tags;
        }

        $filter['normal'] = $request->input('normal') ?? '';
        $filter['new'] = $request->input('new') ?? '';
        $filter['viewcount'] = $request->input('viewcount') ?? '';
        $filter['likecount'] = $request->input('likecount') ?? '';

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 50);

        $result = $this->desginService->getDesignerWorksList($filter,$page,$limit);
        return $this->response->array($result);
    }

    /**
     * 新增设计师作品与商品绑定
     * @route POST /kujiale/designer-works/bind-item
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bindDesignerWorksItem(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'item_id' => 'required',
            'design_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors());
        }

        // item_id 支持数组
        $itemIds = $request->input('item_id');
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }
        // 去重并过滤非法值
        $itemIds = array_values(array_unique(array_filter($itemIds, function ($id) {
            return is_numeric($id) && $id > 0;
        })));

        if (empty($itemIds)) {
            throw new ResourceException('item_id 参数无效');
        }

        $designId = $request->input('design_id');

        // 检查该 design_id 是否已经绑定过商品（一个 design 只能绑定一个商品）
        // $existingRel = $this->worksItemRelRepository->getInfo(['design_id' => $designId]);
        // if (!empty($existingRel)) {
        //     // 如果已存在绑定关系，先删除旧的
        //     $this->worksItemRelRepository->deleteBy(['design_id' => $designId]);
        // }

        $success = 0;
        $skipped = 0;
        $errors = [];
        $createdData = [];

        foreach ($itemIds as $itemId) {
            // 验证商品是否存在
            $item = $this->itemsRepository->getInfo(['item_id' => $itemId]);
            if (empty($item)) {
                $errors[] = "商品不存在: {$itemId}";
                continue;
            }

            // 如果该商品已存在绑定关系则跳过
            $existingItemRel = $this->worksItemRelRepository->getInfo([
                'item_id' => $itemId,
                'design_id'=>$designId
            ]);
            if (!empty($existingItemRel)) {
                $skipped++;
                continue;
            }

            // 获取商品的 goods_bn（SPU货号）
            $goodsBn = isset($item['goods_bn']) ? $item['goods_bn'] : null;

            // 创建新的绑定关系
            $data = [
                'item_id' => $itemId,
                'design_id' => $designId,
                'goods_bn' => $goodsBn,
                'created' => time(),
                'updated' => time(),
            ];

            try {
                $result = $this->worksItemRelRepository->create($data);
                $createdData[] = $result;
                $success++;
            } catch (\Exception $e) {
                $errors[] = "商品 {$itemId} 绑定失败：" . $e->getMessage();
            }
        }

        return $this->response->array([
            'success' => empty($errors),
            'message' => '绑定完成',
            'success_count' => $success,
            'skipped_count' => $skipped,
            'data' => $createdData,
            'errors' => $errors,
        ]);
    }

    /**
     * 删除设计师作品与商品绑定（支持批量）
     * @route DELETE /kujiale/designer-works/unbind-item
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unbindDesignerWorksItem(Request $request)
    {
        $data = $request->input();
        
        // 如果传入的是数组格式 [{"design_id":xx,"item_id":xxx}]
        if (isset($data[0]) && is_array($data[0])) {
            $bindings = $data;
        } else {
            // 兼容单个对象格式 {"design_id":xx,"item_id":xxx}
            $bindings = [$data];
        }

        // 验证数据结构
        if (empty($bindings) || !is_array($bindings)) {
            throw new ValidationHttpException(['message' => '参数格式错误，需要传入数组格式']);
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        try {
            foreach ($bindings as $index => $binding) {
                // 验证每个绑定关系的数据
                if (!isset($binding['design_id']) || !isset($binding['item_id'])) {
                    $failCount++;
                    $errors[] = "第" . ($index + 1) . "条数据缺少 design_id 或 item_id";
                    continue;
                }

                $designId = $binding['design_id'];
                $itemId = $binding['item_id'];

                // 验证 item_id 格式
                if (!is_numeric($itemId) || $itemId < 1) {
                    $failCount++;
                    $errors[] = "第" . ($index + 1) . "条数据的 item_id 格式错误";
                    continue;
                }

                // 验证 design_id 格式
                if (empty($designId)) {
                    $failCount++;
                    $errors[] = "第" . ($index + 1) . "条数据的 design_id 不能为空";
                    continue;
                }

                // 检查绑定关系是否存在
                $existingRel = $this->worksItemRelRepository->getInfo([
                    'design_id' => $designId,
                    'item_id' => $itemId
                ]);

                if (empty($existingRel)) {
                    $failCount++;
                    $errors[] = "第" . ($index + 1) . "条数据的绑定关系不存在";
                    continue;
                }

                // 删除绑定关系
                $this->worksItemRelRepository->deleteBy([
                    'design_id' => $designId,
                    'item_id' => $itemId
                ]);
                $successCount++;
            }

            $result = [
                'success' => true,
                'message' => '批量解绑完成',
                'total' => count($bindings),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ];

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }

            return $this->response->array($result);
        } catch (\Exception $e) {
            throw new ResourceException('解绑失败：' . $e->getMessage());
        }
    }

    /**
     * 查询关联了design的商品列表
     * @route GET /kujiale/designer-works/items
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDesignerWorksItems(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'integer|min:1',
            'pageSize' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors());
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        // 获取当前用户的公司ID
        $auth = (array)app("request")->attributes->get("auth");
        $companyId = app('auth')->user()->get('company_id');
        if (!$companyId) {
            throw new ResourceException('无法获取公司ID');
        }

        // 获取筛选条件
        $itemName = $request->input('item_name'); // 商品名称搜索
        $itemBn = $request->input('item_bn'); // 商品编号搜索
        $goodsBn = $request->input('goods_bn'); // SPU货号搜索
        $designId = $request->input('design_id'); // 方案ID搜索
        $designName = $request->input('design_name'); // 方案名称搜索
        $approveStatus = $request->input('approve_status'); // 商品状态筛选
        $itemCategory = $request->input('item_category'); // 管理分类筛选

        $conn = app('registry')->getConnection('default');
        
        // 构建查询
        $qb = $conn->createQueryBuilder();
        $qb->select([
            'i.item_id',
            'i.item_name',
            'i.item_bn',
            'i.goods_bn as item_goods_bn',
            'i.approve_status',
            'i.item_category',
            'i.store',
            'i.price',
            'i.market_price',
            'i.pics',
            'i.created',
            'i.updated',
            'rel.design_id',
            'rel.goods_bn',
            'w.design_name',
            'w.cover_pic',
            'rel.id as rel_id',
            'rel.created as bind_created'
        ])
        ->from('kujiale_designer_works_item_rel', 'rel')
        ->innerJoin('rel', 'items', 'i', 'rel.item_id = i.item_id')
        ->leftJoin('rel', 'kujiale_designer_works', 'w', 'rel.design_id = w.design_id')
        ->where($qb->expr()->eq('i.company_id', $qb->expr()->literal($companyId)));

        // 商品名称搜索
        if (!empty($itemName)) {
            $qb->andWhere($qb->expr()->like('i.item_name', $qb->expr()->literal('%' . $itemName . '%')));
        }

        // 商品编号搜索
        if (!empty($itemBn)) {
            $qb->andWhere($qb->expr()->like('i.item_bn', $qb->expr()->literal('%' . $itemBn . '%')));
        }

        // SPU货号搜索（支持在绑定表和商品表中搜索）
        if (!empty($goodsBn)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('rel.goods_bn', $qb->expr()->literal('%' . $goodsBn . '%')),
                $qb->expr()->like('i.goods_bn', $qb->expr()->literal('%' . $goodsBn . '%'))
            ));
        }

        // 方案ID搜索
        if (!empty($designId)) {
            $qb->andWhere($qb->expr()->eq('rel.design_id', $qb->expr()->literal($designId)));
        }

        // 方案名称搜索
        if (!empty($designName)) {
            $qb->andWhere($qb->expr()->like('w.design_name', $qb->expr()->literal('%' . $designName . '%')));
        }

        // 商品状态筛选
        if (!empty($approveStatus)) {
            if (is_array($approveStatus)) {
                $placeholders = [];
                foreach ($approveStatus as $index => $status) {
                    $placeholders[] = ':status_' . $index;
                    $qb->setParameter('status_' . $index, $status);
                }
                $qb->andWhere($qb->expr()->in('i.approve_status', $placeholders));
            } else {
                $qb->andWhere($qb->expr()->eq('i.approve_status', $qb->expr()->literal($approveStatus)));
            }
        }

        // 管理分类筛选
        if (!empty($itemCategory)) {
            if (is_array($itemCategory)) {
                $placeholders = [];
                foreach ($itemCategory as $index => $category) {
                    $placeholders[] = ':category_' . $index;
                    $qb->setParameter('category_' . $index, $category);
                }
                $qb->andWhere($qb->expr()->in('i.item_category', $placeholders));
            } else {
                $qb->andWhere($qb->expr()->eq('i.item_category', $qb->expr()->literal($itemCategory)));
            }
        }

        // 获取总数
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT rel.id) as total');
        $total = $countQb->execute()->fetchColumn();
        $total = intval($total);

        // 排序
        $qb->orderBy('rel.created', 'DESC');

        // 分页
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
               ->setMaxResults($pageSize);
        }

        // 执行查询
        $list = $qb->execute()->fetchAll();

        // 收集 item_id 和 design_id，便于批量查询销售分类和标签
        $itemIds = array_column($list, 'item_id');
        $designIds = array_column($list, 'design_id');

        // 批量查询销售分类（items_rel_cats + items_category，is_main_category=0）
        $salesCateMap = [];
        if (!empty($itemIds)) {
            $cateQb = $conn->createQueryBuilder();
            $cateQb->select(['irc.item_id', 'ic.category_name'])
                ->from('goods_items_rel_cats', 'irc')
                ->innerJoin('irc', 'items_category', 'ic', 'irc.category_id = ic.category_id')
                ->where($cateQb->expr()->in('irc.item_id', $itemIds))
                ->andWhere('ic.is_main_category = 0');
            $cateRows = $cateQb->execute()->fetchAll();
            foreach ($cateRows as $row) {
                $salesCateMap[$row['item_id']][] = $row['category_name'];
            }
        }

        // 批量查询商品标签（items_rel_tags + items_tags）
        $itemTagsMap = [];
        if (!empty($itemIds)) {
            $itemTagQb = $conn->createQueryBuilder();
            $itemTagQb->select(['irt.item_id', 'it.tag_id', 'it.tag_name', 'it.tag_color', 'it.font_color'])
                ->from('items_rel_tags', 'irt')
                ->innerJoin('irt', 'items_tags', 'it', 'irt.tag_id = it.tag_id')
                ->where($itemTagQb->expr()->in('irt.item_id', $itemIds))
                ->andWhere($itemTagQb->expr()->eq('irt.company_id', $itemTagQb->expr()->literal($companyId)))
                ->andWhere($itemTagQb->expr()->eq('it.company_id', $itemTagQb->expr()->literal($companyId)));

            $itemTagRows = $itemTagQb->execute()->fetchAll();
            foreach ($itemTagRows as $row) {
                $itemTagsMap[$row['item_id']][] = [
                    'tag_id' => $row['tag_id'],
                    'tag_name' => $row['tag_name'],
                    'tag_color' => $row['tag_color'],
                    'font_color' => $row['font_color'],
                ];
            }
        }

        // 批量查询设计标签（kujiale_designer_works_rel_tags）
        $designTagsMap = [];
        if (!empty($designIds)) {
            $tagQb = $conn->createQueryBuilder();
            // 使用占位符避免字符串未加引号导致的 SQL 错误
            $placeholders = [];
            foreach ($designIds as $idx => $dId) {
                $ph = ':design_id_' . $idx;
                $placeholders[] = $ph;
                $tagQb->setParameter('design_id_' . $idx, $dId);
            }

            $tagQb->select(['design_id', 'tag_name'])
                ->from('kujiale_designer_works_rel_tags')
                ->where($tagQb->expr()->in('design_id', $placeholders));

            $tagRows = $tagQb->execute()->fetchAll();
            foreach ($tagRows as $row) {
                $designTagsMap[$row['design_id']][] = $row['tag_name'];
            }
        }

        // 处理结果
        $resultList = [];
        foreach ($list as $row) {
            $salesCategories = $salesCateMap[$row['item_id']] ?? [];
            $itemTags = $itemTagsMap[$row['item_id']] ?? [];
            $designTags = $designTagsMap[$row['design_id']] ?? [];

            // 将销售分类数组转换为字符串（用逗号分隔）
            $itemCatName = !empty($salesCategories) ? implode(',', $salesCategories) : '';

            $item = [
                'item_id' => $row['item_id'],
                'item_name' => $row['item_name'],
                'item_bn' => $row['item_bn'],
                'goods_bn' => $row['goods_bn'] ?: $row['item_goods_bn'], // 优先使用绑定表中的goods_bn
                'approve_status' => $row['approve_status'],
                'item_category' => $row['item_category'],
                'stock' => isset($row['store']) ? (int)$row['store'] : null,
                'itemCatName' => $itemCatName, // 销售分类名称（字符串格式）
                'tagList' => $itemTags, // 商品标签列表
                'price' => $row['price'],
                'market_price' => $row['market_price'],
                'pics' => $row['pics'] ? json_decode($row['pics'], true) : [],
                'created' => $row['created'],
                'updated' => $row['updated'],
                'design' => [
                    'design_id' => $row['design_id'],
                    'design_name' => $row['design_name'],
                    'cover_pic' => $row['cover_pic'],
                    'tags' => $designTags ? implode(',', $designTags) : '',
                ],
                'bind_info' => [
                    'rel_id' => $row['rel_id'],
                    'bind_created' => $row['bind_created'],
                ]
            ];
            $resultList[] = $item;
        }

        return $this->response->array([
            'success' => true,
            'total_count' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'list' => $resultList
        ]);
    }

    /**
     * 获取设计师作品列表（用于前端选择设计并绑定商品）
     * @route GET /kujiale/designer-works/list
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDesignerWorksList(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'integer|min:1',
            'pageSize' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors());
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        // 获取筛选条件
        $designName = $request->input('design_name'); // 设计名称搜索
        $designId = $request->input('design_id'); // 设计ID搜索
        $keyword = $request->input('keyword'); // 关键词搜索（设计名称或设计ID）

        // 构建查询条件
        $filter = [];

        // 设计名称搜索
        if (!empty($designName)) {
            $filter['design_name|like'] = $designName;
        }

        // 设计ID搜索
        if (!empty($designId)) {
            $filter['design_id'] = $designId;
        }

        // 关键词搜索（同时搜索设计名称和设计ID）
        if (!empty($keyword)) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('*')
               ->from('kujiale_designer_works')
               ->where($qb->expr()->orX(
                   $qb->expr()->like('design_name', $qb->expr()->literal('%' . $keyword . '%')),
                   $qb->expr()->like('design_id', $qb->expr()->literal('%' . $keyword . '%'))
               ));
            
            // 获取总数
            $countQb = clone $qb;
            $countQb->select('COUNT(*) as total');
            $total = $countQb->execute()->fetchColumn();
            $total = intval($total);

            // 排序和分页
            $qb->orderBy('created', 'DESC');
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                   ->setMaxResults($pageSize);
            }

            $list = $qb->execute()->fetchAll();
        } else {
            // 使用 Repository 的 lists 方法
            $orderBy = ['created' => 'DESC'];
            $result = $this->designerWorksRepository->lists($filter, $page, $pageSize, $orderBy);
            $total = $result['total_count'];
            $list = $result['list'];
        }

        // 批量获取所有已绑定的设计信息（用于标记是否已绑定）
        $boundDesignMap = []; // key: design_id, value: boundRel info
        $boundItemMap = []; // key: item_id, value: item info
        if (!empty($list)) {
            $designIds = array_column($list, 'design_id');
            $boundRels = $this->worksItemRelRepository->getLists(['design_id' => $designIds]);
            
            // 构建绑定关系映射
            foreach ($boundRels as $boundRel) {
                $boundDesignMap[$boundRel['design_id']] = $boundRel;
                // 收集需要查询的商品ID
                if (!empty($boundRel['item_id'])) {
                    $boundItemMap[$boundRel['item_id']] = null; // 先占位
                }
            }
            
            // 批量查询商品信息
            if (!empty($boundItemMap)) {
                $itemIds = array_keys($boundItemMap);
                $conn = app('registry')->getConnection('default');
                $qb = $conn->createQueryBuilder();
                $qb->select('item_id', 'item_name', 'item_bn', 'goods_bn')
                   ->from('items')
                   ->where($qb->expr()->in('item_id', $itemIds));
                $items = $qb->execute()->fetchAll();
                
                foreach ($items as $item) {
                    $boundItemMap[$item['item_id']] = $item;
                }
            }
        }

        // 处理结果
        $resultList = [];
        foreach ($list as $row) {
            $designId = $row['design_id'];
            $isBound = isset($boundDesignMap[$designId]);
            
            // 如果已绑定，获取绑定的商品信息
            $boundItemInfo = null;
            if ($isBound) {
                $boundRel = $boundDesignMap[$designId];
                if (!empty($boundRel['item_id']) && isset($boundItemMap[$boundRel['item_id']])) {
                    $item = $boundItemMap[$boundRel['item_id']];
                    if (!empty($item)) {
                        $boundItemInfo = [
                            'item_id' => $item['item_id'],
                            'item_name' => $item['item_name'],
                            'item_bn' => $item['item_bn'],
                            'goods_bn' => $boundRel['goods_bn'] ?: $item['goods_bn'],
                        ];
                    }
                }
            }

            $design = [
                'id' => $row['id'],
                'design_id' => $designId,
                'design_name' => $row['design_name'],
                'cover_pic' => $row['cover_pic'],
                'plan_id' => $row['plan_id'],
                'comm_name' => $row['comm_name'],
                'city' => $row['city'],
                'name' => $row['name'],
                'view_count' => $row['view_count'] ?? 0,
                'like_count' => $row['like_count'] ?? 0,
                'created' => $row['created'],
                'updated' => $row['updated'],
                'is_bound' => $isBound, // 是否已绑定商品
                'bound_item' => $boundItemInfo, // 绑定的商品信息（如果已绑定）
            ];
            $resultList[] = $design;
        }

        return $this->response->array([
            'success' => true,
            'total_count' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'list' => $resultList
        ]);
    }
}
