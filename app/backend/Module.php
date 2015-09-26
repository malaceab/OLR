<?php

namespace app\backend;

    use Phalcon\Loader;
    use Phalcon\Mvc\Dispatcher;
    use Phalcon\Mvc\Url as UrlResolver;
    use Phalcon\Mvc\View;
    use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
    use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
    use Phalcon\Events\Manager;
    use Phalcon\Db\Adapter\Pdo\Mysql as MysqlAdapter;
    use Phalcon\Session\Adapter\Files as SessionAdapter;

class Module
{
    public function config()
    {
        defined('APP_PATH') || define('APP_PATH', realpath('.'));

        return new \Phalcon\Config(array(
            'database' => require(APP_PATH . '/common/config/database.php'),
            'application' => array(
                'controllersDir' => APP_PATH . '/backend/controllers/',
                'modelsDir'      => APP_PATH . '/backend/models/',
                'migrationsDir'  => APP_PATH . '/backend/migrations/',
                'viewsDir'       => APP_PATH . '/backend/views/',
                'pluginsDir'     => APP_PATH . '/backend/plugins/',
                'libraryDir'     => APP_PATH . '/backend/library/',
                'cacheDir'       => APP_PATH . '/backend/cache/',
                'baseUri'        => '/manager.io/',
            )
        ));
    }

    /**
     * Register the application namespaces
     */
    public function registerAutoloaders()
    {
        $loader = new Loader();

        $loader->registerNamespaces([
            'app\backend\controllers'  => APP_PATH . '/backend/controllers/',
            'app\backend\models'       => APP_PATH . '/backend/models/',
            'app\backend\forms'        => APP_PATH . '/backend/forms/'
        ]);

        $loader->register();
    }

    /**
     * Register services used by the backend application
     *
     * @param $di
     */
    public function registerServices($di)
    {
        $config = $this->config();

        /**
         * register the dispatcher
         */
        $di->set('dispatcher', function () {
            $dispatcher = new Dispatcher();

            /*$eventManager = new Manager();
            $eventManager->attach('dispatch', new \Acl('backend'));

            $dispatcher->setEventsManager($eventManager);*/
            $dispatcher->setDefaultNamespace('app\backend\controllers');

            return $dispatcher;
        });

        /**
         * The URL component is used to generate all kind of urls in the application
         */
        $di->set('url', function () use ($config) {
            $url = new UrlResolver();
            $url->setBaseUri($config->application->baseUri);

            return $url;
        }, true);

        $di->setShared('view', function () use ($config) {
            $view = new View();

            $view->setViewsDir($config->application->viewsDir);

            $view->registerEngines([
                '.volt' => function ($view, $di) use ($config) {
                    $volt = new VoltEngine($view, $di);

                    $volt->setOptions([
                        'compiledPath' => $config->application->cacheDir,
                        'compiledSeparator' => '_'
                    ]);

                    return $volt;
                },
                '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
            ]);

            return $view;
        });

        /**
         * Database connection is created based in the parameters defined in the configuration file
         */
        $di->set('db', function () use ($config) {
            return new MysqlAdapter($config->database->toArray());
        });

        /**
         * If the configuration specify the use of metadata adapter use it or use memory otherwise
         */
        $di->set('modelsMetadata', function () {
            return new MetaDataAdapter();
        });

        /**
         * Start the session the first time some component request the session service
         */
        $di->setShared('session', function () {
            $session = new SessionAdapter();
            $session->start();

            return $session;
        });
    }
}