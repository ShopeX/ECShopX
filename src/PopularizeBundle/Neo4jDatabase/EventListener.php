<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PopularizeBundle\Neo4jDatabase;

use GraphAware\Neo4j\Client\Event\FailureEvent;
use GraphAware\Neo4j\Client\Event\PostRunEvent;
use GraphAware\Neo4j\Client\Event\PreRunEvent;

class EventListener
{
    public $hookedPreRun = false;

    public $hookedPostRun = false;

    public $e;

    public function onPreRun(PreRunEvent $event)
    {
        if (count($event->getStatements()) > 0) {
            $this->hookedPreRun = true;
        }
    }

    public function onPostRun(PostRunEvent $event)
    {
        if ($event->getResults()->size() > 0) {
            $this->hookedPostRun = true;
        }
    }

    public function onFailure(FailureEvent $event)
    {
        $this->e = $event->getException();
        app('log')->debug($this->e);
        $event->disableException();
    }
}
