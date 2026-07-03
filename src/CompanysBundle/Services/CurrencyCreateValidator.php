<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CompanysBundle\Services;

use Dingo\Api\Exception\StoreResourceFailedException;

class CurrencyCreateValidator
{
    public const ALLOWED_CURRENCY_CODES = ['CNY', 'HKD', 'USD'];

    public static function validateCurrencyCode(string $currency): void
    {
        if (!in_array($currency, self::ALLOWED_CURRENCY_CODES, true)) {
            throw new StoreResourceFailedException(trans('CompanysBundle/Currency.unsupported_currency_type'));
        }
    }

    public static function validateNotDuplicate(array $existing): void
    {
        if (!empty($existing)) {
            throw new StoreResourceFailedException(trans('CompanysBundle/Currency.currency_already_exists'));
        }
    }
}
