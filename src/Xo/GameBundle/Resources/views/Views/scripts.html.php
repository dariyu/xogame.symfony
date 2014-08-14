<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

?>
<script type="text/javascript">		

	if (typeof userChan === 'undefined')
	{

		var handleHydnaNotify = function (event)
		{
			console.log(event.data);

			var obj = JSON.parse(event.data);
			handleNotify(obj);
		};


		// first we open a channel on the domain `public.hydna.net` in read-
		// and write mode.
		var sharedChan = new HydnaChannel('xoapp.hydna.net/shared', 'r');
		var userChan = new HydnaChannel('xoapp.hydna.net/user/<?php echo $login?>', 'r');

		// then register an event handler that alerts the data-part of messages 
		// as they are received.	
		sharedChan.onmessage = handleHydnaNotify;
		userChan.onmessage = handleHydnaNotify;
		
		console.log('hydna');		
	}
	
	
</script>
