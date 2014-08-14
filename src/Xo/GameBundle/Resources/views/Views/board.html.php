<?php

function RenderBoard($login, \Xo\GameBundle\Abstraction\ILanguage $lang, $board, $can_move, $can_replay, 
		$token, $make_move_url, $leave_url, $replay_url, $accept_replay_url, $main_url) {
	
	$k = 0;
	$replay_btn_class = ($can_replay === true) ? '' : 'hidden';
	
	function printToken($token)
	{
		if ($token === 'o')
		{
			echo '<span class="glyphicon glyphicon-ok-circle"></span>';

		} else
		{
			echo '<span class="glyphicon glyphicon-remove"></span>';
		}
	}
	
	?>
<script type="text/javascript">

	//notifies	
	var handlers = {
		
		move: function (data) {
			
			console.log(data.cell);
			
			$('.make-move').hide();
			$('#cell-'+data.cell).html('<?php printToken($token)?>');			
			if (data.state.canReplay) $('#replay-btn').removeClass('hidden');
		},
				
		rivals_move: function (data) {
			
			if (data.canMove === true) $('.make-move').show();
			
			$('#cell-'+data.cell).html('<?php $token === 'x' ? printToken('o') : printToken('x'); ?>');			
			if (data.canReplay) $('#replay-btn').removeClass('hidden');
		},
				
		replay: function (data) {
			
			$('#replay-modal').modal();			
		},
				
		accept_replay: function (data)
		{
			getContent('<?php echo $main_url?>');
		}
		
	};

	//events
	$(function () {
	
//		$('#replay-modal').on('hidden-bs-modal', function () {
//			getContent('<?php echo $leave_url ?>');
//		});
		
		$('#leave-btn, #modal-leave-btn').click(function (e) {
			e.preventDefault();
			$('#replay-modal').modal('hide');
			getContent('<?php echo $leave_url ?>');
		});
		
		$('#replay-btn').click(function (e) {			
			e.preventDefault();
			send($(this).attr('href'));			
		});
		
		$('#modal-accept-btn').click(function (e) {			
			e.preventDefault();
			$('#replay-modal').modal('hide');
			getContent('<?php echo $accept_replay_url; ?>');
		});
		
		<?php if (!$can_move): ?>
			$('.make-move').hide();		
		<?php endif; ?>
			
		$('.make-move').click(function(e) 
		{
			e.preventDefault();
			send($(this).attr('href'));
		});
		
		$(window).bind("beforeunload", function(evt) {
			
			loaderIn();
			$.ajax('<?php echo $leave_url?>', {
				
				async: false,
				complete: function () { loaderOut(); }
			});
			
			return '';			
		});
        		

		
	});

</script>

<div class="modal fade" id="replay-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
	  <div class="modal-content">
		<div class="modal-header">
		  <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		  <h4 class="modal-title" id="myModalLabel"><?php echo $lang->BoardReplayModalHeader()?></h4>
		</div>
		<div class="modal-body">
			<?php echo $lang->BoardReplayModalBody()?>
		</div>
		<div class="modal-footer">
			<a id="modal-accept-btn" class="btn btn-primary" href="<?php echo $accept_replay_url?>"><?php echo $lang->Accept()?></a>
			<a id="modal-leave-btn" class="btn btn-default" href="<?php echo $leave_url?>"><?php echo $lang->BoardLeave()?></a>
		</div>
	  </div>
	</div>
</div>

<div class="col-lg-4 col-lg-offset-4">			
	<div id="content">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h1 class="h1">
				<?php echo $lang->BoardHeader(); ?>
				</h1>
			</div>
			<div class="panel-body">					
				<table class="table-bordered" style="width: 150px; height: 150px; margin: auto;">
					<?php for ($y = 0; $y < 3; ++ $y):?>
					<tr>
						<?php for ($x = 0; $x < 3; ++ $x):?>
						<td id="cell-<?php echo $k?>" style="font-size: 30px; width: 33%; height: 33%;" class="text-center">
							<?php 
							if (isset($board[$k]))
							{
								if ($board[$k] == 'o') { echo '<span class="glyphicon glyphicon-ok-circle"></span>'; }
								elseif ($board[$k] == 'x') { echo '<span class="glyphicon glyphicon-remove"></span>'; }

							} else {

								echo '<a class="make-move" style="display: block; height: 100%;" href="'.
										$make_move_url.'?cell='.$k.'"></a>';
							}
							?>
						</td>
						<?php ++$k; endfor;?>
					</tr>
					<?php endfor?>
				</table>
			</div>

			<div class="panel-footer">
				<a id="leave-btn" href="<?php echo $leave_url;?>" class="btn btn-primary"><?php echo $lang->BoardLeave();?></a>					
				<a id="replay-btn" href="<?php echo $replay_url;?>" class="btn btn-default <?php echo $replay_btn_class?>"><?php echo $lang->BoardReplay();?></a>
			</div>				
		</div>	
	</div>
</div>
<?php } 

RenderBoard($login, $lang, $board, $can_move, $can_replay, $token, 
		$make_move_url, $leave_url, $replay_url, $accept_replay_url, $main_url);
echo $view->render('XoGameBundle:Views:scripts.html.php', array('login' => $login));