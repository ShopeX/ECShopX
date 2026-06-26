<?php

declare(strict_types=1);

namespace OpenapiBundle\Services\Member;

/**
 * 数云等级 batchSave 时解析 promotion_condition（可单测，不依赖 DB）。
 */
final class MemberCardGradeBatchSavePromotionConditionResolver
{
    /**
     * @param  mixed  $existingPromotionCondition  DB 行上的 promotion_condition（array 或 JSON 字符串）；新增行传 null
     *
     * @return array{total_consumption: int|float|string}
     */
    public static function resolve(int $gradeLevel, bool $preserveOnUpdate, $existingPromotionCondition): array
    {
        if (! $preserveOnUpdate) {
            return ['total_consumption' => $gradeLevel];
        }

        $parsed = self::coercePromotionConditionToArray($existingPromotionCondition);
        if (array_key_exists('total_consumption', $parsed) && is_numeric($parsed['total_consumption'])) {
            return ['total_consumption' => $parsed['total_consumption']];
        }

        return ['total_consumption' => $gradeLevel];
    }

    /**
     * @param  mixed  $promotionCondition
     *
     * @return array<string, mixed>
     */
    private static function coercePromotionConditionToArray($promotionCondition): array
    {
        if (\is_array($promotionCondition)) {
            return $promotionCondition;
        }
        if (\is_string($promotionCondition) && trim($promotionCondition) !== '') {
            try {
                $decoded = json_decode($promotionCondition, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return [];
            }
            if (\is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
