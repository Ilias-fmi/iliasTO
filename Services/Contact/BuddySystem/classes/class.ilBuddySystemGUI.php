<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/JSON/classes/class.ilJsonUtil.php';
require_once 'Services/Contact/BuddySystem/exceptions/class.ilBuddySystemException.php';

/**
 * Class ilBuddySystemGUI
 * @author Michael Jansen <mjansen@databay.de>
 * @ilCtrl_isCalledBy ilBuddySystemGUI: ilUIPluginRouterGUI, ilPublicUserProfileGUI
 */
class ilBuddySystemGUI
{
	const BS_REQUEST_HTTP_GET  = 1;
	const BS_REQUEST_HTTP_POST = 2;

	/**
	 * @var bool
	 */
	protected static $frontend_initialized = false;

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilBuddyList
	 */
	protected $buddylist;

	/**
	 * @var ilBuddySystemRelationStateFactory
	 */
	protected $statefactory;

	/**
	 * @var ilObjUser
	 */
	protected $user;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * 
	 */
	public function __construct()
	{
		/**
		 * @var $ilCtrl ilCtrl
		 * @var $ilUser ilObjUser
		 * @var $lng    ilLanguage
		 */
		global $ilCtrl, $ilUser, $lng;

		$this->ctrl = $ilCtrl;
		$this->user = $ilUser;
		$this->lng  = $lng;

		require_once 'Services/Contact/BuddySystem/classes/class.ilBuddyList.php';
		require_once 'Services/Contact/BuddySystem/classes/states/class.ilBuddySystemRelationStateFactory.php';
		$this->buddylist     = ilBuddyList::getInstanceByGlobalUser();
		$this->statefactory  = ilBuddySystemRelationStateFactory::getInstance();

		$this->lng->loadLanguageModule('buddysystem');
	}

	/**
	 *
	 */
	public static function initializeFrontend()
	{
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilCtrl ilCtrl
		 * @var $lng    ilLanguage
		 */
		global $tpl, $ilCtrl, $lng;

		if(!self::$frontend_initialized)
		{
			$lng->loadLanguageModule('buddysystem');

			require_once 'Services/JSON/classes/class.ilJsonUtil.php';

			$tpl->addJavascript('./Services/Contact/BuddySystem/js/buddy_system.js');

			$config = new stdClass();
			$config->http_post_url        = $ilCtrl->getFormActionByClass(array('ilUIPluginRouterGUI', 'ilBuddySystemGUI'), '', '', true, false);
			$config->transition_state_cmd = 'transition';
			$tpl->addOnLoadCode("il.BuddySystem.setConfig(".ilJsonUtil::encode($config).");");

			$btn_config = new stdClass();
			$btn_config->bnt_class = 'ilBuddySystemLinkWidget';

			$tpl->addOnLoadCode("il.BuddySystemButton.setConfig(".ilJsonUtil::encode($btn_config).");");
			$tpl->addOnLoadCode("il.BuddySystemButton.init();");

			self::$frontend_initialized = true;
		}
	}

	/**
	 * @throws RuntimeException
	 */
	public function executeCommand()
	{
		if($this->user->isAnonymous())
		{
			throw new RuntimeException('This controller only accepts requests of logged in users');
		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd        = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				$cmd .= 'Command';
				$this->$cmd();
				break;
		}
	}

	/**
	 * @param string $key
	 * @param int $type
	 * @return bool
	 */
	protected function isRequestParameterGiven($key, $type)
	{
		switch($type)
		{
			case self::BS_REQUEST_HTTP_POST:
				return isset($_POST[$key]) && strlen($_POST[$key]);
				break;

			case self::BS_REQUEST_HTTP_GET:
			default:
				return isset($_GET[$key]) && strlen($_GET[$key]);
				break;
		}
	}

	/**
	 *
	 */
	private function requestCommand()
	{
		if(!$this->isRequestParameterGiven('user_id', self::BS_REQUEST_HTTP_GET))
		{
			ilUtil::sendInfo($this->lng->txt('buddy_bs_action_not_possible'), true);
			$this->ctrl->returnToParent($this);
		}

		try
		{
			require_once 'Services/Contact/BuddySystem/classes/class.ilBuddyList.php';
			$relation = ilBuddyList::getInstanceByGlobalUser()->getRelationByUserId((int)$_GET['user_id']);

			// The ILIAS JF decided to add a new personal setting
			if($relation->isUnlinked() && !ilUtil::yn2tf(ilObjUser::_lookupPref($relation->getBuddyUserId(), 'bs_allow_to_contact_me')))
			{
				throw new ilException("The requested user does not want to get contact requests");
			}

			ilBuddyList::getInstanceByGlobalUser()->request($relation);
			ilUtil::sendSuccess($this->lng->txt('buddy_relation_requested'), true);
		}
		catch(ilException $e)
		{
			ilUtil::sendInfo($this->lng->txt('buddy_bs_action_not_possible'), true);
		}

		$this->ctrl->returnToParent($this);
	}

	/**
	 * 
	 */
	private function ignoreCommand()
	{
		if(!$this->isRequestParameterGiven('user_id', self::BS_REQUEST_HTTP_GET))
		{
			ilUtil::sendInfo($this->lng->txt('buddy_bs_action_not_possible'), true);
			$this->ctrl->returnToParent($this);
		}

		try
		{
			require_once 'Services/Contact/BuddySystem/classes/class.ilBuddyList.php';
			ilBuddyList::getInstanceByGlobalUser()->ignore(
				ilBuddyList::getInstanceByGlobalUser()->getRelationByUserId((int)$_GET['user_id'])
			);
			ilUtil::sendSuccess($this->lng->txt('buddy_request_ignored'), true);
		}
		catch(ilException $e)
		{
			ilUtil::sendInfo($this->lng->txt('buddy_bs_action_not_possible'), true);
		}

		$this->ctrl->returnToParent($this);
	}

	/**
	 * 
	 */
	private function linkCommand()
	{
		if(!$this->isRequestParameterGiven('user_id', self::BS_REQUEST_HTTP_GET))
		{
			ilUtil::sendInfo($this->lng->txt('buddy_bs_action_not_possible'), true);
			$this->ctrl->returnToParent($this);
		}

		try
		{
			require_once 'Services/Contact/BuddySystem/classes/class.ilBuddyList.php';
			ilBuddyList::getInstanceByGlobalUser()->link(
				ilBuddyList::getInstanceByGlobalUser()->getRelationByUserId((int)$_GET['user_id'])
			);
			ilUtil::sendSuccess($this->lng->txt('buddy_request_approved'), true);
		}
		catch(ilException $e)
		{
			ilUtil::sendInfo($this->lng->txt('buddy_bs_action_not_possible'), true);
		}

		$this->ctrl->returnToParent($this);
	}

	/**
	 * Performs a state transition based on the request action
	 */
	private function transitionCommand()
	{
		/**
		 * @var $lng ilLanguage
		 */
		global $lng;

		if(!$this->ctrl->isAsynch())
		{
			throw new RuntimeException('This action only supports AJAX http requests');
		}

		if(!isset($_POST['usr_id']) || !is_numeric($_POST['usr_id']))
		{
			throw new RuntimeException('Missing "usr_id" parameter');
		}

		if(!isset($_POST['action']) || !strlen($_POST['action']))
		{
			throw new RuntimeException('Missing "action" parameter');
		}

		$response = new stdClass();
		$response->success = false;

		try
		{
			$usr_id = (int)$_POST['usr_id'];
			$action = $_POST['action'];

			if(ilObjUser::_isAnonymous($usr_id))
			{
				throw new ilBuddySystemException(sprintf("You cannot perform a state transition for the anonymous user (id: %s)", $usr_id));
			}

			if(!strlen(ilObjUser::_lookupLogin($usr_id)))
			{
				throw new ilBuddySystemException(sprintf("You cannot perform a state transition for a non existing user (id: %s)", $usr_id));
			}

			$relation = $this->buddylist->getRelationByUserId($usr_id);

			// The ILIAS JF decided to add a new personal setting 
			if($relation->isUnlinked() && !ilUtil::yn2tf(ilObjUser::_lookupPref($relation->getBuddyUserId(), 'bs_allow_to_contact_me')))
			{
				throw new ilException("The requested user does not want to get contact requests");
			}

			try
			{
				$this->buddylist->{$action}($relation);
				$response->success = true;
			}
			catch(Exception $e)
			{
				$response->message = $lng->txt('buddy_bs_action_not_possible');
			}

			$response->state      = get_class($relation->getState());
			$response->state_html = $this->statefactory->getRendererByOwnerAndRelation($this->buddylist->getOwnerId(), $relation)->getHtml();
		}
		catch(Exception $e)
		{
			$response->message = $lng->txt('buddy_bs_action_not_possible');
		}

		echo ilJsonUtil::encode($response);
		exit();
	}
}