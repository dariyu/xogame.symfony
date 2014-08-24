<?php

	$pre = '/bundles/xogame/';
?>
<!doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo ($pre.'css/bootstrap.css');?>"/>
		<script src="<?php echo ($pre.'js/jquery.js');?>"></script>
		<script src="<?php echo ($pre.'js/bootstrap.js');?>"></script>
		<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/hydna/1.0.0/hydna.js"></script>		
		<script src="<?php echo ($pre.'js/core.js');?>"></script>		
		<title>Xo Game</title>
	</head>
	<body style="padding: 50px 0;">		
		
		<div id="loader" class="" style="display: none; position: fixed; z-index: 10000; width: 100%; height: 100%; top: 0; left: 0; 
			 opacity: 0.8; background: #000 url('../<?php echo $pre.'css/loader.gif'?>') no-repeat center center">		
		</div>		

		
	<div class="container">	

			
			<?php echo $navbar; ?>	

			<div class="alert alert-danger hidden" role="alert">
				<span class="glyphicon glyphicon-exclamation-sign"></span> 				
			</div>	
			<div class="alert alert-info hidden" role="alert">
				<span class="glyphicon glyphicon-info-sign"></span> 					
			</div>			
			
			<div class="row">
				<div id="content">
					<?php echo $content; ?>
				</div>
			</div>

			<div id="messages">
				<?php echo $messages; ?>			
			</div>
		</div>

					
	</body>
</html>
