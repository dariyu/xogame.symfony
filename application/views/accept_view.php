
	<div class="row">		
		<div class="col-lg-4 col-lg-offset-4">					
			<div class="panel panel-default">
				<div class="panel-heading"><h1 class="h1"><?php echo $this->lang->line('invite_accepting_header');?></h1></div>
				<div class="panel-body">
					<?php echo $message;?>
				</div>
				<div class="panel-footer">
					<form class="form" class="form-horizontal" role="form" method="post" action="<?php echo $accept_url;?>">
						<button class="btn btn-primary" type="submit" value="to_accept" name="action">
							<?php echo $this->lang->line('accept');?>
						</button>
						<button class="btn btn-warning" type="submit" value="to_decline" name="action">
							<?php echo $this->lang->line('decline');?>
						</button>						
						
					</form>	
				</div>				
			</div>			
		</div>
	</div>
