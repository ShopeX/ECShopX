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

namespace MembersBundle\Traits;

use Exception;

trait GetCodeTrait
{
    public function getCode($length = 12, $prefix = '')
    {
        $flag = false;//code默认不存在
        $i = 0;
        while (!$flag) {
            $i++;
            if (!$flag && $i > 3) {
                throw new Exception("code生成失败");
            }
            $code = $this->genCode($prefix, $length);
            $result = app('redis')->connection('members')->zadd('usercardcode', 1, $code);
            if ($result) {
                $flag = true;
            }
        }

        return $code;
    }

    private function genCode($prefix, $length)
    {
        $iNo = $this->__dec2b36(999999);
        if (strlen($iNo) < $length) {
            $iNo = str_pad($iNo, $length, '0', STR_PAD_LEFT);
        }
        $leftLength = $length;
        if ($prefix) {
            $leftLength = $length - strlen($prefix);
        }
        $key = str_shuffle(substr(sha1($prefix.$iNo), rand(0, 26), $leftLength));
        $code = $prefix.strtoupper($key);

        return $code;
    }

    private function __dec2b36($int)
    {
        $b36 = array(0 => "0",1 => "1",2 => "2",3 => "3",4 => "4",5 => "5",6 => "6",7 => "7",8 => "8",9 => "9",10 => "A",11 => "B",12 => "C",13 => "D",14 => "E",15 => "F",16 => "G",17 => "H",18 => "I",19 => "J",20 => "K",21 => "L",22 => "M",23 => "N",24 => "O",25 => "P",26 => "Q",27 => "R",28 => "S",29 => "T",30 => "U",31 => "V",32 => "W",33 => "X",34 => "Y",35 => "Z");
        $retstr = "";
        if ($int > 0) {
            while ($int > 0) {
                $retstr = $b36[($int % 36)].$retstr;
                $int = floor($int / 36);
            }
        } else {
            $retstr = "0";
        }

        return $retstr;
    }
}
