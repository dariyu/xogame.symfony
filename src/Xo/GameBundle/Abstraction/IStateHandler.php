<?php

namespace Xo\GameBundle\Abstraction;

 interface IStateHandler {

	public function HandleSignin();
	public function HandleLobby();
	public function HandleInvited($inviter);
	public function HandleAwaiting($invitee);

	public function HandleLeft(\Xo\GameBundle\Entity\RoomState $state);
	public function HandleBoard(\Xo\GameBundle\Entity\RoomState $state);
		 
//	public function SetRoom(& $room);
//	
//	public function HandleError();	
//	public function HandleCanMove();
//	public function HandleWaitMove();
//	public function HandleLoss();
//	public function HandleWin();	
//	public function HandleDraw();
	
	//public function HandleDeclined();
	//public function HandleInvited();

}

