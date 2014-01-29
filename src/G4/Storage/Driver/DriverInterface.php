<?php

namespace G4\Storage\Driver;

interface DriverInterface
{
    public function put($localFile, $remoteFile, $deleteSource = false);

    public function get($localFile, $remoteFile, $deleteSource = false);

    public function replace($localFile, $remoteFile, $deleteSource = false);

    public function delete($remoteFile);

    public function setOptions($options);

    public function getOptions();
}