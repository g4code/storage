<?php

namespace G4\Storage;

class Storage
{
    const PATH_SEPARATOR = '_';

    /**
     * @var \G4\Storage\Driver\DriverInterface
     */
    private $_driver = null;

    private $_localFileKey;

    private $_localFile;

    private $_remoteFile;

    public function __construct(\G4\Storage\Driver\DriverInterface $driver)
    {
        $this->_driver = $driver;
    }

    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * @param string $value
     * @return \G4\Storage\Storage
     */
    public function setLocalFile($value, $key = null)
    {
        $this->_localFile = $value;
        $this->_localFileKey = $key;

        return $this;
    }

    public function getLocalFile()
    {
        return $this->_localFile;
    }

    /**
     * @param string $value
     * @return \G4\Storage\Storage
     */
    public function setRemoteFile($value)
    {
        $this->_remoteFile = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteFile()
    {
        return $this->_remoteFile;
    }

    public function put()
    {
        return $this->_driver->put($this->_localFile, $this->_remoteFile);
    }

    public function get()
    {
        return $this->_driver->get($this->_localFile, $this->_remoteFile, false, $this->_localFileKey);
    }

    public function replace()
    {
        return $this->_driver->replace($this->_localFile, $this->_remoteFile);
    }

    public function delete()
    {
        return $this->_driver->delete($this->_remoteFile);
    }
}
