<?php

/*
require dirname(__FILE__ ). '/Ratchet/MessageComponentInterface.php';
require dirname(__FILE__ ). '/ratchet/http/httpserverinterface.php';


require dirname(__FILE__ ). '/ratchet/server/ioserver.php';
require dirname(__FILE__ ). '/ratchet/websocket/wsserver.php';

require dirname(__FILE__ ). '/ratchet/connectioninterface.php';
*/

//require dirname(__FILE__ ).'/classloader.php';



require dirname(__DIR__) . '/codeigniter/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;


/*
$loader = new \Composer\Autoload\ClassLoader();

//register classes with namespaces
$loader->add('Ratchet', __DIR__.'/src');
$loader->add('React', __DIR__.'/src');

// activate the autoloader
$loader->register();
*/


class Serv implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
    }

    public function onMessage(ConnectionInterface $from, $msg) {
		
		error_log('ratchet message');	
    }

    public function onClose(ConnectionInterface $conn) {
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
    }
}

$server = IoServer::factory(new HttpServer(new WsServer(new Serv())), 8080);

$server->run();