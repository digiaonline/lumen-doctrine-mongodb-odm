<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Nord\Lumen\Doctrine\ODM\MongoDB\Tools\Setup;

class DoctrineServiceProvider extends ServiceProvider
{

    const METADATA_ANNOTATIONS = 'annotations';
    const METADATA_XML         = 'xml';
    const METADATA_YAML        = 'yaml';
    const HYDRATOR_NAMESPACE   = 'Hydrators';
    const DEFAULT_MONGODB_PORT = 27017;

    private $documentManager = null;


    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->registerContainerBindings($this->app, $this->app['config']);
        $this->registerFacades();
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
            return $this->createDocumentManager($config);
        });
    }


    /**
     * Registers facades.
     */
    protected function registerFacades()
    {
        //class_alias('Nord\Lumen\Doctrine\ODM\MongoDB\Facades\DocumentManager', 'DocumentManager');
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
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createDocumentManager(ConfigRepository $config)
    {
        if (!isset($config['doctrine-odm'])) {
            throw new Exception('Doctrine ODM configuration not registered.');
        }

        if (!isset($config['mongodb'])) {
            throw new Exception('Database configuration not registered.');
        }

        $doctrineConfig = $config['doctrine-odm'];
        $databaseConfig = $config['mongodb'];

        $connectionConfig = $this->createConnectionConfig($doctrineConfig, $databaseConfig);

        $type              = array_get($doctrineConfig, 'mapping', self::METADATA_ANNOTATIONS);
        $paths             = array_get($doctrineConfig, 'paths', [base_path('app/Entities')]); // what??
        $debug             = $config['app.debug'];
        $proxyDir          = array_get($doctrineConfig, 'proxy.directory');
        $simpleAnnotations = array_get($doctrineConfig, 'simple_annotations', false);

        $metadataConfiguration = $this->createMetadataConfiguration($type, $paths, $debug, $proxyDir, null,
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
     * Creates the Doctrine connection configuration.
     *
     * @param array $doctrineConfig
     * @param array $databaseConfig
     *
     * @return array
     * @throws Exception
     */
    protected function createConnectionConfig(array $doctrineConfig, array $databaseConfig)
    {
        $connectionName   = array_get($doctrineConfig, 'connection', $databaseConfig['default']);
        $connectionConfig = array_get($databaseConfig['connections'], $connectionName);

        if ($connectionConfig === null) {
            throw new Exception("Configuration for connection '$connectionName' not found.");
        }

        return $this->normalizeConnectionConfig($connectionConfig);
    }


    /**
     * Normalizes the connection config to a format Doctrine can use.
     *
     * @param array $config
     *
     * @return array
     * @throws \Exception
     */
    protected function normalizeConnectionConfig(array $config)
    {
        $driverMap = [
            'mongodb' => 'phpmongo',
        ];

        if (!isset($driverMap[$config['driver']])) {
            throw new Exception("Driver '{$config['driver']}' is not supported.");
        }

        return [
            'driver'   => $driverMap[$config['driver']],
            'host'     => $config['host'],
            'port'   => !empty($config['port']) ? $config['port'] : self::DEFAULT_MONGODB_PORT,
            'dbname'   => $config['database'],
            'user'     => $config['username'],
            'password' => $config['password'],
            'charset'  => $config['charset'],
            'prefix'   => array_get($config, 'prefix'),
        ];
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

        if (isset($doctrineConfig['repository'])) {
            $configuration->setDefaultRepositoryClassName($doctrineConfig['repository']);
        }

        if (isset($doctrineConfig['hydrator'])) {
            if (isset($doctrineConfig['hydrator']['directory'])) {
                $configuration->setHydratorDir($doctrineConfig['hydrator']['directory']);
            }
            if (isset($doctrineConfig['hydrator']['namespace'])) {
                $configuration->setHydratorNamespace($doctrineConfig['hydrator']['namespace'] ? $doctrineConfig['hydrator']['namespace'] : self::HYDRATOR_NAMESPACE);
            }
        }

        $configuration->setDefaultDB($databaseConfig['connections']['mongodb']['database']);
//        $configuration->setDefaultRepositoryClassName('gamerefinery');

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
                if (!array_get($filter, 'enabled', false)) {
                    continue;
                }

                $documentManager->getFilterCollection()->enable($name);
            }
        }

//        if (isset($doctrineConfig['types'])) {
//            $connection = $documentManager->getConnection();
//            if ($databasePlatform = $connection->getDatabasePlatform()) {
//
//            }
//
//            foreach ($doctrineConfig['types'] as $name => $className) {
//                Type::addType($name, $className);
//                $databasePlatform->registerDoctrineTypeMapping('db_' . $name, $name);
//            }
//        }
    }
}
