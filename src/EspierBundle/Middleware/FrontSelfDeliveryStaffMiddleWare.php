<?php

declare(strict_types=1);

namespace EspierBundle\Middleware;

use Closure;
use EspierBundle\Support\FrontMerchantOperationGate;
use Illuminate\Http\Request;

class FrontSelfDeliveryStaffMiddleWare
{
    /**
     * @param Request $request
     */
    public function handle($request, Closure $next)
    {
        $auth = (array) $request->attributes->get('auth', []);
        FrontMerchantOperationGate::assertSelfDeliveryStaffOrForbidden($auth);

        return $next($request);
    }
}
