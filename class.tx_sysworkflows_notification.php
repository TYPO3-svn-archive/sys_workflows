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

include_once(t3lib_extMgm::extPath('gabriel','class.tx_gabriel_event.php'));
include_once(PATH_site.'tslib/class.tslib_content.php');




class tx_sysworkflows_notification extends tx_gabriel_event {

	var $target;
	var $reminder;
	var $sendingUserUid;
	var $workflowUid;
	var $subject;
	var $plainText;
	var $html;
	var $from_email;
	var $from_name;
	var	$returnPath;

	/**
	 * PHP4 wrapper for constructor, 
	 * have to be here evne though the constructor is not defined in the derived class, 
	 * else the constructor of the parent class will not be called in PHP4
	 *
	 */
	function tx_sysworkflows_notification() {
		$this->__construct();
	}

	function execute() {
		require_once(PATH_t3lib.'class.t3lib_htmlmail.php');
		$email = t3lib_div::makeInstance('t3lib_htmlmail');
		$email->start();
		$email->useBase64();
		$email->subject = $this->subject;
		$email->from_email = $this->from_email;
		$email->from_name = $this->from_name;
		$email->returnPath = $this->returnPath;
		$email->addPlain($this->plainText);
		$email->setHTML($email->encodeMsg($this->html));
		if($this->reminder) {
			$this->reminder($email);
		}
		$rcpArray = $this->getRecipients();
		$email->setHeaders();
		$email->add_header('CC: '.$this->ccRcps);
		
		$email->setContent();
		foreach ($rcpArray as $rcp) {
			$email->recipient = $rcp;
			$email->sendTheMail();
		}

	}

	function notification(&$email) {

	}

	function reminder(&$email) {
		if ($this->isWorkflowFinished()) {
			$this->remove();
		} else {


		}
	}

	function getCurrentTarget() {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid_foreign','sys_todos_users_mm','uid_local='.intval($this->workflowUid));
		if(is_array($row[0])) {
			return $row[0]['uid_foreign'];
		} else {
			return null;
		}
	}


	function isWorkflowFinished() {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('finished','sys_todos','uid='.intval($this->workflowUid));
		if(is_array($row[0])) {
			return $row[0]['finished']?true:false;
		} else {
			return null;
		}
	}

	function setReminderStatus() {
		$this->reminder = true;
	}

	function setTarget($target) {
		$this->target = $target;
	}

	function setSendingUserUid($userUid) {
		$this->sendingUserUid = $userUid;
	}

	function setWorkflowUid($workflowUid) {
		$this->workflowUid = $workflowUid;
	}

	function setSubject($subject) {
		$this->subject = $subject;
	}

	function setFrom_email($from_email) {
		$this->from_email = $from_email;
	}

	function setFrom_name($from_name) {
		$this->from_name = $from_name;
	}

	function setReturnPath($returnPath) {
		$this->returnPath = $returnPath;
	}

	function setHtmlMessage($html) {
		$this->html = $html;
	}

	function setPlainTextMessage($plainText) {
		$this->plainText = $plainText;
	}
	function getRecipients() {

		$rcpArray = array();
		$target = $this->target?$this->target:$this->getCurrentTarget();

		if ($target >= 0) {
			// Ordinary user
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,realName,email', 'be_users',
			'uid='.intval($target).t3lib_BEfunc::deleteClause('be_users')
			);
		}
		if ($target < 0) {
			// Users in group
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,realName,email',
			'be_users',
			$GLOBALS['TYPO3_DB']->listQuery('usergroup_cached_list', abs($target), 'be_users').t3lib_BEfunc::deleteClause('be_users')
			);
		}

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (strstr($row['email'], '@') && $row['uid'] != $this->sendingUserUid) {
				// the user must have an email address and mails are not sent to the creating user, should he be in the group.
				$rcpArray[] = $row['realName'].' <'.$row['email'].'>';
			}
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,realName,email',
		'be_users,sys_todos_notify_users_mm',
		'uid=uid_foreign AND uid_local='.intval($this->workflowUid).t3lib_BEfunc::deleteClause('be_users')
		);


		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (strstr($row['email'], '@') && $row['uid'] != $this->sendingUserUid) {
				// the user must have an email address and mails are not sent to the creating user, should he be in the group.
				$ccRcps[] = $row['realName'].' <'.$row['email'].'>';
			}
		}
		
		$this->ccRcps = implode(',',$ccRcps);
		return $rcpArray;
	}


}


?>