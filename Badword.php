<?php

class Badword
{

    private static $trie = null;
    private static $isOpen = true;


    private function __construct()
    {

    }


    private function __clone()
    {

    }


    public function __destruct()
    {
        if (!empty(self::$trie)) {
            trie_filter_free(self::$trie);
        }
    }


    public static function isOpen()
    {
        self::$isOpen = true;
        return self::$isOpen;
    }


    public static function getTrie()
    {
        if (empty(self::$trie)) {
            self::$trie = trie_filter_load(dirname(__FILE__) . '/blackword.tree');
        }
        return self::$trie;
    }


    public static function replace($str)
    {
        if (empty($str) || !is_string($str)) {
            return '';
        }

        if (!self::isOpen()) {
            return $str;
        }

        while (self::exist($str)) {
            $str = self::oneReplace($str);
        }

        return $str;
    }


    /**
     * 替换字符串中的脏词为 *
     *
     * @param string $str
     * @return string
     */
    public static function oneReplace($str)
    {
        if (empty($str) || !is_string($str)) {
            return '';
        }

        if (!self::isOpen()) {
            return $str;
        }

        $trie = self::getTrie();

        // 搜索脏词
        $arrRet = trie_filter_search_all($trie, $str);

        // 没有脏词，直接返回原句
        if (empty($arrRet)) {
            return $str;
        }

        // 用于存储替换脏成后的字符串
        $newStr = '';

        // 记录上一个脏词结尾的位置，初始为0
        $end = 0;

        foreach ($arrRet as $key => $value) {
            // 脏词的起始位置
            $start = $value[0];

            // 脏词的长度
            $length = $value[1];

            // 脏词的内容
            $word = substr($str, $start, $length);

            if (self::isSubstitute($word)) {
                continue;
            }

            /**
             * 如果脏词是英文，就需要特殊判断
             * 因为匹配出来的英文脏词可能只是某个单词的一部分
             * 例如：假设 ab 是脏词，可能 ab 只是在单词 abstract 中的一部分内容，这种情况 ab 应该不算作脏词，不应该被替换
             */
            if (self::isAlpha($word)) {
                $beforeChr = mb_substr(substr($str, 0, $start), -1, 1, 'UTF-8');
                $afterChr = mb_substr(substr($str, $start + $length), 0, 1, 'UTF-8');

                // 如果英文脏词的前一个字符或后一个字符也是英文的话，就不替换
                if (self::isAlpha($beforeChr) || self::isAlpha($afterChr)) {
                    continue;
                }
            }
            // 拼接脏词前面的内容
            $newStr .= substr($str, $end, $start - $end);

            // 记录脏词结尾的位置
            $end = ($start + $length);

            // 拼接*号（脏词替换为了*）
            $newStr .= str_repeat('*', mb_strlen($word, 'UTF-8'));
            break;
        }
        $newStr .= substr($str, $end);

        return $newStr;
    }


    public static function isAlpha($str)
    {
        return 1 === preg_match('/^\w+$/i', $str) ? true : false;
    }


    public static function isSubstitute($str)
    {
        return 1 === preg_match('/^\*+$/i', $str) ? true : false;
    }


    /**
     * 检测字符串中是否存在脏词
     *
     * @param string $str
     * @return boolean
     */
    public static function exist($str)
    {
        if (empty($str)) {
            return false;
        }
        if (!self::isOpen()) {
            return false;
        }

        $trie = self::getTrie();

        // 搜索脏词
        $arrRet = trie_filter_search_all($trie, $str);

        // 没有脏词
        if (empty($arrRet)) {
            return false;
        }

        foreach ($arrRet as $value) {
            $start = $value[0];
            $length = $value[1];
            $word = substr($str, $start, $length);

            if (self::isSubstitute($word)) {
                continue;
            }

            if (self::isAlpha($word)) {
                $beforeChr = mb_substr(substr($str, 0, $start), -1, 1, 'UTF-8');
                $afterChr = mb_substr(substr($str, $start + $length), 0, 1, 'UTF-8');

                if (self::isAlpha($beforeChr) || self::isAlpha($afterChr)) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }


    /**
     * 重新生成脏词树结构文件
     */
    public static function recreate()
    {
        $badwords = file(dirname(__FILE__) . '/dirty.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $trie = trie_filter_new();
        foreach ($badwords as $badword) {
            trie_filter_store($trie, $badword);
        }

        trie_filter_save($trie, dirname(__FILE__) . '/blackword.tree');
    }

}