<script type="text/javascript">			
	$(function ()
	{
		console.log('ready');
		
			$('form').ajaxForm({

				data: { ajax: 1 },
				success: function(data) { $('#content').html(data); }

			});
			
			$('a').click(function (e) 
			{			
				if ($(this).attr('href') !== '#' && !$(this).hasClass('noajax'))
				{				
					e.preventDefault();

					$.ajax($(this).attr('href'), {

						data: { ajax: 1 },
						dataType: 'html',
						success: function (data) {  $('#content').html(data); }
						//{  console.log('ajax'); $container.fadeOut(400, function () { $container.html(data); $container.fadeIn(); });  }				
					});
				}
			});


	});			
</script>
