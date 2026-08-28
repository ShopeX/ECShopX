<?php

declare(strict_types=1);

namespace ThirdPartyBundle\Middleware;

use Closure;
use Illuminate\Http\Request;
use ThirdPartyBundle\Entities\CompanyRelKuaizhen;
use ThirdPartyBundle\Support\Kuaizhen580InboundSignatureVerifier;

class Kuaizhen580CallbackCheck
{
    public function handle(Request $request, Closure $next)
    {
        $data = $request->all();
        $sign = isset($data['sign']) ? trim((string) $data['sign']) : '';
        $clientId = isset($data['clientId']) ? trim((string) $data['clientId']) : '';

        if ($sign === '' || $clientId === '') {
            return $this->reject();
        }

        /** @var \ThirdPartyBundle\Repositories\CompanyRelKuaizhenRepository $repo */
        $repo = app('registry')->getManager('default')->getRepository(CompanyRelKuaizhen::class);
        $config = $repo->getInfo(['client_id' => $clientId]);
        $clientSecret = isset($config['client_secret']) ? trim((string) $config['client_secret']) : '';
        if ($clientSecret === '') {
            return $this->reject();
        }

        if (! Kuaizhen580InboundSignatureVerifier::verify($data, $clientSecret, $sign)) {
            return $this->reject();
        }

        return $next($request);
    }

    private function reject()
    {
        return response()->json(['err' => 403, 'errmsg' => 'sign error'], 403);
    }
}
