<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type;
use Exception;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Nord\Lumen\Doctrine\ODM\MongoDB\Config\Config;
use Nord\Lumen\Doctrine\ODM\MongoDB\Tools\Setup;

/**
 * Class DoctrineServiceProvider.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB
 */
class DoctrineServiceProvider extends ServiceProvider
{

    const METADATA_ANNOTATIONS = 'annotations';
    const METADATA_XML         = 'xml';
    const METADATA_YAML        = 'yaml';
    const HYDRATOR_NAMESPACE   = 'Hydrators';
    const DEFAULT_MONGODB_PORT = 27017;

    /**
     * @var DocumentManager
     */
    private $documentManager = null;

    /**
     * @inheritdoc
     */
    public function register()
    {
        Config::check($this->app['config']);
        $this->registerContainerBindings($this->app, $this->app['config']);
        $this->registerCommands();
    }

    /**
     * Registers container bindings.
     *
     * @param Container        $container
     * @param ConfigRepository $config
     *
     * @return DocumentManager
     */
    protected function registerContainerBindings(Container $container, ConfigRepository $config)
    {
        $container->singleton('Doctrine\ODM\MongoDB\DocumentManager', function () use ($config) {
            Config::mergeWith($config);

            return $this->createDocumentManager($config);
        });
    }

    /**
     * Registers console commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\GenerateDocumentsCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\GenerateHydratorsCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\GenerateProxiesCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\GenerateRepositoriesCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\QueryCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\ClearCache\MetadataCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\Schema\CreateCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\Schema\DropCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command\Schema\UpdateCommand',
        ]);
    }

    /**
     * Creates the Doctrine entity manager instance.
     *
     * @param ConfigRepository $config
     *
     * @return DocumentManager
     * @throws Exception
     */
    protected function createDocumentManager(ConfigRepository $config)
    {
        $doctrineConfig   = Config::getODM($config);
        $databaseConfig   = Config::getDB($config);
        $connectionConfig = Config::createConnectionConfig();

        $type = array_get($doctrineConfig, 'mapping', self::METADATA_ANNOTATIONS);
        // if no paths are set set default ones
        $paths             = array_get($doctrineConfig, 'paths', [base_path('app/Entities')]);
        $debug             = $config['app.debug'];
        $proxyDir          = array_get($doctrineConfig, 'proxy.directory');
        $simpleAnnotations = array_get($doctrineConfig, 'simple_annotations', false);
        $cache             = $this->configureCache($doctrineConfig);

        $metadataConfiguration = $this->createMetadataConfiguration($type, $paths, $debug, $proxyDir, $cache,
            $simpleAnnotations);

        $this->configureMetadataConfiguration($metadataConfiguration, $doctrineConfig, $databaseConfig);

        $eventManager = new EventManager;

        $this->configureEventManager($doctrineConfig, $eventManager);
        $connection = new Connection($connectionConfig['host'], [], $metadataConfiguration);

        $documentManager = DocumentManager::create($connection, $metadataConfiguration, $eventManager);

        $this->configureDocumentManager($doctrineConfig, $documentManager);
        $this->documentManager = $documentManager;

        return $documentManager;
    }

    /**
     * Creates the metadata configuration instance.
     *
     * @param string $type
     * @param array  $paths
     * @param bool   $isDevMode
     * @param string $proxyDir
     * @param Cache  $cache
     * @param bool   $useSimpleAnnotationReader
     *
     * @return Configuration
     * @throws \Exception
     */
    protected function createMetadataConfiguration(
        $type,
        $paths,
        $isDevMode,
        $proxyDir,
        $cache,
        $useSimpleAnnotationReader = true
    ) {
        switch ($type) {
            case self::METADATA_ANNOTATIONS:
                return Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache,
                    $useSimpleAnnotationReader);
            case self::METADATA_XML:
                return Setup::createXMLMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache);
            case self::METADATA_YAML:
                return Setup::createYAMLMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache);
            default:
                throw new Exception("Metadata type '$type' is not supported.");
        }
    }

    /**
     * Configures the metadata configuration instance.
     *
     * @param Configuration $configuration
     * @param array         $doctrineConfig
     * @param array         $databaseConfig
     *
     * @throws Exception
     */
    protected function configureMetadataConfiguration(
        Configuration $configuration,
        array $doctrineConfig,
        array $databaseConfig
    ) {
        if (isset($doctrineConfig['filters'])) {
            foreach ($doctrineConfig['filters'] as $name => $filter) {
                $configuration->addFilter($name, $filter['class']);
            }
        }
        if (isset($doctrineConfig['proxy'])) {
            if (isset($doctrineConfig['proxy']['auto_generate'])) {
                $configuration->setAutoGenerateProxyClasses($doctrineConfig['proxy']['auto_generate']);
            }
            if (isset($doctrineConfig['proxy']['namespace'])) {
                $configuration->setProxyNamespace($doctrineConfig['proxy']['namespace']);
            }
            if (isset($doctrineConfig['proxy']['directory'])) {
                $configuration->setProxyDir($doctrineConfig['proxy']['directory']);
            }
        }

        if ( ! empty($doctrineConfig['repository'])) {
            $configuration->setDefaultRepositoryClassName($doctrineConfig['repository']);
        }

        if (isset($doctrineConfig['hydrator'])) {
            if (isset($doctrineConfig['hydrator']['directory'])) {
                $configuration->setHydratorDir($doctrineConfig['hydrator']['directory']);
            }
            if (isset($doctrineConfig['hydrator']['namespace'])) {
                $hydratorNamespace = $doctrineConfig['hydrator']['namespace'] ? $doctrineConfig['hydrator']['namespace'] : self::HYDRATOR_NAMESPACE;
                $configuration->setHydratorNamespace($hydratorNamespace);
            }
            if (isset($doctrineConfig['hydrator']['auto_generate'])) {
                $configuration->setAutoGenerateHydratorClasses($doctrineConfig['hydrator']['auto_generate']);
            }
        }
        if ( ! empty($databaseConfig['connections'][$databaseConfig['default']]['database'])) {
            $configuration->setDefaultDB($databaseConfig['connections'][$databaseConfig['default']]['database']);
        }

    }

    /**
     * Configures the Doctrine event manager instance.
     *
     * @param array        $doctrineConfig
     * @param EventManager $eventManager
     */
    protected function configureEventManager(array $doctrineConfig, EventManager $eventManager)
    {
        if (isset($doctrineConfig['event_listeners'])) {
            foreach ($doctrineConfig['event_listeners'] as $name => $listener) {
                $eventManager->addEventListener($listener['events'], new $listener['class']);
            }
        }
    }

    /**
     * Configures the Doctrine entity manager instance.
     *
     * @param array           $doctrineConfig
     * @param DocumentManager $documentManager
     */
    protected function configureDocumentManager(array $doctrineConfig, DocumentManager $documentManager)
    {
        if (isset($doctrineConfig['filters'])) {
            foreach ($doctrineConfig['filters'] as $name => $filter) {
                if ( ! array_get($filter, 'enabled', false)) {
                    continue;
                }
                $documentManager->getFilterCollection()->enable($name);
            }
        }

        // @see http://doctrine-mongodb-odm.readthedocs.org/en/latest/reference/basic-mapping.html#custom-mapping-types
        if (isset($doctrineConfig['types'])) {
            foreach ($doctrineConfig['types'] as $name => $className) {
                if ( ! Type::hasType($name)) {
                    Type::addType($name, $className);
                } else {
                    Type::overrideType($name, $className);
                }
            }
        }
    }

    /**
     * Configure the cache provider.
     *
     * @param array $doctrineConfig
     *
     * @return CacheProvider
     */
    protected function configureCache(array $doctrineConfig)
    {
        $enableCache = array_get($doctrineConfig, 'cache.default');
        $cacheConfig = array_get($doctrineConfig, 'cache.config', []);
        if ($enableCache === 'apcu' && \extension_loaded('apcu')) {
            $cache = new ApcuCache();
        } elseif ($enableCache === 'apcu' && \extension_loaded('apc')) {
            $cache = new ApcCache();
        } elseif ($enableCache === 'xcache' && \extension_loaded('xcache')) {
            $cache = new XcacheCache();
        } elseif ($enableCache === 'memcache' && \extension_loaded('memcache')) {
            $memcache = new \Memcache();
            $host = array_get($cacheConfig, 'host', '127.0.0.1');
            $memcache->connect($host);
            $cache = new MemcacheCache();
            $cache->setMemcache($memcache);
        } elseif ($enableCache === 'redis' && \extension_loaded('redis')) {
            $redis = new \Redis();
            $host = array_get($cacheConfig, 'host', '127.0.0.1');
            $redis->connect($host);
            $cache = new RedisCache();
            $cache->setRedis($redis);
        } else {
            $cache = new ArrayCache();
        }

        return $cache;
    }
}
