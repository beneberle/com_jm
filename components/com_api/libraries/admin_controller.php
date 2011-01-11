<?php
/**
 * @version		$Id
 * @package		Joomla
 * @subpackage	com_api
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

class ApiControllerAdmin extends ApiController {
	
	public function __construct($config=array()) {
		parent::__construct($config);
		$this->registerTask('apply', 'save');
	}
	
	public function display() {
		$app	= JFactory::getApplication();
		$view 	= JRequest::getVar('view', '');
		if (!$view) :
			JRequest::setVar('view', 'cpanel');
		endif;
		
		parent::display();
	
	}
	
	public function cancel() {
		JRequest::checkToken() or jexit(JText::_('INVALID_TOKEN'));
		$this->setRedirect(JRequest::getVar('ret', 'index.php?option='.$this->get('option')), $msg);
	}
	
	public function save() {
		JRequest::checkToken() or jexit(JText::_('INVALID_TOKEN'));
		$name	= $this->getEntityName();
		$post 	= JRequest::get('post');
		$model 	= $this->getModel($name);
		
		if (!$item = $model->save($post)) :
			$msg = $model->getError();
			$url = JRequest::getVar('HTTP_REFERER', 'index.php', 'server');
			$this->setRedirect($url, $msg, 'error');
			return;
		endif;
		
		$name = strtolower($name);
		$msg = JText::_("COM_API_SAVE_SUCCESSFUL");
		if($this->getTask() == 'apply') :
			$url = "index.php?option=".$this->get('option')."&view=".$name."&cid[]=".$item->id;
		elseif (isset($post['ret'])) :
			$url = $post['ret'];
		else :
			$url = JRequest::getVar('HTTP_REFERER', 'index.php', 'server');
		endif;
		$this->setRedirect($url, $msg);
	}
	
	public function publish() {
		JRequest::checkToken() or jexit(JText::_('INVALID_TOKEN'));
		$this->changeState(1);
		
		if ($error = $this->getError()) :
			$msg = $error;
			$type = 'error';
		else :
			$msg = JText::_("COM_API_PUBLISH_SUCCESS");
			$type = 'message';
		endif;
		
		$this->setRedirect(JRequest::getVar('HTTP_REFERER', 'index.php', 'server'), $msg, $type);
	}
	
	public function unpublish() {
		JRequest::checkToken() or jexit(JText::_('INVALID_TOKEN'));
		$this->changeState(0);
		if ($error = $this->getError()) :
			$msg = $error;
			$type = 'error';
		else :
			$msg = JText::_("COM_API_UNPUBLISH_SUCCESS");
			$type = 'message';
		endif;
		
		$this->setRedirect(JRequest::getVar('HTTP_REFERER', 'index.php', 'server'), $msg, $type);
	}
	
	protected function changeState($state, $cids=array(), $table_class=null) {
		if (empty($cids)) :
			$cids = JRequest::getVar('cid', array(), 'post', 'array');
		endif;
		
		$table_class = $table_class ? $table_class : $this->getEntityName();
		
		$table 	= JTable::getInstance($table_class, 'ApiTable');
		
		if (!$table->publish($cids, $state)) :
			$this->setError($table->getError());
			return false;
		endif;
		
		return true;
	}
	
	protected function getEntityName() {
		preg_match( '/(.*)Controller(.*)/i', get_class( $this ), $r );
		if (!isset($r[2])) :
			return $r[1];
		else :
			return $r[2];
		endif;
	}
	
}