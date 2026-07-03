<?php

declare(strict_types=1);

namespace PaymentBundle\Support;

final class DoumenIntlSignature
{
    public function signPostBody(string $jsonBody, string $secretKey): string
    {
        return md5($jsonBody.$secretKey);
    }

    public function verifyNotify(string $rawBody, string $secretKey, string $signature): bool
    {
        $expected = $this->signPostBody($rawBody, $secretKey);

        return hash_equals(strtolower($expected), strtolower(trim($signature)));
    }
}
