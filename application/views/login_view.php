<?php $this->load->view('ajax_form_script_view');?>

	<div class="container">		
		<div class="row">		
			<div class="col-lg-4 col-lg-offset-4">		

				<div class="panel panel-default">
					<div class="panel-heading"><h1 class="h1"><?php echo $this->lang->line('login_form_header')?></h1></div>
					<div class="panel-body">
						<form class="form" role="form" method="post" action="<?php echo $urls['signin_url'];?>">
							<div class="form-group">
								<input type="text" class="form-control" value="<?php echo $this->input->get_post('login'); ?>" name="login" placeholder="<?php echo $this->lang->line('login_login_placeholder');?>"/>
							</div>
							<div class="form-group">
								<input type="password" class="form-control" name="password" placeholder="<?php echo $this->lang->line('login_password_placeholder');?>"/>
							</div>
							<button class="btn btn-primary" type="submit" value="to_signin" name="action"><?php echo $this->lang->line('login_signin_button_caption');?></button>
							<button class="btn btn-default" type="submit" value="to_register" name="action"><?php echo $this->lang->line('login_register_button_caption');?></button>
						</form>			
					</div>
				</div>
			</div>
		</div>
	</div>
