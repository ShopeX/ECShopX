<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PopularizeBundle\Neo4jDatabase;

use GraphAware\Neo4j\Client\ClientBuilder;
use GraphAware\Neo4j\Client\Neo4jClientEvents;

class Client
{
    public $client;

    public $config;

    public function connection($name = 'default')
    {
        // 0x456353686f7058
        $listener = new EventListener();

        $this->setConfig($name);
        $clientBuilder = new ClientBuilder();

        if (isset($this->client[$name])) {
            return $this->client[$name];
        } else {
            $this->client[$name] = $clientBuilder->create()
                 ->addConnection('default', $this->createUrl())
                 ->registerEventListener(Neo4jClientEvents::NEO4J_PRE_RUN, [$listener, 'onPreRun'])
                 ->registerEventListener(Neo4jClientEvents::NEO4J_POST_RUN, [$listener, 'onPostRun'])
                 ->registerEventListener(Neo4jClientEvents::NEO4J_ON_FAILURE, [$listener, 'onFailure'])
                 ->build();
            return $this->client[$name];
        }
    }

    public function setConfig($name = null)
    {
        $name = $name ?: 'default';
        $this->config = config('database.neo4j.'.$name);
        return $this->config;
    }

    public function createUrl()
    {
        // 0x456353686f7058
        if ($this->config['protocol'] == 'http') {
            $httpUrl = sprintf(
                'http://%s:%s@%s:%s',
                $this->config['username'],
                $this->config['password'],
                $this->config['host'],
                $this->config['port']
            );
            return $httpUrl;
        } else {
            $httpUrl = sprintf(
                'bolt://%s:%s@%s:%s',
                $this->config['username'],
                $this->config['password'],
                $this->config['host'],
                $this->config['port']
            );
            return $httpUrl;
        }
    }
}
