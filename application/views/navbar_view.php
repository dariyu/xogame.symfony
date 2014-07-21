<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

?>

			<nav class="navbar navbar-default" role="navigation">
				<div class="container-fluid">
					 <?php if (isset($login)):?>
					 <p class="navbar-text">
						 <span class="glyphicon glyphicon-user"></span>
						 <?php echo $login; ?>
					 </p>
					 <?php endif; ?>
				
						   
					<ul class="nav navbar-nav navbar-right">
						
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">Language<span class="caret"></span></a>
							<ul class="dropdown-menu" role="menu">
								<li><a href="/ru">Russian</a></li>
								<li><a href="/en">English</a></li>
							</ul>
						</li>
					</ul>
				</div>		
			</nav>
