<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita16924c875473502f40e1c76c84cf1b6
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Rakit\\Console\\' => 14,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'L' => 
        array (
            'League\\Flysystem\\' => 17,
        ),
        'E' => 
        array (
            'Emsifa\\Backuper\\' => 16,
        ),
        'A' => 
        array (
            'Apix\\Log\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Rakit\\Console\\' => 
        array (
            0 => __DIR__ . '/..' . '/rakit/console/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'League\\Flysystem\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/flysystem/src',
        ),
        'Emsifa\\Backuper\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Apix\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/apix/log/src',
        ),
    );

    public static $classMap = array (
        'MySQLDump' => __DIR__ . '/..' . '/dg/mysql-dump/src/MySQLDump.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita16924c875473502f40e1c76c84cf1b6::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita16924c875473502f40e1c76c84cf1b6::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita16924c875473502f40e1c76c84cf1b6::$classMap;

        }, null, ClassLoader::class);
    }
}
