<?php

namespace KujialeBundle\Services;

use GoodsBundle\Services\ItemsAttributesService;
use KujialeBundle\Entities\KujialeDesignerTags;
use KujialeBundle\Repositories\KujialeDesignerTagsRepository;
use KujialeBundle\Services\api\ApiService;

class KujialeDesignerTagsService
{
    /**
     * 
     * @var KujialeDesignerTagsRepository $tagsRepository   
     */
    private $tagsRepository;

    public function __construct(){
        $this->tagsRepository = app('registry')->getManager('default')->getRepository(KujialeDesignerTags::class);
    }

    /**
     * 保存tags内容
     * @param $tagParams
     */
    public function saveTags($tagParams){
        $saveParams = [];

        foreach($tagParams as $tag){
            $tmp = [];
            $tmp['tag_category_id'] = $tag['tagCategoryId'];
            $tmp['tag_category_name'] = $tag['tagCategoryName'];
            $tmp['type'] = $tag['type'];
            $tmp['is_multiple_selected'] = $tag['isMultipleSelected'] == true ? 1 : 0;
            $tmp['is_disabled'] = $tag['isDisabled'] == true ? 1 : 0;
            if(isset($tag['tags']) && !empty($tag['tags'])){
                foreach($tag['tags'] as $t){
                    $tmp['tag_id'] = $t['id'];
                    $tmp['tag_name'] = $t['name'];
                    $saveParams[] = $tmp;
                }
            }
        }
        if(!empty($saveParams)){
            try{
                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();

                foreach($saveParams as $save){
                    $filter['tag_category_id'] = $save['tag_category_id'];
                    $filter['tag_id'] = $save['tag_id'];
                    if($this->tagsRepository->count($filter)){
                        $save['updated'] = time();
                        $this->tagsRepository->updateOneBy($filter,$save);
                    }else{
                        $save['created'] = time();
                        $this->tagsRepository->create($save);
                    }
                }
                $conn->commit();
            }catch(\Exception $e){
                $conn->rollback();
                throw $e;
            }
        }
        return true;
    }

    public function getTagsList($filter){
        $result = $this->tagsRepository->getLists($filter);
        if($result){
            return $this->packageTreeData($result);
        }
        return [];
    }

    /**
     * 组装树形结构体
     * @param $resultData
     * @return array
     */
    private function packageTreeData($resultData){
        $newPackageTree = [];
        foreach($resultData as $data){
            if(isset($newPackageTree[$data['tag_category_id']]) && !empty($newPackageTree[$data['tag_category_id']])){
                $tags['tag_id'] = $data['tag_id'];
                $tags['tag_name'] = $data['tag_name'];
                unset($data['tag_id']);
                unset($data['tag_name']);

                $newPackageTree[$data['tag_category_id']]['tags'][] = $tags;
            }else{
                $tags['tag_id'] = $data['tag_id'];
                $tags['tag_name'] = $data['tag_name'];
                unset($data['tag_id']);
                unset($data['tag_name']);
                $newPackageTree[$data['tag_category_id']] = $data;
                $newPackageTree[$data['tag_category_id']]['tags'][] = $tags;
            }
        }
        return array_values($newPackageTree);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->tagsRepository->$method(...$parameters);
    }
}