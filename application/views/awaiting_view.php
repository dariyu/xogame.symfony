<?php $this->load->view('ajax_update_script_view', array('update_url' => $urls['update_url'])); ?>
<?php $this->load->view('ajax_form_script_view');?>

	<div class="row">		
		<div class="col-lg-4 col-lg-offset-4">					
			<div class="panel panel-default">
				<div class="panel-heading"><h1 class="h1"><?php echo $this->lang->line('invite_accept_awaiting_header');?></h1></div>
				<div class="panel-body">
					<?php echo $message;?>
				</div>
				<div class="panel-footer">
					<a class="btn btn-primary" href="<?php echo $urls['invite_cancel_url']; ?>">
							<?php echo $this->lang->line('cancel');?>
					</a>						
					
				</div>
			</div>			
		</div>
	</div>


