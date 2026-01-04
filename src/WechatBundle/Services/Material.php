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

namespace WechatBundle\Services;

use Dingo\Api\Exception\StoreResourceFailedException;
use EasyWeChat\Kernel\Messages\Article;

/**
 * 素材管理
 */
class Material
{
    /**
     * 公众号实例
     *
     */
    public $app;

    // 上传对象实例
    public $uploadObject;

    public function application($authorizerAppId, $isTemp = false)
    {
        $openPlatform = new OpenPlatform();
        if (!$authorizerAppId) {
            throw new StoreResourceFailedException('当前账号未绑定公众号，请先绑定公众号');
        }
        $this->app = $openPlatform->getAuthorizerApplication($authorizerAppId);

        $this->setMaterialType($isTemp);

        return $this;
    }

    /**
     * 设置是否为临时素材
     */
    public function setMaterialType($isTemp = false)
    {
        if ($isTemp === true || $isTemp === 'true') {
            $this->uploadObject = $this->app->media;
        } else {
            $this->uploadObject = $this->app->material;
        }
        return $this;
    }

    /**
     * 获取永久素材 function
     *
     * @return void
     */
    public function getMaterial($materialId)
    {
        // 判断是否是url
        if (isurl($materialId)) {
            $material['down_url'] = $materialId;
            return $material;
        }
        try {
            $material = $this->app->material->get($materialId);
        } catch (\Exception $e) {
            $material = [];
        }
        return $material;
    }

    /**
     * 获取永久素材列表
     */
    public function getMaterialLists($type, $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        try {
            $result = $this->app->material->list($type, $offset, $limit);
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 上传永久图片素材
     *
     * @return string media_id
     * @return string url
     */
    public function uploadImage($path)
    {
        return $this->uploadObject->uploadImage($path);
    }

    /**
     * 上传永久图片素材
     *
     * @return string media_id
     * @return string url
     */
    public function stats()
    {
        // https://mp.weixin.qq.com/wiki?action=doc&id=mp1444738729
        // 公众号的素材库保存总数量有上限：图文消息素材、图片素材上限为5000，其他类型为1000。
        $data = $this->app->material->stats();
        $data['image_limit'] = isset($data['image_count']) ? (5000 - $data['image_count']) : 5000;
        $data['news_limit'] = isset($data['news_count']) ? (5000 - $data['news_count']) : 5000;
        $data['video_limit'] = isset($data['video_count']) ? (1000 - $data['video_count']) : 1000;
        return $data;
    }

    /**
     * 上传永久上传缩略图
     *
     * @return string media_id
     * @return string url
     */
    public function uploadThumb($path)
    {
        return $this->uploadObject->uploadThumb($path);
    }

    /**
     * 永久素材 上传视频
     *
     * @return string media_id
     * @return string url
     */
    public function uploadVideo($path, $title, $description)
    {
        return $this->uploadObject->uploadVideo($path, $title, $description);
    }

    /**
     * 永久素材 上传声音
     *
     * 语音大小不超过 5M，长度不超过 60 秒，支持 mp3/wma/wav/amr 格式。
     *
     * @return string media_id
     * @return string url
     */
    public function uploadVoice($path)
    {
        return $this->uploadObject->uploadVoice($path);
    }

    /**
     * 上传图文消息内的图片
     * 不占用公众号的素材库中图片数量的5000个的限制，
     * 图片仅支持jpg/png格式
     * 大小必须在1MB以下。
     */
    public function uploadArticleImage($path)
    {
        return $this->app->material->uploadArticleImage($path);
    }

    public function updateArticle($mediaId, $data)
    {
        if (count($data) >= 9) {
            throw new StoreResourceFailedException('最多添加8个图文');
        }
        foreach ($data as $index => $row) {
            if (!$row['title']
                || !$row['author']
                || !$row['content']
                || !$row['thumb_media_id']
            ) {
                throw new StoreResourceFailedException('请填写完整的图文消息');
            }
            $article = new Article([
                'title' => $row['title'],
                'thumb_media_id' => $row['thumb_media_id'],
                'show_cover' => $row['show_cover_pic'],
                'author' => $row['author'],
                'digest' => $row['digest'],
                'content' => $row['content'],
                'content_source_url' => $row['content_source_url']
            ]);
            $this->app->material->updateArticle($mediaId, $article, $index);
        }
        return true;
    }

    /**
     * 上传图文
     */
    public function uploadArticle($data)
    {
        if (count($data) >= 9) {
            throw new StoreResourceFailedException('最多添加8个图文');
        }
        foreach ($data as $row) {
            if (!$row['title']
                || !$row['author']
                || !$row['content']
                || !$row['thumb_media_id']
            ) {
                throw new StoreResourceFailedException('请填写完整的图文消息');
            }
            $article[] = new Article([
                'title' => $row['title'],
                'thumb_media_id' => $row['thumb_media_id'],
                'show_cover' => $row['show_cover_pic'],
                'author' => $row['author'],
                'digest' => $row['digest'],
                'content' => $row['content'],
                'content_source_url' => $row['content_source_url']
            ]);
        }

        return $this->app->material->uploadArticle($article);
    }

    /**
     * 删除素材
     */
    public function deleteMaterial($mediaIds)
    {
        $mediaIds = explode(',', $mediaIds);
        foreach ($mediaIds as $mediaId) {
            $this->app->material->delete($mediaId);
        }

        return true;
    }
}
