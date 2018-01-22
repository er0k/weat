<?php

namespace Weat;

class Config
{
    const CONFIG_FILE = 'config/config.php';

    /** @var string */
    private $debugInfo = '';

    public function __construct()
    {
        if (!$config = include self::CONFIG_FILE) {
            throw new Exception('No config file');
        }

        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @param  string $line
     */
    public function debug($line = '')
    {
        $this->debugInfo = $this->debugInfo . $line . "\n";
    }

    /**
     * @return string
     */
    public function getDebugInfo()
    {
        return $this->debugInfo;
    }
}