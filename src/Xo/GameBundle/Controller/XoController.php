<?php

namespace Xo\GameBundle\Controller;

use Symfony\Component\HttpFoundation;
use Xo\GameBundle\Model;
use Xo\GameBundle\Entity;

class Modal {
	
	public $title = null;
	public $text = null;
	public $buttons = array();
	public $cancel = null;	
}

class StopWatchStub {
	
	public function start($name) {}
	public function stop($name) {}
}

class Notice {
	
	public $type = null;
	public $body = null;
	public $hydna = null;
	
	public function __construct($type, \Hydna & $hydna) {	
		
		$this->hydna = &$hydna;
		$this->type = $type;
		$this->body = new \stdClass();
	}
	
	public function Broadcast()
	{
		// send a message
		$json = json_encode($this);
		error_log('hydna broadcast push: '.$json);
		$this->hydna->push("http://xoapp.hydna.net/shared", $json);
	}
	
	public function SendTo($addressee)
	{
		// send a message
		$json = json_encode($this);
		error_log('hydna push to '.$addressee.': '.$json);
		$this->hydna->push("http://xoapp.hydna.net/user/$addressee", $json);
	}
}

require_once(__DIR__."/hydna-push.php");

class XoController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller implements \Xo\GameBundle\Abstraction\IStateHandler {
	
	
	private $locale = 'ru';
	private $lang = null;
	private $request = null;
	private $messages = array();
	private $response = null;
	private $model = null;
	private $em = null;
	private $stopwatch = null;
	private $hydna = null;
	
	public function __construct() {		
		
		$this->lang = new Model\RusLang();
		$this->response = new HttpFoundation\Response();
		$this->model = new Model\Game($this->lang, null, null, null);		
		$this->hydna = new \Hydna();
	}
	
	public function indexAction()
	{
		return $this->redirect($this->generateUrl("main", array('locale' => $this->locale)));
	}
	
	public function mainAction($locale, HttpFoundation\Request $request)
	{		
		$this->Init($locale, $request);
		
		return $this->RenderResponse($this->model->HandleState($this));		
	}
	
	public function inviteAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);
		$invitee = $request->get('invitee');

		$this->model->Invite($invitee);
		
		$notice = new Notice('invited', $this->hydna);
		$notice->body->inviter = $this->model->login;
		$notice->SendTo($invitee);		

		$response = new \stdClass();
		$response->type = 'invite';
		$response->body = new \stdClass();
		$response->body->invitee = $invitee;

		return $this->FormJsonResponse($response);
	}
	
	public function makemoveAction($locale, HttpFoundation\Request $request)
	{		
		$cell = (integer)$request->get('cell');			
		$this->Init($locale, $request);		
		
		$this->stopwatch->start('makemove');
		
		$state = null;
		$rivalState = null;		
		$rivalLogin = null;
		
		$this->model->MakeMove($cell, $state, $rivalState, $rivalLogin);
		
		$noticeMap = array( 
			Entity\RoomState::STATE_WIN => 'win',
			Entity\RoomState::STATE_LOSS => 'loss',
			Entity\RoomState::STATE_DRAW => 'draw',
			Entity\RoomState::STATE_YOUR_MOVE => 'your_move',
			Entity\RoomState::STATE_RIVALS_MOVE => 'rivals_move');
		
		$notice = new Notice($noticeMap[$rivalState->code], $this->hydna);
		$notice->body->cell = $cell;
		$notice->body->cellToken = $state->token;
		$notice->body->state = $rivalState;

		$this->stopwatch->start('game:hydna:SendToUser');	
		$notice->SendTo($rivalLogin, $notice);
		$this->stopwatch->stop('game:hydna:SendToUser');		
		
		$response = new \stdClass();
		$response->type = $noticeMap[$state->code];
		$response->body = new \stdClass();
		$response->body->cell = $cell;
		$response->body->cellToken = $state->token;
		
		$response->body->state = $state;

		$this->stopwatch->stop('makemove');
		
		return $this->FormJsonResponse($response);
	}
	
	public function declineAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);			
		
		$inviterLogin = null;
		$this->model->Decline($inviterLogin);
		
		$notice = new Notice('declined', $this->hydna);
		$notice->body->invitee = $this->model->login;
		$notice->SendTo($inviterLogin);	
		
		return $this->FormJsonResponse('ok');
	}
	
	public function cancelAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);

		$inviteeLogin = null;
		$this->model->Cancel($inviteeLogin);	
		
		$notice = new Notice('canceled', $this->hydna);
		$notice->body->inviter = $this->model->login;
		$notice->SendTo($inviteeLogin);			
		
		return $this->FormJsonResponse('ok');
	}

	public function leaveAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);		
		
		$remainingPlayer = null;
		$this->model->LeaveBoard($remainingPlayer);
		
		if ($remainingPlayer !== null) {			
			$leaveMessage = new Notice('leave_game', $this->hydna);
			$leaveMessage->SendTo($this->hydna, $remainingPlayer);
		}
		
		return $this->RenderResponse($this->model->HandleState($this));
	}
	
	public function replayAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);
		
		$outRivalLogin = null;
		$this->model->ProposeReplay($outRivalLogin);
		$response = 'ok';
		
		$notice = new Notice('replay', $this->hydna);
		$notice->SendTo($outRivalLogin);
		
		return $this->FormJsonResponse($response);
	}
	
	public function signoutAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);
		$this->model->QuitLobby();
		
		$response = $this->redirect($this->generateUrl("main", array('locale' => $this->locale)));		
		$response->headers->clearCookie('login');
		$response->headers->clearCookie('hash');
		
		return $response;
	}
	
	public function acceptReplayAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);
		
		$outRivalLogin = null;
		$this->model->Replay($outRivalLogin);
		
		$notice = new Notice('accept_replay', $this->hydna);
		$notice->SendTo($outRivalLogin);
		
		return $this->RenderResponse($this->model->HandleState($this));
	}
	
	public function signinAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);

		$login = $request->get('login');
		$hash = $this->toHash($request->get('password'));
		
		if ($this->model->Signin($login, $hash) === true)
		{
			$this->SetCookies($login, $hash);
			
			$response = new \stdClass();
			$response->login = $login;
			$response->html = $this->model->HandleState($this);
			
		} else
		{
			$response = null;
		}
		
		return $this->FormJsonResponse($response);
	}
	
	private function RemoveTimedoutPlayers()
	{
		$leftLogins = array();
		$this->model->RemoveTimedoutPlayers($leftLogins);
		
		if (count($leftLogins))
		{		
			$notice = new Notice('leaved', $this->hydna);
			$notice->body->logins = $leftLogins;
			$notice->Broadcast();
		}
	}

	public function acceptAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);	
		
		$inviterLogin = null;
		$this->model->Accept($inviterLogin);		

		$notice = new Notice('accepted', $this->hydna);
		$notice->body->invitee = $this->model->login;
		$notice->SendTo($inviterLogin);		
		
		return $this->RenderResponse($this->model->HandleState($this));
	}
	
	public function quitLobbyAction($locale, HttpFoundation\Request $request) {
		
		$this->Init($locale, $request);
		$this->model->QuitLobby();		
		
		$notice = new Notice('leaved', $this->hydna);
		$notice->body->logins = array($this->model->login);
		$notice->Broadcast();		
			
		return $this->FormJsonResponse();
	}
	
	public function quitBoardAction($locale, HttpFoundation\Request $request) {
		
		$this->Init($locale, $request);
		$this->model->QuitBoard();
			
		return $this->FormJsonResponse();
	}
	
	private function UpdateLobby() {
		
		$this->model->KeepAlive();
		
		$notice = new Notice('player_online', $this->hydna);	
		$notice->body->login = $this->model->login;		
		$notice->Broadcast();
		
		$this->RemoveTimedoutPlayers();		
	}
	
	public function keepaliveAction($locale, HttpFoundation\Request $request)
	{		
		$this->Init($locale, $request);
		$this->UpdateLobby();
		
		return $this->FormJsonResponse('ok');
	}

	public function signupAction($locale, HttpFoundation\Request $request)
	{
		$this->Init($locale, $request);
		$login = $request->get('login');
		$hash = $this->toHash($request->get('password'));			

		$this->model->Signup($login, $hash);		
		
		$this->SetCookies($login, $hash);			
		$this->PostMessage('info', $this->lang->SignupSuccess());

		$response = new \stdClass();
		$response->html = $this->model->HandleState($this);
		$response->login = $login;		
			
		return $this->FormJsonResponse($response);		
	}
	
	private function toHash($password)
	{
		return sha1($password);
	}

	private function Init($locale, HttpFoundation\Request $request)
	{
		if ($this->has('debug.stopwatch')) {			
			$this->stopwatch = $this->get('debug.stopwatch');
		} 
		else {
			$this->stopwatch = new StopWatchStub();
		}		
		$this->stopwatch->start('controller:init');
		
		$this->request = $request;
		$this->SetLang($locale);		
		$this->em = $this->getDoctrine()->getManager();
		
		$this->stopwatch->start('model:init');	
		
		$this->model->Init($this->em, $this->lang, 
				$request->cookies->get('login'), $request->cookies->get('hash'), $this->stopwatch);	
		
		$this->stopwatch->stop('model:init');		
		$this->stopwatch->stop('controller:init');
	}
	
	
	
	private function SetCookies($login, $hash)
	{
		$this->response->headers->setCookie(new HttpFoundation\Cookie('login', $login));
		$this->response->headers->setCookie(new HttpFoundation\Cookie('hash', $hash));
	}

	private function SetLang($locale)
	{
		if ($locale === 'en')
		{
			$this->locale = $locale;	
			$this->lang = new Model\EngLang();			
		} 
		else
		{
			$this->locale = 'ru';	
			$this->lang = new Model\RusLang();			
		}
		
	}	
		
	public function HandleSignin()
	{		
		$body = $this->renderView('XoGameBundle:Views:signin.html.php', array(			
			'login' => $this->model->login,
			'lang' => $this->lang,
			'signin_url' => $this->generateUrl('signin', array('locale' => $this->locale)),
			'signup_url' => $this->generateUrl('signup', array('locale' => $this->locale))));
		
		return $body;
	}
	
	public function HandleBoard(\Xo\GameBundle\Entity\RoomState $state) {
		
		$stateMessages = array (
			\Xo\GameBundle\Entity\RoomState::STATE_WIN => $this->lang->BoardWin(),
			\Xo\GameBundle\Entity\RoomState::STATE_LOSS => $this->lang->BoardLoss(),
			\Xo\GameBundle\Entity\RoomState::STATE_DRAW => $this->lang->BoardDraw(),
			\Xo\GameBundle\Entity\RoomState::STATE_RIVALS_MOVE => $this->lang->BoardRivalsMove(),
			\Xo\GameBundle\Entity\RoomState::STATE_YOUR_MOVE => $this->lang->BoardYourMove()
		);
		
		$this->PostMessage('info', $stateMessages[$state->code]);
		
		return $this->renderView('XoGameBundle:Views:board.html.php', array(			
			'board' => $state->board,
			'can_move' => $state->canMove,
			'can_replay' => $state->canReplay,
			'token' => $state->token,
			'make_move_url' => $this->generateUrl('makemove', array('locale' => $this->locale)),
			'leave_url' => $this->generateUrl('leave', array('locale' => $this->locale)),
			'quit_board_url' => $this->generateUrl('quit_board', array('locale' => $this->locale)),
			'replay_url' => $this->generateUrl('replay', array('locale' => $this->locale)),
			'accept_replay_url' => $this->generateUrl('accept_replay', array('locale' => $this->locale)),
			'main_url' => $this->generateUrl('main', array('locale' => $this->locale)),
			'login' => $this->model->login,
			'lang' => $this->lang));
		
	}
	
	private function FormLobbyBody($inviter = null, $invitee = null)
	{
		$this->UpdateLobby();
		$players = $this->model->GetLobbyPlayers();			
		
		return $this->renderView('XoGameBundle:Views:lobby.html.php', array(
			'inviter' => $inviter,
			'invitee' => $invitee,
			'players' => $players,
			'quit_url' => $this->generateUrl('quit_lobby', array('locale' => $this->locale)),
			'main_url' => $this->generateUrl('main', array('locale' => $this->locale)),
			'keepalive_url' => $this->generateUrl('keepalive', array('locale' => $this->locale)),
			'accept_url' => $this->generateUrl('accept', array('locale' => $this->locale)),
			'invite_url' => $this->generateUrl('invite', array('locale' => $this->locale)),
			'cancel_url' => $this->generateUrl('cancel', array('locale' => $this->locale)),
			'decline_url' => $this->generateUrl('decline', array('locale' => $this->locale)),
			'login' => $this->model->login,
			'lang' => $this->lang));		
	}
		
	public function HandleLobby()
	{
		return $this->FormLobbyBody();
	}
	
	public function HandleInvited($inviter) {

		return $this->FormLobbyBody($inviter);
	}
	
	public function HandleAwaiting($invitee) {
		
		return $this->FormLobbyBody(null, $invitee);
	}
	
	public function HandleLeft(\Xo\GameBundle\Entity\RoomState $state) {
		
		$this->PostMessage('info', $this->lang->BoardLeft());
		return $this->HandleBoard($state);
	}
	
	private function FormJsonResponse($response = null)
	{
		$this->stopwatch->start('FormJsonResponse');
		
		$this->em->flush();
		
		$obj = new \stdClass();
		$obj->messages = array_merge($this->messages, $this->model->messages);
		
		if ($response != null) { $obj->response = $response; }

		$this->response->headers->set('Content-Type', 'application/json');
		$out = $this->response->setContent(json_encode($obj));
		
		$this->stopwatch->stop('FormJsonResponse');
		return $out;
	}
	
	private function PostMessage($type, $body)
	{
		$newMessage = new \stdClass();
		$newMessage->type = $type;		
		$newMessage->body = $body;
		
		$this->messages[] = $newMessage;
	}
	
	private function RenderResponse($body)
	{
		$this->em->flush();
		$messagesArray = array_merge($this->messages, $this->model->messages);
		
		if ($this->request->isXmlHttpRequest())
		{
			$response = new \stdClass();
			$response->html = $body;
			$response->messages = $messagesArray;
			
			$this->response->headers->set('Content-Type', 'application/json');		
			return $this->response->setContent(json_encode($response));
		}
		else
		{
//			$hydna = $this->renderView('XoGameBundle:Views:scripts.html.php', array(
//				'lang' => $this->lang,		
//				'login' => $this->model->login));

			$messages = $this->renderView('XoGameBundle:Views:messages.html.php', array('messages' => $messagesArray));
			
			$navbar = $this->renderView('XoGameBundle:Views:navbar.html.php', 
					array(	'lang' => $this->lang,		
							'login' => $this->model->login,
							'signout_url' => $this->generateUrl('signout', array('locale' => $this->locale))));			
			
			return $this->response->setContent($this->renderView('XoGameBundle:Views:layout.html.php', array(
//				'scripts' => $hydna,
				'messages' => $messages,
				'navbar' => $navbar, 
				'content' => $body)));
		}
	}
	
}