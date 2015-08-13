<?php

class Badword
{

    protected $defaultFileName = 'badwords.tree';
    protected $path = '';
    protected $dirname = '';
    protected $trie = null;
    protected $isEnable = true;


    public function __construct($path)
    {
        $this->path = $path;

        $pathinfo = pathinfo($path);
        $this->dirname = $pathinfo['dirname'];

        if (is_file($path)) {
            $this->trie = trie_filter_load($this->path);
        }
    }


    public function __destruct()
    {
        if (is_resource($this->trie)) {
            trie_filter_free($this->trie);
        }
    }


    /**
     * 字符串中所有字符是否都是单词字符
     *
     * @param string $str
     * @return bool
     */
    public static function isAlpha($str)
    {
        return 1 === preg_match('/^\w+$/i', $str) ? true : false;
    }


    /**
     * 字符串中所有字符是否都是：*
     *
     * @param string $str
     * @return bool
     */
    public static function isSubstitute($str)
    {
        return 1 === preg_match('/^\*+$/i', $str) ? true : false;
    }


    /**
     * 检测字符串中是否存在敏感词
     *
     * @param $str
     * @return bool
     * @throws Exception
     */
    public function exist($str)
    {
        if (empty($str)) {
            return false;
        }

        // 如果没有开启，就不存在敏感词
        if (!$this->isEnable) {
            return false;
        }

        if (!is_resource($this->trie)) {
            throw new Exception('Trie Error');
        }

        // 搜索敏感词
        $arrRet = trie_filter_search_all($this->trie, $str);

        // 未找到敏感词
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
     * 替换字符串中的敏感词为 *
     *
     * @param $str
     * @return string
     * @throws Exception
     */
    public function replace($str)
    {
        if (empty($str) || !is_string($str)) {
            return '';
        }

        if (!$this->isEnable) {
            return $str;
        }

        while ($this->exist($str)) {
            $str = $this->oneReplace($str);
        }

        return $str;
    }


    /**
     * 替换字符串中首次匹配到的敏感词
     *
     * @param $str
     * @return string
     * @throws Exception
     */
    protected function oneReplace($str)
    {
        if (empty($str) || !is_string($str)) {
            return '';
        }

        if (!$this->isEnable) {
            return $str;
        }

        if (!is_resource($this->trie)) {
            throw new Exception('Trie Error');
        }

        // 搜索敏感词
        $arrRet = trie_filter_search_all($this->trie, $str);

        // 没有敏感词，直接返回原句
        if (empty($arrRet)) {
            return $str;
        }

        // 用于存储替换后的字符串
        $newStr = '';

        // 记录上一个敏感词结尾的位置，初始为0
        $end = 0;

        foreach ($arrRet as $key => $value) {
            $start = $value[0];
            $length = $value[1];
            $word = substr($str, $start, $length);

            if (self::isSubstitute($word)) {
                continue;
            }

            /**
             * 如果敏感词是英文，就需要特殊判断
             * 因为匹配出来的英文敏感词可能只是某个单词的一部分
             * 例如：假设 ab 是敏感词，可能 ab 只是在单词 abstract 中的一部分内容，这种情况 ab 应该不算作敏感词，不应该被替换
             */
            if (self::isAlpha($word)) {
                $beforeChr = mb_substr(substr($str, 0, $start), -1, 1, 'UTF-8');
                $afterChr = mb_substr(substr($str, $start + $length), 0, 1, 'UTF-8');

                // 如果英文敏感词的前一个字符或后一个字符也是英文的话，就不替换
                if (self::isAlpha($beforeChr) || self::isAlpha($afterChr)) {
                    continue;
                }
            }
            // 拼接敏感词前面的内容
            $newStr .= substr($str, $end, $start - $end);

            // 记录敏感词结尾的位置
            $end = ($start + $length);

            // 拼接*号（敏感词替换为了*）
            $newStr .= str_repeat('*', mb_strlen($word, 'UTF-8'));

            // 匹配一次即可，跳出循环
            break;
        }

        $newStr .= substr($str, $end);
        return $newStr;
    }


    /**
     * 重新生成敏感词树结构文件
     */
    public function create($words)
    {
        $trie = trie_filter_new();
        foreach ($words as $word) {
            trie_filter_store($trie, $word);
        }

        trie_filter_save($trie, $this->dirname . DIRECTORY_SEPARATOR . $this->defaultFileName);
    }

}