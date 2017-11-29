<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Config;

use Exception;
use Illuminate\Config\Repository as ConfigRepository;

/**
 * Class Config.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB\Config
 */
class Config
{

    const ODM_CONFIG_NAME      = 'odm';
    const ODM_DB_CONFIG_NAME   = 'mongodb';
    const DEFAULT_MONGODB_PORT = 27017;

    /**
     * Base config
     *
     * @var array
     */
    private static $baseConfig = [

        /*
        |--------------------------------------------------------------------------
        | Mapping driver to use
        |--------------------------------------------------------------------------
        |
        */

        'mapping' => 'annotations',
        /*
        |--------------------------------------------------------------------------
        | Entity paths
        |--------------------------------------------------------------------------
        |
        */

        'paths' => [],
        /*
        |--------------------------------------------------------------------------
        | Custom types
        |--------------------------------------------------------------------------
        |
        */

        'types' => [
            'short_id' => 'Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types\ShortIdType',
        ],
        /*
        |--------------------------------------------------------------------------
        | Proxy configuration
        |--------------------------------------------------------------------------
        |
        */

        'proxy' => [
            'auto_generate' => false,
            'namespace'     => 'Proxies',
        ],
        /*
        |--------------------------------------------------------------------------
        | Repository class
        |--------------------------------------------------------------------------
        |
        */

        'repository' => null,
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
            'auto_generate' => false,
            'namespace' => 'Hydrators',
        ],
        /*
        |--------------------------------------------------------------------------
        | Cache config
        |--------------------------------------------------------------------------
        |
        */
        'cache' => [
            'default' => 'array',
            'config' => [],
        ],
    ];

    /**
     * @var ConfigRepository
     */
    private static $liveConfig;

    /**
     * Constructor
     */
    public function __construct()
    {
        return self::getDefaults();
    }

    /**
     * Returns config defaults
     *
     * @return array
     */
    public static function getDefaults()
    {
        return self::$baseConfig;
    }

    /**
     * Returns ODM config values
     *
     * @param ConfigRepository $config
     *
     * @return array
     */
    public static function getODM(ConfigRepository $config)
    {
        return $config->get(self::ODM_CONFIG_NAME);
    }

    /**
     * Returns mongodb database connection parameters
     *
     * @param ConfigRepository $config
     *
     * @return mixed
     */
    public static function getDB(ConfigRepository $config)
    {
        return $config->get(self::ODM_DB_CONFIG_NAME);
    }

    /**
     * Health check for existence of both config files
     *
     * @param ConfigRepository $config
     *
     * @throws Exception
     */
    public static function check(ConfigRepository $config)
    {
        if ( ! isset($config[self::ODM_CONFIG_NAME])) {
            throw new Exception('Doctrine ODM configuration not registered.');
        }

        if ( ! isset($config[self::ODM_DB_CONFIG_NAME])) {
            throw new Exception('Database configuration not registered.');
        }
    }

    /**
     * Merging defaults and live config
     *
     * @param ConfigRepository $config
     */
    public static function mergeWith(ConfigRepository $config)
    {
        $defaultConfig = self::getDefaults();
        $realConfig    = $config->get(self::ODM_CONFIG_NAME);
        foreach ($realConfig as $configKey => $configValue) {
            if (isset($defaultConfig[$configKey])) {
                if (is_array($defaultConfig[$configKey])) {
                    $defaultConfig[$configKey] += $configValue;
                } else {
                    $defaultConfig[$configKey] = $configValue;
                }
            }
        }
        $config->set(self::ODM_CONFIG_NAME, $defaultConfig);
        self::$liveConfig = $config;
    }

    /**
     * Creates the Doctrine connection configuration.
     *
     * @return array
     * @throws Exception
     */
    public static function createConnectionConfig()
    {
        $doctrineConfig   = self::$liveConfig->get(self::ODM_CONFIG_NAME);
        $databaseConfig   = self::$liveConfig->get(self::ODM_DB_CONFIG_NAME);
        $connectionName   = array_get($doctrineConfig, 'connection', $databaseConfig['default']);
        $connectionConfig = array_get($databaseConfig['connections'], $connectionName);

        if ($connectionConfig === null) {
            throw new Exception("Configuration for connection '$connectionName' not found.");
        }

        return self::normalizeConnectionConfig();
    }

    /**
     * Normalizes the connection config to a format Doctrine can use.
     *
     * @return array
     * @throws \Exception
     */
    public static function normalizeConnectionConfig()
    {
        $config   = self::$liveConfig->get(self::ODM_DB_CONFIG_NAME);
        $dbConfig = $config['connections'][$config['default']];

        return [
            'host'     => $dbConfig['host'],
            'port'     => ! empty($dbConfig['port']) ? $dbConfig['port'] : self::DEFAULT_MONGODB_PORT,
            'user'     => $dbConfig['username'],
            'password' => $dbConfig['password'],
        ];
    }
}

