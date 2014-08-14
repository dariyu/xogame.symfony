<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * appProdUrlMatcher
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appProdUrlMatcher extends Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($pathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($pathinfo);
        $context = $this->context;
        $request = $this->request;

        // start
        if (rtrim($pathinfo, '/') === '') {
            if (substr($pathinfo, -1) !== '/') {
                return $this->redirect($pathinfo.'/', 'start');
            }

            return array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::indexAction',  '_route' => 'start',);
        }

        // main
        if (preg_match('#^/(?P<locale>[^/]++)$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'main')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::mainAction',));
        }

        // signin
        if (preg_match('#^/(?P<locale>[^/]++)/signin$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'signin')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::signinAction',));
        }

        // signup
        if (preg_match('#^/(?P<locale>[^/]++)/signup$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'signup')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::signupAction',));
        }

        // invite
        if (preg_match('#^/(?P<locale>[^/]++)/invite$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'invite')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::inviteAction',));
        }

        // cancel
        if (preg_match('#^/(?P<locale>[^/]++)/cancel$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'cancel')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::cancelAction',));
        }

        // accept
        if (preg_match('#^/(?P<locale>[^/]++)/accept$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'accept')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::acceptAction',));
        }

        // decline
        if (preg_match('#^/(?P<locale>[^/]++)/decline$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'decline')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::declineAction',));
        }

        // keepalive
        if (preg_match('#^/(?P<locale>[^/]++)/keepalive$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'keepalive')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::keepaliveAction',));
        }

        // makemove
        if (preg_match('#^/(?P<locale>[^/]++)/makemove$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'makemove')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::makemoveAction',));
        }

        // leave
        if (preg_match('#^/(?P<locale>[^/]++)/leave$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'leave')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::leaveAction',));
        }

        // replay
        if (preg_match('#^/(?P<locale>[^/]++)/replay$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'replay')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::replayAction',));
        }

        // accept_replay
        if (preg_match('#^/(?P<locale>[^/]++)/accept_replay$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'accept_replay')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::acceptReplayAction',));
        }

        // signout
        if (preg_match('#^/(?P<locale>[^/]++)/signout$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'signout')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::signoutAction',));
        }

        // quit_lobby
        if (preg_match('#^/(?P<locale>[^/]++)/quit_lobby$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'quit_lobby')), array (  '_controller' => 'Xo\\GameBundle\\Controller\\FrontController::quitLobbyAction',));
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
