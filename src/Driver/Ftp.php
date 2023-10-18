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

    /**
     * @var bool
     */
    private $passive = false;

    private $useFtpSiteCommand;

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

        if(isset($options['passive']) && $options['passive']) {
            $this->passive = true;
        }

        $this->_user = isset($options['user']) ? $options['user'] : '';
        $this->_pass = isset($options['pass']) ? $options['pass'] : '';

        $this->useFtpSiteCommand = isset($options['use_ftp_site_cmd']) ? $options['use_ftp_site_cmd'] : false;

        return $this;
    }

    public function get($localFileName, $remoteFile, $deleteSource = false, $localFileKey = null)
    {
        // save local files if we are going to delete them
        $this->_addLocalFile($localFileName, $localFileKey);

        $localFile = $this->_buildLocalPath($localFileName);
        $this->setLocalFilePath($localFileName, $localFile);

        $ftpSize = ftp_size($this->_connect(), $remoteFile) > -1;
        $ftpDownloaded = $ftpSize ? ftp_get($this->_connect(), $localFile, $remoteFile, FTP_BINARY) : $ftpSize;

        $done = ($ftpSize && $ftpDownloaded)
            ? $localFile
            : false;

        //since we're allowing copying of zero size file, we want to know the size of saved local file
        $this->setLocalFileSize($localFileName, file_exists($localFile) ? filesize($localFile) : 0);

        if(!$done) {
            $msgs = [];
            if(!$ftpSize) {
                $msgs[] = 'remote does not exist';
            }
            if(!$ftpDownloaded) {
                $msgs[] = 'not downloaded';
            }
            $msg = 'File ' . implode(' and ', $msgs);

            //if there was any issue at this point, we want to save it in localFile array and use it if needed more info
            $this->setLocalFileError($localFileName, 404, $msg);
        }

        return $done ? $localFile : false;
    }

    public function put($localFile, $remoteFile, $deleteSource = false)
    {
        $this->_processOptions();

        $localFile = realpath($localFile);

        if(false === $localFile) {
            throw new \Exception('Local file path is invalid');
        }

        (new Directory($this->_connect(), $remoteFile, $this->useFtpSiteCommand))->create();

        ftp_chdir($this->_connect(), dirname($remoteFile));

        $done = ftp_put($this->_connect(), $remoteFile, $localFile, FTP_BINARY);

        $localSize = filesize($localFile);
        $remoteSize = ftp_size($this->_connect(), $remoteFile);

        if ($localSize != $remoteSize) {
            throw new \Exception("FTP uploaded remote file size mismatch. Local size: $localSize, remote size $remoteSize");
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
        return (ftp_size($this->_connect(), $remoteFile) > -1)
            ? ftp_delete($this->_connect(), $remoteFile)
            : true;
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

        if ($this->passive) {
            ftp_pasv($this->_connection, true);
        }
    }
}
