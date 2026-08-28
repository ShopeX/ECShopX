<?php

declare(strict_types=1);

namespace Tests\OpenapiBundle;

use Illuminate\Http\Request;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Filter\BaseFilter;

/**
 * codex-security-01-platform-baseline T01：BaseFilter company_id fail-closed（TC-01-01..03）
 */
class BaseFilterCompanyIdTest extends \TestCase
{
    /**
     * TC-01-01 / AC-01-01：伪造 company_id → 4xx
     * #given auth company_id=1，请求体 company_id=999
     * #when 构造 BaseFilter
     * #then 抛出 ErrorException，不得采用伪造租户
     */
    public function testTc0101RejectsMismatchedCompanyId(): void
    {
        #given
        $this->bindRequest(['company_id' => 999], ['company_id' => 1]);

        #when
        try {
            $this->createTestFilter(['company_id' => 999]);
            $this->fail('Expected ErrorException for mismatched company_id');
        } catch (ErrorException $e) {
            #then
            $this->assertSame((int) ErrorCode::VALIDATION_MISSING_PARAMS, $e->getCode());
        }
    }

    /**
     * TC-01-02 / AC-01-02：缺省 company_id 用 auth
     * #given auth company_id=42，请求体未带 company_id
     * #when 构造 BaseFilter
     * #then filter 使用 auth 租户 42
     */
    public function testTc0102UsesAuthCompanyIdWhenMissing(): void
    {
        #given
        $this->bindRequest(['mobile' => '13800138000'], ['company_id' => 42]);

        #when
        $filter = $this->createTestFilter(['mobile' => '13800138000']);

        #then
        $this->assertSame(42, $filter->get()['company_id']);
    }

    /**
     * TC-01-03 / AC-01-03：空/0/非法 company_id → 拒绝
     * #given auth company_id=1，请求体 company_id 为 empty/null/0/非数字
     * #when 构造 BaseFilter
     * #then 抛出 ErrorException，不得静默回退 auth
     */
    public function testTc0103RejectsInvalidCompanyIdOverrides(): void
    {
        $invalidValues = ['', 0, null, 'abc'];

        foreach ($invalidValues as $invalid) {
            $this->bindRequest(['company_id' => $invalid], ['company_id' => 1]);

            try {
                $this->createTestFilter(['company_id' => $invalid]);
                $this->fail('Expected ErrorException for invalid company_id: ' . var_export($invalid, true));
            } catch (ErrorException $e) {
                $this->assertSame((int) ErrorCode::VALIDATION_MISSING_PARAMS, $e->getCode());
            }
        }
    }

    private function bindRequest(array $input, array $auth): void
    {
        $request = Request::create('/', 'POST', $input);
        $request->attributes->set('auth', $auth);
        $this->app->instance('request', $request);
    }

    private function createTestFilter(?array $requestInputData = null): BaseFilter
    {
        return new class($requestInputData) extends BaseFilter {
            protected function init(): void
            {
            }
        };
    }
}
