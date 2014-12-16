<?php

namespace Xo\GameBundle\View;

use Symfony\Component\HttpFoundation;

use Xo\GameBundle\Model;
use Xo\GameBundle\Entity;
use Xo\GameBundle\Abstraction;


class XoView implements Abstraction\IStateHandler {

	private $lang;
	private $login;
	private $locale;
	private $renderer;
	private $model;
	private $messages = array();
	private $cookies = array();

	public function __construct(Model\Game & $model, Abstraction\IRenderer & $renderer) {
		$this->model = $model;
		$this->lang = $model->lang;
		$this->renderer = $renderer;
		$this->locale = $model->locale;
		$this->login = $model->login;
	}

	public function HandleSignin()
	{
		$body = $this->renderer->RenderTemplate('XoGameBundle:Views:signin.html.php', array(
			'login' => $this->login,
			'lang' => $this->lang,
			'signin_url' => $this->renderer->MakeUrl('signin', array('locale' => $this->locale)),
			'signup_url' => $this->renderer->MakeUrl('signup', array('locale' => $this->locale))));

		return $body;
	}

	public function HandleBoard(Entity\RoomState $state) {

		$stateMessages = array (
			Entity\RoomState::STATE_WIN => $this->lang->BoardWin(),
			Entity\RoomState::STATE_LOSS => $this->lang->BoardLoss(),
			Entity\RoomState::STATE_DRAW => $this->lang->BoardDraw(),
			Entity\RoomState::STATE_RIVALS_MOVE => $this->lang->BoardRivalsMove(),
			Entity\RoomState::STATE_YOUR_MOVE => $this->lang->BoardYourMove()
		);

		$this->PostMessage('info', $stateMessages[$state->code]);

		return $this->renderer->RenderTemplate('XoGameBundle:Views:board.html.php', array(
			'board' => $state->board,
			'can_move' => $state->canMove,
			'can_replay' => $state->canReplay,
			'token' => $state->token,
			'make_move_url' => $this->renderer->MakeUrl('makemove', array('locale' => $this->locale)),
			'leave_url' => $this->renderer->MakeUrl('leave', array('locale' => $this->locale)),
			'quit_board_url' => $this->renderer->MakeUrl('quit_board', array('locale' => $this->locale)),
			'replay_url' => $this->renderer->MakeUrl('replay', array('locale' => $this->locale)),
			'accept_replay_url' => $this->renderer->MakeUrl('accept_replay', array('locale' => $this->locale)),
			'main_url' => $this->renderer->MakeUrl('main', array('locale' => $this->locale)),
			'login' => $this->login,
			'lang' => $this->lang));

	}

	private function FormLobbyBody($inviter = null, $invitee = null)
	{
		$this->model->KeepAlive();
		$this->model->RemoveInactivePlayers();

		$players = $this->model->GetLobbyPlayers();

		return $this->renderer->RenderTemplate('XoGameBundle:Views:lobby.html.php', array(
			'inviter' => $inviter,
			'invitee' => $invitee,
			'players' => $players,
			'quit_url' => $this->renderer->MakeUrl('quit_lobby', array('locale' => $this->locale)),
			'main_url' => $this->renderer->MakeUrl('main', array('locale' => $this->locale)),
			'keepalive_url' => $this->renderer->MakeUrl('keepalive', array('locale' => $this->locale)),
			'accept_url' => $this->renderer->MakeUrl('accept', array('locale' => $this->locale)),
			'invite_url' => $this->renderer->MakeUrl('invite', array('locale' => $this->locale)),
			'cancel_url' => $this->renderer->MakeUrl('cancel', array('locale' => $this->locale)),
			'decline_url' => $this->renderer->MakeUrl('decline', array('locale' => $this->locale)),
			'login' => $this->login,
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

	public function HandleLeft(Entity\RoomState $state) {

		$this->PostMessage('info', $this->lang->BoardLeft());
		return $this->HandleBoard($state);
	}

	public function SetCookies($login, $hash)
	{
		$this->cookies['login'] = $login;
		$this->cookies['hash'] = $hash;
	}

	private function InjectCookies(HttpFoundation\Response & $response)
	{
		foreach ($this->cookies as $name => $val)
		{
			$response->headers->setCookie(new HttpFoundation\Cookie($name, $val));
		}
	}

	public function FormJsonResponse($data = null)
	{
		$this->model->Flush();
		$obj = new \stdClass();
		$obj->messages = array_merge($this->messages, $this->model->messages);

		if ($data !== null) { $obj->response = $data; }

		$response = new HttpFoundation\JsonResponse($obj);
		$this->InjectCookies($response);
		return $response;
	}

	private function PostMessage($type, $body)
	{		
		$newMessage = new \stdClass();
		$newMessage->type = $type;
		$newMessage->body = $body;

		$this->messages[] = $newMessage;
	}

	public function RenderResponse(HttpFoundation\Request & $request)
	{
		$this->model->Flush();
		$body = $this->model->HandleState($this);
		$messagesArray = array_merge($this->messages, $this->model->messages);

		$response = null;

		if ($request->isXmlHttpRequest())
		{
			$data = new \stdClass();
			$data->html = $body;
			$data->messages = $messagesArray;
			$response = new HttpFoundation\JsonResponse($data);
		}
		else
		{
			$messages = $this->renderer->RenderTemplate('XoGameBundle:Views:messages.html.php', array(
				'messages' => $messagesArray));

			$navbar = $this->renderer->RenderTemplate('XoGameBundle:Views:navbar.html.php', array(
				'lang' => $this->lang,
				'login' => $this->model->login,
				'signout_url' => $this->renderer->MakeUrl('signout', array('locale' => $this->locale))));

			$response = new HttpFoundation\Response();
			$response->setContent($this->renderer->RenderTemplate('XoGameBundle:Views:layout.html.php', array(
				'messages' => $messages,
				'navbar' => $navbar,
				'content' => $body)));
		}

		$this->InjectCookies($response);
		return $response;
	}

} 