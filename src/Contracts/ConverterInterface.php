<?php 
namespace tekintian\pinyin\Contracts;


interface ConverterInterface
{
     /**
     * 转换文本为拼音（最终处理）
     *
     * @param string $text 要转换的文本
     * @param string $separator 拼音分隔符，默认为空格
     * @param bool $withTone 是否保留声调，默认为false
     * @param array|string $specialCharParam 特殊字符处理参数，默认为空数组
     * @param array $polyphoneTempMap 临时多音字映射表，默认为空数组
     * @return string 转换后的拼音字符串
     */
    public function convert(
        string $text,
        string $separator = ' ',
        bool $withTone = false,
        $specialCharParam = [],
        array $polyphoneTempMap = []
    ): string;

     /**
     * 转换为URL Slug
     * @param string $text 文本
     * @param string $separator 分隔符
     * @return string URL Slug
     */
    public function getUrlSlug(string $text, string $separator = '-'): string;
    
}