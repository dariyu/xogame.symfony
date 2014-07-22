<?php  if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*
class Room {
	
	$inviter_login = null;
	$invitee_login = null;
	$state = null;
	$board = null;
			
	private function AssignResult($result)
	{
		
	}	
}
*/

class Xo_Model extends CI_Model {

	const STATE_PLAYING = 0;	
	const STATE_LEAVED_BY_INVITER = 100;
	const STATE_LEAVED_BY_INVITEE = 200;
	const STATE_INVITED = 300;
	const STATE_DECLINED = 400;
	
	
	//$lobby_key = 'xo_lobby';
	private $updateTime = 30;
	
	function __construct()
	{	
		parent::__construct();
		//$this->cache = new Cache();
	}
	
	public function IsAwaiting($login)
	{	
		$room = $this->GetInviteRoom($login);
		return ($room !== false && $room->inviter_login == $login) ? true : false;
	}
	
	public function IsAccepting($login)
	{
		$room = $this->GetInviteRoom($login);
		return ($room !== false && $room->invitee_login == $login) ? true : false;
	}

	public function Replay($login)
	{
		$room = $this->xo_model->GetPlayRoom($login);
		
		if ($room !== false)
		{			
			$this->db->where('inviter_login', $room->inviter_login);
			return $this->db->update('xo_rooms', array('board' => serialize(array())));
		} 
		else { return false; }		
	}
	
	public function Invite($login, $invitee)
	{
		$this->db->where(array('invitee_login' => $invitee, 'state' => self::STATE_PLAYING));				
		$query = $this->db->get('xo_rooms');
		$result = $query->result();
	
		if (count($result) == 0)
		{
			$columns = array(
				'inviter_login' => $login, 
				'invitee_login' => $invitee, 
				'state' => self::STATE_INVITED);
			
			return $this->db->insert('xo_rooms', $columns);			
		} 
		else
		{
			return false;
		}	
	}	
	
	public function HandlePlayState($login, $room, IStateHandler & $handler)
	{
		$combos = array(array(0, 1, 2), array(3, 4, 5), array(6, 7, 8), 
						array(0, 3, 6), array(1, 4, 7), array(2, 5, 8),
						array(0, 4, 8), array(2, 4, 6));
		
		foreach ($combos as $combo)
		{
			$win = true;			
			foreach ($combo as $cell)
			{
				if (!isset($room->board[$cell]) || $room->board[$cell] != 'x') { $win = false; break; }
			}
			if ($win) 
			{
				return $room->invitee_login == $login ? $handler->HandleWin() : $handler->HandleLoss(); 
			}
			
			$win = true;
			foreach ($combo as $cell)
			{
				if (!isset($room->board[$cell]) || $room->board[$cell] != 'o') { $win = false; break; }
			}
			if ($win) 
			{
				return $room->inviter_login == $login ? $handler->HandleWin() : $handler->HandleLoss(); 				
			}
		}
		
		$isOdd = count($room->board) % 2 == 0;		
		
		$canMoveIfInviter = $isOdd && $login == $room->invitee_login;
		$canMoveIfInvitee = !$isOdd && $login == $room->inviter_login;
		
		return $canMoveIfInviter || $canMoveIfInvitee ? $handler->HandleCanMove() : $handler->HandleWaitMove();		
	}
	
	public function GetInviteRoom($login)
	{
		$this->db->order_by('state', 'asc');
		$this->db->where("(state=".self::STATE_INVITED." AND (inviter_login='$login' OR invitee_login='$login'))");
		
		//$this->db->limit(1);		
		$result = $this->db->get('xo_rooms')->result();
		
		if (count($result) == 0) 
		{ 
			return false;			
		}
		else
		{
			$room = $result[0];
			$room->board = unserialize($room->board);			
			return $room;
		}				
		
	}
	
	public function GetActiveRoom($login)
	{
		$this->db->order_by('state', 'asc');
		$this->db->where(
				"(state=".self::STATE_PLAYING." AND (inviter_login='$login' OR invitee_login='$login')) OR ".
				"(state=".self::STATE_LEAVED_BY_INVITER." AND invitee_login='$login') OR ".
				"(state=".self::STATE_LEAVED_BY_INVITEE." AND inviter_login='$login')");
		
		//$this->db->limit(1);		
		$result = $this->db->get('xo_rooms')->result();
		
		if (count($result) == 0) 
		{ 
			return false;			
		}
		else
		{
			$room = $result[0];
			$room->board = unserialize($room->board);			
			return $room;
		}				
	}

	public function HandleGameState($login, IStateHandler & $handler)
	{
		$room = $this->GetActiveRoom($login);
		
		if ($room === false) { return false; }
		
		$handler->SetRoom($room);
		
		switch ($room->state)
		{
			case self::STATE_PLAYING: $this->HandlePlayState($login, $room, $handler); break;
			case self::STATE_LEAVED_BY_INVITER: $handler->HandleLeavedByInviter(); break;
			case self::STATE_LEAVED_BY_INVITEE: $handler->HandleLeavedByInvitee(); break;
			default: return false;
		}
		
		return true;
	}
	
	public function Accept($login)
	{	
		$data = array('state' => self::STATE_PLAYING, 'board' => serialize(array()));		
		$this->db->where('invitee_login', $login);
		return $this->db->update('xo_rooms', $data);
	}

	public function Decline($login)
	{
		$room = $this->GetRoomByInvitee($login);
		
		if ($room !== false)
		{
			return $this->db->delete('xo_rooms', array('id' => $room->id));			
		}
		else 
		{
			
		} 
		
	}
	
	public function CancelInvite($login)
	{
		$room = $this->GetRoomByInviter($login);
		if ($room !== false)
		{
			return $this->db->delete('xo_rooms', array('inviter_login' => $login));
		} 
		else { return false; }
	}
	
	public function LeaveRoom($login)
	{
		$room = $this->GetActiveRoom($login);
		
		if ($room !== false)
		{
			if (($room->state == self::STATE_LEAVED_BY_INVITEE && $login == $room->inviter_login) ||
				($room->state == self::STATE_LEAVED_BY_INVITER && $login == $room->invitee_login))
			{
				$this->db->delete('xo_rooms', array('inviter_login' => $room->inviter_login));
			}			
			else
			{
				$newState = $login == $room->inviter_login ? 
						self::STATE_LEAVED_BY_INVITER : self::STATE_LEAVED_BY_INVITEE;
				
				$this->db->where('inviter_login', $room->inviter_login);
				$this->db->update('xo_rooms', array('state' => $newState));
			}
			
			return true;
		} 
		else { return false; }
	}
	
	public function GetPlayRoom($login)
	{
		$this->db->where("(state=".self::STATE_PLAYING." AND (inviter_login='$login' OR invitee_login='$login'))");
		$result = $this->db->get('xo_rooms')->result();
		
		if ($result !== false && count($result) > 0) 
		{
			$room = $result[0];
			$room->board = unserialize($room->board);
			return $room;
		}
		
		return false;		
	}
	
	public function GetRoom($login)
	{
		$this->db->where('inviter_login', $login);
		$this->db->or_where('invitee_login', $login);
		$query = $this->db->get('xo_rooms');
		$result = $query->result();		
		
		if ($query !== false && count($result) > 0)
		{
			$room = $result[0];
			$room->board = unserialize($room->board);
			return $room;
		}
		else { return false; }
	}
	
	public function GetRoomByInviter($login)
	{		
		$query = $this->db->get_where('xo_rooms', array('inviter_login' => $login));
		$result = $query->result();
		
		if ($query !== false && count($result) > 0)
		{
			$room = $query->result()[0];
			$room->board = unserialize($room->board);
			return $room;
		}
		else { return false; }

	}

	public function GetRoomByInvitee($login)
	{		
		$query = $this->db->get_where('xo_rooms', array('invitee_login' => $login));		
		$result = $query->result();
		
		if ($query !== false && count($result) > 0)
		{
			$room = $query->result()[0];
			$room->board = unserialize($room->board);
			return $room;
		}
		else { return false; }

	}	
	
	public function GetLobby()
	{
		$this->RefreshLobby();
		return $this->db->get('xo_lobby')->result();
	}
	
	public function GetUser($login)
	{
		$query = $this->db->get_where('xo_users', array('login' => $login));
		$result = $query->result();
		return count($result) > 0 ? $result[0] : false;
	}
	
	public function Register($login, $hash)
	{		
		$result = $this->GetUser($login);		
		return !empty($login) && !empty($hash) && $result === false && $this->db->insert('xo_users', array('login' => $login, 'hash' => $hash));
	}
	
	public function Login($login, $pass)
	{
		$user_result = $this->GetUser($login);			
		
		if ($user_result !== false && $user_result->hash == $pass)
		{
			$data = array('login' => $login, 'timestamp' => time());
			
			$lobby_result = $this->db->get_where('xo_lobby', array('login' => $login))->result();
			
			if (empty($lobby_result))
			{
				return $this->db->insert('xo_lobby', $data);
			}
			else 
			{
				$this->db->where('login', $login);
				return $this->db->update('xo_lobby', $data);							
			}			
		}
		else
		{			
			return false;
		}
	}
	
	public function WriteMove($login, $inviter, $cell)
	{
		$room = $this->GetPlayRoom($login);
		
		if ($room !== false && $cell !== false && $inviter !== false)
		{			
			$state = $room->board;
			$state[$cell] = $login == $room->inviter_login ? 'o' : 'x';

			$this->db->where('inviter_login', $room->inviter_login);
			$this->db->update('xo_rooms', array('board' => serialize($state)));							

			return true;			
		} 
		else { return false; }

	}
	
	private function RefreshLobby()
	{
		$threshold = time() - $this->updateTime;
		
		$this->db->where('timestamp <', $threshold);
		$this->db->delete('xo_lobby');
	}

}	