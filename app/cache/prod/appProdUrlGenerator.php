<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * appProdUrlGenerator
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appProdUrlGenerator extends Symfony\Component\Routing\Generator\UrlGenerator
{
    private static $declaredRoutes = array(
        'start' => array (  0 =>   array (  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::indexAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'main' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::mainAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'signin' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::signinAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/signin',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'signup' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::signupAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/signup',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'invite' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::inviteAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/invite',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'cancel' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::cancelAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/cancel',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'accept' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::acceptAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/accept',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'decline' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::declineAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/decline',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'keepalive' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::keepaliveAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/keepalive',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'makemove' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::makemoveAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/makemove',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'leave' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::leaveAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/leave',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'replay' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::replayAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/replay',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'accept_replay' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::acceptReplayAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/accept_replay',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'signout' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::signoutAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/signout',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'quit_lobby' => array (  0 =>   array (    0 => 'locale',  ),  1 =>   array (    '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::quitLobbyAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/quit_lobby',    ),    1 =>     array (      0 => 'variable',      1 => '/',      2 => '[^/]++',      3 => 'locale',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
    );

    /**
     * Constructor.
     */
    public function __construct(RequestContext $context, LoggerInterface $logger = null)
    {
        $this->context = $context;
        $this->logger = $logger;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (!isset(self::$declaredRoutes[$name])) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }

        list($variables, $defaults, $requirements, $tokens, $hostTokens, $requiredSchemes) = self::$declaredRoutes[$name];

        return $this->doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }
}
