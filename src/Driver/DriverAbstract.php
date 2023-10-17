<?php

namespace G4\Storage\Driver;

abstract class DriverAbstract implements DriverInterface
{
    protected $_driver = null;

    private $_options = array();

    private $_optionsSet = false;

    private $_rootLocal;

    private $_localFiles = array();

    private $_deleteLocalFileOnExit;

    public function __destruct()
    {
        if(!$this->_deleteLocalFileOnExit || !is_array($this->_localFiles) || empty($this->_localFiles)) {
            return;
        }

        foreach($this->_localFiles as $file=>$data) {
            $localPath = $this->_buildLocalPath($file);
            if(file_exists($localPath)){
                unlink($localPath);
            }
        }
    }

    /**
     * @return \G4\Storage\Driver\DriverAbstract
     */
    protected function _processOptions()
    {
        if(true === $this->_areOptionsSet()) {
            return true;
        }

        $options = $this->getOptions();

        if(empty($options)) {
            throw new \Exception('Options must be set');
        }

        if(!isset($options['root_local'])
            || !is_string($options['root_local'])
            || empty($options['root_local'])
            || !realpath($options['root_local'])
        ) {
            throw new \Exception('Local file root path for is invalid');
        }

        $this->_rootLocal = realpath($options['root_local']);

        $this->_deleteLocalFileOnExit = isset($options['delete_local_file'])
            ? (bool) $options['delete_local_file']
            : true;

        $this->_markOptionsAsSet();

        return $this;
    }

    protected function _areOptionsSet()
    {
        return $this->_optionsSet;
    }

    protected function _markOptionsAsSet()
    {
        $this->_optionsSet = true;
        return $this;
    }

    protected function _addLocalFile($file, $key = null)
    {
        if(!array_key_exists($file, $this->_localFiles)) {
            $this->_localFiles[$file] = [
                'key'       => $key,
                'path'      => null,
                'size'      => null,
                'error_no'  => null,
                'error_msg' => null,
            ];
        }
    }

    public function setLocalFilePath($fileName, $value)
    {
        $this->_localFiles[$fileName]['path'] = $value;
    }

    public function getLocalFilePath($key)
    {
        return $this->getFileInfoFromKey($key, 'path');
    }

    public function setLocalFileSize($fileName, $value)
    {
        $this->_localFiles[$fileName]['size'] = $value;
    }

    public function getLocalFileSize($key)
    {
        return $this->getFileInfoFromKey($key, 'size');
    }

    public function setLocalFileError($fileName, $errorNo, $errorMsg)
    {
        $this->_localFiles[$fileName]['error_no'] = $errorNo;
        $this->_localFiles[$fileName]['error_msg'] = $errorMsg;
    }

    public function getLocalFileErrorNo($key)
    {
        return $this->getFileInfoFromKey($key, 'error_no');
    }

    public function getLocalFileErrorMsg($key)
    {
        return $this->getFileInfoFromKey($key, 'error_msg');
    }

    /**
     * (non-PHPdoc)
     * @see \G4\Storage\Driver\DriverInterface::setOptions()
     * @return \G4\Storage\Driver\DriverAbstract
     */
    public function setOptions($options)
    {
         $this->_options = $options;
         return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \G4\Storage\Driver\DriverInterface::getOptions()
     * return $array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    protected function _buildLocalPath($file)
    {
        $this->_processOptions();

        $base = basename($file);

        $dir = $this->_rootLocal . DIRECTORY_SEPARATOR . dirname($file);

        if(!file_exists($dir) && !mkdir($dir, 644, true)) {
            throw new \Exception(sprintf('Local path "%s" is not writable', $dir));
        }

        $toTouch = $dir . DIRECTORY_SEPARATOR . $base;
        @touch($toTouch);

        $path = realpath($dir) . DIRECTORY_SEPARATOR . $base;

        if(false === $path) {
            throw new \Exception('Remote path for selected file is invalid');
        }

        return $path;
    }


    private function getFileInfoFromKey($key, $infoIndex)
    {
        $return = [];
        foreach ($this->_localFiles as $fileName=>$fileInfo) {
            if($fileInfo['key'] === $key) {
                $return[] = $fileInfo[$infoIndex];
            }
        }
        return $return;
    }
}
