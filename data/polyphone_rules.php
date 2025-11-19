<?php
/**
 * 多音字规则模板（紧凑友好格式，单规则一行，方便修改）
 * 注意本规则里面记录的多音字的拼音是 非默认读音的规则, 和特殊的多音字规则
 *  type支持三种类型：
 * 1. word: 匹配完整词语  2. pre: 匹配前置汉字  3. post: 匹配后置汉字
 * pinyin 为带声调的准确读音
 * 
 * 多音字规则的格式:
 * 1. 单个多音字的规则：
 * 格式：['type' => 'post/pre/word', 'char/word' => '字符/词语', 'pinyin' => '拼音', 'weight' => 权重]
 * 注释格式： // 词语  拼音
 * 
 */
return [
    '行' => [
        ['type' => 'post', 'char' => '为', 'pinyin' => 'xíng'],  // 行为  xíng wéi
        ['type' => 'post', 'char' => '动', 'pinyin' => 'xíng'],  // 行动  xíng dòng
        ['type' => 'post', 'char' => '走', 'pinyin' => 'xíng'],  // 行走  xíng zǒu
        ['type' => 'post', 'char' => '者', 'pinyin' => 'xíng'],  // 行者  xíng zhě
        ['type' => 'word', 'word' => '行动', 'pinyin' => 'xing', 'weight' => '1'],  // 行动  xíng dòng
        ['type' => 'word', 'word' => '银行', 'pinyin' => 'hang', 'weight' => '2'],  // 银行  yín háng
        ['type' => 'word', 'word' => '行动', 'pinyin' => 'xing', 'weight' => '1']   // 行动  xíng dòng
    ],
    '长' => [
        ['type' => 'post', 'char' => '大', 'pinyin' => 'zhǎng'],  // 长大  zhǎng dà
        ['type' => 'post', 'char' => '进', 'pinyin' => 'zhǎng'],  // 长进  zhǎng jìn
        ['type' => 'pre', 'char' => '校', 'pinyin' => 'zhǎng'],   // 校长  xiào zhǎng
        ['type' => 'pre', 'char' => '行', 'pinyin' => 'zhǎng'],   // 行长  háng zhǎng
        ['type' => 'post', 'char' => '成', 'pinyin' => 'zhǎng'],  // 成长  chéng zhǎng
        ['type' => 'word', 'word' => '成长', 'pinyin' => 'zhǎng']  // 成长  chéng zhǎng
    ],
    '乐' => [
        ['type' => 'pre', 'char' => '音', 'pinyin' => 'yuè'],    // 音乐  yīn yuè
        ['type' => 'post', 'char' => '器', 'pinyin' => 'yuè'],   // 乐器  yuè qì
        ['type' => 'word', 'word' => '乐谱', 'pinyin' => 'yuè']   // 乐谱  yuè pǔ
    ],
    '发' => [
        ['type' => 'pre', 'char' => '头', 'pinyin' => 'fà'],     // 头发  tóu fà
        ['type' => 'post', 'char' => '型', 'pinyin' => 'fà']     // 发型  fà xíng
    ],
    '重' => [
        ['type' => 'pre', 'char' => '重', 'pinyin' => 'liàng'],  // 重量  zhòng liàng
        ['type' => 'word', 'word' => '重量', 'pinyin' => 'liàng'],  // 重量  zhòng liàng
        ['type' => 'post', 'char' => '复', 'pinyin' => 'chóng'],  // 重复  chóng fù
        ['type' => 'post', 'char' => '新', 'pinyin' => 'chóng'],  // 重新  chóng xīn
        ['type' => 'word', 'word' => '重构', 'pinyin' => 'chóng']  // 重构  chóng gòu
    ],
    '参' => [
        ['type' => 'pre', 'char' => '人', 'pinyin' => 'shēn'],    // 人参  rén shēn
        ['type' => 'post', 'char' => '差', 'pinyin' => 'cēn']    // 参差  cēn cī
    ],
    '量' => [
        ['type' => 'word', 'word' => '流量', 'pinyin' => 'liàng'],  // 流量  liú liàng
        ['type' => 'pre', 'char' => '重', 'pinyin' => 'liàng'],   // 重量  zhòng liàng
        ['type' => 'word', 'word' => '重量', 'pinyin' => 'liàng']  // 重量  zhòng liàng
    ],
    '度' => [
        ['type' => 'pre', 'char' => '揣', 'pinyin' => 'duó']      // 揣度  chuǎi duó
    ],
    '数' => [
        ['type' => 'post', 'char' => '数', 'pinyin' => 'shǔ']     // 数数  shǔ shù
    ],
    '中' => [
        ['type' => 'post', 'char' => '奖', 'pinyin' => 'zhòng'],  // 中奖  zhòng jiǎng
        ['type' => 'post', 'char' => '靶', 'pinyin' => 'zhòng'],  // 中靶  zhòng bǎ
        ['type' => 'post', 'char' => '了', 'pinyin' => 'zhòng']   // 中了  zhòng le
    ],
    '盛' => [
        ['type' => 'post', 'char' => '饭', 'pinyin' => 'chéng']   // 盛饭  chéng fàn
    ],
    '奔' => [
        ['type' => 'post', 'char' => '赴', 'pinyin' => 'bèn'],    // 奔赴  bēn fù
        ['type' => 'pre', 'char' => '投', 'pinyin' => 'bèn']      // 投奔  tóu bèn
    ],
    '调' => [
        ['type' => 'pre', 'char' => '声', 'pinyin' => 'diào'],    // 声调  shēng diào
        ['type' => 'post', 'char' => '动', 'pinyin' => 'diào'],   // 调动  diào dòng
        ['type' => 'word', 'char' => '调调', 'pinyin' => 'diào']  // 调调  diào diào
    ],
    '单' => [
        ['type' => 'post', 'char' => '于', 'pinyin' => 'chán']    // 单于  chán yú
    ],
    '尉' => [
        ['type' => 'post', 'char' => '迟', 'pinyin' => 'yù']      // 尉迟  yù chí
    ],
    '说' => [
        ['type' => 'post', 'char' => '客', 'pinyin' => 'shuì'],   // 说客  shuì kè
        ['type' => 'pre', 'char' => '游', 'pinyin' => 'shuì']     // 游说  yóu shuì
    ],
    '朝' => [
        ['type' => 'post', 'char' => '代', 'pinyin' => 'cháo']    // 朝代  cháo dài
    ],
    '的' => [
        ['type' => 'word', 'word' => '的确', 'pinyin' => 'dí'],   // 的确  dí què
        ['type' => 'word', 'word' => '目的', 'pinyin' => 'dì'],   // 目的  mù dì
        ['type' => 'word', 'word' => '有的', 'pinyin' => 'dì']    // 有的  yǒu dì
    ],
    '地' => [
        ['type' => 'word', 'word' => '地面', 'pinyin' => 'dì'],   // 地面  dì miàn
        ['type' => 'word', 'word' => '土地', 'pinyin' => 'dì'],   // 土地  tǔ dì
        ['type' => 'word', 'word' => '地方', 'pinyin' => 'dì']    // 地方  dì fāng
    ],
    '得' => [
        ['type' => 'word', 'word' => '得了', 'pinyin' => 'děi'],  // 得了  děi le
        ['type' => 'word', 'word' => '得了吧', 'pinyin' => 'děi'] // 得了吧  děi le ba
    ],
    '了' => [
        ['type' => 'word', 'word' => '了解', 'pinyin' => 'liǎo'],  // 了解  liǎo jiě
        ['type' => 'word', 'word' => '了却', 'pinyin' => 'liǎo'],  // 了却  liǎo què
        ['type' => 'word', 'word' => '了结', 'pinyin' => 'liǎo']   // 了结  liǎo jié
    ],
    '着' => [
        ['type' => 'word', 'word' => '着急', 'pinyin' => 'zháo'],  // 着急  zháo jí
        ['type' => 'word', 'word' => '着火', 'pinyin' => 'zháo'],  // 着火  zháo huǒ
        ['type' => 'word', 'word' => '着凉', 'pinyin' => 'zháo'],  // 着凉  zháo liáng
        ['type' => 'word', 'word' => '听着', 'pinyin' => 'zhāo'],  // 听着  tīng zhāo
        ['type' => 'word', 'word' => '看着', 'pinyin' => 'zhāo']   // 看着  kàn zhāo
    ],
    '和' => [
        ['type' => 'word', 'word' => '和面', 'pinyin' => 'huó'],   // 和面  huó miàn
        ['type' => 'word', 'word' => '和泥', 'pinyin' => 'huó'],   // 和泥  huó ní
        ['type' => 'word', 'word' => '和药', 'pinyin' => 'huò'],   // 和药  huò yào
        ['type' => 'word', 'word' => '和牌', 'pinyin' => 'hú']     // 和牌  hú pái
    ],
    '与' => [
        ['type' => 'word', 'word' => '参与', 'pinyin' => 'yù'],   // 参与  cān yù
        ['type' => 'word', 'word' => '与会', 'pinyin' => 'yù']     // 与会  yù huì
    ],
    '为' => [
        ['type' => 'word', 'word' => '因为', 'pinyin' => 'wèi'],   // 因为  yīn wèi
        ['type' => 'word', 'word' => '为了', 'pinyin' => 'wèi'],   // 为了  wèi le
        ['type' => 'word', 'word' => '为何', 'pinyin' => 'wèi']    // 为何  wèi hé
    ],
    '给' => [
        ['type' => 'word', 'word' => '供给', 'pinyin' => 'jǐ'],   // 供给  gōng jǐ
        ['type' => 'word', 'word' => '给养', 'pinyin' => 'jǐ']     // 给养  jǐ yǎng
    ],
    '过' => [
        ['type' => 'word', 'word' => '经过', 'pinyin' => 'guò'],   // 经过  jīng guò
        ['type' => 'word', 'word' => '通过', 'pinyin' => 'guò']    // 通过  tōng guò
    ],
    '好' => [
        ['type' => 'word', 'word' => '爱好', 'pinyin' => 'hào'],   // 爱好  ài hào
        ['type' => 'word', 'word' => '好客', 'pinyin' => 'hào'],   // 好客  hào kè
        ['type' => 'word', 'word' => '好奇', 'pinyin' => 'hào'],   // 好奇  hào qí
        ['type' => 'word', 'word' => '好学', 'pinyin' => 'hào']    // 好学  hào xué
    ],
    '空' => [
        ['type' => 'word', 'word' => '空白', 'pinyin' => 'kòng'],  // 空白  kòng bái
        ['type' => 'word', 'word' => '空地', 'pinyin' => 'kòng'],  // 空地  kòng dì
        ['type' => 'word', 'word' => '空闲', 'pinyin' => 'kòng'],  // 空闲  kòng xián
        ['type' => 'word', 'word' => '空缺', 'pinyin' => 'kòng']   // 空缺  kòng quē
    ],
    '少' => [
        ['type' => 'word', 'word' => '少年', 'pinyin' => 'shào'],  // 少年  shào nián
        ['type' => 'word', 'word' => '少女', 'pinyin' => 'shào'],  // 少女  shào nǚ
        ['type' => 'word', 'word' => '少主', 'pinyin' => 'shào'],  // 少主  shào zhǔ
        ['type' => 'word', 'word' => '少爷', 'pinyin' => 'shào']   // 少爷  shào yé
    ],
    '大' => [
        ['type' => 'word', 'word' => '大夫', 'pinyin' => 'dài'],   // 大夫  dài fu
        ['type' => 'word', 'word' => '大王', 'pinyin' => 'dài']    // 大王  dài wáng
    ],
    '没' => [
        ['type' => 'word', 'word' => '没收', 'pinyin' => 'mò'],   // 没收  mò shōu
        ['type' => 'word', 'word' => '淹没', 'pinyin' => 'mò'],   // 淹没  yān mò
        ['type' => 'word', 'word' => '沉没', 'pinyin' => 'mò'],   // 沉没  chén mò
        ['type' => 'word', 'word' => '出没', 'pinyin' => 'mò']    // 出没  chū mò
    ],
    '会' => [
        ['type' => 'word', 'word' => '会计', 'pinyin' => 'kuài'],  // 会计  kuài jì
        ['type' => 'word', 'word' => '会稽', 'pinyin' => 'kuài']   // 会稽  kuài jī
    ],
    '传' => [
        ['type' => 'word', 'word' => '传记', 'pinyin' => 'zhuàn'],  // 传记  zhuàn jì
        ['type' => 'word', 'word' => '自传', 'pinyin' => 'zhuàn'],  // 自传  zì zhuàn
        ['type' => 'word', 'word' => '传奇', 'pinyin' => 'zhuàn']   // 传奇  chuán qí
    ],
    '只' => [
        ['type' => 'word', 'word' => '只有', 'pinyin' => 'zhǐ'],   // 只有  zhǐ yǒu
        ['type' => 'word', 'word' => '只好', 'pinyin' => 'zhǐ'],   // 只好  zhǐ hǎo
        ['type' => 'word', 'word' => '只能', 'pinyin' => 'zhǐ']    // 只能  zhǐ néng
    ],
    '便' => [
        ['type' => 'word', 'word' => '便宜', 'pinyin' => 'piányi'],  // 便宜  pián yi
        ['type' => 'word', 'word' => '大腹便便', 'pinyin' => 'pián'] // 大腹便便  dà fù pián pián
    ],
    '干' => [
        ['type' => 'word', 'word' => '干净', 'pinyin' => 'gān'],   // 干净  gān jìng
        ['type' => 'word', 'word' => '饼干', 'pinyin' => 'gān'],   // 饼干  bǐng gān
        ['type' => 'word', 'word' => '干旱', 'pinyin' => 'gān']    // 干旱  gān hàn
    ],
    '分' => [
        ['type' => 'word', 'word' => '分外', 'pinyin' => 'fèn'],   // 分外  fèn wài
        ['type' => 'word', 'word' => '本分', 'pinyin' => 'fèn'],   // 本分  běn fèn
        ['type' => 'word', 'word' => '过分', 'pinyin' => 'fèn'],   // 过分  guò fèn
        ['type' => 'word', 'word' => '分内', 'pinyin' => 'fèn']    // 分内  fèn nèi
    ],
    '当' => [
        ['type' => 'word', 'word' => '当作', 'pinyin' => 'dàng'],  // 当作  dàng zuò
        ['type' => 'word', 'word' => '恰当', 'pinyin' => 'dàng'],  // 恰当  qià dàng
        ['type' => 'word', 'word' => '上当', 'pinyin' => 'dàng']   // 上当  shàng dàng
    ],
    '处' => [
        ['type' => 'word', 'word' => '处理', 'pinyin' => 'chǔ'],   // 处理  chǔ lǐ
        ['type' => 'word', 'word' => '处境', 'pinyin' => 'chǔ'],   // 处境  chǔ jìng
        ['type' => 'word', 'word' => '相处', 'pinyin' => 'chǔ']    // 相处  xiāng chǔ
    ],
    '石' => [
        ['type' => 'word', 'word' => '一石', 'pinyin' => 'dàn']   // 一石  yī dàn
    ],
    '打' => [
        ['type' => 'word', 'word' => '一打', 'pinyin' => 'dá']    // 一打  yī dá
    ],
    '种' => [
        ['type' => 'word', 'word' => '种植', 'pinyin' => 'zhòng'], // 种植  zhòng zhí
        ['type' => 'word', 'word' => '播种', 'pinyin' => 'zhòng']  // 播种  bō zhòng
    ],
    '背' => [
        ['type' => 'word', 'word' => '背负', 'pinyin' => 'bēi'],  // 背负  bēi fù
        ['type' => 'word', 'word' => '背带', 'pinyin' => 'bēi']    // 背带  bēi dài
    ],
    '兴' => [
        ['type' => 'word', 'word' => '兴趣', 'pinyin' => 'xìng'],  // 兴趣  xìng qù
        ['type' => 'word', 'word' => '兴致', 'pinyin' => 'xìng']   // 兴致  xìng zhì
    ],
    '血' => [
        ['type' => 'word', 'word' => '流血', 'pinyin' => 'xiě'],   // 流血  liú xiě
        ['type' => 'word', 'word' => '吐血', 'pinyin' => 'xiě']    // 吐血  tǔ xiě
    ],
    '假' => [
        ['type' => 'word', 'word' => '假期', 'pinyin' => 'jià'],   // 假期  jià qī
        ['type' => 'word', 'word' => '请假', 'pinyin' => 'jià']    // 请假  qǐng jià
    ],
    '宿' => [
        ['type' => 'word', 'word' => '宿愿', 'pinyin' => 'xiù'],   // 宿愿  sù yuàn
        ['type' => 'word', 'word' => '星宿', 'pinyin' => 'xiù']    // 星宿  xīng xiù
    ],
    '卡' => [
        ['type' => 'word', 'word' => '关卡', 'pinyin' => 'qiǎ'],   // 关卡  guān qiǎ
        ['type' => 'word', 'word' => '卡住', 'pinyin' => 'qiǎ']    // 卡住  qiǎ zhù
    ],
    '场' => [
        ['type' => 'word', 'word' => '市场', 'pinyin' => 'shì']    // 市场  shì chǎng
    ],
    '应' => [
        ['type' => 'word', 'word' => '应用', 'pinyin' => 'yìng'],  // 应用  yìng yòng
        ['type' => 'word', 'word' => '应对', 'pinyin' => 'yìng']   // 应对  yìng duì
    ],
    '还' => [
        ['type' => 'word', 'word' => '还给', 'pinyin' => 'huán'],  // 还给  huán gěi
        ['type' => 'word', 'word' => '还钱', 'pinyin' => 'huán']   // 还钱  huán qián
    ],
    '更' => [
        ['type' => 'word', 'word' => '更换', 'pinyin' => 'gēng'],  // 更换  gēng huàn
        ['type' => 'word', 'word' => '更新', 'pinyin' => 'gēng']   // 更新  gēng xīn
    ],
    '正' => [
        ['type' => 'word', 'word' => '正月', 'pinyin' => 'zhēng']  // 正月  zhēng yuè
    ],
    '看' => [
        ['type' => 'word', 'word' => '看守', 'pinyin' => 'kān']    // 看守  kān shǒu
    ],
    '角' => [
        ['type' => 'word', 'word' => '角色', 'pinyin' => 'jué'],   // 角色  jué sè
        ['type' => 'word', 'word' => '主角', 'pinyin' => 'jué'],   // 主角  zhǔ jué
        ['type' => 'word', 'word' => '口角', 'pinyin' => 'jué']    // 口角  kǒu jué
    ],
    '校' => [
        ['type' => 'word', 'word' => '学校', 'pinyin' => 'xiào'],  // 学校  xué xiào
        ['type' => 'word', 'word' => '校长', 'pinyin' => 'xiào']   // 校长  xiào zhǎng
    ],
    '员' => [
        ['type' => 'word', 'word' => '员工', 'pinyin' => 'yuán'],  // 员工  yuán gōng
        ['type' => 'word', 'word' => '成员', 'pinyin' => 'yuán']   // 成员  chéng yuán
    ],
    '业' => [
        ['type' => 'word', 'word' => '行业', 'pinyin' => 'háng'],  // 行业  háng yè
        ['type' => 'word', 'word' => '毕业', 'pinyin' => 'yè']     // 毕业  bì yè
    ],
    '体' => [
        ['type' => 'word', 'word' => '体育', 'pinyin' => 'tǐ'],    // 体育  tǐ yù
        ['type' => 'word', 'word' => '体验', 'pinyin' => 'tǐ']     // 体验  tǐ yàn
    ],
    '文' => [
        ['type' => 'word', 'word' => '文化', 'pinyin' => 'wén'],   // 文化  wén huà
        ['type' => 'word', 'word' => '文学', 'pinyin' => 'wén']    // 文学  wén xué
    ],
    '学' => [
        ['type' => 'word', 'word' => '学习', 'pinyin' => 'xué'],   // 学习  xué xí
        ['type' => 'word', 'word' => '学校', 'pinyin' => 'xiào']   // 学校  xué xiào
    ],
    '生' => [
        ['type' => 'word', 'word' => '生活', 'pinyin' => 'shēng'], // 生活  shēng huó
        ['type' => 'word', 'word' => '学生', 'pinyin' => 'shēng']  // 学生  xué shēng
    ],
    '作' => [
        ['type' => 'word', 'word' => '工作', 'pinyin' => 'zuò'],   // 工作  gōng zuò
        ['type' => 'word', 'word' => '作业', 'pinyin' => 'zuò']    // 作业  zuò yè
    ],
    '年' => [
        ['type' => 'word', 'word' => '年龄', 'pinyin' => 'nián'],  // 年龄  nián líng
        ['type' => 'word', 'word' => '新年', 'pinyin' => 'nián']   // 新年  xīn nián
    ],
    '月' => [
        ['type' => 'word', 'word' => '月亮', 'pinyin' => 'yuè'],   // 月亮  yuè liang
        ['type' => 'word', 'word' => '月份', 'pinyin' => 'yuè']    // 月份  yuè fèn
    ],
    '日' => [
        ['type' => 'word', 'word' => '日子', 'pinyin' => 'rì'],    // 日子  rì zi
        ['type' => 'word', 'word' => '日本', 'pinyin' => 'rì']     // 日本  rì běn
    ],
    '时' => [
        ['type' => 'word', 'word' => '时间', 'pinyin' => 'shí'],   // 时间  shí jiān
        ['type' => 'word', 'word' => '时候', 'pinyin' => 'shí']    // 时候  shí hou
    ],
    '开' => [
        ['type' => 'word', 'word' => '开始', 'pinyin' => 'kāi'],   // 开始  kāi shǐ
        ['type' => 'word', 'word' => '开放', 'pinyin' => 'kāi']    // 开放  kāi fàng
    ],
    '关' => [
        ['type' => 'word', 'word' => '关系', 'pinyin' => 'guān'],  // 关系  guān xì
        ['type' => 'word', 'word' => '关键', 'pinyin' => 'guān']   // 关键  guān jiàn
    ],
    '点' => [
        ['type' => 'word', 'word' => '要点', 'pinyin' => 'diǎn'],  // 要点  yào diǎn
        ['type' => 'word', 'word' => '特点', 'pinyin' => 'diǎn']   // 特点  tè diǎn
    ],
    '线' => [
        ['type' => 'word', 'word' => '直线', 'pinyin' => 'xiàn'],  // 直线  zhí xiàn
        ['type' => 'word', 'word' => '路线', 'pinyin' => 'xiàn']   // 路线  lù xiàn
    ],
    '面' => [
        ['type' => 'word', 'word' => '表面', 'pinyin' => 'miàn'],  // 表面  biǎo miàn
        ['type' => 'word', 'word' => '方面', 'pinyin' => 'miàn']   // 方面  fāng miàn
    ],
    '系' => [
        ['type' => 'word', 'word' => '关系', 'pinyin' => 'xì'],    // 关系  guān xì
        ['type' => 'word', 'word' => '体系', 'pinyin' => 'xì']     // 体系  tǐ xì
    ],
    '统' => [
        ['type' => 'word', 'word' => '系统', 'pinyin' => 'tǒng'],  // 系统  xì tǒng
        ['type' => 'word', 'word' => '传统', 'pinyin' => 'tǒng']   // 传统  chuán tǒng
    ],
    '构' => [
        ['type' => 'word', 'word' => '结构', 'pinyin' => 'gòu'],   // 结构  jié gòu
        ['type' => 'word', 'word' => '构造', 'pinyin' => 'gòu']    // 构造  gòu zào
    ],
    '成' => [
        ['type' => 'word', 'word' => '成功', 'pinyin' => 'chéng'], // 成功  chéng gōng
        ['type' => 'word', 'word' => '完成', 'pinyin' => 'chéng']  // 完成  wán chéng
    ],
    '果' => [
        ['type' => 'word', 'word' => '结果', 'pinyin' => 'guǒ'],   // 结果  jié guǒ
        ['type' => 'word', 'word' => '效果', 'pinyin' => 'guǒ']    // 效果  xiào guǒ
    ],
    '程' => [
        ['type' => 'word', 'word' => '过程', 'pinyin' => 'chéng'], // 过程  guò chéng
        ['type' => 'word', 'word' => '课程', 'pinyin' => 'chéng']  // 课程  kè chéng
    ],
    '展' => [
        ['type' => 'word', 'word' => '发展', 'pinyin' => 'zhǎn'],  // 发展  fā zhǎn
        ['type' => 'word', 'word' => '展开', 'pinyin' => 'zhǎn']   // 展开  zhǎn kāi
    ],
    '现' => [
        ['type' => 'word', 'word' => '现在', 'pinyin' => 'xiàn'],  // 现在  xiàn zài
        ['type' => 'word', 'word' => '出现', 'pinyin' => 'xiàn']   // 出现  chū xiàn
    ],
    '实' => [
        ['type' => 'word', 'word' => '实际', 'pinyin' => 'shí'],   // 实际  shí jì
        ['type' => 'word', 'word' => '实现', 'pinyin' => 'shí']    // 实现  shí xiàn
    ],
    '际' => [
        ['type' => 'word', 'word' => '国际', 'pinyin' => 'jì'],    // 国际  guó jì
        ['type' => 'word', 'word' => '实际', 'pinyin' => 'jì']     // 实际  shí jì
    ],
    '间' => [
        ['type' => 'word', 'word' => '间隔', 'pinyin' => 'jiàn']   // 间隔  jiàn gé
    ],
    '题' => [
        ['type' => 'word', 'word' => '问题', 'pinyin' => 'tí'],    // 问题  wèn tí
        ['type' => 'word', 'word' => '题目', 'pinyin' => 'tí']     // 题目  tí mù
    ],
    '方' => [
        ['type' => 'word', 'word' => '方法', 'pinyin' => 'fāng'],  // 方法  fāng fǎ
        ['type' => 'word', 'word' => '方面', 'pinyin' => 'fāng']   // 方面  fāng miàn
    ],
    '式' => [
        ['type' => 'word', 'word' => '方式', 'pinyin' => 'shì'],   // 方式  fāng shì
        ['type' => 'word', 'word' => '形式', 'pinyin' => 'shì']    // 形式  xíng shì
    ],
    '法' => [
        ['type' => 'word', 'word' => '方法', 'pinyin' => 'fǎ'],    // 方法  fāng fǎ
        ['type' => 'word', 'word' => '法律', 'pinyin' => 'fǎ']     // 法律  fǎ lǜ
    ],
    '原' => [
        ['type' => 'word', 'word' => '原因', 'pinyin' => 'yuán'],  // 原因  yuán yīn
        ['type' => 'word', 'word' => '原来', 'pinyin' => 'yuán']   // 原来  yuán lái
    ],
    '则' => [
        ['type' => 'word', 'word' => '规则', 'pinyin' => 'zé'],    // 规则  guī zé
        ['type' => 'word', 'word' => '原则', 'pinyin' => 'zé']     // 原则  yuán zé
    ],
    '理' => [
        ['type' => 'word', 'word' => '理论', 'pinyin' => 'lǐ'],    // 理论  lǐ lùn
        ['type' => 'word', 'word' => '道理', 'pinyin' => 'lǐ']     // 道理  dào lǐ
    ],
    '由' => [
        ['type' => 'word', 'word' => '由于', 'pinyin' => 'yóu'],   // 由于  yóu yú
        ['type' => 'word', 'word' => '理由', 'pinyin' => 'yóu']    // 理由  lǐ yóu
    ],
    '钉' => [
        ['type' => 'word', 'word' => '钉子', 'pinyin' => 'dīng']   // 钉子  dīng zi
    ],
    '缝' => [
        ['type' => 'word', 'word' => '裂缝', 'pinyin' => 'fèng']   // 裂缝  liè fèng
    ],
    '扇' => [
        ['type' => 'word', 'word' => '扇动', 'pinyin' => 'shān']   // 扇动  shān dòng
    ],
    '磨' => [
        ['type' => 'word', 'word' => '石磨', 'pinyin' => 'mò']     // 石磨  shí mò
    ],
    '咽' => [
        ['type' => 'word', 'word' => '咽喉', 'pinyin' => 'yān'],   // 咽喉  yān hóu
        ['type' => 'word', 'word' => '咽下', 'pinyin' => 'yàn'],   // 咽下  yàn xià
        ['type' => 'word', 'word' => '哽咽', 'pinyin' => 'yè']     // 哽咽  gěng yè
    ],
    '藏' => [
        ['type' => 'word', 'word' => '西藏', 'pinyin' => 'zàng']   // 西藏  xī zàng
    ],
    '弹' => [
        ['type' => 'word', 'word' => '子弹', 'pinyin' => 'dàn']    // 子弹  zǐ dàn
    ],
    '倒' => [
        ['type' => 'word', 'word' => '倒车', 'pinyin' => 'dào']    // 倒车  dào chē
    ],
    '涨' => [
        ['type' => 'word', 'word' => '涨红', 'pinyin' => 'zhàng']  // 涨红  zhàng hóng
    ],
    '饮' => [
        ['type' => 'word', 'word' => '饮马', 'pinyin' => 'yìn']    // 饮马  yìn mǎ
    ],
    '冠' => [
        ['type' => 'word', 'word' => '冠军', 'pinyin' => 'guàn']   // 冠军  guàn jūn
    ],
    '荷' => [
        ['type' => 'word', 'word' => '重荷', 'pinyin' => 'hè']     // 重荷  zhòng hè
    ],
    '散' => [
        ['type' => 'word', 'word' => '散文', 'pinyin' => 'sǎn']    // 散文  sǎn wén
    ],
    '曲' => [
        ['type' => 'word', 'word' => '弯曲', 'pinyin' => 'qū']     // 弯曲  wān qū
    ],
    '差' => [
        ['type' => 'word', 'word' => '出差', 'pinyin' => 'chāi'],  // 出差  chū chāi
        ['type' => 'word', 'word' => '差不多', 'pinyin' => 'chà']  // 差不多  chà bu duō
    ],
    '将' => [
        ['type' => 'word', 'word' => '将军', 'pinyin' => 'jiàng']  // 将军  jiāng jūn
    ],
    '相' => [
        ['type' => 'word', 'word' => '照片', 'pinyin' => 'xiàng']  // 照片  zhào piàn
    ],
    '泊' => [
        ['type' => 'word', 'word' => '湖泊', 'pinyin' => 'pō']     // 湖泊  hú pō
    ],
    '劲' => [
        ['type' => 'word', 'word' => '强劲', 'pinyin' => 'jìng']   // 强劲  qiáng jìng
    ],
    '舍' => [
        ['type' => 'word', 'word' => '舍弃', 'pinyin' => 'shě']    // 舍弃  shě qì
    ],
    '否' => [
        ['type' => 'word', 'word' => '否定', 'pinyin' => 'fǒu']    // 否定  fǒu dìng
    ],
    '创' => [
        ['type' => 'word', 'word' => '创伤', 'pinyin' => 'chuāng'] // 创伤  chuāng shāng
    ],
    '畜' => [
        ['type' => 'word', 'word' => '畜牧', 'pinyin' => 'xù']     // 畜牧  xù mù
    ],
    '禅' => [
        ['type' => 'word', 'word' => '禅让', 'pinyin' => 'shàn'],  // 禅让  shàn ràng
        ['type' => 'word', 'word' => '禅师', 'pinyin' => 'chán']   // 禅师  chán shī
    ],
    '屏' => [
        ['type' => 'word', 'word' => '屏住', 'pinyin' => 'bǐng']   // 屏住  bǐng zhù
    ],
    '折' => [
        ['type' => 'word', 'word' => '折腾', 'pinyin' => 'zhē'],   // 折腾  zhē teng
        ['type' => 'word', 'word' => '折本', 'pinyin' => 'shé']    // 折本  shé běn
    ],
    '提' => [
        ['type' => 'word', 'word' => '提防', 'pinyin' => 'dī']     // 提防  dī fang
    ],
    '绿' => [
        ['type' => 'word', 'word' => '鸭绿江', 'pinyin' => 'lù']   // 鸭绿江  yā lù jiāng
    ],
    '把' => [
        ['type' => 'word', 'word' => '刀把', 'pinyin' => 'bà']     // 刀把  dāo bà
    ],
    '艾' => [
        ['type' => 'word', 'word' => '自怨自艾', 'pinyin' => 'yì'] // 自怨自艾  zì yuàn zì yì
    ],
    '扁' => [
        ['type' => 'word', 'word' => '扁舟', 'pinyin' => 'piān']   // 扁舟  piān zhōu
    ],
    '供' => [
        ['type' => 'word', 'word' => '供奉', 'pinyin' => 'gòng']   // 供奉  gòng fèng
    ],
    '旋' => [
        ['type' => 'word', 'word' => '旋风', 'pinyin' => 'xuàn']   // 旋风  xuàn fēng
    ],
    '粘' => [
        ['type' => 'word', 'word' => '粘土', 'pinyin' => 'nián']   // 粘土  nián tǔ
    ],
    '率' => [
        ['type' => 'word', 'word' => '率领', 'pinyin' => 'shuài']  // 率领  shuài lǐng
    ],
    '觉' => [
        ['type' => 'word', 'word' => '睡觉', 'pinyin' => 'jiào']   // 睡觉  shuì jiào
    ],
    '铺' => [
        ['type' => 'word', 'word' => '店铺', 'pinyin' => 'pù']     // 店铺  diàn pù
    ],
    '几' => [
        ['type' => 'word', 'word' => '茶几', 'pinyin' => 'jī']     // 茶几  chá jī
    ],
    '解' => [
        ['type' => 'word', 'word' => '解数', 'pinyin' => 'xiè'],   // 解数  xiè shù
        ['type' => 'word', 'word' => '押解', 'pinyin' => 'jiè']    // 押解  yā jiè
    ],
    '禁' => [
        ['type' => 'word', 'word' => '禁受', 'pinyin' => 'jīn']    // 禁受  jīn shòu
    ],
    '塞' => [
        ['type' => 'word', 'word' => '堵塞', 'pinyin' => 'sè'],    // 堵塞  dǔ sè
        ['type' => 'word', 'word' => '要塞', 'pinyin' => 'sài']    // 要塞  yào sài
    ],
    '省' => [
        ['type' => 'word', 'word' => '反省', 'pinyin' => 'xǐng']   // 反省  fǎn xǐng
    ],
    '要' => [
        ['type' => 'word', 'word' => '要求', 'pinyin' => 'yāo']    // 要求  yāo qiú
    ],
    '降' => [
        ['type' => 'word', 'word' => '投降', 'pinyin' => 'xiáng']  // 投降  tóu xiáng
    ],
    '恶' => [
        ['type' => 'word', 'word' => '恶心', 'pinyin' => 'ě'],     // 恶心  ě xin
        ['type' => 'word', 'word' => '可恶', 'pinyin' => 'wù']     // 可恶  kě wù
    ],
    '喝' => [
        ['type' => 'word', 'word' => '喝彩', 'pinyin' => 'hè']     // 喝彩  hè cǎi
    ],
    '蕃' => [
        ['type' => 'word', 'word' => '蕃衍', 'pinyin' => 'fán']    // 蕃衍  fán yǎn
    ],
    '沓' => [
        ['type' => 'word', 'word' => '杂沓', 'pinyin' => 'tà']     // 杂沓  zá tà
    ],
    '烊' => [
        ['type' => 'word', 'word' => '打烊', 'pinyin' => 'yàng']   // 打烊  dǎ yàng
    ],
    '载' => [
        ['type' => 'word', 'word' => '记载', 'pinyin' => 'zǎi']    // 记载  jì zǎi
    ],
    '曝' => [
        ['type' => 'word', 'word' => '曝晒', 'pinyin' => 'pù']     // 曝晒  pù shài
    ],
    '宁' => [
        ['type' => 'word', 'word' => '宁可', 'pinyin' => 'nìng']   // 宁可  nìng kě
    ],
    '拗' => [
        ['type' => 'word', 'word' => '执拗', 'pinyin' => 'niù']    // 执拗  zhí niù
    ],
    '臭' => [
        ['type' => 'word', 'word' => '乳臭', 'pinyin' => 'xiù']    // 乳臭  rǔ xiù
    ],
    '哄' => [
        ['type' => 'word', 'word' => '哄骗', 'pinyin' => 'hǒng'],  // 哄骗  hǒng piàn
        ['type' => 'word', 'word' => '起哄', 'pinyin' => 'hòng']   // 起哄  qǐ hòng
    ],
    '丧' => [
        ['type' => 'word', 'word' => '丧事', 'pinyin' => 'sāng']   // 丧事  sāng shì
    ],
    '薄' => [
        ['type' => 'word', 'word' => '薄饼', 'pinyin' => 'báo'],   // 薄饼  báo bǐng
        ['type' => 'word', 'word' => '薄荷', 'pinyin' => 'bò']     // 薄荷  bó he
    ],
    '骑' => [
        ['type' => 'word', 'word' => '骑兵', 'pinyin' => 'jì']     // 骑兵  qí bīng
    ],
    '号' => [
        ['type' => 'word', 'word' => '号叫', 'pinyin' => 'háo']    // 号叫  háo jiào
    ],
    '读' => [
        ['type' => 'word', 'word' => '句读', 'pinyin' => 'dòu']    // 句读  jù dòu
    ],
    '上' => [
        ['type' => 'word', 'word' => '上涨', 'pinyin' => 'zhǎng']  // 上涨  shàng zhǎng
    ],
    '鸭' => [
        ['type' => 'word', 'word' => '鸭绿江', 'pinyin' => 'yā']   // 鸭绿江  yā lù jiāng
    ]
];