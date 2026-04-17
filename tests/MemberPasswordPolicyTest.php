<?php

use MembersBundle\Services\MemberPasswordPolicyService;

class MemberPasswordPolicyTest extends TestCase
{
    public function testPasswordTooShortIsWeak(): void
    {
        $s = new MemberPasswordPolicyService();
        $r = $s->evaluate('Ab1');
        $this->assertFalse($r['valid']);
        $this->assertSame('weak', $r['strength']);
    }

    public function testPasswordWithoutDigitInvalid(): void
    {
        $s = new MemberPasswordPolicyService();
        $r = $s->evaluate('Abcdefgh');
        $this->assertFalse($r['valid']);
    }

    public function testPasswordValidMedium(): void
    {
        $s = new MemberPasswordPolicyService();
        $r = $s->evaluate('Abcdef12');
        $this->assertTrue($r['valid']);
        $this->assertTrue(in_array($r['strength'], ['medium', 'strong'], true));
    }

    public function testWeakPasswordRejected(): void
    {
        $s = new MemberPasswordPolicyService();
        $r = $s->evaluate('12345678a');
        $this->assertFalse($r['valid']);
    }
}
