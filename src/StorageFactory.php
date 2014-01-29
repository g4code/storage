<?php

namespace G4\Storage;

class StorageFactory
{
    const DRIVER_FILE  = 'File';
    const DRIVER_FTP   = 'Ftp';

    private static $_validDrivers = array(
        self::DRIVER_FILE,
        self::DRIVER_FTP,
    );

    /**
     * Create new instance of G4\Storage\Storage
     *
     * @param string $driver
     * @param array $options
     *
     * @throws \Exception
     * @return \G4\Storage\Storage
     */
    public static function createInstance($driver, $options)
    {
        if(!in_array($driver, self::$_validDrivers)) {
            throw new \Exception("Driver '{$driver}' not implemented");
        }

        $class = __NAMESPACE__ . '\\Driver\\' . $driver;

        $driver = new $class;
        $driver->setOptions($options);

        return new Storage($driver);
    }
}