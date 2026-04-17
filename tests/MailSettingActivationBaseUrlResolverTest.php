<?php

use CompanysBundle\Services\MailSettingActivationBaseUrlResolver;

class MailSettingActivationBaseUrlResolverTest extends TestCase
{
    public function testSanitizeStoredDomainTrimsAndStripsNewlines(): void
    {
        $this->assertSame(
            'https://h5.example.com',
            MailSettingActivationBaseUrlResolver::sanitizeStoredDomain("  https://h5.example.com\n\r")
        );
    }

    public function testSanitizeStoredDomainTruncatesLongString(): void
    {
        $long = str_repeat('a', 600);
        $out = MailSettingActivationBaseUrlResolver::sanitizeStoredDomain($long);
        $this->assertSame(512, strlen($out));
    }
}
