<?php

namespace Xo\GameBundle\Model;

use Xo\GameBundle\Abstraction;
use Xo\GameBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

require_once(__DIR__."/hydna-push.php");

class Notify {
	
	public $type = null;
	public $body = null;
	public $messages = array();
	
	public function __construct($type) {	
		
		$this->type = $type;
		$this->body = new \stdClass();
	}
	
	public function Post($type, $body)
	{
		$msg = new \stdClass();
		$msg->type = $type;
		$msg->body = $body;
		
		$this->messages[] = $msg;
	}
}

class Game {

	const LEAVE_TIMEOUT = 120;
	
	const STATE_PLAYING = 0;	
	const STATE_LEFT_BY_INVITER = 100;
	const STATE_LEFT_BY_INVITEE = 200;
	const STATE_INVITED = 300;
	const STATE_DECLINED = 400;	
	
	const REPO_USER = 'User';
	const REPO_ROOM = 'Room';
	const REPO_LOBBY = 'LobbyPlayer';	
	
	private $hydna = null;
	private $em = null;
	private $stopwatch = null;
	private $lang = null;
	
	
	public $login = null;
	public	$messages = array();
	
	public function __construct(Abstraction\ILanguage $lang) {
		
		$this->hydna = new \Hydna();
		$this->lang = $lang;		
	}
	
	private function BroadcastToUsers(Notify $notify)
	{
		// send a message
		$this->stopwatch->start('game:hydna:BroadcastToUsers');
		$this->hydna->push("http://xoapp.hydna.net/shared", json_encode($notify));
		$this->stopwatch->stop('game:hydna:BroadcastToUsers');
	}
	
	public function SendToUser($addressee, Notify $notify)
	{
		// send a message
		$this->stopwatch->start('game:hydna:SendToUser');
		$this->hydna->push("http://xoapp.hydna.net/user/$addressee", json_encode($notify));
		$this->stopwatch->stop('game:hydna:SendToUser');
	}
	
	public function Init(EntityManager $em, Abstraction\ILanguage $lang, $login, $hash, $stopwatch)
	{
		$this->em = $em;
		$this->lang = $lang;
		$this->stopwatch = $stopwatch;
		
		if ($login !== null && $this->Signin($login, $hash) === true)
		{
			$this->login = $login;
		}
	}
	
	public function MakeMove($cell)
	{		
		if ($this->login === null) 
		{			
			return false;
		}		
		
		$room = $this->FindPlayingGame();
		
		if (!($room instanceof Entity\Room)) { return false; }
		
		$nMoves = count($room->board);
		
		if ($nMoves > 9 || isset($room->board[$cell])) { $this->PostMessage('error', $this->lang->ErrorMove()); return false; }		
		
		
		$state = $room->getRoomState($this->login, $this->lang);
		if ($state->canMove)
		{		
			$board = $room->board;
			$board[$cell] = $state->token;
			$room->board = $board;
			
			$rivalLogin = $this->login === $room->inviter_login ? $room->invitee_login : $room->inviter_login;
			
			$state = $room->getRoomState($rivalLogin, $this->lang);
			
			$notify = new Notify('rivals_move');
			$notify->body->cell = $cell;
			$notify->body->canMove = $state->canMove;
			$notify->body->canReplay = $state->canReplay;
			$notify->Post('info', $state->message);	
			
			$this->SendToUser($rivalLogin, $notify);
			
			return $room->getRoomState($this->login, $this->lang);
		}		
		$this->PostMessage('error', $this->lang->ErrorMove());
		
		
		return false;
	}
	
	public function GetLobbyPlayers()
	{
		$this->em->flush();
		$repo = $this->GetRepo(self::REPO_LOBBY);
		return $repo->findAll();
	}
	
	private function PostMessage($type, $body)
	{
		$newMessage = new \stdClass();
		$newMessage->type = $type;		
		$newMessage->body = $body;
		
		$this->messages[] = $newMessage;
	}
	
	public function GetLobbyData()
	{
		$out = new \stdClass();
		
		$players = $this->GetLobbyPlayers();
		
		$out->players = array();
		foreach ($players as $player)
		{			
			$out->players[] = $player->login;
		}		
		 		
		$game = $this->FindOpenGame();
		
		if ($game !== null)
		{		
			$out->game = new \stdClass();

			$out->game->inviter = $game->inviter_login;
			$out->game->invitee = $game->invitee_login;
			
			switch ($game->state)
			{
				case self::STATE_INVITED: 
					$out->game->state = 'invited'; 
					break;
					
				case self::STATE_DECLINED:
					$out->game->state = 'declined';
					break;
				
				case self::STATE_PLAYING:
					$out->game->state = 'playing';
					break;
			}
		}
		
		return $out;		
	}
	
	public function Leave()
	{
		if ($this->login === null) { return false; }
		
		$room = $this->FindOpenGame();
		
		if (!($room instanceof Entity\Room)) { return false; }

		$quitMessage = new Notify('leave_game');
		$quitMessage->Post('info', $this->lang->BoardLeft());
		
		switch ($room->state)
		{
			case self::STATE_PLAYING: 
				if ($this->login === $room->inviter_login)
				{
					$this->SendToUser($room->invitee_login, $quitMessage);				
					$room->state = self::STATE_LEFT_BY_INVITER;
				} else
				{
					$this->SendToUser($room->inviter_login, $quitMessage);				
					$room->state = self::STATE_LEFT_BY_INVITEE;
				}
				break;
			
			case self::STATE_LEFT_BY_INVITEE:
				if ($this->login == $room->inviter_login) { $this->em->remove($room); } else { return false; }
				break;
				
			case self::STATE_LEFT_BY_INVITER:
				if ($this->login == $room->invitee_login) { $this->em->remove($room); } else { return false; }
				break;
			
			default: return false;
		}
		
		
		
		return true;
		
	}
	
	public function FindPlayingGame()
	{
		if ($this->login === null) { return null; }
		
		$rooms = $this->GetRepo(self::REPO_ROOM);
		$criteria = new Criteria();
		$criteria
				->where($criteria->expr()->eq('inviter_login', $this->login))
				->orWhere($criteria->expr()->eq('invitee_login', $this->login))
				->andWhere($criteria->expr()->eq('state', self::STATE_PLAYING))
				->setFirstResult(0)
				->setMaxResults(1);

		$matching = $rooms->matching($criteria);
		return $matching[0];		
	}
	
	public function HandleState(Abstraction\IStateHandler & $handler)
	{	
		if ($this->login === null) { return $handler->HandleSignin(); }		

		$room = $this->FindOpenGame();
		
		if ($room instanceof Entity\Room) 
		{
			switch ($room->state)
			{
				case self::STATE_PLAYING: 
					return $handler->HandleBoard($room->getRoomState($this->login, $this->lang, $this->stopwatch));
					
				case self::STATE_LEFT_BY_INVITER:
					return $this->login === $room->inviter_login ? $handler->HandleLobby() : 
						$handler->HandleLeft($room->getRoomState($this->login, $this->lang));
					
				case self::STATE_LEFT_BY_INVITEE:
					return $this->login === $room->invitee_login ?  $handler->HandleLobby() :
						$handler->HandleLeft($room->getRoomState($this->login, $this->lang));
					
				case self::STATE_INVITED:
					return $this->login === $room->inviter_login ? 
						$handler->HandleAwaiting($room->invitee_login) : $handler->HandleInvited($room->inviter_login);
				
				case self::STATE_DECLINED:
					return $handler->HandleLobby();			
			}
		} 
		else
		{
			return $handler->HandleLobby();	
		}		
	}
	
	public function QuitLobby()
	{
		if ($this->login === null) { $this->PostMessage('error', $this->lang->ErrorLeave()); return false; }
		
		$logins = array($this->login);
		$notify = new Notify('leaved');
		$notify->body->logins = $logins;
		$this->BroadcastToUsers($notify);		
		
		$lobbyRepo = $this->GetRepo(self::REPO_LOBBY);
		$qb = $lobbyRepo->createQueryBuilder('Player');
		$qb
			->delete()
			->where($qb->expr()->in('Player.login', $logins))
			->getQuery()->execute();
		
		return true;
	}
	
	public function QuitBoard()
	{
		if ($this->login === null) { $this->PostMessage('error', $this->lang->ErrorLeave()); return false; }		
	}

	public function Accept()
	{
		if ($this->login === null) { return false; }
		
		$repo = $this->GetRepo(self::REPO_ROOM);
		$room = $repo->findOneBy(array('invitee_login' => $this->login, 'state' => self::STATE_INVITED));
		
		if (!($room instanceof Entity\Room)) { 
			
			$this->PostMessage('error', $this->lang->ErrorAccept());
			return false; 
			
		}

		$notify = new Notify('accepted');
		$notify->body->invitee = $this->login;
		$this->SendToUser($room->inviter_login, $notify);	
		
		$room->state = self::STATE_PLAYING;				
		return true;
	}
	
	
	public function Decline()
	{
		if ($this->login === null) { return false; }
		
		$repo = $this->GetRepo(self::REPO_ROOM);
		$room = $repo->findOneBy(array('invitee_login' => $this->login, 'state' => self::STATE_INVITED));
		
		if (!($room instanceof Entity\Room)) { return false; }

		$notify = new Notify('declined');
		$notify->body->invitee = $this->login;
		$this->SendToUser($room->inviter_login, $notify);	
		
		$this->em->remove($room);
		
		return true;
	}
	
	public function Cancel()
	{
		if ($this->login === null) { return false; }
		
		$repo = $this->GetRepo(self::REPO_ROOM);		
		$game = $repo->find($this->login);		
		
		if (!($game instanceof Entity\Room)) { return false; }
		
		$notify = new Notify('canceled');
		$notify->body->inviter = $this->login;
		$this->SendToUser($game->invitee_login, $notify);		
		
		$this->em->remove($game);
	}
	
	public function Signup($login, $hash)
	{
		if (!is_string($login) || empty($login) || strlen($login) > 64) {

			$this->PostMessage('error', $this->lang->ErrorLogin());
			return false;
		}

		$user = new Entity\User($login, $hash);
		$this->em->persist($user);
		$this->SigninRoutine($login);
		
		return true;		
	}
	
	private function GetRepo($entity)
	{
		return $this->em->getRepository('Xo\\GameBundle\\Entity\\'.$entity);		
	}
	
	public function ProposeReplay()
	{
		$room = $this->FindOpenGame();		
		if (!($room instanceof Entity\Room)) { return false; }
		
		$notify = new Notify('replay');
		$this->SendToUser($this->login === $room->inviter_login ? $room->invitee_login : $room->inviter_login, $notify);	
		
		return true;
	}

	public function Replay()
	{
		$room = $this->FindOpenGame();		
		if (!($room instanceof Entity\Room)) { return false; }

		$notify = new Notify('accept_replay');
		$this->SendToUser($this->login === $room->inviter_login ? $room->invitee_login : $room->inviter_login, $notify);	
		
		$room->board = array();
		
		return true;
	}
	
	private function RemoveTimedoutPlayers()
	{
		$threshold = time() - self::LEAVE_TIMEOUT;
		
		$this->em->flush();
		$lobbyRepo = $this->GetRepo('LobbyPlayer');		
		$criteria = new Criteria();
		$criteria->where($criteria->expr()->lt('timestamp', $threshold));
		$leaved = $lobbyRepo->matching($criteria);
		
		$leavedLogins = array();
		foreach ($leaved as $player)
		{
			$leavedLogins[] = $player->login;
		}		
		
		if (is_array($leavedLogins) && !empty($leavedLogins))
		{
			$notify = new Notify('leaved');
			$notify->body->logins = $leavedLogins;
			$this->BroadcastToUsers($notify);			
			
			$qb = $lobbyRepo->createQueryBuilder('Player');
			$qb
				->delete()
				->where($qb->expr()->in('Player.login', $leavedLogins))
				->getQuery()->execute();
		}
	}
	
	public function UpdateLobby()
	{
		$lobbyRepo = $this->GetRepo('LobbyPlayer');
		$player = $lobbyRepo->find($this->login);

		if (is_object($player))
		{
			$player->timestamp = time();

		} else
		{
			$notify = new Notify('player_online');	
			$notify->body->login = $this->login;
		
			$this->BroadcastToUsers($notify);
			
			$player = new Entity\LobbyPlayer($this->login, time());
			$this->em->persist($player);
		}
		
		$this->RemoveTimedoutPlayers();
	}
	
	public function SigninRoutine($login)
	{		
		$this->login = $login;
	}
	
	public function Signin($login, $hash)
	{
		if (!is_string($login) || empty($login)) { return false; }
		
		$this->em->flush();
		$userRepo = $this->GetRepo('User');
		$user = $userRepo->find($login);
		
		if (is_object($user) && $user->hash === $hash)
		{
			$this->SigninRoutine($login);
			return true;
		}
		else
		{
			$this->PostMessage('error', $this->lang->ErrorSignin());
			return false;
		}		
	}
	
	public function HasUser($login)
	{
		$users = $this->GetRepo(self::REPO_USER);
		$user = $users->find($login);
		
		return is_object($user);
	}
	
	public function FindOpenGame()
	{
		if ($this->login === null) { return null; }
		
		$this->em->flush();
		$rooms = $this->GetRepo(self::REPO_ROOM);
				
		$criteria = new Criteria();
		$criteria
				->where($criteria->expr()->eq('inviter_login', $this->login))
				->orWhere($criteria->expr()->eq('invitee_login', $this->login))
				->orderBy(array('state' => Criteria::ASC))
				->setFirstResult(0)
				->setMaxResults(1);

		$matching = $rooms->matching($criteria);
		$room = $matching[0];
		
		return is_object($room) ? $room : null;
	}
	
	public function Invite($invitee_login)
	{
		if ($this->login === null || !$this->HasUser($invitee_login)) 
		{
			$this->PostMessage('error', $this->lang->ErrorLogin());
			return false;				
		}		
				
		$rooms = $this->GetRepo(self::REPO_ROOM);
		$logins = array($this->login, $invitee_login);
		
		$qb = new Criteria();
		$qb
			->where($qb->expr()->in('inviter_login', $logins))
			->orWhere($qb->expr()->in('invitee_login', $logins))
			->setFirstResult(0)
			->setMaxResults(1);

		$openRoom = $rooms->matching($qb)->first();

		if (is_object($openRoom))
		{
			$this->PostMessage('error', $this->lang->ErrorInvite());
			return false;
		}
		
		$newRoom = new Entity\Room($this->login, $invitee_login, self::STATE_INVITED);
		$this->em->persist($newRoom);
		
		$invitedMsg = new Notify('invited');
		$invitedMsg->body->inviter = $this->login;
		$this->SendToUser($invitee_login, $invitedMsg);		
		
		return true;
		
	}	
	
}