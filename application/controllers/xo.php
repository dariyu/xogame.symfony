<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(E_ALL);
ini_set("display_errors", 1);

//error_log('message');

class Xo extends CI_Controller {
	
	private $login = null;
	private $urls = array();
	private $localeUrls = array();	
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
		
			if ($this->IsMyMove() === true && $this->xo_model->WriteMove($this->login, $inviter, $cell) !== false) 
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
	
	private function RenderBoard($layoutData = array())
	{
		$room = $this->xo_model->GetRoom($this->login);
		
		log_message('debug', 'RenderBoard::room: '.print_r($room, true));
		
		if ($room !== false)
		{
			$state = null;
			
			$viewData = array(
				'urls' => $this->localeUrls,
				'state' => $room->board,
				'login' => $this->login, 
				'room' => $room, 
				'can_move' => false,
				'show_replay_button' => false);
			
			if ($room->state == 'playing' && !is_null($state = $this->xo_model->GetGameState($this->login)))
			{			
				$phraseArray = array(
					Xo_Model::STATE_CAN_MOVE => '<span class="glyphicon glyphicon-circle-arrow-right"></span> '.$this->lang->line('board_your_move'),
					Xo_Model::STATE_WAIT_MOVE => '<span class="glyphicon glyphicon-time"></span> '.$this->lang->line('board_rivals_move'),
					Xo_Model::STATE_WIN => '<span class="glyphicon glyphicon-time"></span> '.$this->lang->line('board_win'),
					Xo_Model::STATE_LOSS => '<span class="glyphicon glyphicon-time"></span> '.$this->lang->line('board_loss')
				);

				$movePhrase = $phraseArray[$state];

				$viewData['can_move'] = $state == Xo_Model::STATE_CAN_MOVE;
				$viewData['show_replay_button'] = $state == Xo_Model::STATE_WIN || $state == Xo_Model::STATE_LOSS;

				$layoutData = array_merge($layoutData, array('info' => $movePhrase));
				$this->RenderContent($this->load->view('board_view', $viewData, true), $layoutData);
			}
			elseif (($room->state == 'leaved_by_invitee' && $this->login == $room->inviter_login) ||
					($room->state == 'leaved_by_inviter' && $this->login == $room->invitee_login))
			{
				$extData = array_merge($layoutData, array('info' => $this->lang->line('board_rival_left')));
				$this->RenderContent($this->load->view('board_view', $viewData, true), $extData);
				
			}
			elseif ($room->state == 'declined' && $this->login == $room->inviter_login)
			{
				$this->xo_model->Decline($this->login);
				$extData = array_merge($layoutData, array('info' => $this->lang->line('invite_declined')));
				$this->RenderAwaiting($extData);				
			}
			else { $this->RenderAwaiting(); }
			
		} 
		else
		{
			$this->RenderAwaiting();
		}
	}
	
	
	private function IsAwaiting()
	{		
		$room = $this->xo_model->GetRoomByInviter($this->login);		
		
		log_message('debug', 'IsAwaiting::room: '.print_r($room, true));
		
		return ($room !== false && $room->inviter_login == $this->login && $room->state == 'invited') ? true : false;
	}
	
	private function IsAccepting()
	{
		$room = $this->xo_model->GetRoomByInvitee($this->login);	
		return ($room !== false && $room->invitee_login == $this->login && $room->state == 'invited') ? true : false;
	}

	
	private function RenderLobby($layoutData = array())
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
	
	private function IsMyMove()
	{
		return $this->xo_model->GetGameState($this->login) == Xo_Model::STATE_CAN_MOVE;
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
	
	private function RenderAwaiting($layoutData = array())
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
	
	private function RenderAccepting($layoutData = array())
	{		
		if ($this->IsAccepting() === true)
		{
			$room = $this->xo_model->GetRoom($this->login);
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
	
	private function RenderContent($content, $data = array())
	{
		$ajax = $this->input->get_post('ajax', true);				
		
		$navbar = $this->load->view('navbar_view', array('login' => $this->login), true);
		
		$layout = $ajax == 1 ? 'ajax_layout_view' : 'layout_view';
		$this->load->view($layout, array_merge($data, 
				array('content' => $navbar.$content, 'title' => 'Xo Game', 'urls' => $this->localeUrls)));
	}
	

}