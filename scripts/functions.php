<?php 


if (!function_exists('parse_env_file')) {
    /**
     * 解析 .env 文件
     * 
     * @param string $envFile .env 文件路径
     * @param bool $setEnv 是否设置环境变量
     * @return array 解析后的环境变量数组
     */
    function parse_env_file(string $envFile, bool $setEnv = true): array
    {
        $envVars = [];
        
        if (!is_file($envFile)) {
            return $envVars;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // 跳过注释和空行
            if (empty($line) || strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // 解析键值对
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 移除引号
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $envVars[$key] = $value;
                // 设置环境变量
                if($setEnv) putenv("{$key}={$value}");
            }
        }
        
        return $envVars;
    }
}
