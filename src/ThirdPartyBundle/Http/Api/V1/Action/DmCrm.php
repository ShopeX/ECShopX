<?php
namespace ThirdPartyBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;

use Exception;

class DmCrm extends Controller
{
    /**
     * @SWG\Post(
     *     path="/third/dmcrm/setting",
     *     summary="达摩CRM配置信息保存",
     *     tags={"DmCrm"},
     *     description="达摩CRM配置信息保存",
     *     operationId="setSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="ent_sign", in="query", description="ent_sign required=true, type="string"),
     *     @SWG\Parameter( name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter( name="app_secret", in="query", description="app_secret", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="company_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new DmCrmSettingService();
        $postdata = $request->input();

        $data = [
            'is_open' => (isset($postdata['is_open']) && $postdata['is_open'] == 'true') ? true : false,
            'ent_sign' => trim($postdata['ent_sign']),
            'app_key' => trim($postdata['app_key']),
            'app_secret' => trim($postdata['app_secret']),
            'encodeAESKey' => $postdata['app_secret'] ?? '', // 用户回调解密的秘钥，又就有灭有就没有
            'url' => $postdata['url'] ?? '', // 接口域名
            'company_id' => $companyId,
        ];

        $service->setDmCrmSetting($companyId, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/third/dmcrm/setting",
     *     summary="获取达摩CRM配置信息保存",
     *     tags={"DmCrm"},
     *     description="获取达摩CRM配置信息保存",
     *     operationId="getSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="is_open", type="stirng", description="是否开启"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $service = new DmCrmSettingService();
        $data = $service->getDmCrmSetting($companyId);

        return $this->response->array($data);
    }
}
