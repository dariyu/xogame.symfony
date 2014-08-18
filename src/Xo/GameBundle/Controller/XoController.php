<?php

namespace Xo\GameBundle\Controller;

use Symfony\Component\HttpFoundation;


use Xo\GameBundle\Model;

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

class XoController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller implements \Xo\GameBundle\Abstraction\IStateHandler {
	
	
	private $locale = 'ru';
	private $lang = null;
	private $request = null;
	private $messages = array();
	private $response = null;
	private $model = null;
	private $em = null;
	private $stopwatch = null;
	
	public function __construct() {		
		
		$this->lang = new Model\RusLang();
		$this->response = new HttpFoundation\Response();
		$this->model = new Model\Game($this->lang, null, null);		
	}
	
	public function indexAction()
	{
		return $this->redirect($this->generateUrl("main", array('locale' => $this->locale)));
	}
	
	public function mainAction($locale, HttpFoundation\Request $request)
	{
		try {
		
			$this->Init($locale, $request);
			
		} catch (\Exception $e)
		{
			$this->PostMessage('error', $this->lang->ErrorUnknown());
		}	
		
		return $this->RenderResponse($this->model->HandleState($this));		
	}
	
	public function inviteAction($locale, HttpFoundation\Request $request)
	{
		try {
			
			$this->Init($locale, $request);
			$invitee = $request->get('invitee');
			
			if ($this->model->Invite($invitee) !== false)
			{
				$response = new \stdClass();
				$response->type = 'invite';
				$response->body = new \stdClass();
				$response->body->invitee = $invitee;
			} 
			else
			{
				$response = null;
			}
			
		} catch (\Exception $ex) {
			
			$response = null;
			$this->PostMessage('error', $this->lang->ErrorUnknown());			
		}

		return $this->FormJsonResponse($response);
	}
	
	public function makemoveAction($locale, HttpFoundation\Request $request)
	{		
		try {
		
			$cell = (integer)$request->get('cell');			
			$this->Init($locale, $request);
			
			$this->stopwatch->start('makemove');
			
			if (($state = $this->model->MakeMove($cell)) !== false)
			{			
				$response = new \stdClass();
				$response->type = 'move';
				$response->body = new \stdClass();
				$response->body->state = $state;
				$response->body->cell = $cell;
				
				$this->PostMessage('info', $state->message);	
				
			} else
			{
				$response = null;
			}

		} catch (\Exception $ex) {

			$this->PostMessage('error', $this->lang->ErrorUnknown());
			$response = null;
		}
		
		$this->stopwatch->stop('makemove');
		
		return $this->FormJsonResponse($response);
	}
	
	public function declineAction($locale, HttpFoundation\Request $request)
	{
		try {
			
			$this->Init($locale, $request);			
			if ($this->model->Decline() === false)
			{
				$this->PostMessage('error', $this->lang->ErrorDecline());
			}				
			
		} catch (\Exception $ex) {

			$this->PostMessage('error', $this->lang->ErrorUnknown());			
		}
		
		return $this->FormJsonResponse('ok');
	}
	
	public function cancelAction($locale, HttpFoundation\Request $request)
	{
		try {
			$this->Init($locale, $request);
		
			if ($this->model->Cancel() === false)
			{
				$this->PostMessage('error', $this->lang->ErrorCancel());
				$response = null;
			} else
			{
				$response = 'ok';
			}
		}
		catch (\Exception $ex)
		{
			$this->PostMessage('error', $this->lang->ErrorUnknown());
			$response = null;
		}
		
		return $this->FormJsonResponse($response);
	}

	public function leaveAction($locale, HttpFoundation\Request $request)
	{
		try {
			
			$this->Init($locale, $request);				
			if ($this->model->Leave() === false)
			{
				$this->PostMessage('error', $this->lang->ErrorCancel());				
			}
		}
		catch (\Exception $ex)
		{
			$this->PostMessage('error', $this->lang->ErrorUnknown());
		}
		
		return $this->RenderResponse($this->model->HandleState($this));
	}
	
	public function replayAction($locale, HttpFoundation\Request $request)
	{
		$response = null;
		
		try {
			
			$this->Init($locale, $request);
			if ($this->model->ProposeReplay() === true) { $response = 'ok'; }
			
		} catch (\Exception $ex) {

			$this->PostMessage('error', $this->lang->ErrorUnknown());
		}
		
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
		try {
			
			$this->Init($locale, $request);
			if ($this->model->Replay() === false)
			{
				$this->PostMessage('error', $this->lang->ErrorReplay());				
			} 
			
		} catch (\Exception $ex) {

			$this->PostMessage('error', $this->lang->ErrorUnknown());
		}
		
		return $this->RenderResponse($this->model->HandleState($this));
	}
	
	public function signinAction($locale, HttpFoundation\Request $request)
	{
		$response = null;
		
		try {
			
			$this->Init($locale, $request);
		
			$login = $request->get('login');
			$hash = $this->toHash($request->get('password'));
		
			if ($this->model->Signin($login, $hash) === true)
			{
				$this->SetCookies($login, $hash);
				$response = new \stdClass();
				$response->html = $this->model->HandleState($this);
				$response->login = $login;
			}
			
		} catch (\Exception $ex)
		{
			$this->PostMessage('error', $this->lang->ErrorSignin());			
		}
		
		return $this->FormJsonResponse($response);
	}

	public function acceptAction($locale, HttpFoundation\Request $request)
	{
		try {
			
			$this->Init($locale, $request);	
			$this->model->Accept();
			
		} catch (\Exception $ex)
		{
			$this->PostMessage('error', $this->lang->ErrorUnknown());	
		}
		
		
		return $this->RenderResponse($this->model->HandleState($this));
	}
	
	public function quitLobbyAction($locale, HttpFoundation\Request $request) {
		
		try {
			
			$this->Init($locale, $request);
			$this->model->QuitLobby();
			
		} catch (Exception $ex) {
		}
		
		return $this->FormJsonResponse();
	}
	
	public function keepaliveAction($locale, HttpFoundation\Request $request)
	{		
		try {
			
			$this->Init($locale, $request);
			$this->model->UpdateLobby();		
			
		} catch (\Exception $ex) {

			$this->PostMessage('error', $this->lang->ErrorUnknown());
		}
		
		return $this->FormJsonResponse('ok');
	}	

	public function signupAction($locale, HttpFoundation\Request $request)
	{
		$response = null;
		
		try {
			
			$this->Init($locale, $request);
			$login = $request->get('login');
			$hash = $this->toHash($request->get('password'));			
			
			if ($this->model->Signup($login, $hash) === true)
			{
				$this->SetCookies($login, $hash);			
				$this->PostMessage('info', $this->lang->SignupSuccess());

				$response = new \stdClass();
				$response->html = $this->model->HandleState($this);
				$response->login = $login;
			}
			
		} catch (\Exception $ex) {
			
			$this->PostMessage('error', $this->lang->ErrorUnknown());
		}
		
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
		else
		{
			$this->stopwatch = new StopWatchStub();
		}
		
		$this->stopwatch->start('controller:init');
		
		$this->request = $request;
		$this->SetLang($locale);
		
		$this->em = $this->getDoctrine()->getManager();
		
		$this->stopwatch->start('model:init');		
		$this->model->Init($this->em, $this->lang, $request->cookies->get('login'), $request->cookies->get('hash'));	
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
		
		if ($state->message !== null) { $this->PostMessage('info', $state->message); }
		
		return $this->renderView('XoGameBundle:Views:board.html.php', array(			
			'board' => $state->board,
			'can_move' => $state->canMove,
			'can_replay' => $state->canReplay,
			'token' => $state->token,
			'make_move_url' => $this->generateUrl('makemove', array('locale' => $this->locale)),
			'leave_url' => $this->generateUrl('leave', array('locale' => $this->locale)),
			'replay_url' => $this->generateUrl('replay', array('locale' => $this->locale)),
			'accept_replay_url' => $this->generateUrl('accept_replay', array('locale' => $this->locale)),
			'main_url' => $this->generateUrl('main', array('locale' => $this->locale)),
			'login' => $this->model->login,
			'lang' => $this->lang));
		
	}
	
	private function FormLobbyBody($inviter = null, $invitee = null)
	{	
		try {
			
			$players = $this->model->GetLobbyPlayers();
			
		} catch (\Exception $ex) {
			
			$players = array();
			$this->PostMessage('error', $this->lang->ErrorUnknown());			
		}		
		
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
		$this->model->UpdateLobby();		
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
			
			
			$messages = $this->renderView('XoGameBundle:Views:messages.html.php', array('messages' => $messagesArray));
			$navbar = $this->renderView('XoGameBundle:Views:navbar.html.php', 
					array(	'lang' => $this->lang,
							'login' => $this->model->login,
							'signout_url' => $this->generateUrl('signout', array('locale' => $this->locale))));
			
			return $this->response->setContent($this->renderView('XoGameBundle:Views:layout.html.php', array(				
				'messages' => $messages,
				'navbar' => $navbar, 
				'content' => $body)));
		}
	}
	
}