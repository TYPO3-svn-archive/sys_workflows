<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Christian Jul Jensen (julle(at)typo3(dot)org)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * $Id$
 * 
 * @TODO: write something clever here!
 *
 * @author	Christian Jul Jensen <julle(at)typo3(dot)org>
 */


class tx_sysworkflows_notifier {


	var $stateRecord;
	var $target;

	function setStateRecord($stateRecordArray) {
		$this->stateRecord = $stateRecordArray;
	}

	function setTarget($target) {
		$this->target = $target;
	}

	function exec_create() {
		$this->createNotifications('You have been assigned a new workflow','You have been assigned a new workflow');
	}

	function exec_comment_workflow() {
		$this->createNotifications('A workflow have been commented','A workflow have been commented');
	}

	function exec_begin() {
		$this->createNotifications('Workflow has been started','Workflow has been started');
	}

	function exec_end() {
		$this->createNotifications('Workflow has been ended','');
	}

	function exec_passon() {
		$this->createNotifications('Workflow has been passd on','');
	}

	function exec_reject() {
		$this->createNotifications('Workflow has been rejected','');
	}

	function exec_review() {
		$this->createNotifications('Workflow has been sent to review','');
	}

	function exec_reset() {
		$this->createNotifications('Workflow has been reset','');
	}

	function exec_finalize() {
		$this->createNotifications('Workflow has been published','');
	}

	function exec_newinstance() {
		$this->createNotifications('New instance of workflow has been created','');
	}

	function reminders($recurring) {
		$this->createNotifications('Reminder','this is a reminder',$recurring);
	}

	function createNotifications($subject,$text,$recurring=null) {
		$user = $GLOBALS['BE_USER']->user;
		if($this->stateRecord['uid']) {
			$text .="\r\n".t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir.'index.php?redirect_url=alt_main.php%3Fmodule%3Duser_task%26modParams%3Dsys_todos_uid%3D'.$this->stateRecord['uid'];
		}


		$notification = t3lib_div::getUserObj('EXT:sys_workflows/class.tx_sysworkflows_notification.php:tx_sysworkflows_notification');

		if ($recurring!=null) {
			$notification->registerRecurringExecution(time()+$recurring,$recurring,strtotime('+10 years'));
			$notification->setTarget(null);
			$notification->setSendinguserUid(0);
			$notification->setReminderStatus();

		} else {
			$notification->registerSingleExecution(time());
			#$notification->setTarget($this->target);
			$notification->setSendinguserUid($GLOBALS['BE_USER']->user['uid']);
		}

		$notification->setWorkflowUid($this->stateRecord['uid']);

		$notification->setSubject($subject);
		$notification->setFrom_email($user['email']);
		$notification->setFrom_name($user['realName']);
		$notification->setReturnPath((trim($user['realName'])?$user['realName']:$user['realName']).' <'.$this->user['email'].'>');

		$notification->setPlainTextMessage($text);
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$notification->setHtmlMessage('<html><body>'.nl2br($cObj->http_makelinks($text,array())).'</body></html>');
		$gabriel = t3lib_div::getUserObj('EXT:gabriel/class.tx_gabriel.php:&tx_gabriel');
		$gabriel->addEvent($notification,'sys_workflows '.time().'_'.$this->stateRecord['uid']);
	}

}

?>