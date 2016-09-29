<?php

namespace G4\Storage\Driver;

use G4\Storage\Ftp\Directory;

class Ftp extends DriverAbstract
{
    const DEFAULT_PORT    = 21;

    const DEFAULT_TIMEOUT = 90;

    /**
     * @var resource
     */
    private $_connection;

    private $_host;

    private $_port;

    private $_timeout;

    private $_user;

    private $_pass;

    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * @return \G4\Storage\Driver\Ftp
     */
    protected function _processOptions()
    {
        parent::_processOptions();

        $options = $this->getOptions();

        if(empty($options)) {
            throw new \Exception('Options must be set');
        }

        if(!isset($options['host']) || !is_string($options['host']) || empty($options['host'])) {
            throw new \Exception('Ftp host is invalid');
        }

        $this->_host = $options['host'];

        $this->_port = isset($options['port']) ? intval($options['port']) : 0;
        if(!$this->_port) {
            $this->_port = self::DEFAULT_PORT;
        }

        $this->_timeout = isset($options['timeout']) ? intval($options['timeout']) : 0;
        if(!$this->_timeout) {
            $this->_timeout = self::DEFAULT_TIMEOUT;
        }

        $this->_user = isset($options['user']) ? $options['user'] : '';
        $this->_pass = isset($options['pass']) ? $options['pass'] : '';

        return $this;
    }

    public function get($localFile, $remoteFile, $deleteSource = false)
    {
        // save local files if we are going to delete them
        $this->_addLocalFile($localFile);

        $localFile = $this->_buildLocalPath($localFile);

        return (ftp_size($this->_connect(), $remoteFile) > -1) && ftp_get($this->_connect(), $localFile, $remoteFile, FTP_BINARY)
            ? $localFile
            : false;
    }

    public function put($localFile, $remoteFile, $deleteSource = false)
    {
        $localFile = realpath($localFile);

        if(false === $localFile) {
            throw new \Exception('Local file path is invalid');
        }

        (new Directory($this->_connect(), $remoteFile))->create();

        ftp_chdir($this->_connect(), dirname($remoteFile));

        $done = ftp_put($this->_connect(), $remoteFile, $localFile, FTP_BINARY);

        if (filesize($localFile) != ftp_size($this->_connect(), $remoteFile)) {
            throw new \Exception('FTP uploaded remote file size mismatch');
        }

        ftp_chdir($this->_connect(), '/');

        return $done;
    }

    public function replace($localFile, $remoteFile, $deleteSource = false)
    {
        return $this->delete($remoteFile)
            ? $this->put($localFile, $remoteFile)
            : false;
    }

    public function delete($remoteFile)
    {
        return ftp_delete($this->_connect(), $remoteFile);
    }

    private function _connect()
    {
        if(!is_resource($this->_connection)) {
            $this->_connectionFactory();
        }

        return $this->_connection;
    }

    private function _connectionFactory()
    {
        $this->_processOptions();

        $this->_connection = ftp_connect($this->_host, $this->_port, $this->_timeout);

        if(!$this->_connection) {
            throw new \Exception('Failed to connect to FTP host');
        }

        $login = ftp_login($this->_connection, $this->_user, $this->_pass);

        if(!$login) {
            throw new \Exception('Wrong user/pass combination failed to autheticate');
        }
    }
}