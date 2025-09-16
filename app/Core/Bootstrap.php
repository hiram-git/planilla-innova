<?php

namespace App\Core;

class Bootstrap
{
    public static function init()
    {
        self::loadHelpers();
        self::startSession();
        self::setTimezone();
        self::setErrorReporting();
        Config::load();
    }

    private static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = Config::get('app.session', []);
            
            if (isset($config['name'])) {
                session_name($config['name']);
            }
            
            if (isset($config['lifetime'])) {
                ini_set('session.gc_maxlifetime', $config['lifetime'] * 60);
            }
            
            session_start();
        }
    }

    private static function setTimezone()
    {
        $timezone = Config::get('app.timezone', 'UTC');
        date_default_timezone_set($timezone);
    }

    private static function loadHelpers()
    {
        require_once __DIR__ . '/helpers.php';
    }

    private static function setErrorReporting()
    {
        if (Config::get('app.debug', false)) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }
}