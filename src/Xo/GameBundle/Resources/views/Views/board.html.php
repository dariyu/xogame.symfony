<?php

function RenderBoard($login, \Xo\GameBundle\Abstraction\ILanguage $lang, $board, $can_move, $can_replay, 
		$token, $make_move_url, $leave_url, $replay_url, $accept_replay_url, $main_url, $quit_board_url) {
	
	$k = 0;
	$replay_btn_class = ($can_replay === true) ? '' : 'hidden';
	
	$zero = '<span class="glyphicon glyphicon-ok-circle"></span>';
	$cross = '<span class="glyphicon glyphicon-remove"></span>';
	
	function printToken($token, $zero, $cross)
	{
		if ($token === 'o')
		{
			echo $zero;

		} else
		{
			echo $cross;
		}
	}
	
	?>
<script type="text/javascript">

	var move = {
		
		make: function (cell) {

			this.cell = cell;
			$('#cell-'+this.cell).html('<?php printToken($token, $zero, $cross)?>');
			$('.make-move').hide();			
		},
				
		cancel: function () {
		
			console.log('move not accepted');
			$('#cell-'+this.cell).html('');
			$('.make-move').show();
		}
		
	};
	
	var stateHandler = function (data)
	{
		console.log(data);		
	
		//has move
		if (data.cell !== 'undefined')
		{
			$('#cell-'+data.cell).html(data.cellToken === 'o' ? '<?php echo $zero;?>' : '<?php echo $cross;?>');
		}
	
		
		if (data.state.canReplay) { $('#replay-btn').removeClass('hidden'); }
		if (data.state.canMove) { $('.make-move').show(); } else { $('.make-move').hide(); }
	};	

	//notifies	
	handlers = {
		
		your_move: function (data) {
			stateHandler(data);
			showInfoMessage('<?php echo $lang->BoardYourMove(); ?>');
		},
				
		rivals_move: function (data) {
			
			
			stateHandler(data);
			showInfoMessage('<?php echo $lang->BoardRivalsMove(); ?>');
		},
				
		win: function (data) {
			stateHandler(data);
			showInfoMessage('<?php echo $lang->BoardWin()?>');
		},
				
		loss: function (data) {			
			stateHandler(data);
			showInfoMessage('<?php echo $lang->BoardLoss(); ?>');			
		},
				
		draw: function (data) {
			stateHandler(data);
			showInfoMessage('<?php echo $lang->BoardDraw(); ?>');
		},
				
		replay: function (data) {
			
			$('#replay-modal').modal();	
		},
				
		accept_replay: function (data)
		{
			$('#accept-modal').modal('hide');
			getContent('<?php echo $main_url?>');
		},
				
		leave_game: function (data)
		{
			$('#accept-modal').modal('hide');
			$('.make-move').hide();
			$('#replay-btn').hide();
			
			showInfoMessage('<?php echo $lang->BoardLeft();?>');
		}
		
	};

	//events
	$(function () {
	
//		$('#replay-modal').on('hidden-bs-modal', function () {
//			getContent('<?php echo $leave_url ?>');
//		});
		
		$('#leave-btn, #modal-leave-btn').click(function (e) {
			e.preventDefault();
			$('#replay-modal').data('prevent-leave', true).modal('hide');
			getContent('<?php echo $leave_url ?>');
		});
		
		$('#replay-modal').on('hide.bs.modal', function (e) {
			
			if ($(this).data('prevent-leave') !== true)
			{
				getContent('<?php echo $leave_url ?>');
			}
			
			$(this).data('prevent-leave', false);
		});
		
		$('#replay-btn').click(function (e) {			
			e.preventDefault();
			$('#accept-modal').modal();
			send($(this).attr('href'), handlers);			
		});
		
		$('#modal-accept-btn').click(function (e) {			
			e.preventDefault();
			$('#replay-modal').data('prevent-leave', true).modal('hide');
			getContent('<?php echo $accept_replay_url; ?>');
		});
		
		<?php if (!$can_move): ?>
			$('.make-move').hide();		
		<?php endif; ?>
			
		$('.make-move').click(function(e) 
		{
			e.preventDefault();
			move.make($(this).data('cell'));		
			send($(this).attr('href'), handlers, false, function (response) {
				
				//console.log('cell', move.cell, response.body.cell);				
				if (move.cell !== response.body.cell) 
				{
					move.cancel();
				} 
				
			}, function () { move.cancel(); console.log('move error'); });			
		});
		
		$(window).off("beforeunload").on("beforeunload", function(evt) {
			
			loaderIn();
			$.ajax('<?php echo $quit_board_url?>', {
				
				async: false,
				success: function () { loaderOut(); },
				complete: function () {  }
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

<div class="modal fade" id="accept-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
	  <div class="modal-content">
		<div class="modal-header">
		  <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		  <h4 class="modal-title" id="myModalLabel"><?php echo $lang->BoardReplayAcceptModalHeader()?></h4>
		</div>
		<div class="modal-body">
			<?php echo $lang->BoardReplayAcceptModalBody()?>
		</div>
	  </div>
	</div>
</div>


<div class="col-lg-4 col-lg-offset-4">			
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
							printToken($board[$k], $zero, $cross);

						} else {

							echo '<a data-cell="'.$k.'" class="make-move" style="display: block; height: 100%;" href="'.
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
<?php }

RenderBoard($login, $lang, $board, $can_move, $can_replay, $token, 
		$make_move_url, $leave_url, $replay_url, $accept_replay_url, $main_url, $quit_board_url);
echo $view->render('XoGameBundle:Views:scripts.html.php', array('login' => $login, 'lang' => $lang));