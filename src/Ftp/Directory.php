<?php

namespace G4\Storage\Ftp;

class Directory
{

    private $connection;

    private $directoryPath;

    private $pathParts;

    private $useFtpSiteCommand;

    public function __construct($connection, $filePath, $useFtpSiteCommand = false)
    {
        $this->connection       = $connection;
        $this->directoryPath    = dirname($filePath);
        $this->pathParts        = array_filter(explode('/', $this->directoryPath)); // foo/bar/bat
        $this->useFtpSiteCommand = $useFtpSiteCommand;
    }

    public function create()
    {
        if($this->useFtpSiteCommand) {
            ftp_site($this->connection, "MKDIR $this->directoryPath");
            return;
        }

        $pathPart = '';
        if(!$this->exists($this->directoryPath)) {
            foreach($this->pathParts as $part){
                $pathPart .= '/' . $part;
                if(!$this->exists($pathPart)){
                    ftp_mkdir($this->connection, $pathPart);
                }
            }
        }
    }

    private function exists($directoryPath)
    {
        $list = ftp_nlist($this->connection, dirname($directoryPath));
        return is_array($list) && in_array($directoryPath, $list);
    }
}