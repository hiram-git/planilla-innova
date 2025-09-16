<?php

namespace App\Core;

class Logger
{
    private static $logDir = '../storage/logs/';
    
    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }
    
    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }
    
    public static function warning($message, $context = [])
    {
        self::log('WARNING', $message, $context);
    }
    
    public static function debug($message, $context = [])
    {
        if (Config::get('app.debug', false)) {
            self::log('DEBUG', $message, $context);
        }
    }
    
    private static function log($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        $logEntry = "[{$timestamp}] {$level}: {$message}";
        if ($contextStr) {
            $logEntry .= " Context: {$contextStr}";
        }
        $logEntry .= "\n";
        
        $filename = self::$logDir . date('Y-m-d') . '.log';
        
        if (!file_exists(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function cleanup($daysToKeep = 30)
    {
        $cutoffDate = time() - ($daysToKeep * 24 * 60 * 60);
        
        if (is_dir(self::$logDir)) {
            $files = scandir(self::$logDir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && substr($file, -4) === '.log') {
                    $filePath = self::$logDir . $file;
                    if (filemtime($filePath) < $cutoffDate) {
                        unlink($filePath);
                    }
                }
            }
        }
    }
}