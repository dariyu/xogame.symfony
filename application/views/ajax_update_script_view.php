

<script type="text/javascript">			

	$(function () {

		var $container = $('#content');

		var timerId = setTimeout(function () {

			$.ajax('<?php echo $update_url;?>', {

				data: { ajax: 1 },
				dataType: 'html',
				success: function (data) {  clearTimeout(timerId); $container.html(data); }
				//{  console.log('ajax'); $container.fadeOut(400, function () { $container.html(data); $container.fadeIn(); });  }				
			});

		}, 3000);

	});	
</script>
