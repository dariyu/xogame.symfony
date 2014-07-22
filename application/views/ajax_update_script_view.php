<script type="text/javascript">			

	$(function () {

		var timerId = setTimeout(function () {			

			$.ajax('<?php echo $update_url;?>', {

				data: { ajax: 1 },
				dataType: 'html',
				success: function (data) {  console.log('clear timeout: ', timerId); clearTimeout(timerId); $('#content').html(data); }
				//{  console.log('ajax'); $container.fadeOut(400, function () { $container.html(data); $container.fadeIn(); });  }				
			});

		}, 3000);
		
		console.log('set timeout: ', timerId);

	});	
</script>