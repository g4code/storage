<?php

namespace G4\Storage\Ftp;

class Directory
{

    private $connection;

    private $directoryPath;

    private $pathParts;

    public function __construct($connection, $filePath)
    {
        $this->connection       = $connection;
        $this->directoryPath    = dirname($filePath);
        $this->pathParts        = array_filter(explode('/', $this->directoryPath)); // foo/bar/bat
    }

    public function create()
    {
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