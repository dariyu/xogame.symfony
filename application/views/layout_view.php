<?php
	
	$this->load->helper('url');
	
?>
<!doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo base_url('assets/css/bootstrap.css');?>"/>
		<script src="<?php echo base_url('assets/js/jquery.js');?>"></script>
		<script src="<?php echo base_url('assets/js/bootstrap.js');?>"></script>
		<script src="<?php echo base_url('assets/js/jquery.form.js');?>"></script>
		<title><?php echo $title;?></title>
	</head>
	<body style="padding: 50px 0;">
	
		<div class="container">
			
			<?php $this->load->view('navbar_view', array('login' => $login)); ?>
			
			<div id="content">
				<?php if (isset($error)): ?>
				<div class="alert alert-danger" role="alert"><strong>Error:</strong> <?php echo $error?></div>	
				<?php endif; ?>
				<?php if (isset($info)): ?>
				<div class="alert alert-info" role="alert"><?php echo $info?></div>	
				<?php endif; ?>

				<?php echo $content; ?>

				<?php 
					if ($this->config->item('backtrace') === true)
					{
						echo '<pre>'.print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true).'</pre>';

					}
				?>	
			</div>
		</div>	
	</body>
</html>



