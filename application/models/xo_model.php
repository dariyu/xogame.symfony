<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Xo_Model extends CI_Model {

	const STATE_CAN_MOVE = 0;
	const STATE_WAIT_MOVE = 1;
	const STATE_LOSS = 2;
	const STATE_WIN = 3;
	
	//$lobby_key = 'xo_lobby';
	private $updateTime = 120;
	
	function __construct()
	{	
		parent::__construct();
		//$this->cache = new Cache();
	}

	public function Replay($login)
	{
		$room = $this->xo_model->GetRoom($login);
		
		if ($room->state == 'playing')
		{			
			$this->db->where('inviter_login', $room->inviter_login);
			return $this->db->update('xo_rooms', array('board' => serialize(array())));
		} 
		else { return false; } 
		
	}
	
	public function Invite($login, $invitee)
	{
		$this->db->where(array('invitee_login' => $invitee, 'state' => 'playing'));				
		$query = $this->db->get('xo_rooms');
		$result = $query->result();
	
		if (count($result) == 0)
		{
			$columns = array(
				'inviter_login' => $login, 
				'invitee_login' => $invitee, 
				'state' => 'invited');
			
			return $this->db->insert('xo_rooms', $columns);			
		} 
		else
		{
			return false;
		}	
	}	
	
	public function GetGameState($login)
	{
		$room = $this->xo_model->GetRoom($login);
		
		if ($room === false) { return null; }
		
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
				return $room->invitee_login == $login ? self::STATE_WIN : self::STATE_LOSS; 
			}
			
			$win = true;
			foreach ($combo as $cell)
			{
				if (!isset($room->board[$cell]) || $room->board[$cell] != 'o') { $win = false; break; }
			}
			if ($win) 
			{
				return $room->inviter_login == $login ? self::STATE_WIN : self::STATE_LOSS; 				
			}
		}
		
		$isOdd = count($room->board) % 2 == 0;		
		
		$canMoveIfInviter = $isOdd && $login == $room->invitee_login;
		$canMoveIfInvitee = !$isOdd && $login == $room->inviter_login;
		
		return $canMoveIfInviter || $canMoveIfInvitee ? self::STATE_CAN_MOVE : self::STATE_WAIT_MOVE;
	}
	
	public function Accept($login)
	{	
		$data = array('state' => 'playing', 'board' => serialize(array()));		
		$this->db->where('invitee_login', $login);
		return $this->db->update('xo_rooms', $data);
	}

	public function Decline($login)
	{
		$room = $this->GetRoom($login);
		
		if ($room !== false)
		{		
			if ($room->board == 'declined')
			{
				return $this->db->delete('xo_rooms', array('inviter_login' => $room->inviter_login));
			}
			else 
			{
				$data = array('state' => 'declined');		
				$this->db->where('invitee_login', $login);
				return $this->db->update('xo_rooms', $data);
			}
		
		} return false;
		
	}
	
	public function CancelInvite($login)
	{
		$room = $this->GetRoomByInviter($login);
		if ($room !== false)
		{
			return $this->db->delete('xo_rooms', array('inviter_login' => $login));
		} 
		else return false;
	}
	
	public function LeaveRoom($login)
	{
		$room = $this->GetRoom($login);
		if ($room !== false)
		{
			if (($room->state == 'leaved_by_invitee' && $login == $room->inviter_login) ||
				($room->state == 'leaved_by_inviter' && $login == $room->invitee_login))
			{
				$this->db->delete('xo_rooms', array('inviter_login' => $room->inviter_login));
			}			
			else
			{
				$newState = $login == $room->inviter_login ? 'leaved_by_inviter' : 'leaved_by_invitee';
				
				$this->db->where('inviter_login', $room->inviter_login);
				$this->db->update('xo_rooms', array('state' => $newState));
			}
			
			return true;
		} 
		else { return false; }
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
		if ($cell !== false && $inviter !== false && ($room = $this->GetRoomByInviter($inviter)) !== false)
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