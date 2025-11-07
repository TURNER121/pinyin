<?php
/**
 * 自定义多音字组合规则（补充通用规则的高频场景）
 * 格式同 polyphone_rules.php，优先级高于默认规则
 */
return [
    // 载：zài（装载/下载） vs zǎi（记载/年份）
    '载' => [
        ['type' => 'word', 'word' => '下载', 'pinyin' => 'zài'],   // 网络场景：下载文件
        ['type' => 'word', 'word' => '上传下载', 'pinyin' => 'zài'], // 技术场景：上传下载
        ['type' => 'post', 'char' => '体', 'pinyin' => 'zài'],     // 载体（数据载体）
        ['type' => 'post', 'char' => '记', 'pinyin' => 'zǎi'],     // 记载
        ['type' => 'word', 'word' => '三年五载', 'pinyin' => 'zǎi'], // 固定搭配
    ],

    // 传：chuán（传输） vs zhuàn（传记）
    '传' => [
        ['type' => 'post', 'char' => '输', 'pinyin' => 'chuán'],   // 传输（数据传输）
        ['type' => 'post', 'char' => '送', 'pinyin' => 'chuán'],   // 传送（文件传送）
        ['type' => 'post', 'char' => '统', 'pinyin' => 'chuán'],   // 传统
        ['type' => 'word', 'word' => '传记', 'pinyin' => 'zhuàn'], // 人物传记
        ['type' => 'word', 'word' => '外传', 'pinyin' => 'zhuàn'], // 番外外传
    ],

    // 的：de（助词） vs dí（的确） vs dì（目的） vs dī（的士）
    '的' => [
        ['type' => 'pre', 'char' => '我', 'pinyin' => 'de'],       // 我的（助词）
        ['type' => 'word', 'word' => '的确', 'pinyin' => 'dí'],    // 的确（肯定语气）
        ['type' => 'word', 'word' => '目的', 'pinyin' => 'dì'],    // 目的（目标）
        ['type' => 'word', 'word' => '打的', 'pinyin' => 'dī'],    // 网络/生活场景：打的（打车）
        ['type' => 'word', 'word' => '的哥', 'pinyin' => 'dī'],    // 的哥（出租车司机）
    ],

    // 行：xíng（口语/动作） vs háng（行业）
    '行' => [
        ['type' => 'word', 'word' => '行吧', 'pinyin' => 'xíng'],   // 口语：行吧（同意）
        ['type' => 'word', 'word' => '还行', 'pinyin' => 'xíng'],   // 口语：还行（勉强认可）
        ['type' => 'word', 'word' => '行内', 'pinyin' => 'háng'],   // 行业内：行内人士
        ['type' => 'word', 'word' => '行规', 'pinyin' => 'háng'],   // 行业规则：行规
    ],

    // 重：chóng（重复/技术场景） vs zhòng（重要）
    '重' => [
        ['type' => 'word', 'word' => '重写', 'pinyin' => 'chóng'],  // 技术场景：重写代码
        ['type' => 'word', 'word' => '重命名', 'pinyin' => 'chóng'], // 技术场景：文件重命名
        ['type' => 'word', 'word' => '重点', 'pinyin' => 'zhòng'],  // 重点内容
        ['type' => 'post', 'char' => '磅', 'pinyin' => 'zhòng'],    // 重磅（重磅消息）
    ],

    // 舍：shě（舍弃） vs shè（宿舍）
    '舍' => [
        ['type' => 'post', 'char' => '弃', 'pinyin' => 'shě'],     // 舍弃（舍弃冗余代码）
        ['type' => 'word', 'word' => '舍得', 'pinyin' => 'shě'],   // 舍得（口语）
        ['type' => 'post', 'char' => '友', 'pinyin' => 'shè'],     // 舍友（室友）
        ['type' => 'word', 'word' => '宿舍', 'pinyin' => 'shè'],   // 宿舍（住宿场景）
    ],

    // 弹：tán（弹性） vs dàn（子弹）
    '弹' => [
        ['type' => 'word', 'word' => '弹性', 'pinyin' => 'tán'],   // 技术场景：弹性扩容
        ['type' => 'post', 'char' => '力', 'pinyin' => 'tán'],     // 弹力
        ['type' => 'word', 'word' => '子弹', 'pinyin' => 'dàn'],   // 子弹（武器）
        ['type' => 'word', 'word' => '弹弓', 'pinyin' => 'dàn'],   // 弹弓（工具）
    ],

    // 参：cān（参数） vs cēn（参差）
    '参' => [
        ['type' => 'post', 'char' => '数', 'pinyin' => 'cān'],     // 技术场景：API参数
        ['type' => 'word', 'word' => '参数', 'pinyin' => 'cān'],   // 明确技术场景
        ['type' => 'post', 'char' => '差', 'pinyin' => 'cēn'],     // 参差不齐（状态描述）
        ['type' => 'word', 'word' => '参考', 'pinyin' => 'cān'],   // 参考文档
    ],

    // 角：jiǎo（角落） vs jué（角色）
    '角' => [
        ['type' => 'word', 'word' => '角色', 'pinyin' => 'jué'],   // 角色（用户角色）
        ['type' => 'post', 'char' => '色', 'pinyin' => 'jué'],     // 角色（同上）
        ['type' => 'post', 'char' => '落', 'pinyin' => 'jiǎo'],    // 角落
        ['type' => 'word', 'word' => '直角', 'pinyin' => 'jiǎo'],  // 几何场景：直角
    ],

    // 奔：bēn（奔波） vs bèn（直奔）
    '奔' => [
        ['type' => 'word', 'word' => '直奔', 'pinyin' => 'bèn'],   // 直奔主题（口语）
        ['type' => 'post', 'char' => '赴', 'pinyin' => 'bèn'],     // 奔赴（目标）
        ['type' => 'post', 'char' => '波', 'pinyin' => 'bēn'],     // 奔波（忙碌）
        ['type' => 'word', 'word' => '奔跑', 'pinyin' => 'bēn'],   // 奔跑（动作）
    ],
];
