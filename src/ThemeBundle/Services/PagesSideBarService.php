<?php

namespace ThemeBundle\Services;

use ThemeBundle\Entities\PagesSideBar;
use Dingo\Api\Exception\ResourceException;

class PagesSideBarService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PagesSideBar::class);
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