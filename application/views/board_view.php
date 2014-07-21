<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

	$k = 0;	
?>

	<div class="row">		
		<div class="col-lg-4 col-lg-offset-4">					
			<div class="panel panel-default">
				<div class="panel-heading">
					<h1 class="h1">
					<?php 						
						echo $this->lang->line('board_panel_header'); ?>
					</h1>
				</div>
				<div class="panel-body">					
					<div class="center-block">
					<table class="table-bordered">
						<?php for ($y = 0; $y < 3; ++ $y):?>
						<tr>
							<?php for ($x = 0; $x < 3; ++ $x):?>
							<td style="width:50px; height: 50px; font-size: 30px;" class="text-center">
								<?php 
								if (isset($state[$k]))
								{
									if ($state[$k] == 'o') { echo '<span class="glyphicon glyphicon-ok-circle"></span>'; }
									elseif ($state[$k] == 'x') { echo '<span class="glyphicon glyphicon-remove"></span>'; }
									
								} else {
									
									if ($can_move)
									{									
										echo '<a style="display: block; height: 100%;" href="'.$make_move_url.'?cell='.$k.'&inviter='.$room->inviter_login.'"></a>'; 
									}									
								}
								?>
							</td>
							<?php ++$k; endfor;?>
						</tr>
						<?php endfor?>
					</table>
					</div>
				</div>
				
				<div class="panel-footer">
					<a href="<?php echo $leave_url;?>" class="btn btn-primary"><?php echo $this->lang->line('board_leave');?></a>
					<?php if (isset($show_replay_button) && $show_replay_button === true):?>
					<a href="<?php echo $replay_url;?>" class="btn btn-default"><?php echo $this->lang->line('board_replay');?></a>
					<?php endif; ?>
				</div>
				
			</div>			
		</div>
	</div>

