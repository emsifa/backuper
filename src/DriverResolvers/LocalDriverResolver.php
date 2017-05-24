<?php

namespace Emsifa\Backuper\DriverResolvers;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class LocalDriverResolver extends BaseDriverResolver
{

    public function makeFilesystem(array $params)
    {
        $this->requireParams(['root'], $params);
        $root = $params['root'];

        return new Filesystem(new Local($root));
    }

}
