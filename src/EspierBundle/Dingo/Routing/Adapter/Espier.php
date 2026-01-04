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
