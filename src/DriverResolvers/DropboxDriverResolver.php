<?php

namespace Emsifa\Backuper\DriverResolvers;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Srmklive\Dropbox\Adapter\DropboxAdapter;
use Srmklive\Dropbox\Client\DropboxClient;

class DropboxDriverResolver extends BaseDriverResolver
{

    public function makeFilesystem(array $params)
    {
        if (!class_exists(DropboxClient::class)) {
            throw new \Exception("Cannot resolve dropbox driver. You must install 'srmklive/flysystem-dropbox-v2' first.");
        }

        $this->requireParams(['token'], $params);
        $token = $params['token'];
        $client = new DropboxClient($token);
        $adapter = new DropboxAdapter($client);
        $filesystem = new Filesystem($adapter);
        return $filesystem;
    }

}
