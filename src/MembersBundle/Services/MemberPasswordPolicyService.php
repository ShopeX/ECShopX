<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace MembersBundle\Services;

use Dingo\Api\Exception\ResourceException;

class MemberPasswordPolicyService
{
    /** @var string[] */
    private static $weakPasswords = [
        '12345678',
        '123456789',
        '1234567890',
        'password',
        'password123',
        'qwerty123',
        'admin123',
        '88888888',
    ];

    /**
     * @return array{valid: bool, strength: string, message: string}
     */
    public function evaluate(string $password): array
    {
        $len = strlen($password);
        $hasLetter = (bool) preg_match('/[a-zA-Z]/', $password);
        $hasDigit = (bool) preg_match('/[0-9]/', $password);

        if ($len < 8) {
            return ['valid' => false, 'strength' => 'weak', 'message' => trans('MembersBundle/Members.password_min_length_8')];
        }
        if (!$hasLetter || !$hasDigit) {
            return ['valid' => false, 'strength' => 'weak', 'message' => trans('MembersBundle/Members.password_need_letter_and_digit')];
        }
        $lower = strtolower($password);
        foreach (self::$weakPasswords as $w) {
            if ($lower === $w || str_contains($lower, $w)) {
                return ['valid' => false, 'strength' => 'weak', 'message' => trans('MembersBundle/Members.password_too_weak')];
            }
        }

        $strength = 'medium';
        if ($len >= 12 && preg_match('/[^a-zA-Z0-9]/', $password)) {
            $strength = 'strong';
        } elseif ($len >= 10) {
            $strength = 'strong';
        }

        return ['valid' => true, 'strength' => $strength, 'message' => ''];
    }

    public function validateOrFail(string $password): void
    {
        $r = $this->evaluate($password);
        if (!$r['valid']) {
            throw new ResourceException($r['message']);
        }
    }
}
