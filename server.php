<?php 

echo 'ok';

/*

try {
	
	$socket = socket_create_listen (8080);
	if ($socket === false) { throw new Exception('create error: '.socket_strerror(socket_last_error ())); }

	socket_set_nonblock($socket);
	$observables = array();

	$handlers = array();
	$handlers['handshake'] = function ($handshake) 
	{	
		//$observables[$handshake->listenerToken] = true; 
	};

	function checkNewObservable($socket, & $observables)
	{
		$observable = socket_accept($socket);

		if ($observable !== false)
		{
			$observables[] = $observable;
		}

	}

	while (true)
	{	
		checkNewObservable($socket, $observables);

		foreach ($observables as $observable)
		{
			$data = socket_read($observable, 1024, PHP_NORMAL_READ);
			if ($data === false) { throw new Exception('read error: '.socket_strerror(socket_last_error ())); }

			if (!empty($data))
			{
				echo $data;
			}


//			if (socket_write($observable, $data) === false) 
//			{ 
//				throw new Exception('write error: '.socket_strerror(socket_last_error ())); 					
//			}
		}	
	}
	
} catch(Exception $ex)
{
	echo 'exception: '.$ex->getMessage();
}

socket_close($socket);