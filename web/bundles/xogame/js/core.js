	var errorMsg = function(xhr, status, error) { console.log(error); };			

	var fadeMessage = function ($element)
	{
		var fadeTimerId = setTimeout(function () { 

			$element.fadeOut(10000, function () { });
			clearTimeout(fadeTimerId);

		}, 3000);
	};

	var showMessage = function ($element, body)
	{
		$element.append(body);
		$element.appendTo('#messages');
		$element.removeClass('hidden');

		fadeMessage($element);
	};

	var showInfoMessage = function (body)
	{
		var $newDanger = $('.container .alert-info.hidden').clone();
		showMessage($newDanger, body);
	};

	var showErrorMessage = function (body)
	{
		var $newDanger = $('.container .alert-danger.hidden').clone();
		showMessage($newDanger, body);
	};

	var handleMessages = function (messages) 
	{		
		for (id in messages)
		{			
			var message = messages[id];

			switch (message.type)
			{
				case 'error':
					showErrorMessage(message.body);
					break;

				case 'info':
					showInfoMessage(message.body);
					break;
			}
		}
	};
	
	var handleNotify = function (obj)
	{
		if (typeof handlers[obj.type] !== 'undefined')
		{
			handlers[obj.type](obj.body);			
		}		
		
		if (typeof obj.messages !== 'undefined')
		{
			handleMessages(obj.messages);
		}
	};
	
	var loaderIn = function ()
	{
		$('#loader').fadeIn();
	};

	var loaderOut = function ()
	{
		$('#loader').fadeOut();
	};

	var send = function(url) {

		loaderIn();

		$.ajax(url, {

			dataType: 'json',
			success: function(data) { 
				handleMessages(data.messages); 
				if (typeof data.response !== 'undefined') handleNotify(data.response);				
			},
			complete: function (data) { loaderOut(); },
			error: errorMsg
		});

	};
	
	var getContent = function (url) {			
		
		loaderIn();
		
		$.ajax(url, {		
			
			dataType: 'json',
			success: function (data) { 
				
				if (typeof data.html !== 'undefined')
				{
					$('#content').html(data.html);
				}
				else console.log('no html repsonse from: '+url);					
					
				handleMessages(data.messages);				
			},	
			complete: function (data) { loaderOut(); }
		});			
	};

	
	$(function (){
		
		fadeMessage($('#messages > div'));
			
	});
