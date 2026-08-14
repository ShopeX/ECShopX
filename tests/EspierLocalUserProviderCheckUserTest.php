<?php

/**
 * 计划：.tasks/plans/cnvd-h5-login-bypass.md
 * TC01/TC02/TC09/TC03/TC04–TC07/TC08：策略 A 短信校验与建号路径
 */

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Auth\Jwt\EspierLocalUserProvider;
use MembersBundle\Entities\Members;

class EspierLocalUserProviderCheckUserTest extends TestCase
{
    /** @var bool */
    private $createMemberPathInvoked = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createMemberPathInvoked = false;
        $this->mockRegistryForUnregisteredMobile();
    }

    /**
     * TC01：未注册手机 + check_type=password + autoRegister=true + vcode 空。
     * #given MembersRepository::findOneBy 对 fixedencrypt(mobile) 返回 null（未注册）
     * #when Reflection 调用 private checkUser
     * #then 抛 ResourceException，消息「短信验证码错误」；不进入 createMember 建号路径
     */
    public function testTc01UnregisteredPasswordAutoRegisterEmptyVcodeThrowsResourceException(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $password = 'TestPass123';

        $provider = new EspierLocalUserProvider($this->app, []);
        // MemberService 构造会 resolve MembersInfo，须在 invoke 前重置 createMember 路径标记
        $this->createMemberPathInvoked = false;
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke($provider, $companyId, $mobile, $password, 'password', '', true, false);
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertFalse(
            $this->createMemberPathInvoked,
            'TC01: must not enter createMember when vcode is empty (unregistered + password + autoRegister)'
        );
        $this->assertInstanceOf(ResourceException::class, $thrown);
        $this->assertSame('短信验证码错误', $thrown->getMessage());
    }

    /**
     * TC02：未注册手机 + password + autoRegister=true + vcode 非空但 checkSmsVcode 失败。
     * #given MembersRepository::findOneBy 返回 null；redis 无匹配验证码
     * #when Reflection 调用 private checkUser，vcode='999999'
     * #then 抛 ResourceException「短信验证码错误」；不进入 createMember 建号路径
     */
    public function testTc02UnregisteredPasswordAutoRegisterInvalidVcodeThrowsResourceException(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $password = 'TestPass123';
        $invalidVcode = '999999';

        $provider = new EspierLocalUserProvider($this->app, []);
        $this->createMemberPathInvoked = false;
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke(
                $provider,
                $companyId,
                $mobile,
                $password,
                'password',
                $invalidVcode,
                true,
                false
            );
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertFalse(
            $this->createMemberPathInvoked,
            'TC02: must not enter createMember when checkSmsVcode fails (wrong vcode)'
        );
        $this->assertInstanceOf(ResourceException::class, $thrown);
        $this->assertSame('短信验证码错误', $thrown->getMessage());
    }

    /**
     * TC09：check_type 缺省（等价 password）+ autoRegister + 无 vcode → 同 TC01。
     * #given 凭据无 check_type（按 retrieveByCredentials 缺省为 password）；Members 未注册
     * #when Reflection 调用 private checkUser
     * #then 抛 ResourceException「短信验证码错误」；不进入 createMember
     */
    public function testTc09DefaultCheckTypePasswordAutoRegisterEmptyVcodeThrowsResourceException(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $password = 'TestPass123';
        $credentials = [
            'password' => $password,
            'auto_register' => true,
        ];
        $checkType = (isset($credentials['check_type']) && $credentials['check_type'])
            ? $credentials['check_type']
            : 'password';
        $vcode = isset($credentials['vcode']) ? $credentials['vcode'] : '';
        $autoRegister = (bool) ($credentials['auto_register'] ?? false);

        $provider = new EspierLocalUserProvider($this->app, []);
        $this->createMemberPathInvoked = false;
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke(
                $provider,
                $companyId,
                $mobile,
                $password,
                $checkType,
                $vcode,
                $autoRegister,
                false
            );
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertSame('password', $checkType, 'TC09: default check_type must be password');
        $this->assertFalse(
            $this->createMemberPathInvoked,
            'TC09: must not enter createMember when default check_type password and vcode empty'
        );
        $this->assertInstanceOf(ResourceException::class, $thrown);
        $this->assertSame('短信验证码错误', $thrown->getMessage());
    }

    /**
     * TC03：未注册 + password + autoRegister=true + vcode 合法 → 可进入建号路径。
     * #given redis 预置匹配验证码；Members 未注册
     * #when Reflection 调用 private checkUser
     * #then 不抛「短信验证码错误」；进入 createMember 建号路径
     */
    public function testTc03UnregisteredPasswordAutoRegisterValidVcodeEntersCreateMemberPath(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $password = 'TestPass123';
        $validVcode = '123456';
        $this->mockRedisSmsVcode($mobile, $companyId, 'login', $validVcode);

        $provider = new EspierLocalUserProvider($this->app, []);
        $this->createMemberPathInvoked = false;
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke(
                $provider,
                $companyId,
                $mobile,
                $password,
                'password',
                $validVcode,
                true,
                false
            );
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        if ($thrown instanceof ResourceException) {
            $this->assertNotSame(
                '短信验证码错误',
                $thrown->getMessage(),
                'TC03: valid vcode must not throw sms verification ResourceException'
            );
        }
        $this->assertTrue(
            $this->createMemberPathInvoked,
            'TC03: must enter createMember path when vcode is valid (unregistered + password + autoRegister)'
        );
    }

    /**
     * TC04：已存在用户 + password + 正确密码 → 成功；不调用 checkSmsVcode。
     * #given mock Members 实体含 password_hash；findOneBy 返回该实体
     * #when Reflection 调用 private checkUser，check_type=password、无 vcode
     * #then 返回含 user_id>0；不进入 createMember；不要求短信验证码
     */
    public function testTc04ExistingUserPasswordCorrectPasswordSucceedsWithoutSms(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $password = 'TestPass123';
        $userId = 1001;

        $memberEntity = $this->createExistingMemberEntity($companyId, $mobile, $password, $userId);
        $this->mockRegistryForExistingMember($memberEntity);

        $provider = new EspierLocalUserProvider($this->app, []);
        $this->createMemberPathInvoked = false;
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $result = $method->invoke(
            $provider,
            $companyId,
            $mobile,
            $password,
            'password',
            '',
            false,
            false
        );

        $this->assertFalse(
            $this->createMemberPathInvoked,
            'TC04: existing user must not enter createMember path'
        );
        $this->assertIsArray($result);
        $this->assertSame($userId, $result['user_id']);
        $this->assertSame($companyId, $result['company_id']);
        $this->assertSame(0, $result['is_new']);
    }

    /**
     * TC05：已存在用户 + password + 错误密码 → 用户名或密码错误。
     * #given mock Members 实体含正确密码 hash
     * #when Reflection 调用 private checkUser，传入错误 password
     * #then 抛 ResourceException「用户名或密码错误」
     */
    public function testTc05ExistingUserPasswordWrongPasswordThrowsResourceException(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $correctPassword = 'TestPass123';
        $wrongPassword = 'WrongPass999';
        $userId = 1001;

        $memberEntity = $this->createExistingMemberEntity($companyId, $mobile, $correctPassword, $userId);
        $this->mockRegistryForExistingMember($memberEntity);

        $provider = new EspierLocalUserProvider($this->app, []);
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke(
                $provider,
                $companyId,
                $mobile,
                $wrongPassword,
                'password',
                '',
                false,
                false
            );
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ResourceException::class, $thrown);
        $this->assertSame('用户名或密码错误', $thrown->getMessage());
    }

    /**
     * TC06：mobile + 错 vcode → 短信验证码错误。
     * #given Members 未注册；无匹配 redis 验证码
     * #when Reflection 调用 private checkUser，check_type=mobile、vcode 错误
     * #then 抛 ResourceException「短信验证码错误」
     */
    public function testTc06MobileCheckTypeWrongVcodeThrowsResourceException(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $password = '';
        $wrongVcode = '999999';

        $provider = new EspierLocalUserProvider($this->app, []);
        $this->createMemberPathInvoked = false;
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke(
                $provider,
                $companyId,
                $mobile,
                $password,
                'mobile',
                $wrongVcode,
                false,
                false
            );
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertFalse(
            $this->createMemberPathInvoked,
            'TC06: wrong mobile vcode must not enter createMember path'
        );
        $this->assertInstanceOf(ResourceException::class, $thrown);
        $this->assertSame('短信验证码错误', $thrown->getMessage());
    }

    /**
     * TC07：未注册 + autoRegister=false + silent=false → 不建号，抛未注册类错误。
     * #given MembersRepository::findOneBy 返回 null（未注册）
     * #when Reflection 调用 private checkUser，autoRegister=false、silent=false
     * #then 抛 ResourceException「手机号码未注册，请注册后登陆」；不进入 createMember
     */
    public function testTc07UnregisteredAutoRegisterFalseSilentFalseThrowsUnregisteredError(): void
    {
        $companyId = 1;
        $mobile = '13800138000';
        $password = 'TestPass123';

        $provider = new EspierLocalUserProvider($this->app, []);
        $this->createMemberPathInvoked = false;
        $method = new ReflectionMethod(EspierLocalUserProvider::class, 'checkUser');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke(
                $provider,
                $companyId,
                $mobile,
                $password,
                'password',
                '',
                false,
                false
            );
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertFalse(
            $this->createMemberPathInvoked,
            'TC07: unregistered with autoRegister=false must not enter createMember path'
        );
        $this->assertInstanceOf(ResourceException::class, $thrown);
        $this->assertSame('手机号码未注册，请注册后登陆', $thrown->getMessage());
    }

    /**
     * TC08：未注册 + auto_register=0 + silent=1 → retrieveByCredentials 返回 null。
     * #given MembersRepository::findOneBy 返回 null（未注册）；mock registry 同 helper
     * #when 调用 public retrieveByCredentials，silent=1、auto_register=0、check_type=password
     * #then 返回 null，不得返回 GenericUser（不得签发 JWT 用户对象）
     */
    public function testTc08SilentUnregisteredRetrieveByCredentialsReturnsNull(): void
    {
        $mobile = '13800138000';
        $credentials = [
            'auth_type' => 'local',
            'username' => $mobile,
            'password' => 'TestPass123',
            'check_type' => 'password',
            'auto_register' => 0,
            'silent' => 1,
            'company_id' => 1,
        ];

        $provider = new EspierLocalUserProvider($this->app, []);
        $result = $provider->retrieveByCredentials($credentials);

        $this->assertNull(
            $result,
            'TC08: silent unregistered user must not return GenericUser (retrieveByCredentials must return null)'
        );
    }

    private function mockRedisSmsVcode(string $mobile, int $companyId, string $type, string $vcode): void
    {
        $key = 'member-' . $type . ':company' . $companyId . ':' . $mobile;
        $mockConnection = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get', 'set', 'expire', 'del'])
            ->getMock();
        $mockConnection->method('get')->willReturnCallback(
            function (string $requestedKey) use ($key, $vcode) {
                return $requestedKey === $key ? $vcode : null;
            }
        );
        $mockConnection->method('set')->willReturn(true);
        $mockConnection->method('expire')->willReturn(true);
        $mockConnection->method('del')->willReturn(1);

        $mockRedis = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['connection'])
            ->getMock();
        $mockRedis->method('connection')->with('members')->willReturn($mockConnection);

        $this->app->instance('redis', $mockRedis);
    }

    private function mockRegistryForUnregisteredMobile(): void
    {
        $self = $this;
        $expectedMobile = '13800138000';

        $mockMembersRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy', 'get', 'create', 'update'])
            ->getMock();
        $mockMembersRepo->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($self, $expectedMobile) {
                if (isset($criteria['mobile'])) {
                    $self->assertSame(
                        fixedencrypt($expectedMobile),
                        $criteria['mobile'],
                        'findOneBy must query mobile with fixedencrypt(mobile)'
                    );
                }

                return null;
            }
        );
        $mockMembersRepo->method('get')->willReturn(null);

        $defaultRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get', 'create', 'update', 'getInfo', 'updateOneBy'])
            ->getMock();

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $membersRepoResolved = false;
        $mockManager->method('getRepository')->willReturnCallback(
            function ($class) use ($mockMembersRepo, $defaultRepo, $self, &$membersRepoResolved) {
                if ($class === Members::class) {
                    $membersRepoResolved = true;

                    return $mockMembersRepo;
                }
                if ($membersRepoResolved) {
                    $self->createMemberPathInvoked = true;
                }

                return $defaultRepo;
            }
        );

        $conn = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $conn->method('beginTransaction')->willReturn(null);
        $conn->method('commit')->willReturn(null);
        $conn->method('rollBack')->willReturn(null);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager', 'getConnection'])
            ->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);

        $this->app->instance('registry', $mockRegistry);
    }

    private function createExistingMemberEntity(
        int $companyId,
        string $mobile,
        string $plainPassword,
        int $userId
    ): Members {
        $member = new Members();
        $member->setCompanyId($companyId);
        $member->setMobile(fixedencrypt($mobile));
        $member->setPassword(password_hash($plainPassword, PASSWORD_DEFAULT));

        $userIdProperty = new ReflectionProperty(Members::class, 'user_id');
        $userIdProperty->setAccessible(true);
        $userIdProperty->setValue($member, $userId);

        return $member;
    }

    private function mockRegistryForExistingMember(Members $memberEntity): void
    {
        $self = $this;
        $expectedMobile = '13800138000';

        $mockMembersRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy', 'get', 'create', 'update', 'getDataByEntity'])
            ->getMock();
        $mockMembersRepo->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($self, $expectedMobile, $memberEntity) {
                if (isset($criteria['mobile'])) {
                    $self->assertSame(
                        fixedencrypt($expectedMobile),
                        $criteria['mobile'],
                        'findOneBy must query mobile with fixedencrypt(mobile)'
                    );
                }

                return $memberEntity;
            }
        );
        $mockMembersRepo->method('get')->willReturn(null);
        $mockMembersRepo->method('getDataByEntity')->willReturnCallback(
            function (Members $entity) use ($memberEntity) {
                return [
                    'user_id' => $entity->getUserId(),
                    'company_id' => $entity->getCompanyId(),
                    'mobile' => $entity->getMobile(),
                    'disabled' => 0,
                ];
            }
        );

        $defaultRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get', 'create', 'update', 'getInfo', 'updateOneBy'])
            ->getMock();

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $membersRepoResolved = false;
        $mockManager->method('getRepository')->willReturnCallback(
            function ($class) use ($mockMembersRepo, $defaultRepo, $self, &$membersRepoResolved) {
                if ($class === Members::class) {
                    $membersRepoResolved = true;

                    return $mockMembersRepo;
                }
                if ($membersRepoResolved) {
                    $self->createMemberPathInvoked = true;
                }

                return $defaultRepo;
            }
        );

        $conn = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $conn->method('beginTransaction')->willReturn(null);
        $conn->method('commit')->willReturn(null);
        $conn->method('rollBack')->willReturn(null);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager', 'getConnection'])
            ->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);

        $this->app->instance('registry', $mockRegistry);
    }
}
