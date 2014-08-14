<?php

namespace Xo\GameBundle\Entity;

class RoomState {
	
	public $board = null;
	public $canMove = null;
	public $canReplay = null;
	public $message = null;
	public $token = null;
	
	public function __construct($board, $canMove, $canReplay, $token, $message = null)
	{
		$this->board = $board;
		$this->canMove = $canMove;
		$this->canReplay = $canReplay;
		$this->token = $token;
		$this->message = $message;
	}
	
}
		

class Room {
	
	private $inviter_login = null;
	private $invitee_login = null;
	private $state = null;
	private $board = array();
	
	public function __construct($inviter, $invitee, $state) {
		
		$this->inviter_login = $inviter;
		$this->invitee_login = $invitee;
		$this->state = $state;	
	}
	
	public function __get($name)
	{
		return $this->$name;
	}

	public function __set($name, $value)
	{
		$this->$name = $value;
	}
	
	private function CheckCombo($combo, $token)
	{
		$result = true;
		
		foreach ($combo as $cell)
		{
			if (!isset($this->board[$cell]) || $this->board[$cell] !== $token) { $result = false; break; }
		}
		
		return $result;
	}
	
	private function GetStateAsInviter($isGameover, $isEven, \Xo\GameBundle\Abstraction\ILanguage $lang)
	{
		$token = 'o';
		return $this->CalcState($isGameover, !$isEven, $lang, $token);
	}
	
	private function CalcState($isGameover, $isEven, \Xo\GameBundle\Abstraction\ILanguage $lang, $token)
	{
		if ($isEven)
		{			
			if ($isGameover)
			{
				return new RoomState($this->board, false, true, $token, $lang->BoardLoss());
			}
			else
			{
				return new RoomState($this->board, true, false, $token, $lang->BoardYourMove());
			}
		} 
		else
		{
			if ($isGameover)
			{
				return new RoomState($this->board, false, false, $token, $lang->BoardWin());
			}
			else
			{
				return new RoomState($this->board, false, false, $token, $lang->BoardRivalsMove());				
			}			
		}
		
	}
	
	private function GetStateAsInvitee($isGameover, $isEven, \Xo\GameBundle\Abstraction\ILanguage $lang)
	{
		$token = 'x';
		return $this->CalcState($isGameover, $isEven, $lang, $token);
	}
	
	public function getRoomState($login, \Xo\GameBundle\Abstraction\ILanguage $lang)
	{		
		
		if (count($this->board) >= 9)
		{			
			return new RoomState($this->board, false, true, $this->invitee_login === $login ? 'x' : 'o', $lang->BoardDraw()); 
		}
		
		$combos = array(array(0, 1, 2), array(3, 4, 5), array(6, 7, 8), 
						array(0, 3, 6), array(1, 4, 7), array(2, 5, 8),
						array(0, 4, 8), array(2, 4, 6));	
		
		$isEven = count($this->board) % 2 == 0;
		
		$checkToken = $isEven ? 'o' : 'x';		
			
		$isGameover = false;
		foreach ($combos as $combo)
		{
			if ($this->CheckCombo($combo, $checkToken)) { $isGameover = true; break; }
		}
		
		return $this->invitee_login === $login ?
				$this->GetStateAsInvitee($isGameover, $isEven, $lang) : $this->GetStateAsInviter($isGameover, $isEven, $lang);
		
	}

}
