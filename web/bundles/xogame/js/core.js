	var fadeMessage = function ($element)
	{
		$element.fadeOut(10000, function () { $(this).addClass('hidden'); });		
/*		var fadeTimerId = setTimeout(function () { 
			
			clearTimeout(fadeTimerId);

		}, 3000);*/
	};

	var showMessage = function ($element, body)
	{
		$element.append(body);
		$('#messages').prepend($element);
		$element.removeClass('hidden');	
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
	
	var errorMsg = function(xhr, status, error) 
	{ 
		//console.log(xhr, status, error); 
		handleMessages(xhr.responseJSON.messages); 
	};	

	var handleMessages = function (messages) 
	{	
		//console.log(messages);		
		//fadeMessage($('#messages > div:not(.hidden)'));
		
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

	var send = function(url, loader, onSuccess, onError) {

		var hasLoader = (typeof loader !== 'undefined') ? loader : true;
		if (hasLoader) loaderIn();

		$.ajax(url, {

			dataType: 'json',
			success: function(data) { 
				handleMessages(data.messages); 
				if (typeof data.response !== 'undefined') handleNotify(data.response);
				if (typeof onSuccess !== 'undefined') onSuccess(data.response);
			},
			complete: function (data) { if (hasLoader) loaderOut(); },
			error: function (xhr, status, error) {				
				errorMsg(xhr, status, error); 
				if (typeof onError !== 'undefined') onError(); 
			}
		});

	};
	
	var getContent = function (url, loader) {			

		var hasLoader = (typeof loader !== 'undefined') ? loader : true;
		if (hasLoader) loaderIn();
	
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
			complete: function (data) { if (hasLoader) loaderOut(); },
			error: function (xhr, status, error) {				
				errorMsg(xhr, status, error);
			}
			
		});			
	};
	
	$(function (){
		
		fadeMessage($('#messages > div'));
		
			
	});

