<?php

namespace G4\Storage\Driver;

interface DriverInterface
{
    public function put($localFile, $remoteFile, $deleteSource = false);

    public function get($localFile, $remoteFile, $deleteSource = false, $localFileKey = null);

    public function replace($localFile, $remoteFile, $deleteSource = false);

    public function delete($remoteFile);

    public function setOptions($options);

    public function getOptions();

    public function setLocalFilePath($key, $value);

    public function getLocalFilePath($key);

    public function setLocalFileSize($key, $value);

    public function getLocalFileSize($key);

    public function setLocalFileError($key, $errorNo, $errorMsg);

    public function getLocalFileErrorNo($key);

    public function getLocalFileErrorMsg($key);
}