<?php

namespace Weat;

class Config
{
    const CONFIG_FILE = 'config/config.php';

    public function __construct()
    {
        if (!$config = include self::CONFIG_FILE) {
            throw new Exception('No config file');
        }

        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }
}
