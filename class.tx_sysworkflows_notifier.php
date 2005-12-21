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

	function setStateRecord($stateRecordArray) {
		$this->stateRecord = $stateRecordArray;
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
		$this->createNotifications('Workflow has been sen to review','');
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

	function createNotifications($subject,$text) {
		$user = $GLOBALS['BE_USER']->user;
		$rcpArray = $this->getRecipients();
		foreach ($rcpArray as $rcp) {
			$notification = t3lib_div::getUserObj('EXT:sys_workflows/class.tx_sysworkflows_notification.php:tx_sysworkflows_notification');
			$notification->registerSingleExecution(time());
			$notification->setRcp($rcp);
			$notification->setSubject($subject);
			$notification->setFrom_email($user['email']);
			$notification->setFrom_name($user['realName']);
			$notification->setReturnPath((trim($user['realName'])?$user['realName']:$user['realName']).' <'.$this->user['email'].'>');
			if($this->stateRecord['uid']) {
				$text .="\r\n".t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir.'index.php?redirect_url=alt_main.php%3Fmodule%3Duser_task%26modParams%3Dsys_todos_uid%3D'.$this->stateRecord['uid'];
			}

			$notification->setPlainTextMessage($text);
			$cObj = t3lib_div::makeInstance('tslib_cObj');
			$notification->setHtmlMessage('<html><body>'.nl2br($cObj->http_makelinks($text,array())).'</body></html>');

			$gabriel = t3lib_div::getUserObj('EXT:gabriel/class.tx_gabriel.php:&tx_gabriel');
			$gabriel->addEvent($notification,'sys_workflows '.time());
		}
	}

	/**
	 * @todo add selection of rcps that chose to watch the wf.
	 */

	function getRecipients() {
		$target = intval($this->stateRecord['uid_foreign']);
		if ($target >= 0) {
			// Ordinary user
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,realName,email', 'be_users', 'uid='.intval($target).t3lib_BEfunc::deleteClause('be_users'));
		}
		if ($target < 0) {
			// Users in group
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,realName,email', 'be_users', $GLOBALS['TYPO3_DB']->listQuery('usergroup_cached_list', abs($target), 'be_users').t3lib_BEfunc::deleteClause('be_users'));
		}
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (strstr($row['email'], '@') && $row['uid'] != $this->BE_USER->user['uid']) {
				// the user must have an email address and mails are not sent to the creating user, should he be in the group.
				$rcpArray[] = $row['realName'].' <'.$row['email'].'>';
			}
		}
		return $rcpArray;
	}

}

// cut from tx_sysworkflows::createTodo(()

/*
// SEnding email notification and filling the emRec array:
$tempQ = FALSE;
$emRec = array();

if ($data['sys_todos'][$key]['target_user'] > 0) {
// Ordinary user
$tempQ = TRUE;
$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,realName,email', 'be_users', 'uid='.intval($data['sys_todos'][$key]['target_user']).t3lib_BEfunc::deleteClause('be_users'));
}
if ($data['sys_todos'][$key]['target_user'] < 0) {
// Users in group
$tempQ = TRUE;
$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,realName,email', 'be_users', $GLOBALS['TYPO3_DB']->listQuery('usergroup_cached_list', abs($data['sys_todos'][$key]['target_user']), 'be_users').t3lib_BEfunc::deleteClause('be_users'));
}
if ($tempQ) {
//					$sAE = t3lib_div::_GP('sendAsEmail'); // This flag must be set in order for the email to get sent
$sAE = true;
while ($brow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
$sendM = 0;
if ($sAE && strstr($brow['email'], '@') && $brow['uid'] != $this->BE_USER->user['uid']) {
// Send-flag must be set, the user must have an email address and finally mails are not sent to the creating user, should he be in the group.
//							$this->sendEmail($brow['email'], $data['sys_todos'][$key]['title'], $data['sys_todos'][$key]['description']);
$sendM = 1;
}
$emRec[] = $brow['username'].($sendM ? " (".$brow['email'].")" : "");
}
}


if (count($emRec)) {
// $emRec just stores the users which is in the target group/target-user and here the list is displayed for convenience.
$emailList = implode('<BR>&nbsp;&nbsp;', $emRec);
$theCode .= $this->pObj->doc->section($LANG->getLL('todos_created'), $LANG->getLL('todos_created_msg').'<BR>&nbsp;&nbsp;'.$emailList, 0, 1, 1);
}
*/


?>