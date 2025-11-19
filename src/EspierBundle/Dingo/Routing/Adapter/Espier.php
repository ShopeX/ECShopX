<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Dingo\Routing\Adapter;

use Illuminate\Http\Request;
use Dingo\Api\Exception\UnknownVersionException;
use Dingo\Api\Routing\Adapter\Lumen as DingoLumenAdapter;

class Espier extends DingoLumenAdapter
{
    /**
     * Dispatch a request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $version
     *
     * @return mixed
     */
    public function dispatch(Request $request, $version)
    {
        if (!isset($this->routes[$version])) {
            throw new UnknownVersionException();
        }

        $routeCollector = $this->mergeOldRoutes($version);
        $dispatcher = call_user_func($this->dispatcherResolver, $routeCollector);

        $this->app->setDispatcher($dispatcher);

        $this->normalizeRequestUri($request);

        return $this->app->directDispatch($request);
    }
}
