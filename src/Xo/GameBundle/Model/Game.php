<?php

namespace Xo\GameBundle\Model;

use Xo\GameBundle\Abstraction;
use Xo\GameBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

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
	
	private $em = null;
	private $stopwatch = null;
	private $lang = null;
	
	
	public $login = null;
	public $messages = array();
	
	public function __construct(Abstraction\ILanguage $lang) {		

		$this->lang = $lang;		
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
		if ($this->login === null) { throw new \Exception($this->lang->ErrorMove()); }		
		
		$room = $this->FindPlayingGame();		
		if (!($room instanceof Entity\Room)) { throw new \Exception($this->lang->ErrorMove()); }
		
		$nMoves = count($room->board);		
		if ($nMoves >= 9 || isset($room->board[$cell])) { throw new \Exception($this->lang->ErrorMove()); }			

		if ($room->makeMove($cell, $this->login) !== true) { throw new \Exception($this->lang->ErrorMove()); }		

		$outRivalLogin = $this->login === $room->inviter_login ? $room->invitee_login : $room->inviter_login;
		$outRivalState = $room->getRoomState($outRivalLogin);
		$outState = $room->getRoomState($this->login);

		return array($outState, $outRivalState, $outRivalLogin);
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
	
	public function LeaveRoomIfPlaying()
	{
		$remainingPlayer = false;
		if ($this->login === null) { throw new \Exception($this->lang->ErrorLeave()); }
		
		$room = $this->FindGame();
		if ($room instanceof Entity\Room) {

			switch ($room->state)
			{
				case self::STATE_PLAYING:
					if ($this->login === $room->inviter_login)
					{
						$room->state = self::STATE_LEFT_BY_INVITER;
						$remainingPlayer = $room->invitee_login;
					} else
					{
						$room->state = self::STATE_LEFT_BY_INVITEE;
						$remainingPlayer = $room->inviter_login;
					}
					break;

				case self::STATE_LEFT_BY_INVITEE:
					if ($this->login == $room->inviter_login) { $this->em->remove($room); $wasPlaying = false; }
					else { throw new \Exception($this->lang->ErrorLeave()); }
					break;

				case self::STATE_LEFT_BY_INVITER:
					if ($this->login == $room->invitee_login) { $this->em->remove($room); $wasPlaying = false; }
					else { throw new \Exception($this->lang->ErrorLeave()); }
					break;

				default: throw new \Exception($this->lang->ErrorLeave());
			}
		}

		return $remainingPlayer;
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

		$room = $this->FindGame();
		
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

		return $handler->HandleLobby();
	}
	
	public function QuitLobby()
	{
		if ($this->login === null) { throw new \Exception($this->lang->ErrorLeave()); }			

		$this->em->flush();
		$lobbyRepo = $this->GetRepo(self::REPO_LOBBY);
		$player = $lobbyRepo->find($this->login);
		if ($player instanceof Entity\LobbyPlayer)
		{
			$this->em->remove($player);
		}
//			$lobbyRepo = $this->GetRepo(self::REPO_LOBBY);
//			$qb = $lobbyRepo->createQueryBuilder('Player');
//			$qb
//				->delete()
//				->where($qb->expr()->eq('Player.login', ':login'))
//				->setParameter('login', $this->login)->getQuery()->execute();
		return true;
	}

	public function Accept(&$outInviterLogin)
	{
		if ($this->login === null) { throw new \Exception($this->lang->ErrorAccept()); }
		
		$repo = $this->GetRepo(self::REPO_ROOM);
		$room = $repo->findOneBy(array('invitee_login' => $this->login, 'state' => self::STATE_INVITED));
		
		if (!($room instanceof Entity\Room)) { throw new \Exception($this->lang->ErrorAccept()); }

		$outInviterLogin = $room->inviter_login;		
		
		$room->state = self::STATE_PLAYING;				
	}
	
	
	public function Decline(&$outInviterLogin)
	{
		if ($this->login === null) { throw new \Exception($this->lang->ErrorDecline()); }
		
		$repo = $this->GetRepo(self::REPO_ROOM);
		$room = $repo->findOneBy(array('invitee_login' => $this->login, 'state' => self::STATE_INVITED));
		
		if (!($room instanceof Entity\Room)) { throw new \Exception($this->lang->ErrorDecline()); }
		
		$outInviterLogin = $room->inviter_login;		
		//$room->state = self::STATE_DECLINED;
		$this->em->remove($room);
	}
	
	public function Cancel(&$outInviteeLogin)
	{
		if ($this->login === null) { throw new \Exception($this->lang->ErrorCancel()); }
		
		$repo = $this->GetRepo(self::REPO_ROOM);		
		$game = $repo->find($this->login);		
		
		if (!($game instanceof Entity\Room)) { throw new \Exception($this->lang->ErrorCancel()); }
		
		$outInviteeLogin = $game->invitee_login;		
		$this->em->remove($game);
	}
	
	public function Signup($login, $hash)
	{
		if (!is_string($login) || empty($login) || strlen($login) > 64) {

			throw new \Exception($this->lang->ErrorSignup());
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
		$room = $this->FindGame();
		if (!($room instanceof Entity\Room)) { throw new \Exception($this->lang->ErrorReplay()); }
		return $this->login === $room->inviter_login ? $room->invitee_login : $room->inviter_login;
	}

	public function Replay(&$outRivalLogin)
	{
		$room = $this->FindGame();
		if (!($room instanceof Entity\Room)) { throw new \Exception($this->lang->ErrorReplay()); }

		$outRivalLogin = $this->login === $room->inviter_login ? $room->invitee_login : $room->inviter_login;		
		
		$room->board = array();		
	}
	
	public function RemoveTimedoutPlayers(&$outLeftLogins)
	{
		$threshold = time() - self::LEAVE_TIMEOUT;
		
		$this->em->flush();
		$lobbyRepo = $this->GetRepo('LobbyPlayer');		
		$criteria = new Criteria();
		$criteria->where($criteria->expr()->lt('timestamp', $threshold));
		$leaved = $lobbyRepo->matching($criteria);
		
		$outLeftLogins = array();
		foreach ($leaved as $player)
		{
			$outLeftLogins[] = $player->login;
		}		
		
		if (is_array($outLeftLogins) && !empty($outLeftLogins))
		{		
			$qb = $lobbyRepo->createQueryBuilder('Player');
			$qb
				->delete()
				->where($qb->expr()->in('Player.login', $outLeftLogins))
				->getQuery()->execute();
		}
	}
	
	public function KeepAlive()
	{
		$lobbyRepo = $this->GetRepo('LobbyPlayer');
		$player = $lobbyRepo->find($this->login);

		if (is_object($player))
		{
			$player->timestamp = time();

		} else
		{			
			$player = new Entity\LobbyPlayer($this->login, time());
			$this->em->persist($player);
		}		
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
		$this->em->flush();
		$users = $this->GetRepo(self::REPO_USER);
		$user = $users->find($login);
		
		return is_object($user);
	}
	
	public function FindGame()
	{
		if ($this->login === null) { throw new \Exception($this->lang->ErrorLogin()); }
		
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
			throw new \Exception($this->lang->ErrorInvite());
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
			throw new \Exception($this->lang->ErrorInvite());
		}
		
		$newRoom = new Entity\Room($this->login, $invitee_login, self::STATE_INVITED);
		$this->em->persist($newRoom);
		
		return true;		
	}	
	
}