<?php

namespace G4\Storage\Driver;

class File extends DriverAbstract
{
    private $_rootRemote;

    /**
     * @return \G4\Storage\Driver\File
     */
    protected function _processOptions()
    {
        parent::_processOptions();

        $options = $this->getOptions();

        if(!isset($options['root_remote'])
            || !is_string($options['root_remote'])
            || empty($options['root_remote'])
            || !realpath($options['root_remote'])
        ) {
            throw new \Exception('Remote file root path for is invalid');
        }

        $this->_rootRemote = realpath($options['root_remote']);

        return $this;
    }

    public function get($localFileName, $remoteFile, $deleteSource = false, $localFileKey = null)
    {
        // save local files if we are going to delete them
        // for identification purposes we use optional $localFileKey
        $this->_addLocalFile($localFileName, $localFileKey);

        $localFile = $this->_buildLocalPath($localFileName);
        $this->setLocalFilePath($localFileName, $localFile);

        $remoteFile = $this->_buildRemotePath($remoteFile);

        $remoteExists = file_exists($remoteFile);
        $remoteIsReadable = is_readable($remoteFile);

        $done = ($remoteExists && $remoteIsReadable)
            ? copy($remoteFile, $localFile)
            : false;

        //since we're allowing copying of zero size file, we want to know the size of saved local file
        $this->setLocalFileSize($localFileName, filesize($localFile));

        if(!$done) {
            $msgs = [];
            if(!$remoteExists) {
                $msgs[] = 'does not exist';
            }
            if(!$remoteIsReadable) {
                $msgs[] = 'is not readable';
            }
            $msg = 'File ' . implode(' and ', $msgs);

            //if there was any issue at this point, we want to save it in localFile array and use it if needed more info
            $this->setLocalFileError($localFileName, 404, $msg);
        }

        return $done ? $localFile : false;
    }

    public function put($localFile, $remoteFile, $deleteSource = false)
    {
        $remoteFile = $this->_buildRemotePath($remoteFile);

        return (file_exists($localFile) && is_readable($localFile))
            ? copy($localFile, $remoteFile)
            : false;
    }

    public function replace($localFile, $remoteFile, $deleteSource = false)
    {
        return $this->delete($remoteFile)
            ? $this->put($localFile, $remoteFile)
            : false;
    }

    public function delete($remoteFile)
    {
        $remoteFile = $this->_buildRemotePath($remoteFile);

        return (file_exists($remoteFile) && is_readable($remoteFile))
            ? unlink($remoteFile)
            : false;
    }

    private function _buildRemotePath($file)
    {
        if(null === $this->_rootRemote) {
            $this->_processOptions();
        }

        $base = basename($file);

        $dir = $this->_rootRemote . DIRECTORY_SEPARATOR . dirname($file);

        if(!file_exists($dir) && !mkdir($dir, 0755, true)) {
            throw new \Exception('Remote path is not writable');
        }

        $path = $dir . DIRECTORY_SEPARATOR . $base;

        if(false === $path) {
            throw new \Exception('Remote path for selected file is invalid');
        }

        return $path;
    }

}