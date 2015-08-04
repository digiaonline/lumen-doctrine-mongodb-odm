<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Illuminate\Config\Repository as ConfigRepository;
use Nord\Lumen\Doctrine\ODM\MongoDB\Tools\Setup;

class LumenDocumentManager implements DocumentManagerInterface
{
    const METADATA_ANNOTATIONS = 'annotations';
    const METADATA_XML = 'xml';
    const METADATA_YAML = 'yaml';
    const HYDRATOR_NAMESPACE = 'Hydrators';

    /**
     * @var null
     */
    private $documentManager = null;


    public function __construct(ConfigRepository $config)
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

        $type = array_get($doctrineConfig, 'mapping', self::METADATA_ANNOTATIONS);
        $paths = array_get($doctrineConfig, 'paths', [base_path('app/Entities')]);
        $debug = $config['app.debug'];
        $proxyDir = array_get($doctrineConfig, 'proxy.directory');
        $simpleAnnotations = array_get($doctrineConfig, 'simple_annotations', false);

        $metadataConfiguration = $this->createMetadataConfiguration($type, $paths, $debug, $proxyDir, null,
            $simpleAnnotations);

        $this->configureMetadataConfiguration($metadataConfiguration, $doctrineConfig);

        $eventManager = new EventManager;

        $this->configureEventManager($doctrineConfig, $eventManager);

        $documentManager = DocumentManager::create(new Connection(), $metadataConfiguration, $eventManager);

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
        $connectionName = array_get($doctrineConfig, 'connection', $databaseConfig['default']);
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
            'mongodb' => 'phpmongo'
        ];

        if (!isset($driverMap[$config['driver']])) {
            throw new Exception("Driver '{$config['driver']}' is not supported.");
        }

        return [
            'driver' => $driverMap[$config['driver']],
            'host' => $config['host'],
            'dbname' => $config['database'],
            'user' => $config['username'],
            'password' => $config['password'],
            'charset' => $config['charset'],
            'prefix' => array_get($config, 'prefix'),
        ];
    }


    /**
     * Creates the metadata configuration instance.
     *
     * @param string $type
     * @param array $paths
     * @param bool $isDevMode
     * @param string $proxyDir
     * @param Cache $cache
     * @param bool $useSimpleAnnotationReader
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

                return Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache);
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
     * @param array $doctrineConfig
     *
     * @throws Exception
     */
    protected function configureMetadataConfiguration(Configuration $configuration, array $doctrineConfig)
    {
        if (isset($doctrineConfig['filters'])) {
            foreach ($doctrineConfig['filters'] as $name => $filter) {
                $configuration->addFilter($name, $filter['class']);
            }
        }
//        if (isset($doctrineConfig['logger'])) {
//            $configuration->setSQLLogger($doctrineConfig['logger']);
//        }

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

    }


    /**
     * Configures the Doctrine event manager instance.
     *
     * @param array $doctrineConfig
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
     * @param array $doctrineConfig
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


    /**
     * @return mixed
     */
    public function getDocumentManager()
    {
        // TODO: Implement getDocumentManager() method.
    }

    /**
     * @inheritdoc
     */
    public function persist($object)
    {
        // TODO: Implement persist() method.
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }

    /**
     * Finds an object by its identifier.
     *
     * This is just a convenient shortcut for getRepository($className)->find($id).
     *
     * @param string $className The class name of the object to find.
     * @param mixed $id The identity of the object to find.
     *
     * @return object The found object.
     */
    public function find($className, $id)
    {
        // TODO: Implement find() method.
    }

    /**
     * Removes an object instance.
     *
     * A removed object will be removed from the database as a result of the flush operation.
     *
     * @param object $object The object instance to remove.
     *
     * @return void
     */
    public function remove($object)
    {
        // TODO: Implement remove() method.
    }

    /**
     * Merges the state of a detached object into the persistence context
     * of this ObjectManager and returns the managed copy of the object.
     * The object passed to merge will not become associated/managed with this ObjectManager.
     *
     * @param object $object
     *
     * @return object
     */
    public function merge($object)
    {
        // TODO: Implement merge() method.
    }

    /**
     * Clears the ObjectManager. All objects that are currently managed
     * by this ObjectManager become detached.
     *
     * @param string|null $objectName if given, only objects of this type will get detached.
     *
     * @return void
     */
    public function clear($objectName = null)
    {
        // TODO: Implement clear() method.
    }

    /**
     * Detaches an object from the ObjectManager, causing a managed object to
     * become detached. Unflushed changes made to the object if any
     * (including removal of the object), will not be synchronized to the database.
     * Objects which previously referenced the detached object will continue to
     * reference it.
     *
     * @param object $object The object to detach.
     *
     * @return void
     */
    public function detach($object)
    {
        // TODO: Implement detach() method.
    }

    /**
     * Refreshes the persistent state of an object from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $object The object to refresh.
     *
     * @return void
     */
    public function refresh($object)
    {
        // TODO: Implement refresh() method.
    }

    /**
     * Gets the repository for a class.
     *
     * @param string $className
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($className)
    {
        // TODO: Implement getRepository() method.
    }

    /**
     * Returns the ClassMetadata descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)).
     *
     * @param string $className
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        // TODO: Implement getClassMetadata() method.
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        // TODO: Implement getMetadataFactory() method.
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * This method is a no-op for other objects.
     *
     * @param object $obj
     *
     * @return void
     */
    public function initializeObject($obj)
    {
        // TODO: Implement initializeObject() method.
    }

    /**
     * Checks if the object is part of the current UnitOfWork and therefore managed.
     *
     * @param object $object
     *
     * @return bool
     */
    public function contains($object)
    {
        // TODO: Implement contains() method.
    }
}
