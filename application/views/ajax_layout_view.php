<?php
	
	$this->load->helper('url');

	
?>
	
	<?php if (isset($error)): ?>
	<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span> <?php echo $error?></div>	
	<?php endif; ?>
	<?php if (isset($info)): ?>
	<div class="alert alert-info" role="alert"><span class="glyphicon glyphicon-info-sign"></span> <?php echo $info?></div>	
	<?php endif; ?>
		
	<?php echo $content; ?>


<?php 
	if ($this->config->item('backtrace') === true)
	{
		echo '<pre>'.print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true).'</pre>';
		
	}	
