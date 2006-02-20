<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Christian Jul Jensen <christian(at)jul(dot)net>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @author Christian Jul Jensen <christian(at)jul(dot)net>
 */


class tx_sysworkflows_definition {

	function getWorkflowTypes(&$BE_USER) {
		$wfTypes = array();
		if ($BE_USER->isAdmin()) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_workflows', 'sys_workflows.pid=0', '', 'sys_workflows.title');
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'sys_workflows.*',
			'sys_workflows',
			"sys_workflows_algr_mm",
			'be_groups',
			"AND be_groups.uid IN (".($BE_USER->groupList?$BE_USER->groupList:0).")
						AND sys_workflows.pid=0
						AND sys_workflows.hidden=0",
						'sys_workflows.uid',
						'sys_workflows.title' );
		}
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$wfTypes['wf_'.$row['uid']] = $row['title'];
		}
		return $wfTypes;
	}

	function anyUserWorkFlows($table) {
		$count = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->queryUserWorkFlows($table,'count(*)'));
		return $count['count(*)']?true:false;
	}

	function getUserWorkFlows($table) {
		$res = $this->queryUserWorkFlows($table,'uid,title,tablename,tablename_ver,tablename_del,tablename_move');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$tmpRow['uid'] = $row['uid'];
			$tmpRow['title'] = $row['title'];
			if('pages'==$table) {
				$tmpRow['new inside'] = t3lib_div::inList($row['tablename'],$table);
			}
			$tmpRow['new after'] = t3lib_div::inList($row['tablename'],$table);
			$tmpRow['version'] = t3lib_div::inList($row['tablename_ver'],$table);
			$tmpRow['delete'] = t3lib_div::inList($row['tablename_del'],$table);
			$tmpRow['move'] = t3lib_div::inList($row['tablename_move'],$table);
			$rows[] = $tmpRow;
			unset($tmpRow);
		}
		return $rows;
	}

	function getWorkFlow($uid,$checkTable='',$checkAction='',$checkUser=false) {
		$res = $this->queryUserWorkFlows($checkTable,'sys_workflows.*',$uid,$checkAction,$checkUser);
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	}

	function queryUserWorkFlows($table='',$fields='sys_workflows.*',$uid='',$checkAction='',$checkUser='create') {
		if($table) {
			if(!$checkAction || substr(trim($checkAction),0,3)=='new') {
				$tableChecks[] = $GLOBALS['TYPO3_DB']->listQuery('tablename',$table,'sys_workflows');
			}
			if(!$checkAction || $checkAction=='version') {
				$tableChecks[] = $GLOBALS['TYPO3_DB']->listQuery('tablename_ver',$table,'sys_workflows');
			}
			if(!$checkAction || $checkAction=='delete') {
				$tableChecks[] = $GLOBALS['TYPO3_DB']->listQuery('tablename_del',$table,'sys_workflows');
			}
			if(!$checkAction || $checkAction=='move') {
				$tableChecks[] = $GLOBALS['TYPO3_DB']->listQuery('tablename_move',$table,'sys_workflows');
			}
			if(is_array($tableChecks)) {
				$extraClauses .= ' AND ('.implode(' OR ',$tableChecks).')';
			}
		}
		if($uid) {
			$extraClauses = ' AND sys_workflows.uid='.$uid;
		}

		if(!$checkUser || $GLOBALS['BE_USER']->isAdmin()) {
			return $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'sys_workflows', 'sys_workflows.pid=0 AND sys_workflows.hidden=0'.$extraClauses, '', 'sys_workflows.title');
		} else {
			$checkFields = array('create' => 'allowed_groups', 'edit' => 'target_groups', 'review' => 'sys_workflows_rvuser_mm', 'publish' => 'sys_workflows_pubuser_mm');
			$checkField = $checkFields[$checkUser];
			if(substr($checkField,-6)=='groups') {
				$groups = explode(',',$GLOBALS['BE_USER']->groupList?$GLOBALS['BE_USER']->groupList:0);
				foreach($groups as $group) {
					$access_clause[] = $GLOBALS['TYPO3_DB']->listQuery($checkField,$group,'sys_workflows');
				}
				return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$fields,
				'sys_workflows',
				'('.implode(' OR ',$access_clause).') '.'AND sys_workflows.pid=0	AND sys_workflows.hidden=0'.$extraClauses,
				'',
				'sys_workflows.title'
				);

			} else {
				#					$access_clause = $GLOBALS['TYPO3_DB']->listQuery($checkField,$GLOBALS['BE_USER']->user['uid'],'sys_workflows');
				return $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				$fields,
				'sys_workflows',
				$checkField,
				'be_users',
				' AND be_users.uid='.$GLOBALS['BE_USER']->user['uid'].' AND sys_workflows.pid=0	AND sys_workflows.hidden=0'.$extraClauses,
				'',
				'sys_workflows.title'
				);


			}
		}
	}

	/**
	  * Get allowed review users for workflow with uid $wfUid
	  *
	  * @param  Int  $wfUid: Uid on workflow record
	  * @return Array     Array of users keyed by the uid, containg the fields: uid, username,realname
	  *
	  */
	function getTargetUsers($wfUid) {
		$workflowDef = $this->getWorkFlow($wfUid,'','edit');
		$grL = implode(',', t3lib_div::intExplode(',', $workflowDef['target_groups']));
		$wf_groupArray = t3lib_BEfunc::getGroupNames('title,uid', "AND uid IN (".($grL?$grL:0).')');
		$wf_userArray = $this->pObj->blindUserNames($this->pObj->userGroupArray[2], array_keys($wf_groupArray));
		return $wf_userArray;
	}

	/**
	  * Get allowed review users for workflow with uid $wfUid
	  *
	  * @param  Int  $wfUid: Uid on workflow record
	  * @return Array     Array of users keyed by the uid, containg the fields: uid, username,realname
	  *
	  */
	function getReviewUsers($wfUid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
		'be_users.uid,be_users.username,be_users.realName',
		'sys_workflows',
		"sys_workflows_rvuser_mm",
		'be_users',
		t3lib_BEfunc::deleteClause('be_users').t3lib_BEfunc::deleteClause('sys_workflows').' AND sys_workflows.uid='.intval($wfUid),
		'',
		'be_users.username' );
		$outARr = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$outARr[$row['uid']] = $row;
		}
		return $outARr;
	}

	/**
	  * Get allowed publishing users for workflow with uid $wfUid
	  *
	  * @param  Int  $wfUid: Uid on workflow record
	  * @return Array     Array of users keyed by the uid, containg the fields: uid, username,realname
	  *
	  */
	function getPublishingUsers($wfUid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
		'be_users.uid,be_users.username,be_users.realName',
		'sys_workflows',
		"sys_workflows_pubuser_mm",
		'be_users',
		t3lib_BEfunc::deleteClause('be_users').t3lib_BEfunc::deleteClause('sys_workflows').' AND sys_workflows.uid='.intval($wfUid),
		'',
		'be_users.username' );
		$outARr = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$outARr[$row['uid']] = $row;
		}
		return $outARr;
	}

	function getTransitions($state,$wfUid,$action=null) {
		global $LANG;
		$statusLabels = array();
		switch ($state) {
			case 'initiated':
			if ($action=='delete') {
				return $this->getTransitions('reviewing',$wfUid);
			}
			$statusLabels['comment']['label'] = htmlspecialchars($LANG->getLL('todos_status_comment'));
			$statusLabels['begin']['label'] = htmlspecialchars($LANG->getLL('todos_status_begin'));
			$statusLabels['passon']['label'] = htmlspecialchars($LANG->getLL('todos_status_passOn'));
			$statusLabels['passon']['targets'] = $this->getTargetUsers($wfUid);
			$statusLabels['reject']['label'] = htmlspecialchars($LANG->getLL('todos_status_reject'));
			break;

			case 'rejected':
			$statusLabels['comment']['label'] = htmlspecialchars($LANG->getLL('todos_status_comment'));
			$statusLabels['assign']['label'] = htmlspecialchars($LANG->getLL('todos_status_assign'));
			$statusLabels['assign']['targets'] = $this->getTargetUsers($wfUid);

			break;

			case 'started':
			$statusLabels['comment']['label'] = htmlspecialchars($LANG->getLL('todos_status_comment'));
			$statusLabels['passon']['label'] = htmlspecialchars($LANG->getLL('todos_status_passOn'));
			$statusLabels['passon']['targets'] = $this->getTargetUsers($wfUid);
			$statusLabels['end']['label'] = htmlspecialchars($LANG->getLL('todos_status_end'));
			$statusLabels['end']['targets'] = $this->getReviewUsers($wfUid);
			$statusLabels['reject']['label'] = htmlspecialchars($LANG->getLL('todos_status_reject'));
			break;

			case 'reviewing':
			$statusLabels['comment']['label'] = htmlspecialchars($LANG->getLL('todos_status_comment'));
			$statusLabels['passon']['label'] = htmlspecialchars($LANG->getLL('todos_status_passOn'));
			$statusLabels['passon']['targets'] = $this->getReviewUsers($wfUid);
			$statusLabels['review']['label'] = htmlspecialchars($LANG->getLL('todos_status_review'));
			$statusLabels['review']['targets'] = $this->getPublishingUsers($wfUid);
			$statusLabels['reject']['label'] = htmlspecialchars($LANG->getLL('todos_status_reject'));
			break;

			case 'reviewed':
			$statusLabels['comment']['label'] = htmlspecialchars($LANG->getLL('todos_status_comment'));
			$statusLabels['passon']['label'] = htmlspecialchars($LANG->getLL('todos_status_passOn'));
			$statusLabels['passon']['targets'] = $this->getPublishingUsers($wfUid);
			$statusLabels['finalize']['label'] = htmlspecialchars($LANG->getLL('todos_status_finalize'));
			$statusLabels['reject']['label'] = htmlspecialchars($LANG->getLL('todos_status_reject'));
			break;

			case 'published':

			break;

			default:
		}
		/*
		$statusLabels['comment'] = htmlspecialchars($LANG->getLL('todos_status_comment'));
		$statusLabels['begin'] = htmlspecialchars($LANG->getLL('todos_status_begin'));
		$statusLabels['end'] = htmlspecialchars($LANG->getLL('todos_status_end'));
		$statusLabels['passon'] = htmlspecialchars($LANG->getLL('todos_status_passOn'));
		$statusLabels['reject'] = htmlspecialchars($LANG->getLL('todos_status_reject'));
		$statusLabels['review'] = htmlspecialchars($LANG->getLL('todos_status_review'));

		$statusLabels['reset'] = htmlspecialchars($LANG->getLL('todos_status_resetStatus'));
		$statusLabels['finalize'] = htmlspecialchars($LANG->getLL('todos_status_finalize'));
		//  $statusLabels['delete']=htmlspecialchars($LANG->getLL('todos_status_delete'));
		$statusLabels['newinstance'] = htmlspecialchars($LANG->getLL('todos_status_newInstance'));
		*/
		foreach ($statusLabels as $key => $statusLabel) {
			if(is_array($statusLabel['targets']) && key_exists($GLOBALS['BE_USER']->user['uid'],$statusLabel['targets'])) {
				//TODO: label keys and codes should be synchronized, so they cab be used directly, but for now, this will do
				switch ($key) {
					case 'end':
					$statusLabels += $this->getTransitions('reviewing',$wfUid);
					break;
					case 'review':
					$statusLabels += $this->getTransitions('reviewed',$wfUid);
					break;
				}
				// remove the option if the only possible target is the user itself
				// else just remove the user from the list of targets
				if(sizeof($statusLabel['targets'])==1) {
					unset($statusLabels[$key]);
				} else {
					unset($statusLabel['targets'][$GLOBALS['BE_USER']->user['uid']]);
				}
			}
		}
		return $statusLabels;
	}

	//	function getStateDescription($code) {
	//	return $LANG->getLL()
	//	}


	/* All exec_ functions are called with the row representing the
	* current instance of the todo for the user as parameter.
	*
	* @see exec_todos_getQueryForTodoRels
	* @todo all exec functions should be moved to def class, execution functionality should be distilled and kept here.
	*/

	function exec_comment_workflow() {

	}

	/**
	* $iRow: workflow instance row
	* $uid: uid of the current workflow
	*/

	function exec_begin($iRow,$uid,$input,&$RD_URL,$field_values) {
		if (is_array($iRow) && $iRow['tablename'] && !$iRow['rec_reference']) {
			if($this->pObj->loadExecutor()) {
				$recId = $this->pObj->wfExe->beginWorkflow($iRow);
			}
			if ($recId) {
				$this->pObj->wfExe->addvalue('rec_reference',$recId);
				$this->pObj->wfExe->addvalue('state','started');
				$RD_URL = $this->pObj->getEditRedirectUrlForReference($recId,('new'==substr(trim($iRow['action']), 0, 3)));
				return $field_values;
			} else {
				debug('ERROR: The record was not created, so either the workflow is not properly configured or the user did not have permissions to create the record (check the system log for details if this is the case)');
				return null;
			}
		} else {
			debug('ERROR: No workflow record found OR no tablename defined OR there was already a record reference in the record!');
			return null;
		}
	}


	/**
	 * Ends _the editing_ of a workflow and sends it to review
	 *
	 * @param unknown_type $iRow
	 * @param unknown_type $uid
	 * @param unknown_type $input
	 * @param unknown_type $RD_URL
	 * @param unknown_type $field_values
	 * @return unknown
	 */

	function exec_end($iRow,$uid,$input,&$RD_URL,$field_values) {
		$workflowRecord = $this->getWorkflow($uid,$iRow['tablename'],$iRow['action'],'edit');
		// todos_status_end, pass on to reviewer if found (may select reviewer in form), else back to admin
		$first = 0;
		$recRefArr = explode(':', $iRow['rec_reference']);
		if ('pages'==$recRefArr[0]) {
			$this->pObj->wfExe->setFinalPerms($workflowRecord,$recRefArr[1]);
		}
		// Trying to find a review user if any and apply this user instead of the owner.
		//		if (is_array($workflowRecord) && $workflowRecord['tablename']) {
		if (is_array($workflowRecord)) {
			$revUsers = $this->getReviewUsers($workflowRecord['uid']);
			reset($revUsers);
			while (list($u_id) = each($revUsers)) {
				// CHECK IF the submittet target user matches one of the reviewers
				if (!$first) $first = $u_id;
				if ($u_id == $input['newTarget']) {
					$this->pObj->wfExe->addvalue('uid_foreign',$u_id);
					$this->pObj->wfExe->addvalue('state','reviewing');
					$this->pObj->wfExe->addvalue('reject_user',$iRow['uid_foreign']);
					$this->pObj->wfExe->addvalue('reject_state',$iRow['state']);
					return $field_values;
				}
			}

		}
		//		 TODO: eftersom field_values er flyttet til wfExe fejler det her check altid!
		if (!$field_values['uid_foreign']) {
			//			IF the target is NOT found yet (may have been between the submitted targets.)
			$field_values['uid_foreign'] = $first ? $first :
			$row['cruser_id']; // ... select the first review user and if that is not set, select the owner
		}
		return null;
	}

	function exec_assign($iRow,$uid,$input,&$RD_URL,$field_values) {
		if (intval($input['newTarget'])) {
			$this->pObj->wfExe->addvalue('uid_foreign',$input['newTarget']);
			$this->pObj->wfExe->addvalue('state',$iRow['reject_state']);
			$this->pObj->wfExe->addvalue('reject_user',$iRow['uid_foreign']);
			$this->pObj->wfExe->addvalue('reject_state','rejected');
		}
	}


	function exec_passon($iRow,$uid,$input,&$RD_URL,$field_values) {
		// todos_status_passOn, just pass on to selected target
		if (intval($input['newTarget'])) {
			$this->pObj->wfExe->addvalue('uid_foreign',$input['newTarget']);
			$this->pObj->wfExe->addvalue('reject_user',$iRow['uid_foreign']);
			$this->pObj->wfExe->addvalue('reject_state',$iRow['state']);
		}
	}

	function exec_reject($iRow,$uid,$input,&$RD_URL,$field_values) {
		// todos_status_reject, target = sender user
		$this->pObj->wfExe->addvalue('uid_foreign',intval($iRow['reject_user'])?$iRow['reject_user']:$iRow['cruser_id']);
		$this->pObj->wfExe->addvalue('state',$iRow['reject_state']);
		$this->pObj->wfExe->addvalue('reject_user',$iRow['uid_foreign']);
		$this->pObj->wfExe->addvalue('reject_state',$iRow['state']);
	}

	function exec_review($iRow,$uid,$input,&$RD_URL,$field_values) {
		$workflowRecord = $this->getWorkflow($uid,$iRow['tablename'],$iRow['action'],'review');
		// todos_status_reject, target = sender user
		$this->pObj->wfExe->addvalue('state','reviewed');
		$this->pObj->wfExe->addvalue('reject_user',$iRow['uid_foreign']);
		$this->pObj->wfExe->addvalue('reject_state',$iRow['state']);

		// todos_status_end, pass on to reviewer if found (may select publisher in form), else back to admin
		$first = 0;
		// Trying to find a review user if any and apply this user instead of the owner.
		//		if (is_array($workflowRecord) && $workflowRecord['tablename']) {
		if (is_array($workflowRecord)) {
			$pubUsers = $this->getPublishingUsers($workflowRecord['uid']);
			reset($pubUsers);
			while (list($u_id) = each($pubUsers)) {
				// CHECK IF the submittet target user matches one of the reviewers
				if (!$first) $first = $u_id;
				if ($u_id == $input['newTarget']) {
					$this->pObj->wfExe->addvalue('uid_foreign',$u_id);

					return $field_values;
				}
			}
		}
		if ($this->pObj->wfExe->field_values['uid_foreign']) {
			// IF the target is NOT found yet (may have been between the submitted targets.)
			$this->pObj->wfExe->addvalue('uid_foreign',($first ? $first : $row['cruser_id'])); // ... select the first review user and if that is not set, select the owner
		}
	}


	function exec_reset($iRow,$uid,$input,&$RD_URL,$field_values) {
		// Reset status-log
		if ($this->BE_USER->user['uid'] == $iRow['cruser_id']) {
			// Must own
			$this->pObj->logData['status_log_clear'] = 1;
		} else {
			debug('User did not have access to reset log',__FUNCTION__,__LINE__,__FILE__);
		}
	}

	function exec_finalize($iRow,$uid,$input,&$RD_URL,$field_values) {
		$workflowRecord = $this->getWorkFlow($uid,$iRow['table'],$iRow['action'],'publish');
		// Finalize
		if (is_array($workflowRecord) && $this->pObj->loadExecutor()) {
			$this->pObj->wfExe->addvalue('uid_foreign',$iRow['cruser_id']);
			$this->pObj->wfExe->addvalue('finalized',$this->pObj->wfExe->finalizeWorkflow($workflowRecord, $iRow) ? 1 :	0);
			$this->pObj->wfExe->addvalue('state','published');
			$this->pObj->wfExe->addvalue('reject_user',$iRow['uid_foreign']);
			$this->pObj->wfExe->addvalue('reject_state',$iRow['state']);

			return $field_values;
		} else {
			debug('No valid workflowrecord or failed to load execution class',__FUNCTION__,__LINE__,__FILE__);
			return null;
		}
	}


	/**
	 * @todo move functionality to wfExe, make wrapper call from wfDef
	 */
	function exec_newinstance() {
		if ($this->BE_USER->user['uid'] == $row['cruser_id']) {
			// Must own
			$this->logData['uid_ign'] = $data['sys_todos_users_mm'][$key]['status']['newTarget'];

			$field_values = array(
			'uid_local' => $row['uid_local'],
			'uid_foreign' => $this->logData['uid_foreign'],
			'status' => 102,
			'tstamp' => time(),
			'status_log' => serialize(array($this->logData))
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_todos_users_mm', $field_values);

			$noUpdate = 1;
		}
	}

}

//load XCLASS?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_definition.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_definition.php']);
}

?>
