<?php
namespace tekintian\pinyin\Utils;

use tekintian\pinyin\Exception\PinyinException;
/**
 * 自动拼音获取工具
 * 用于为未找到拼音的汉字自动获取拼音
 */

class AutoPinyinFetcher {
    
    /**
     * 使用百度拼音API获取拼音（备用方案）
     * @param string $char 汉字
     * @return array|null 拼音信息
     */
    public static function getPinyinFromBaidu($char) {
        // 百度拼音API已失效，尝试其他替代方案
        // 使用百度翻译API作为替代
        $url = "https://fanyi.baidu.com/v2transapi?from=zh&to=en&query=" . urlencode($char);
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            // 百度翻译API可能不直接返回拼音，暂时返回null
            return null;
            
        } catch (PinyinException $e) {
            // 忽略网络错误
        }
        
        return null;
    }
    
    /**
     * 使用汉典网获取拼音
     * @param string $char 汉字
     * @return array|null 拼音信息
     */
    public static function getPinyinFromZdic($char): ?array {
        // 将汉字转换为URL编码（汉典网使用UTF-8编码）
        $encodedChar = urlencode($char);
        $url = "https://www.zdic.net/hans/{$encodedChar}";
        
        // 设置超时时间
        $timeout = 10;
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'header' => "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                               "Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
                               "Connection: close\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            // 使用错误抑制符
            $html = @file_get_contents($url, false, $context);
            if ($html === false || empty($html)) {
                return null;
            }
            
            // 解析HTML获取拼音
            $pinyin = self::parsePinyinFromZdicHtml($html, $char);
            if ($pinyin) {
                return ['pinyin' => $pinyin, 'source' => 'zdic'];
            }
            
        } catch (PinyinException $e) {
            // 忽略网络错误
            return null;
        }
        
        return null;
    }
    
    /**
     * 从汉典网HTML中解析拼音
     * @param string $html HTML内容
     * @param string $char 汉字
     * @return array|null 拼音数组
     */
    private static function parsePinyinFromZdicHtml($html, $char) {
        // 汉典网拼音在 <td class="z_py"> 标签中的 <span class="z_d song"> 标签内
        $pinyins = [];
        
        // 查找所有拼音区域（会有2个匹配项目，拼音在第二个匹配项里面）
        if (preg_match_all('/<td[^>]*class=["\']z_py["\'][^>]*>(.*?)<\/td>/is', $html, $tdMatches)) {
            // 获取第二个匹配项（第一个是表头"拼音"）
            if (count($tdMatches[1]) >= 2) {
                $pyContent = $tdMatches[1][1];
                
                // 提取所有拼音 - 使用更准确的正则表达式
                if (preg_match_all('/<span[^>]+>([a-zāáǎàōóǒòēéěèīíǐìūúǔùüǖǘǚǜ]+)<span/i', $pyContent, $matches)) {
                    foreach ($matches[1] as $pinyin) {
                        $pinyin = trim($pinyin);
                        if (!empty($pinyin) && self::isValidPinyin($pinyin)) {
                            $pinyins[] = $pinyin;
                        }
                    }
                }
            }
        }
        
        // 如果没有找到，尝试其他方法
        if (empty($pinyins)) {
            // 方法2：查找拼音标签
            if (preg_match('/<span[^>]*class=["\']dicpy["\'][^>]*>([^<]+)<\/span>/i', $html, $matches)) {
                $pinyin = trim($matches[1]);
                if (!empty($pinyin) && self::isValidPinyin($pinyin)) {
                    $pinyins[] = $pinyin;
                }
            }
            
            // 方法3：查找拼音相关的meta信息
            if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\'][^"\']*拼音[^"\']*([a-zāáǎàōóǒòēéěèīíǐìūúǔùüǖǘǚǜ]+)[^"\']*["\']/i', $html, $matches)) {
                $pinyin = trim($matches[1]);
                if (!empty($pinyin) && self::isValidPinyin($pinyin)) {
                    $pinyins[] = $pinyin;
                }
            }
            
            // 方法4：查找包含拼音的特定区域
            if (preg_match('/拼音[：:]([a-zāáǎàōóǒòēéěèīíǐìūúǔùüǖǘǚǜ\s]+)/i', $html, $matches)) {
                $pinyin = trim(preg_replace('/[^a-zāáǎàōóǒòēéěèīíǐìūúǔùüǖǘǚǜ\s]/i', '', $matches[1]));
                if (!empty($pinyin) && self::isValidPinyin($pinyin)) {
                    $pinyins[] = $pinyin;
                }
            }
        }
        
        if (!empty($pinyins)) {
            // 去重并返回拼音数组
            $pinyins = array_unique($pinyins);
            return $pinyins;
        }
        
        return null;
    }
    
    /**
     * 验证拼音格式
     * @param string $pinyin 拼音
     * @return bool 是否有效
     */
    private static function isValidPinyin($pinyin) {
        // 基本拼音格式验证
        return preg_match('/^[a-zāáǎàōóǒòēéěèīíǐìūúǔùüǖǘǚǜ]+$/i', $pinyin) && 
               strlen($pinyin) >= 1 && 
               strlen($pinyin) <= 7; // 拼音通常1-7个字符
    }
    
    /**
     * 使用在线汉字字典API获取真实拼音
     * @param string $char 汉字
     * @return array|null 拼音信息
     */
    public static function getPinyinFromDictAPI($char) {
        // 优先使用汉典网
        $zdicResult = self::getPinyinFromZdic($char);
        if ($zdicResult) {
            return $zdicResult;
        }
        
        // 尝试其他在线字典API
        $apis = [
            // 汉字叔叔字典
            function($char) {
                $url = "http://zi.tools/api/zi/" . urlencode($char);
                try {
                    $response = @file_get_contents($url, false, stream_context_create([
                        'http' => ['timeout' => 5]
                    ]));
                    if ($response) {
                        $data = json_decode($response, true);
                        if (isset($data['pinyin'])) {
                            return $data['pinyin'];
                        }
                    }
                } catch (PinyinException $e) {
                    // 忽略错误
                }
                return null;
            },
            
            // 新华字典API（示例）
            function($char) {
                // 这里可以集成其他字典API
                return null;
            }
        ];
        
        foreach ($apis as $api) {
            $pinyin = $api($char);
            if ($pinyin) {
                return ['pinyin' => $pinyin, 'source' => 'dict_api'];
            }
        }
        
        return null;
    }
    
    /**
     * 使用本地字典文件查找拼音
     * @param string $char 汉字
     * @return array|null 拼音信息
     */
    public static function getPinyinFromLocalDict($char) {
        $dictFiles = [
            __DIR__ . '/../../data/custom_with_tone.php', // 自定义数据库
            __DIR__ . '/../../data/unihan/all_unihan_pinyin.php' // unihan 数据库
        ];
        
        foreach ($dictFiles as $dictFile) {
            if (file_exists($dictFile)) {
                $dict = require $dictFile;
                if (isset($dict[$char])) {
                    return ['pinyin' => $dict[$char], 'source' => 'local_dict'];
                }
            }
        }
        
        return null;
    }
    
    /**
     * 使用字形结构推测拼音
     * 基于汉字部首和构件的常见读音
     * @param string $char 汉字
     * @return array|null 推测的拼音信息
     */
    public static function guessPinyinFromStructure($char) {
        // 常见部首读音映射
        $radicalPinyin = [
            '口' => 'kou', '木' => 'mu', '水' => 'shui', '火' => 'huo', '土' => 'tu',
            '金' => 'jin', '人' => 'ren', '心' => 'xin', '手' => 'shou', '足' => 'zu',
            '日' => 'ri', '月' => 'yue', '山' => 'shan', '石' => 'shi', '田' => 'tian',
            '禾' => 'he', '竹' => 'zhu', '艹' => 'cao', '虫' => 'chong', '鱼' => 'yu',
            '鸟' => 'niao', '马' => 'ma', '车' => 'che', '刀' => 'dao', '力' => 'li'
        ];
        
        // 尝试分解汉字（简化版）
        // 这里可以集成更复杂的汉字分解算法
        
        return null;
    }
    
    /**
     * 基于Unicode范围推测拼音
     * @param string $char 汉字
     * @return array|null 推测的拼音信息
     */
    public static function guessPinyinFromUnicode($char) {
        $unicode = mb_ord($char, 'UTF-8');
        
        // CJK扩展B区字符（U+20000-U+2A6DF）
        if ($unicode >= 0x20000 && $unicode <= 0x2A6DF) {
            // 对于CJK扩展B区字符，返回占位符
            return ['pinyin' => '?', 'source' => 'unicode_ext_b'];
        }
        
        // CJK扩展A区字符（U+3400-U+4DBF）
        if ($unicode >= 0x3400 && $unicode <= 0x4DBF) {
            return ['pinyin' => '?', 'source' => 'unicode_ext_a'];
        }
        
        // CJK扩展C区字符（U+2A700-U+2B73F）
        if ($unicode >= 0x2A700 && $unicode <= 0x2B73F) {
            return ['pinyin' => '?', 'source' => 'unicode_ext_c'];
        }
        
        // CJK扩展D区字符（U+2B740-U+2B81F）
        if ($unicode >= 0x2B740 && $unicode <= 0x2B81F) {
            return ['pinyin' => '?', 'source' => 'unicode_ext_d'];
        }
        
        // 其他Unicode范围的字符
        return ['pinyin' => '?', 'source' => 'unicode_other'];
    }
    
    /**
     * 批量获取拼音
     * @param array $chars 汉字数组
     * @param bool $useOnline 是否使用在线API
     * @return array 拼音结果
     */
    public static function batchGetPinyin($chars, $useOnline = true) {
        $results = [];
        
        foreach ($chars as $char) {
            $result = [
                'char' => $char,
                'unicode' => 'U+' . strtoupper(dechex(mb_ord($char, 'UTF-8'))),
                'pinyin' => null,
                'source' => null,
                'confidence' => 0
            ];
            
            // 首先检查本地字典（最快）
            $localResult = self::getPinyinFromLocalDict($char);
            if ($localResult) {
                $result['pinyin'] = $localResult['pinyin'];
                $result['source'] = $localResult['source'];
                $result['confidence'] = 100;
            }
            
            // 优先使用汉典网
            if (!$result['pinyin'] && $useOnline) {
                $zdicResult = self::getPinyinFromZdic($char);
                if ($zdicResult) {
                    $result['pinyin'] = $zdicResult['pinyin'];
                    $result['source'] = $zdicResult['source'];
                    $result['confidence'] = 95;
                }
            }
            
            // 其次使用百度拼音API
            if (!$result['pinyin'] && $useOnline) {
                $onlineResult = self::getPinyinFromBaidu($char);
                if ($onlineResult) {
                    $result['pinyin'] = $onlineResult['pinyin'];
                    $result['source'] = $onlineResult['source'];
                    $result['confidence'] = 90;
                }
            }
            
            // 如果在线API失败，尝试本地推测
            if (!$result['pinyin']) {
                $guessResult = self::guessPinyinFromUnicode($char);
                if ($guessResult) {
                    $result['pinyin'] = $guessResult['pinyin'];
                    $result['source'] = $guessResult['source'];
                    $result['confidence'] = 30;
                }
            }
            
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * 紧凑数组序列化（用于字典文件）
     * 特殊处理：将拼音字符串转换为数组格式
     */
    private static function compactArrayExport($array) {
        if (empty($array)) return '[]';
        
        $processedArray = [];
        foreach ($array as $key => $value) {
            // 特殊处理：将拼音字符串转换为数组格式
            if (is_string($value)) {
                $processedArray[$key] = array_map('trim', explode(',', $value));
            } else {
                $processedArray[$key] = $value;
            }
        }
        
        return PinyinHelper::compactArrayExport($processedArray);
    }

    /**
     * 生成自定义字典文件
     * @param array $pinyinResults 拼音结果
     * @param string $outputPath 输出文件路径
     * @param bool $withTone 是否带声调
     */
    public static function generateCustomDict($pinyinResults, $outputPath, $withTone = false) {
        $dict = [];
        
        foreach ($pinyinResults as $result) {
            if ($result['pinyin'] && $result['pinyin'] !== '?') {
                // 去除声调（如果需要）
                $pinyin = $withTone ? $result['pinyin'] : self::removeTone($result['pinyin']);
                $dict[$result['char']] = $pinyin;
            }
        }
        
        // 生成紧凑格式的PHP数组文件
        $content = "<?php\n/** 紧凑格式自定义拼音字典 生成时间：" . date('Y-m-d H:i:s') . " 条目数：" . count($dict) . " **/\nreturn ";
        $content .= self::compactArrayExport($dict) . ";\n";
        file_put_contents($outputPath, $content);
    }
    
    /**
     * 去除拼音声调
     * @param string|array $pinyin 带声调的拼音（字符串或数组）
     * @return string|array 无声调的拼音
     */
    private static function removeTone($pinyin) {
        if (is_array($pinyin)) {
            // 如果是数组，对每个元素进行处理
            return array_map(function($item) {
                return PinyinHelper::removeTone($item);
            }, $pinyin);
        } else {
            // 如果是字符串，直接处理
            return PinyinHelper::removeTone($pinyin);
        }
    }
}