<?php


namespace Xo\GameBundle\Controller;

use Symfony\Component\HttpFoundation;

use Xo\GameBundle\Model\HydnaLayer as Model;
use Xo\GameBundle\View\XoView;


class MainController extends BaseController {

	public function indexAction()
	{
		return $this->redirect($this->generateUrl("main", array('locale' => Model::DEFAULT_LOCALE)));
	}
	
	public function mainAction($locale, HttpFoundation\Request $request)
	{
		$model = $this->GetModel($locale, $request);
		$view = new XoView($model, $this);
		return $view->RenderResponse($request);
	}
}