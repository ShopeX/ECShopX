<?php

use Laravel\Lumen\Application;
use Dingo\Api\Http\Middleware\Request as DingoRequest;
use Illuminate\Http\Request;

class AppKernel extends Application
{
    public function __construct($basePath = null)
    {
        parent::__construct($basePath);
        // 提前实例化queue, 提前configuration
        $this->make('queue');
    }

    public function loadRoutes()
    {
        $app = $this;
        $request = $this['request'];

        # 从2开始
        $pathInfoArray = explode('/', $request->getPathInfo());
        $dingoRoutingKeyOne = $dingoRoutingKeyTwo = $dingoRoutingKeyThree = '';

        if (isset($pathInfoArray[2])) {
            $dingoRoutingKeyOne = $pathInfoArray[2];
        }
        if (isset($pathInfoArray[3])) {
            $dingoRoutingKeyTwo = $pathInfoArray[3];
        }
        if (isset($pathInfoArray[4])) {
            $dingoRoutingKeyThree = $pathInfoArray[4];
        }

        $lumenRoutingKeyOne = $pathInfoArray[1];
        $lumenRoutingMd5 = md5($request->getMethod().'|'.$request->getPathInfo());

        require_once __DIR__.'/../bootstrap/route.php';
    }

    public function dispatch($request = null)
    {
        $this->parseIncomingRequest($request);

        $this->loadRoutes();

        $middleware = [DingoRequest::class];

        $dingoRequest = $this->make(DingoRequest::class);

        try {
            $this->boot();
            $response = $dingoRequest->handle($this['request'], function($request) {
                // 当api路由找不到时
                return $this->directDispatch($request);
            });
            // 统一http响应状态码
            $statusCode = $response->getStatusCode();
            if ($statusCode != 200 && $statusCode != 401) {
                $response->setStatusCode(200);
            }
            return $response;
            //return $this->sendThroughPipeline($middleware, function (){});
        } catch (Exception $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        } catch (Throwable $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        }
    }

    public function directDispatch($request = null)
    {
        return parent::dispatch($request);
    }
}
