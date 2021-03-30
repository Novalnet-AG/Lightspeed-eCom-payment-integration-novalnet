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
 * Script: module.config.php
 */
 
namespace Lightspeed;

use Zend\Router\Http\Segment;


return [

    // mapping url to a lighspeed controller for payment process

    'router' => [
        'routes' => [
            'lightspeed' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/lightspeed/payment_methods',
                    'defaults' => array(
                        'controller' => Controller\LightspeedController::class,
                        'action'     => 'paymentmethods',
                    ),
                    'route' => '/lightspeed[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\LightspeedController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
	
	// mapping url to a backend controller for backend configurations
	
            'backend' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/backend[/:action[/:id]][/:signature][/:lang[/]][/:success[/]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\BackendController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            
    // mapping url to a callback controller for callback process
            
            'callback' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/callback[/:id[/:action]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CallbackController::class,
                        'action'     => 'index',
                    ],
                ],
            ],

        ],
    ],

    // set template for the module

    'view_manager' => [
        'invokables' => [
            'translate' => \Zend\I18n\View\Helper\Translate::class
        ],
        'template_map' => [
            'layout/layout2'           => __DIR__ . '/../view/layout/layout.phtml',
        ],
        'template_path_stack' => [
            'lightspeed' => __DIR__ . '/../view',
        ],
    ],

    // set translator for the module

    'translator' => array(
    'locale' => 'en_US',
    'translation_file_patterns' => array(
        array(
            'type'     => 'phparray',
            'base_dir' => __DIR__ . '/../src/lang',
            'pattern'  => '%s.php',
        ),
    ),
),
];
