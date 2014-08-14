<?php $this->load->view('ajax_update_script_view', array('update_url' => $urls['update_url'])); ?>
<?php $this->load->view('ajax_form_script_view');?>

<div class="row">		
		<div class="col-lg-4 col-lg-offset-4">					
			<div class="panel panel-default">
				<div class="panel-heading">
					<h1 class="h1">
						<?php echo $this->lang->line('lobby_players_list')?>
						<span class="badge" style="vertical-align: top;"><?php echo count($players);?></span
					</h1>					
				</div>
				<div class="panel-body">
					
						<div class="list-group">
						<?php foreach ($players as $player):?>							
							<div class="list-group-item">
								
									
									<a href="<?php echo ($urls['invite_url'].'?invitee='.$player->login); ?>" class="btn btn-primary pull-right">
									 <?php echo $this->lang->line('invite');?>
									</a>
								
								<div class="h5">
								<span class="glyphicon glyphicon-user"></span> <?php echo $player->login;?>
								</div>
								
								<div class="clearfix"></div>
							</div>
						<?php endforeach; ?>
						</div>				  
					
				</div>
			</div>			
		</div>
</div>