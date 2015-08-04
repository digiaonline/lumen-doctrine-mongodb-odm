<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB;

use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;

class DoctrineServiceProvider extends ServiceProvider
{
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
     * @param Container $container
     * @param ConfigRepository $config
     * @return DocumentManager
     */
    protected function registerContainerBindings(Container $container, ConfigRepository $config)
    {

        $container->singleton('Nord\Lumen\Doctrine\ODM\MongoDB\DocumentManager', function () use ($config) {
            try {
                return new LumenDocumentManager($config);
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }

        });

//        $container->alias('Doctrine\ODM\MongoDB\DocumentManager', 'Doctrine\ODM\MongoDB\DocumentManagerInterface');
    }


    /**
     * Registers facades.
     */
    protected function registerFacades()
    {
        class_alias('Nord\Lumen\Doctrine\ODM\MongoDB\Facades\DocumentManager', 'DocumentManager');
    }


    /**
     * Registers console commands.
     */
    protected function registerCommands()
    {
        $this->commands([
//            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Schema\UpdateCommand',
            'Nord\Lumen\Doctrine\ODM\MongoDB\Console\Schema\UpdateCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand',
//            'Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand',
        ]);
    }

}
