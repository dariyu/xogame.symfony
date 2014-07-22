<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

interface IStateHandler {
	
	public function SetRoom(& $room);
	
	public function HandleError();	
	public function HandleLeavedByInviter();
	public function HandleLeavedByInvitee();
	public function HandleCanMove();
	public function HandleWaitMove();
	public function HandleLoss();
	public function HandleWin();	
	
	//public function HandleDeclined();
	//public function HandleInvited();

}


class Xo extends CI_Controller {
	
	public $localeUrls = array();	
	public $login = null;
	
	private $urls = array();	
	private $locale = '/ru';

	public function __construct() {
		parent::__construct();
		
		$this->urls = array(
			'lobby_url' => '',
			'signin_url' => '/signin',
			'invite_url' => '/send_invite',
			'invite_accept_url' => '/accept_invite',
			'invite_cancel_url' => '/cancel_invite',
			'board_leave_url' => '/leave_room',
			'board_make_move_url' => '/make_move',
			'board_replay_url' => '/replay_game',
			'update_url' => '/update');

		
		//$this->output->enable_profiler(TRUE);		
		$this->load->helper('url');
		$this->load->model('xo_model', '', true);
		
	}

	public function index()
	{	
		redirect($this->locale);
	}
	
	private function SetLang($lang)
	{
		$this->locale = '/'.$lang;
		$this->lang->load('labels', $lang == 'ru' ? 'russian' : 'english');		
		
		foreach($this->urls as $name => $url)
		{
			$this->localeUrls[$name] = $this->locale.$url;
		}
	}
	
	public function GetLocaleUrl($url)
	{
		return $this->locale.$url;
	}
	
	public function action_main_page($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{
			$this->RenderBoard();
		}
	}
	
	public function action_accept_invite($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{
			$action = $this->input->get_post('action', true);
						
			if ($action == 'to_accept')
			{			
				$this->xo_model->Accept($this->login);
			} else 
			{
				$this->xo_model->Decline($this->login);
			}
			
			$this->RenderBoard();
		}
	}
	
	public function action_send_invite($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{
			$inviteeLogin = $this->input->get_post('invitee', true);			
			
			if ($inviteeLogin !== false && $this->xo_model->Invite($this->login, $inviteeLogin) === true)
			{				
				$this->RenderAwaiting();
			} 
			else
			{				
				$viewData = array('error' => $this->lang->line('lobby_invite_error').': '.$inviteeLogin);
				$this->RenderLobby($viewData);
			}			
		}		
	}
	
	public function action_cancel_invite($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{
			if ($this->xo_model->CancelInvite($this->login))
			{
				$this->RenderLobby();				
			}
			else
			{
				$this->RenderAwaiting(array('error' => $this->lang->line('awaiting_cancel_invite_error')));
			}
		}
	}
	
	public function action_leave_room($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{
			$this->xo_model->LeaveRoom($this->login);			
			$this->RenderBoard();
		}
	}
	
	public function action_signin($lang)
	{
		$this->SetLang($lang);
		
		$actions = array();		
		$actions['to_signin'] = function (Xo & $this) {			
				
			$auth = $this->GetAuthFromPost();
			$result = $this->Login($auth['login'], $auth['hash']);
			
			log_message('debug', 'Login::result: '.print_r($result !== false ? 'true' : 'false', true));
			
			return $result; 			
		};		
		$actions['to_register'] = function (Xo & $this) { 
		
			$auth = $this->GetAuthFromPost();
			$result = $this->Register($auth['login'], $auth['hash']);			
			
			if ($result === true)
			{
				$result = $this->Login($auth['login'], $auth['hash']);
			}
			
			return $result;
		};		
		
		$action = $this->input->post('action', true);
		$result = false;
		
		if ($action !== false && key_exists($action, $actions) && ($result = $actions[$action]($this)) !== false)
		{			
			$this->RenderBoard();
		}
		else
		{
			$layoutData = $action !== false ? array('error' => $this->lang->line('login_login_error')) : array();
			
			$this->RenderSignin($layoutData);
		}		 
	}
	
	public function action_make_move($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{			
			$inviter = $this->input->get_post('inviter', true);
			$cell = $this->input->get_post('cell', true);
		
			if ($this->xo_model->WriteMove($this->login, $inviter, $cell) !== false) 
			{				
				$this->RenderBoard();
			}
			else
			{				
				$this->RenderBoard(array('error' => $this->lang->line('board_bad_move')));				
			}	
		}	
	}
	
	public function action_update($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{
			$this->RenderBoard();
		}		
	}
	
	public function action_replay($lang)
	{
		$this->SetLang($lang);
		
		if ($this->IsSignedElseRenderSignupView())
		{
			$result = $this->xo_model->Replay($this->login);
			
			$layoutData = array();
			if ($result !== true)
			{
				$layoutData['error'] = $this->lang->line('board_replay_error');
			}
			
			$this->RenderBoard($layoutData);
		}
	}
	
	private function RenderSignin($layoutData = array())
	{		
		$this->RenderContent($this->load->view('login_view', array('urls' => $this->localeUrls), true),
				array_merge(array('no_update' => true), $layoutData));

	}
	
	public function RenderBoard($layoutData = array())
	{		
		if ($this->xo_model->HandleGameState($this->login, new Handler($this->login, $this, $layoutData)) === false)		
		{
			$this->RenderAwaiting();
		}
	}	
	
	private function IsAwaiting()
	{		
		return $this->xo_model->IsAwaiting($this->login);
	}
	
	private function IsAccepting()
	{
		return $this->xo_model->IsAccepting($this->login);
	}

	
	public function RenderLobby($layoutData = array())
	{
		$all_players = $this->xo_model->GetLobby();
		
		$players = array();
		foreach ($all_players as $player)
		{
			if ($player->login != $this->login) { $players[] = $player; }
		}
		
		log_message('debug', 'data: '.print_r($layoutData, true));

		$viewData = array(
			'login' => $this->login, 
			'players' => $players,
			'urls' => $this->localeUrls);
		
		$room = $this->xo_model->GetRoom($this->login);
		if ($room !== false)
		{
			$viewData['invitee'] = $room->invitee_login;
		}
		
		$this->RenderContent($this->load->view('lobby_view', $viewData, true), $layoutData);

	}
	
	private function IsSignedElseRenderSignupView()
	{
		$this->LoginByCookies();
		
		if (!is_null($this->login))
		{
			return true;
		}
		else
		{	
			$this->RenderSignin(array('no_update' => true));
			return false;
		}		
	}
	
	public function RenderAwaiting($layoutData = array())
	{
		if ($this->IsAwaiting() === true)
		{
			$room = $this->xo_model->GetRoomByInviter($this->login);		
			$message = $this->lang->line('invite_accept_awaiting').' '.$room->invitee_login;	
			
			$viewData = array(
				'urls' => $this->localeUrls,
				'login' => $this->login, 
				'message' => $message);
			
			$this->RenderContent($this->load->view('awaiting_view', $viewData, true), $layoutData);
		}
		else
		{
			$this->RenderAccepting($layoutData);
		}
	}
	
	public function RenderAccepting($layoutData = array())
	{		
		if ($this->IsAccepting() === true)
		{
			$room = $this->xo_model->GetRoomByInvitee($this->login);
			$viewData = array(
				'urls' => $this->localeUrls,
				'login' => $this->login, 
				'message' => ($this->lang->line('invite_accepting').' '.$room->inviter_login));
			
			$this->RenderContent($this->load->view('accept_view', array_merge($viewData), true), $layoutData);		
		}
		else 
		{
			$this->RenderLobby($layoutData);			
		}
	}

	
	private function SetCookies($login, $pass)
	{
		$this->input->set_cookie(array(
			'name' => 'login',
			'value' => $login,
			'expire' => '0',
			'path' => '/')); 

		$this->input->set_cookie(array(
			'name' => 'hash',	
			'value' => $pass,
			'expire' => '0',
			'path' => '/'));
	}
	
	private function Login($login, $hash)
	{		
		$result = $this->xo_model->Login($login, $hash);
		
		if ($result === false)
		{
			return false;
		} 
		else {
			
			$this->login = $login;
			$this->SetCookies($login, $hash);
			return true; 
			
		}		
	}
	
	private function LoginByCookies()
	{
		$login = $this->input->cookie('login', true);
		$hash = $this->input->cookie('hash', true);					
		
		if ($login !== false && $hash !== false && $this->xo_model->Login($login, $hash) === true)
		{	
			$this->login = $login;
			return true;
		} 
		else 
		{
			return $this->lang->line('login_login_error');		
		}
	}	

	private function Register($login, $hash)
	{		
		$result = $this->xo_model->Register($login, $hash);
		
		if ($result === false) {
			
			return $this->lang->line('login_register_error');
		}
		else { return true; }
	}
	
	private function GetAuthFromPost()
	{
		return array(
			'login' => $this->input->post('login', true),
			'hash' => sha1($this->input->post('password', true)));		

	}
	
	public function RenderContent($content, $data = array())
	{
		$ajax = $this->input->get_post('ajax', true);				
		
		$navbar = $this->load->view('navbar_view', array('login' => $this->login), true);
		
		$layout = $ajax == 1 ? 'ajax_layout_view' : 'layout_view';
		$this->load->view($layout, array_merge($data, 
				array('content' => $navbar.$content, 'title' => 'Xo Game', 'urls' => $this->localeUrls)));
	}
}


class Handler implements IStateHandler
{			
	private $room = null;
	private $login = null;
	private $ctrl = null;
	private $layoutData = null;
	private $viewData = null;

	function __construct($login, Xo & $ctrl, $layoutData)
	{
		$this->login = $login;
		$this->ctrl = $ctrl;
		$this->layoutData = $layoutData;

		$this->viewData = array(
			'urls' => $this->ctrl->localeUrls,					
			'login' => $this->login,					
			'can_move' => false,
			'show_replay_button' => false);

	}

	public function SetRoom(& $room) { $this->room = $room; $this->viewData['room'] = $room; }

	public function HandleInvited()
	{
		$this->ctrl->RenderAwaiting();
	}

	public function HandleError()
	{
		$this->ctrl->RenderAwaiting();
	}

	public function HandleDeclined()
	{				
		$this->ctrl->xo_model->Decline($this->login);
		$extData = array_merge($this->layoutData, array('info' => $this->ctrl->lang->line('invite_declined')));
		$this->ctrl->RenderAwaiting($extData);
	}

	public function HandleLeavedByInviter()
	{
		if ($this->login == $this->room->invitee_login)
		{				
			$extData = array_merge($this->layoutData, array('info' => $this->ctrl->lang->line('board_rival_left')));
			
			$viewData = $this->viewData;
			$viewData['state'] = $this->room->board;
			$this->ctrl->RenderContent($this->ctrl->load->view('board_view', $viewData, true), $extData);
		} else
		{
			$this->ctrl->RenderAwaiting($this->layoutData);
		}
	}

	public function HandleLeavedByInvitee()
	{
		if ($this->login == $this->room->inviter_login)
		{				
			$extData = array_merge($this->layoutData, array('info' => $this->ctrl->lang->line('board_rival_left')));					
			
			$viewData = $this->viewData;
			$viewData['state'] = $this->room->board;
			$this->ctrl->RenderContent($this->ctrl->load->view('board_view', $viewData, true), $extData);
		}
		else
		{
			$this->ctrl->RenderAwaiting($this->layoutData);
		}
	}

	public function HandleCanMove()
	{
		$this->viewData['can_move'] = true;

		$layoutData = array_merge($this->layoutData, array(
			'info' => '<span class="glyphicon glyphicon-circle-arrow-right"></span> '.$this->ctrl->lang->line('board_your_move')));

		$this->ctrl->RenderContent($this->ctrl->load->view('board_view', $this->viewData, true), $layoutData);				
	}

	public function HandleWaitMove()
	{
		$this->viewData['can_move'] = false;

		$layoutData = array_merge($this->layoutData, array(
			'info' => '<span class="glyphicon glyphicon-time"></span> '.$this->ctrl->lang->line('board_rivals_move')));

		$this->ctrl->RenderContent($this->ctrl->load->view('board_view', $this->viewData, true), $layoutData);				

	}
	public function HandleLoss()
	{
		$this->viewData['can_move'] = false;
		$this->viewData['show_replay_button'] = true;				

		$layoutData = array_merge($this->layoutData, array(
			'info' => '<span class="glyphicon glyphicon-thumbs-down"></span> '.$this->ctrl->lang->line('board_loss')));				

		$this->ctrl->RenderContent($this->ctrl->load->view('board_view', $this->viewData, true), $layoutData);				
	}

	public function HandleWin()
	{
		$this->viewData['can_move'] = false;
		$this->viewData['show_replay_button'] = true;

		$layoutData = array_merge($this->layoutData, array(
			'info' => '<span class="glyphicon glyphicon-flag"></span> '.$this->ctrl->lang->line('board_win')));

		$this->ctrl->RenderContent($this->ctrl->load->view('board_view', $this->viewData, true), $layoutData);				
	}

}
