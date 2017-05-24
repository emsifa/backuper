<?php


use Apix\Log\Logger\File as FileLogger;
use Emsifa\Backuper\Backuper;
use Jasny\ErrorHandler;
use Rakit\Console\App;

require('vendor/autoload.php');

define('BACKUPER_PATH', __DIR__);
define('TEMP_PATH', BACKUPER_PATH.'/temp');
define('LOG_PATH', BACKUPER_PATH.'/logs');

$app = new App;

$app->command('backup {config_file::Backup configuration file}', 'Backup site', function($configFile) {
    $configFilepath = BACKUPER_PATH.'/'.$configFile;
    $logFilename = preg_replace("/[^a-zA-Z0-9]/", "_", $configFile);
    $logFilepath = LOG_PATH.'/'.$logFilename.'.log';
    $logger = new FileLogger($logFilepath);

    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logger) {
        $logger->error($errstr." at ".$errfile." line ".$errline);
    });

    register_shutdown_function(function() use ($logger) {
        $error = error_get_last();
        if ($error !== NULL) {
            $logger->error($error['message']." at ".$error['file']." line ".$error['line']);
        }
    });

    try {
        if (!file_exists($configFilepath)) {
            throw new InvalidArgumentException("Cannot run backup. Config file '{$configFile}' not found.", 1);
        }

        $configs = require($configFilepath);
        $backuper = new Backuper(TEMP_PATH, $configs, $logger);
        $backuper->backup();
    } catch (Exception $e) {
        $logger->error($e);
    }
});

$app->run();
