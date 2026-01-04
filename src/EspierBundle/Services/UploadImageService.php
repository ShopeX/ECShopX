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

namespace EspierBundle\Services;

use EspierBundle\Entities\UploadImages;
use EspierBundle\Entities\UploadImagesCat;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\DeleteResourceFailedException;

class UploadImageService
{
    public $uploadImagesRepository = null;
    public $uploadImagesCatRepository = null;

    public function __construct()
    {
        // ShopEx EcShopX Service Component
        $this->uploadImagesRepository = app('registry')->getManager('default')->getRepository(UploadImages::class);
        // $this->uploadImagesCatRepository = app('registry')->getManager('default')->getRepository(UploadImagesCat::class);
        $this->uploadImagesCatRepository = getRepositoryLangue(UploadImagesCat::class);
    }

    /**
     * 保存图片分类
     *
     * @return
     * @author array
     **/
    public function saveImageCat($params)
    {
        // ShopEx EcShopX Business Logic Layer
        if (isset($params['image_cat_id']) && $params['image_cat_id']) {
            $catFilter = [
                'image_cat_id' => $params['image_cat_id'],
                'company_id' => $params['company_id'],
                'parent_id' => $params['parent_id'],
            ];
            $result = $this->uploadImagesCatRepository->updateOneBy($catFilter, $params);
        } else {
            if ($params['parent_id']) {
                $parentFilter = [
                    'image_cat_id' => $params['parent_id'],
                    'company_id' => $params['company_id'],
                ];
                $parentCat = $this->uploadImagesCatRepository->getInfo($parentFilter);
                if (!$parentCat) {
                    throw new ResourceException("父分类id为{$params['parent_id']}不存在");
                }
                $params['path'] = $parentCat['path'].$params['parent_id'].',';
            } else {
                $params['path'] = ',';
            }
            $result = $this->uploadImagesCatRepository->create($params);
        }

        return $result;
    }

    /**
     * 删除图片分类
     *
     * @return array
     * @author
     **/
    public function delImageCat($companyId, $imageCatId)
    {
        // ShopEx EcShopX Service Component
        $params = [
            'company_id' => $companyId,
            'parent_id' => $imageCatId,
        ];
        $children = $this->uploadImagesCatRepository->getInfo($params);
        if ($children) {
            throw new DeleteResourceFailedException('请先删除子文件夹');
        }

        $filter = [
            'company_id' => $companyId,
            'image_cat_id' => $imageCatId,
        ];
        $imagesCount = $this->uploadImagesRepository->count($filter);
        if ($imagesCount > 0) {
            throw new DeleteResourceFailedException('该文件夹下存在图片，不能删除');
        }

        $res = $this->uploadImagesCatRepository->deleteBy($filter);

        return ['status' => $res];
    }


    /**
     * 获取商品图片分类信息
     *
     * @return array
     * @author
     **/
    public function getImageCatInfo($companyId, $imageCatId)
    {
        $filter = [
            'company_id' => $companyId,
            'image_cat_id' => $imageCatId,
        ];

        $result = $this->uploadImagesCatRepository->getInfo($filter);

        return $result;
    }

    /**
     * 获取分类下的子分类
     *
     * @return array
     * @author
     **/
    public function getImageCatChildren($params)
    {
        $parentId = (isset($params['image_cat_id']) && $params['image_cat_id']) ? $params['image_cat_id'] : 0;
        $filter = [
            'company_id' => $params['company_id'],
            'parent_id' => $parentId,
            'supplier_id' => $params['supplier_id'],
            'merchant_id' => $params['merchant_id'],
            'distributor_id' => $params['distributor_id'],
        ];

        $result = $this->uploadImagesCatRepository->lists($filter);

        return $result;
    }

    /**
     * 将图片从图片类型的一个子分类移动到(同类型或不同类型)另一个子分类
     *
     * @return void
     * @author
     **/
    public function moveImgCat($params)
    {
        $catFilter = [
            'company_id' => $params['company_id'],
            'image_cat_id' => $params['image_cat_id'],
        ];
        $cat = $this->uploadImagesCatRepository->getInfo($catFilter);
        if ($params['image_cat_id'] && !$cat) {
            throw new ResourceException("被移动到的图片类型分类不存在");
        }

        $imageFilter = [
            'company_id' => $params['company_id'],
            'image_id' => explode(',', $params['image_id']),
        ];
        $images = $this->uploadImagesRepository->lists($imageFilter);
        if ($images['total_count'] <= 0) {
            throw new ResourceException("被移动图片不存在");
        }

        $updateImageIds = [];
        foreach ($images['list'] as $item) {
            if ($item['image_cat_id'] != $params['image_cat_id']) {
                $updateImageIds[] = $item['image_id'];
            }
        }

        if ($updateImageIds) {
            $update = [
                'image_cat_id' => $params['image_cat_id']
            ];

            $filter = [
                'company_id' => $params['company_id'],
                'image_id' => $updateImageIds,
            ];

            $result = $this->uploadImagesRepository->updateBy($filter, $update);
        }

        return true;
    }

    /**
     * 获取图片
     *
     * @return array
     * @author
     **/
    public function getImagesBy($params, $page, $limit, $orderBy = ["created" => 'DESC'])
    {
        $result = $this->uploadImagesRepository->lists($params, $page, $limit, $orderBy);
        if ($result['list']) {
            switch (strtolower($params['storage'])) {
                case 'videos':
                    $filesystem = app('filesystem')->disk('import-videos');
                    break;
                default:
                    $filesystem = app('filesystem')->disk('import-image');
                    break;
            }
            // $bucketDomain = $filesystem->url('/');;
            foreach ($result['list'] as &$list) {
                // 图片翻转还原
                // $list['image_full_url'] = $bucketDomain."/".$list['image_url'].'?imageMogr2/auto-orient';
                // $list['url'] = $bucketDomain."/".$list['image_url'].'?imageMogr2/auto-orient';
                // 图片可能翻转了

                //$list['image_full_url'] = $bucketDomain."/".$list['image_url'];
                //$list['url'] = $bucketDomain."/".$list['image_url'];
                $url = $filesystem->url($list['image_url']);
                $list['image_full_url'] = $url;
                $list['url'] = $url;
            }
        }
        return $result;
    }

    /**
     * 图片保存
     *
     * @return array
     * @author
     **/
    public function saveImage($params)
    {
        $result = $this->uploadImagesRepository->create($params);

        return $result;
    }

    /**
     * 批量删除图片
     *
     * @return void
     * @author
     **/
    public function delImage($companyId, $imageIds)
    {
        $filter = [
            'company_id' => $companyId,
            'image_id' => $imageIds,
        ];

        $update = [
            'disabled' => 1
        ];

        $this->uploadImagesRepository->updateBy($filter, $update);

        return true;
    }
}
