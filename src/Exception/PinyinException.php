<?php

namespace tekintian\pinyin\Exception;

use Exception;

/**
 * 拼音转换异常类
 * 处理拼音转换过程中可能出现的异常
 */
class PinyinException extends Exception
{
    // 异常代码常量
    public const ERROR_FILE_NOT_FOUND = 1001; // 文件不存在
    public const ERROR_DICT_LOAD_FAIL = 1002; // 字典加载失败
    public const ERROR_INVALID_INPUT = 1003; // 输入无效
    public const ERROR_UNKNOWN = 1000; // 未知错误

    /**
     * 创建新的拼音转换异常实例
     *
     * @param string         $message  错误消息
     * @param int            $code     错误代码
     * @param Exception|null $previous 前一个异常
     */
    public function __construct($message, $code = self::ERROR_UNKNOWN, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
