<?php
/**
 * Novalnet payment module
 *
 * This module is used for real time processing of Novalnet transaction of customers.
 *
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: Module.php
 */
 
namespace Lightspeed;

use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Hydrator;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

     /**
     * get the database properties and pass it to the controller
     *
     * @param  none
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                Model\LightspeedTable::class => function($container) {
                    $tableGateway = $container->get(Model\LightspeedTableGateway::class);
                    return new Model\LightspeedTable($tableGateway);
                },
                Model\LightspeedTableGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    return $dbAdapter;
                },
            ],
        ];
    }

     /**
     * Initialise the controller
     *
     * @param  none
     *
     * @return array
     */
    public function getControllerConfig()
    {
        return [
            'factories' => [
                Controller\LightspeedController::class => function($container) {
                    return new Controller\LightspeedController(
                        $container->get(Model\LightspeedTable::class)
                    );
                },
                Controller\BackendController::class => function($container) {
                    return new Controller\BackendController(
                        $container->get(Model\LightspeedTable::class)
                    );
                },
                Controller\CallbackController::class => function($container) {
                    return new Controller\CallbackController(
                        $container->get(Model\LightspeedTable::class)
                    );
                },
            ],
        ];
    }

     /**
     * load the layout
     *
     * @param
     * @param  object $e
     * @return none
     */
    public function onBootstrap($e)
    {
        // Register a dispatch event
        $app = $e->getParam('application');
        $app->getEventManager()->attach('dispatch', array($this, 'setLayout'));
    }

    /**
     * @param  \Zend\Mvc\MvcEvent $e The MvcEvent instance
     * @return void
     */
    public function setLayout($e)
    {
        $matches    = $e->getRouteMatch();
        $controller = $matches->getParam('controller');
        if (false === strpos($controller, 'Lightspeed')) {
            // not a controller from this module
            return;
        }

        // Set the layout template
        $viewModel = $e->getViewModel();
        $viewModel->setTemplate('layout/layout2');
    }
}
