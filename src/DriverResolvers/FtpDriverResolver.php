<?php

namespace Emsifa\Backuper\DriverResolvers;

use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Filesystem;

class FtpDriverResolver extends BaseDriverResolver
{

    public function makeFilesystem(array $params)
    {
        $this->requireParams(['host', 'username', 'password'], $params);
        $adapter = new Ftp($params);
        return new Filesystem($adapter);
    }

}
