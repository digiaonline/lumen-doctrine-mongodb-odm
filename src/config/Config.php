<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB\Config;

use Illuminate\Config\Repository as ConfigRepository;

class Config
{

    const ODM_CONFIG_NAME    = 'doctrine-odm';
    const ODM_DB_CONFIG_NAME = 'mongodb';

    /**
     * Base config
     * @var array
     */
    private static $baseConfig = [

        /*
        |--------------------------------------------------------------------------
        | Mapping driver to use
        |--------------------------------------------------------------------------
        |
        */

        'mapping'            => 'annotations',
        /*
        |--------------------------------------------------------------------------
        | Entity paths
        |--------------------------------------------------------------------------
        |
        */

        'paths'              => [],
        /*
        |--------------------------------------------------------------------------
        | Custom types
        |--------------------------------------------------------------------------
        |
        */

        'types'              => [
            'short_id' => 'Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types\ShortIdType',
        ],
        /*
        |--------------------------------------------------------------------------
        | Proxy configuration
        |--------------------------------------------------------------------------
        |
        */

        'proxy'              => [
            'auto_generate' => false,
            'namespace'     => 'Proxies',
        ],
        /*
        |--------------------------------------------------------------------------
        | Repository class
        |--------------------------------------------------------------------------
        |
        */

        'repository'         => null,
        /*
        |--------------------------------------------------------------------------
        | Logger class
        |--------------------------------------------------------------------------
        |
        */

        'logger'             => null,
        /*
        |--------------------------------------------------------------------------
        | Simple annotations
        |--------------------------------------------------------------------------
        |
        */
        'simple_annotations' => false,
        /*
        |--------------------------------------------------------------------------
        | Filter classes
        |--------------------------------------------------------------------------
        |
        */
        'filters'            => [],
        /*
        |--------------------------------------------------------------------------
        | Event listener classes
        |--------------------------------------------------------------------------
        |
        */
        'event_listeners'    => [],
        /*
        |--------------------------------------------------------------------------
        | Hydrator config
        |--------------------------------------------------------------------------
        |
        */
        'hydrator'           => [
            'namespace' => 'Hydrators',
        ],
    ];


    /**
     * Constructor
     */

    public function __construct()
    {
        return self::get();
    }


    /**
     * Returns base config values
     * @return array
     */
    public static function get()
    {
        return self::$baseConfig;
    }


    public static function mergeWith(ConfigRepository $config)
    {
        $defaultConfig = self::get();
        $realConfig    = $config->get(self::ODM_CONFIG_NAME);
        foreach ($realConfig as $configKey => $configValue) {
            if (isset($defaultConfig[$configKey])) {
                $defaultConfig[$configKey] += $configValue;
            }
        }

        //array_merge_recursive($defaultConfig, $config->get(self::ODM_CONFIG_NAME))
        $config->set(self::ODM_CONFIG_NAME, $defaultConfig);
    }
}

