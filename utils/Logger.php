<?php

namespace Utils;

/**
* utils/Logger.php - Simple logging utility
*/

use Config\Config;

class Logger
{
    private static $instance = null;
    private $logFile;

    private function __construct()
    {
        if (!is_dir(Config::getLogPath())) {
            mkdir(Config::getLogPath(), 0777, true);
        }

        $this->logFile = Config::getLogPath() . '/app-' . date('Y-m-d') . '.log';
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($level, $message, $context = [])
    {
        $logLevels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3
        ];

        // Check if the log level is sufficient
        if ($logLevels[$level] < $logLevels[Config::getLogLevel()]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context) : '';

        $logEntry = "[{$timestamp}] [{$level}] {$message} {$contextString}" . PHP_EOL;

        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function debug($message, $context = [])
    {
        $this->log('debug', $message, $context);
    }

    public function info($message, $context = [])
    {
        $this->log('info', $message, $context);
    }

    public function warning($message, $context = [])
    {
        $this->log('warning', $message, $context);
    }

    public function error($message, $context = [])
    {
        $this->log('error', $message, $context);
    }
}
