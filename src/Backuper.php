<?php

namespace Emsifa\Backuper;

use Emsifa\Backuper\Exceptions\MissingDatabaseConfigException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use ZipArchive;
use MySQLDump;

class Backuper
{

    protected static $hasInitResolvers = false;
    protected static $driverResolvers = [];

    protected $configs;
    protected $logger;

    public function __construct($tempDir, array $configs, LoggerInterface $logger)
    {
        static::initDriverResolvers();
        $this->tempDir = $tempDir;
        $this->configs = $this->resolveConfigs($configs);
        $this->logger = $logger;
    }

    public function backup()
    {
        $backupFilename = $this->getBackupFilename();
        $dirName = date('ymdhis').'-'.uniqid();
        $tempDir = $this->tempDir.'/'.$dirName;
        $zipFilepath = $tempDir.'/'.$backupFilename.'.zip';

        $tempFs = new Filesystem(new Local($this->tempDir));
        $tempFs->createDir($dirName);

        // Creating archive
        $zip = new ZipArchive();
        if (true !== $zip->open($zipFilepath, ZipArchive::CREATE)) {
            throw new \Exception("Failed to create {$zipFilepath}");
        }
        $this->putBackupDatabases($zip, $tempDir);
        $this->putBackupFiles($zip);
        $zip->close();

        // Send zip file to backup storages
        $backups = $this->configs['backups'];
        foreach($backups as $key => $fs) {
            $putStream = $tempFs->readStream($dirName.'/'.$backupFilename.'.zip');
            $fs->putStream($backupFilename.'.zip', $putStream);
            if (is_resource($putStream)) {
                fclose($putStream);
            }
        }

        // Remove backup files
        $tempFs->deleteDir($dirName);
    }

    protected function putBackupDatabases(ZipArchive $zip, $tempDir)
    {
        $baseDir = 'databases';
        $databaseConfigs = $this->configs['databases'];

        $sqlFiles = [];
        foreach($databaseConfigs as $key => $dbConfig) {
            $missingConfigKeys = array_diff(['host', 'username', 'password', 'database'], array_keys($dbConfig));

            if (!empty($missingConfigKeys)) {
                $len = count($missingConfigKeys);
                $missingConfigKeys = array_map(function($param, $index) use ($len) {
                    return ($len-1 == $index AND $len > 1)? "and '{$param}'" : "'{$param}'";
                }, $missingConfigKeys, array_keys($missingConfigKeys));
                throw new MissingDatabaseConfigException("Missing database configuration ".implode(", ", $missingConfigKeys));
            }

            $mysqli = new \mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
            $dumpFile = $tempDir.'/'.$key.'.sql';
            $dump = new MySQLDump($mysqli);
            $dump->save($dumpFile);

            $zip->addFile($dumpFile, $baseDir.'/'.$key.'.sql');
        }
    }

    protected function putBackupFiles(ZipArchive $zip)
    {
        $baseDir = 'files';
        $entry = rtrim($this->configs['entry'], '/');
        if (!is_dir($entry)) {
            throw new \Exception("Entry directory '{$entry}' not found.", 1);
        }

        $fileConfigs = $this->configs['files'];

        $files = [];
        foreach($fileConfigs as $fileSearch) {
            $path = $entry.'/'.$fileSearch;
            $files = array_merge($files, $this->getFiles($path));
        }

        // put files to zip
        foreach($files as $file) {
            $fileInZip = str_replace($entry.'/', "", $file);
            $zip->addFile($file, $baseDir.'/'.$fileInZip);
        }
    }

    protected function getFiles($path)
    {
        if (is_dir($path)) {
            $path = rtrim($path, '/').'/*';
        }

        $files = glob($path);
        // glob('*') doesn't get dotfiles,
        // so we should glob('.*') to get dot files
        if (substr($path, -2, 2) === '/*') {
            $dotpath = preg_replace("/\/\*$/", "/.*", $path);
            $dotfiles = array_filter(glob($dotpath), function($file) {
                return !in_array(pathinfo($file, PATHINFO_BASENAME), ['.', '..']);
            });

            $files = array_merge($files, $dotfiles);
        }

        foreach($files as $i => $file) {
            if (is_dir($file)) {
                $files = array_merge($files, $this->getFiles($file));
            }
        }

        return array_filter($files, function($file) {
            return !is_dir($file);
        });
    }

    protected function getBackupFilename()
    {
        return preg_replace("/\.zip$/", "", $this->configs['output']);
    }

    public function restore()
    {

    }

    protected function resolveConfigs(array $configs)
    {
        $configs = array_merge([
            'entry' => null,
            'output' => date('Ymd_his'),
            'databases' => [],
            'files' => [],
            'backups' => []
        ], $configs);

        foreach($configs['backups'] as $key => $backupConfig) {
            $fs = null;

            if ($backupConfig instanceof Filesystem) {
                $fs = $backupConfig;
            } elseif (is_array($backupConfig)) {
                if (!isset($backupConfig['driver'])) {
                    throw new MissingRequiredParameterException("Missing required parameter 'driver' in backup '{$key}' configuration");
                }
                $driver = $backupConfig['driver'];
                $fs = $this->makeFilesystem($driver, $backupConfig);
            } elseif($backupConfig instanceof \Closure) {
                $fs = $backupConfig();
            }

            if (false == $fs instanceof Filesystem) {
                throw new \Exception("Backup config must be array, Closure, or ".Filesystem::class." instance.");
            }

            $configs['backups'][$key] = $fs;
        }

        return $configs;
    }

    protected function makeFilesystem($driver, array $backupConfig)
    {
        $driver = $backupConfig['driver'];
        if (!isset(static::$driverResolvers[$driver])) {
            throw new \Exception("Backup driver '{$driver}' is not registered");
        }
        $resolver = static::$driverResolvers[$driver];
        return $resolver->makeFilesystem($backupConfig);
    }

    public static function registerDriver($key, DriverResolvers\BaseDriverResolver $resolver)
    {
        static::$driverResolvers[$key] = $resolver;
    }

    protected static function initDriverResolvers()
    {
        if (!static::$hasInitResolvers) {
            static::registerDriver('local', new DriverResolvers\LocalDriverResolver());
            static::registerDriver('ftp', new DriverResolvers\FtpDriverResolver());
            static::registerDriver('dropbox', new DriverResolvers\DropboxDriverResolver());

            static::$hasInitResolvers = true;
        }
    }

}
