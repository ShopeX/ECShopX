<?php

namespace ThemeBundle\Services;

use ThemeBundle\Entities\PagesAdPlace;
use Dingo\Api\Exception\ResourceException;
use ThemeBundle\Entities\PagesAdPlaceRelMemberTags;
use ThemeBundle\Entities\PagesAdPlaceRelDistributors;

class PagesAdPlaceService
{
    private $entityRepository;
    private $relMemberTagsRepository;
    private $relDistributorsRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PagesAdPlace::class);
        $this->relMemberTagsRepository = app('registry')->getManager('default')->getRepository(PagesAdPlaceRelMemberTags::class);
        $this->relDistributorsRepository = app('registry')->getManager('default')->getRepository(PagesAdPlaceRelDistributors::class);
    }

    public function getListWithRelTags($filter, $page, $pageSize, $orderBy)
    {
        $result = $this->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if ($result['list']) {
            $adPlaceIds = array_column($result['list'], 'id');
            $relTags = $this->relMemberTagsRepository->getRelTagsByAdPlaceIds($adPlaceIds);
            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['rel_tags'] = $relTags[$value['id']] ?? [];
            }
            $relDistributors = $this->relDistributorsRepository->getRelDistributorsByAdPlaceIds($adPlaceIds);
            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['rel_distributors'] = $relDistributors[$value['id']] ?? [];
            }
        }
        return $result;
    }

    public function getInfoWithRelTags($filter)
    {
        $result = $this->entityRepository->getInfo($filter);
        if ($result) {
            $relTags = $this->relMemberTagsRepository->getRelTagsByAdPlaceIds([$result['id']]);
            $result['rel_tags'] = $relTags[$result['id']] ?? [];
            $relDistributors = $this->relDistributorsRepository->getRelDistributorsByAdPlaceIds([$result['id']]);
            $result['rel_distributors'] = $relDistributors[$result['id']] ?? [];
        }
        return $result;
    }

    public function createWithRelTags($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $params['use_bound'] = 0;
            if (isset($params['distributor_id'])) {
                $params['distributor_id'] = (array)$params['distributor_id'];
                $params['distributor_id'] = array_filter($params['distributor_id']);
                if ($params['distributor_id']) {
                    $params['use_bound'] = 1;
                }
            }
            $result = $this->entityRepository->create($params);
            if (isset($params['rel_tags']) && $params['rel_tags']) {
                $relTags = [];
                foreach ($params['rel_tags'] as $tag) {
                    $relTags[] = [
                        'company_id' => $result['company_id'],
                        'ad_place_id' => $result['id'],
                        'tag_id' => $tag['tag_id'],
                    ];
                }
                $this->relMemberTagsRepository->batchInsert($relTags);
            }
            if (isset($params['distributor_id']) && $params['distributor_id']) {
                $relDistributors = [];
                foreach ($params['distributor_id'] as $distributorId) {
                    $relDistributors[] = [
                        'company_id' => $result['company_id'],
                        'ad_place_id' => $result['id'],
                        'distributor_id' => $distributorId,
                    ];
                }
                $this->relDistributorsRepository->batchInsert($relDistributors);
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw new ResourceException($e->getMessage());
        }
    }

    public function updateWithRelTags($filter, $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $params['use_bound'] = 0;
            if (isset($params['distributor_id'])) {
                $params['distributor_id'] = (array)$params['distributor_id'];
                $params['distributor_id'] = array_filter($params['distributor_id']);
                if ($params['distributor_id']) {
                    $params['use_bound'] = 1;
                }
            }
            $result = $this->entityRepository->updateOneBy($filter, $params);
            $this->relMemberTagsRepository->deleteBy(['ad_place_id' => $filter['id'], 'company_id' => $filter['company_id']]);
            $this->relDistributorsRepository->deleteBy(['ad_place_id' => $filter['id'], 'company_id' => $filter['company_id']]);
            if (isset($params['rel_tags']) && $params['rel_tags']) {
                $relTags = [];
                foreach ($params['rel_tags'] as $tag) {
                    $relTags[] = [
                        'company_id' => $result['company_id'],
                        'ad_place_id' => $result['id'],
                        'tag_id' => $tag['tag_id'],
                    ];
                }
                $this->relMemberTagsRepository->batchInsert($relTags);
            }
            if (isset($params['distributor_id']) && $params['distributor_id']) {
                $relDistributors = [];
                foreach ($params['distributor_id'] as $distributorId) {
                    $relDistributors[] = [
                        'company_id' => $result['company_id'],
                        'ad_place_id' => $result['id'],
                        'distributor_id' => $distributorId,
                    ];
                }
                $this->relDistributorsRepository->batchInsert($relDistributors);
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw new ResourceException($e->getMessage());
        }
    }

    public function deleteWithRelTags($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->relMemberTagsRepository->deleteBy(['ad_place_id' => $filter['id'], 'company_id' => $filter['company_id']]);
            $this->relDistributorsRepository->deleteBy(['ad_place_id' => $filter['id'], 'company_id' => $filter['company_id']]);
            $this->entityRepository->deleteBy($filter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}