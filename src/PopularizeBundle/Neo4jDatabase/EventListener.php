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
