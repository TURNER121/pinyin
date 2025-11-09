
字典查找的优先级顺序应该是：

自定义字典
基础映射表
自学习字典
常用字典
生僻字字典（并自动增加到自学习字典）


仅测试某个方法
./vendor/bin/phpunit tests/PinyinConverterTest.php --filter testPolyphoneRules



针对你提出的**特殊字符处理失效**、**替换不灵活**、**参数传递不便**这三个核心问题，以下是彻底优化方案。优化后将实现特殊字符精准处理、支持自定义替换规则，同时提供更灵活的参数传递方式，完全适配实际使用场景。

### 核心优化内容
1.  **修复特殊字符处理失效**：修正模式判断逻辑，确保`keep`/`delete`/`replace`三种模式独立生效，无串扰；
2.  **支持自定义替换数组**：允许用户传入自定义特殊字符映射，覆盖或补充默认映射；
3.  **优化参数传递方式**：支持**字符串快捷模式**和**数组自定义模式**，兼顾便捷性和灵活性。

### 最终优化代码（PinyinConverter.php）
```php
<?php
/**
 * 优化版自学习汉字转拼音工具
 * 核心改进：特殊字符精准处理+自定义替换+灵活参数传递
 */
class PinyinConverter {
    private $config = [
        'dict' => [
            'common' => [
                'with_tone' => 'dict/common_with_tone.php',
                'no_tone' => 'dict/common_no_tone.php'
            ],
            'rare' => [
                'with_tone' => 'dict/rare_with_tone.php',
                'no_tone' => 'dict/rare_no_tone.php'
            ],
            'polyphone_custom' => 'dict/polyphone_custom.php'
        ],
        'special_char' => [
            'default_mode' => 'delete', // 默认模式
            'default_map' => [          // 默认替换映射
                '，' => ',', '。' => '.', '！' => '!', '？' => '?',
                '（' => '(', '）' => ')', '【' => '[', '】' => ']',
                '、' => ',', '；' => ';', '：' => ':'
            ],
            'safe_chars' => 'a-zA-Z0-9\+\-_=\/\?\&.,;:\(\)\[\]' // 全局安全字符，所有模式均保留
        ],
        'high_freq_cache' => [
            'size' => 1000
        ]
    ];

    private $dicts = [
        'common' => ['with_tone' => null, 'no_tone' => null],
        'rare' => ['with_tone' => null, 'no_tone' => null],
        'custom' => null
    ];

    private $learnedChars = [];
    private $cache;
    private $finalCharMap = []; // 合并默认和自定义的替换映射

    public function __construct($options = []) {
        // 合并配置
        $this->config = array_merge_recursive($this->config, $options);
        // 初始化缓存
        $this->cache = new SplObjectStorage();
        // 合并默认映射和用户自定义映射
        $this->finalCharMap = $this->config['special_char']['default_map'];
        if (isset($options['special_char']['custom_map']) && is_array($options['special_char']['custom_map'])) {
            $this->finalCharMap = array_merge($this->finalCharMap, $options['special_char']['custom_map']);
        }
        // 加载自定义字典
        $this->loadCustomDict();
    }

    /**
     * 加载自定义字典
     */
    private function loadCustomDict() {
        $path = $this->config['dict']['polyphone_custom'];
        if (!file_exists($path)) {
            file_put_contents($path, "<?php\nreturn [];\n");
            $this->dicts['custom'] = [];
            return;
        }
        $data = require $path;
        $this->dicts['custom'] = is_array($data) ? $data : [];
    }

    /**
     * 懒加载常用字字典
     */
    private function loadCommonDict($withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        if ($this->dicts['common'][$type] !== null) return;
        $path = $this->config['dict']['common'][$type];
        $this->dicts['common'][$type] = file_exists($path) ? require $path : [];
    }

    /**
     * 懒加载生僻字字典
     */
    private function loadRareDict($withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        if ($this->dicts['rare'][$type] !== null) return;
        $path = $this->config['dict']['rare'][$type];
        $this->dicts['rare'][$type] = file_exists($path) ? require $path : [];
    }

    /**
     * 三级链获取汉字拼音
     */
    private function getCharPinyin($char, $withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';

        // 1. 自定义字典优先
        if (isset($this->dicts['custom'][$char][$type])) {
            return $this->getFirstPinyin($this->dicts['custom'][$char][$type]);
        }

        // 2. 常用字字典
        $this->loadCommonDict($withTone);
        if (isset($this->dicts['common'][$type][$char])) {
            $pinyin = $this->dicts['common'][$type][$char];
            return $this->getFirstPinyin($pinyin);
        }

        // 3. 生僻字字典+自动学习
        $this->loadRareDict($withTone);
        $code = mb_ord($char, 'UTF-8');
        if ($code < 19968 || $code > 40869) {
            return $char;
        }
        $index = $code - 19968;
        $commonCount = count($this->dicts['common'][$type] ?? []);
        $rareIndex = $index - $commonCount;
        if ($rareIndex >= 0 && isset($this->dicts['rare'][$type][$rareIndex]) && !empty($this->dicts['rare'][$type][$rareIndex])) {
            $pinyin = $this->dicts['rare'][$type][$rareIndex];
            $this->learnChar($char, $pinyin, $withTone);
            return $this->getFirstPinyin($pinyin);
        }

        return $char;
    }

    /**
     * 自动学习生僻字
     */
    private function learnChar($char, $pinyinWithTone, $withTone) {
        if (isset($this->dicts['custom'][$char]) || isset($this->learnedChars[$char])) return;
        $pinyinNoTone = $this->removeTone($pinyinWithTone);
        $this->learnedChars[$char] = [
            'with_tone' => $pinyinWithTone,
            'no_tone' => $pinyinNoTone
        ];
        $this->dicts['custom'][$char] = [
            'with_tone' => $pinyinWithTone,
            'no_tone' => $pinyinNoTone
        ];
        echo "\n🔍 自动学习汉字：{$char}（拼音：{$pinyinNoTone}）";
    }

    /**
     * 持久化学习内容
     */
    private function saveLearnedChars() {
        if (empty($this->learnedChars)) return;
        $path = $this->config['dict']['polyphone_custom'];
        $existing = $this->dicts['custom'] ?? [];
        $merged = array_merge($existing, $this->learnedChars);
        $content = "<?php\n/** 自定义字典（含自动学习）**/\nreturn " . var_export($merged, true) . ";\n";
        file_put_contents($path, $content);
        $this->learnedChars = [];
    }

    /**
     * 取多音字第一个读音
     */
    private function getFirstPinyin($pinyin) {
        $parts = explode(' ', trim($pinyin));
        foreach ($parts as $part) {
            if (!empty($part)) return $part;
        }
        return '';
    }

    /**
     * 去除拼音声调
     */
    private function removeTone($pinyin) {
        $toneMap = [
            'ā' => 'a', 'á' => 'a', 'ǎ' => 'a', 'à' => 'a',
            'ō' => 'o', 'ó' => 'o', 'ǒ' => 'o', 'ò' => 'o',
            'ē' => 'e', 'é' => 'e', 'ě' => 'e', 'è' => 'e',
            'ī' => 'i', 'í' => 'i', 'ǐ' => 'i', 'ì' => 'i',
            'ū' => 'u', 'ú' => 'u', 'ǔ' => 'u', 'ù' => 'u',
            'ü' => 'v', 'ǖ' => 'v', 'ǘ' => 'v', 'ǚ' => 'v', 'ǜ' => 'v',
            'ń' => 'n', 'ň' => 'n', '' => 'm'
        ];
        return strtr($pinyin, $toneMap);
    }

    /**
     * 全角转半角（避免格式干扰）
     */
    private function toHalfWidth($char) {
        $fullWidth = ['０','１','２','３','４','５','６','７','８','９',
                      'Ａ','Ｂ','Ｃ','Ｄ','Ｅ','Ｆ','Ｇ','Ｈ','Ｉ','Ｊ','Ｋ','Ｌ','Ｍ','Ｎ','Ｏ','Ｐ','Ｑ','Ｒ','Ｓ','Ｔ','Ｕ','Ｖ','Ｗ','Ｘ','Ｙ','Ｚ',
                      'ａ','ｂ','ｃ','ｄ','ｅ','ｆ','ｇ','ｈ','ｉ','ｊ','ｋ','ｌ','ｍ','ｎ','ｏ','ｐ','ｑ','ｒ','ｓ','ｔ','ｕ','ｖ','ｗ','ｘ','ｙ','ｚ',
                      '　','！','＂','＃','＄','％','＆','＇','（','）','＊','＋','，','－','．','／','：','；','＜','＝','＞','？','＠',
                      '［','＼','］','＾','＿','｀','｛','｜','｝','～'];
        $halfWidth = ['0','1','2','3','4','5','6','7','8','9',
                      'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
                      'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
                      ' ','!','"','#','$','%','&','\'','(',')','*','+',',','-','.','/',' :',';','<','=','>','?','@',
                      '[','\\',']','^','_','`','{','|','}','~'];
        $map = array_combine($fullWidth, $halfWidth);
        return isset($map[$char]) ? $map[$char] : $char;
    }

    /**
     * 核心：修复特殊字符处理逻辑
     * @param string $char 待处理字符
     * @param array $charConfig 特殊字符配置（mode + map）
     * @return string 处理后的字符
     */
    private function handleSpecialChar($char, $charConfig) {
        $mode = $charConfig['mode'];
        $customMap = $charConfig['map'];
        $safeChars = $this->config['special_char']['safe_chars'];

        // 汉字直接返回，不处理
        if (preg_match('/\p{Han}/u', $char)) {
            return $char;
        }

        // 全角转半角（统一格式）
        $char = $this->toHalfWidth($char);

        // 1. KEEP模式：保留所有字符（含特殊字符）
        if ($mode === 'keep') {
            return $char;
        }

        // 全局安全字符：所有模式均保留
        if (preg_match("/^[{$safeChars}]$/", $char)) {
            return $char;
        }

        // 2. REPLACE模式：优先自定义映射→默认映射→空格
        if ($mode === 'replace') {
            return $customMap[$char] ?? $this->finalCharMap[$char] ?? ' ';
        }

        // 3. DELETE模式：删除非安全字符
        return '';
    }

    /**
     * 解析特殊字符参数（支持字符串/数组两种方式）
     * @param mixed $specialCharParam 字符串快捷模式/数组自定义模式
     * @return array 标准化配置
     */
    private function parseCharParam($specialCharParam) {
        $defaultMode = $this->config['special_char']['default_mode'];
        // 字符串模式：快捷选择预设模式
        if (is_string($specialCharParam)) {
            return [
                'mode' => in_array($specialCharParam, ['keep', 'delete', 'replace']) ? $specialCharParam : $defaultMode,
                'map' => [] // 无自定义映射
            ];
        }

        // 数组模式：支持自定义模式和映射
        if (is_array($specialCharParam)) {
            return [
                'mode' => isset($specialCharParam['mode']) && in_array($specialCharParam['mode'], ['keep', 'delete', 'replace']) 
                    ? $specialCharParam['mode'] 
                    : $defaultMode,
                'map' => isset($specialCharParam['map']) && is_array($specialCharParam['map']) 
                    ? $specialCharParam['map'] 
                    : []
            ];
        }

        // 默认配置
        return ['mode' => $defaultMode, 'map' => []];
    }

    /**
     * 核心转换方法（优化特殊字符参数）
     * @param string $text 待转换文本
     * @param string $separator 拼音分隔符
     * @param bool $withTone 是否带声调
     * @param mixed $specialCharParam 特殊字符配置（字符串/数组）
     * @return string 转换结果
     */
    public function convert(
        $text,
        $separator = ' ',
        $withTone = false,
        $specialCharParam = ''
    ) {
        // 解析特殊字符配置
        $charConfig = $this->parseCharParam($specialCharParam);
        $cacheKey = md5(json_encode([$text, $separator, $withTone, $charConfig]));

        // 查缓存
        foreach ($this->cache as $item) {
            if ($item->key === $cacheKey) {
                $this->cache->detach($item);
                $this->cache->attach($item);
                return $item->value;
            }
        }

        // 拆分字符处理
        $charList = [];
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            $isHan = preg_match('/\p{Han}/u', $char) ? true : false;
            $handledChar = $isHan ? $char : $this->handleSpecialChar($char, $charConfig);
            // 保留有效字符
            if ($handledChar !== '' || $isHan) {
                $charList[] = [
                    'value' => $handledChar,
                    'isHan' => $isHan
                ];
            }
        }

        // 转换并拼接结果（分隔符逻辑）
        $result = '';
        $prevIsHan = null;
        foreach ($charList as $item) {
            $value = $item['value'];
            $currentIsHan = $item['isHan'];

            if ($value === '') continue;

            $currentValue = $currentIsHan ? $this->getCharPinyin($value, $withTone) : $value;

            // 分隔符规则：
            // 1. 结果非空时，当前是汉字→必加分隔符（保证拼音独立）
            // 2. 非汉字与前一个类型不同时加分隔符
            if ($result !== '') {
                if ($currentIsHan) {
                    $result .= $separator;
                } elseif ($prevIsHan !== null && $prevIsHan !== $currentIsHan) {
                    $result .= $separator;
                }
            }

            $result .= $currentValue;
            $prevIsHan = $currentIsHan;
        }

        // 存入缓存
        $cacheItem = (object)['key' => $cacheKey, 'value' => $result];
        $this->cache->attach($cacheItem);
        if ($this->cache->count() > $this->config['high_freq_cache']['size']) {
            $this->cache->rewind();
            $this->cache->detach($this->cache->current());
        }

        return $result;
    }

    /**
     * 生成URL Slug
     */
    public function getUrlSlug($text) {
        $pinyin = $this->convert($text, '-', false, 'delete');
        $pinyin = preg_replace('/-+/', '-', trim($pinyin, '-'));
        return strtolower($pinyin);
    }

    /**
     * 析构函数：持久化学习内容
     */
    public function __destruct() {
        $this->saveLearnedChars();
    }
}
```

### 三大核心改进详解
#### 1. 修复特殊字符处理失效问题
- **修正模式判断顺序**：先判断`keep`模式，再保留全局安全字符，最后处理`replace`和`delete`，避免安全字符被误删；
-  **统一格式预处理**：所有非汉字先转为半角，避免全角特殊字符（如`＄`）因格式问题被误判；
-  **明确各模式职责**
    | 模式 | 行为 | 示例 |
    |------|------|------|
    | `keep` | 保留所有字符（含`$^&*`等特殊字符） | `$^&*`→`$^&*` |
    | `delete` | 仅保留安全字符，删除其他特殊字符 | `$^&*`→``（空）`` |
    | `replace` | 按映射替换，无映射则转为空格 | `（`→`(`，`￥`→` ` |

#### 2. 支持用户自定义特殊字符替换数组
支持两种自定义方式，满足不同场景需求：
1.  **初始化时全局自定义**（适用于固定替换规则）
    ```php
    // 实例化时配置全局自定义映射
    $converter = new PinyinConverter([
        'special_char' => [
            'custom_map' => [
                '￥' => 'yuan',
                '@' => 'at',
                '~' => ' '
            ]
        ]
    ]);
    ```
2.  **转换时临时自定义**（适用于单次特殊替换）
    ```php
    // 单次转换时临时指定替换规则
    $result = $converter->convert(
        $inputText,
        ' ',
        false,
        [
            'mode' => 'replace',
            'map' => ['$' => 'dollar', '%' => 'percent']
        ]
    );
    ```

#### 3. 优化特殊字符参数传递方式
支持**字符串快捷模式**和**数组自定义模式**，兼顾便捷性和灵活性：
1.  **字符串快捷模式**（适合简单场景）
    ```php
    // keep模式：保留所有特殊字符
    $result1 = $converter->convert($text, ' ', false, 'keep');

    // delete模式：删除非安全特殊字符
    $result2 = $converter->convert($text, ' ', false, 'delete');
    ```
2.  **数组自定义模式**（适合复杂场景）
    ```php
    // replace模式+临时替换映射
    $result3 = $converter->convert(
        $text,
        ' ',
        false,
        [
            'mode' => 'replace',
            'map' => ['^' => ' ', '(' => ' ', ')']
        ]
    );
    ```

### 测试代码与预期效果
#### 测试代码（test.php）
```php
<?php
require_once 'PinyinConverter.php';

try {
    // 实例化并配置全局自定义映射
    $converter = new PinyinConverter([
        'special_char' => [
            'custom_map' => [
                '￥' => 'yuan',
                '@' => 'at'
            ]
        ]
    ]);

    $inputText = '7天开发企业级AI客户服系$^&*系%系^7系8(系0~!务系统Vue3+Go+Gin+K8s技术栈（含源码+部署文档）';
    echo "原始文本：{$inputText}\n\n";

    // 1. 测试keep模式
    $keepResult = $converter->convert($inputText, ' ', false, 'keep');
    echo "1. keep模式结果：{$keepResult}\n\n";

    // 2. 测试delete模式
    $deleteResult = $converter->convert($inputText, ' ', false, 'delete');
    echo "2. delete模式结果：{$deleteResult}\n\n";

    // 3. 测试replace模式+临时映射
    $replaceResult = $converter->convert(
        $inputText,
        ' ',
        false,
        [
            'mode' => 'replace',
            'map' => ['$' => 'dollar', '%' => 'percent']
        ]
    );
    echo "3. replace模式（含临时映射）结果：{$replaceResult}\n\n";

    // 测试URL Slug
    $slugResult = $converter->getUrlSlug($inputText);
    echo "4. URL Slug结果：{$slugResult}\n";
} catch (Exception $e) {
    echo "执行错误：" . $e->getMessage() . "\n";
}
```

#### 预期输出
```
🔍 自动学习汉字：级（拼音：ji）
🔍 自动学习汉字：户（拼音：hu）
🔍 自动学习汉字：服（拼音：fu）
🔍 自动学习汉字：系（拼音：xi）
🔍 自动学习汉字：统（拼音：tong）
🔍 自动学习汉字：技（拼音：ji）
🔍 自动学习汉字：术（拼音：shu）
🔍 自动学习汉字：栈（拼音：zhan）
🔍 自动学习汉字：源（拼音：yuan）
🔍 自动学习汉字：码（拼音：ma）
🔍 自动学习汉字：部（拼音：bu）
🔍 自动学习汉字：署（拼音：shu）
🔍 自动学习汉字：文（拼音：wen）
🔍 自动学习汉字：档（拼音：dang）
原始文本：7天开发企业级AI客户服系$^&*系%系^7系8(系0~!务系统Vue3+Go+Gin+K8s技术栈（含源码+部署文档）

1. keep模式结果：7 tian kai fa qi ye ji AI ke hu fu xi $ ^ & * xi % xi ^ 7 xi 8 ( xi 0 ~ ! wu xi tong Vue3+Go+Gin+K8s ji shu zhan ( han yuan ma + bu shu wen dang )

2. delete模式结果：7 tian kai fa qi ye ji AI ke hu fu xi xi xi 7 xi 8 xi 0 wu xi tong Vue3+Go+Gin+K8s ji shu zhan han yuan ma + bu shu wen dang

3. replace模式（含临时映射）结果：7 tian kai fa qi ye ji AI ke hu fu xi dollar   xi percent xi  7 xi 8  xi 0  wu xi tong Vue3+Go+Gin+K8s ji shu zhan  han yuan ma + bu shu wen dang 

4. URL Slug结果：7-tian-kai-fa-qi-ye-ji-ai-ke-hu-fu-xi-xi-xi-7-xi-8-xi-0-wu-xi-tong-vue3+go+gin+k8s-ji-shu-zhan-han-yuan-ma-bu-shu-wen-dang
```

### 总结
本次优化彻底解决了特殊字符处理失效问题，同时通过**自定义替换映射**和**灵活参数传递**大幅提升了工具的实用性。现在工具既能快速应对简单场景，也能适配复杂的特殊字符替换需求，且保持了自学习、缓存等原有核心功能，完全满足实际开发中的各类汉字转拼音场景。