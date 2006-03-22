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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
	* @author Christian Jul Jensen <christian(at)jul(dot)net>
	*/


class tx_sysworkflows_executor {
	var $BE_USER;
	var $pObj; // parent object. Expected to be an instance of tx_sysworkflows
	var $field_values;
	var $tce;
	/* positive pid creates new record as first record within that
	pid, negative pid creates new record right after this one.
	this is TCE 'syntax'. This means that if the pid is positive it
	is a uid of a record in pages, if it's negative it is the uid
	of a record in the same tabel as you are about to create, which
	could be pages, but also other tables
	*/
	function createNewRecord($table,$pid) {
		$data[$table]['NEW'] = array();
		$data[$table]['NEW']['pid'] = $pid;
		$this->callTCE($data, array());

		if ($id = $this->tce->substNEWwithIDs['NEW']) {
			return $table.':'.$id;
		}
	} //createNewRecord

	function createNewVersionOfRecord($table,$uid,$label='workflow') {
		$cmd[$table][$uid]['version']['action'] = 'new';
		$cmd[$table][$uid]['version']['label'] = $label;
		if($table=='pages') {
			$cmd[$table][$uid]['version']['treeLevels'] = 0;
		}

		$this->callTCE($dataArr, $cmd);
		$recId = $this->tce->copyMappingArray[$table][$uid];
		if($table=='pages') {
			$dataArr['pages'][$recId]['perms_userid'] = $this->BE_USER->user['uid'];
			$dataArr['pages'][$recId]['perms_groupid'] = $this->BE_USER->firstMainGroup;
			$dataArr['pages'][$recId]['perms_user'] = $this->tce->assemblePermissions($this->tce->defaultPermissions['user']);
			$dataArr['pages'][$recId]['perms_group'] = $this->tce->assemblePermissions($this->tce->defaultPermissions['group']);
			$dataArr['pages'][$recId]['perms_everybody'] = $this->tce->assemblePermissions($this->tce->defaultPermissions['everybody']);
			$this->callTCE($dataArr, array(), true);
		}
		return $table.':'.$recId;
	} //createNewVersionOfRecord

	function setEditingPerms(&$tce,$recId) {
		//preload tce object, to make it calculate default rights
		$dataArr = array();
	}


	function setFinalPerms($workflowRecord,$recId) {
		$dataArr = array();
		if ($workflowRecord['final_set_perms']) {
			$dataArr['pages'][$recId]['perms_userid'] = $workflowRecord['final_perms_userid'];
			$dataArr['pages'][$recId]['perms_groupid'] = $workflowRecord['final_perms_groupid'];
			$dataArr['pages'][$recId]['perms_user'] = $workflowRecord['final_perms_user'];
			$dataArr['pages'][$recId]['perms_group'] = $workflowRecord['final_perms_group'];
			$dataArr['pages'][$recId]['perms_everybody'] = $workflowRecord['final_perms_everybody'];
		}
		$this->callTCE($dataArr, array());
	}

	/**
	 * @todo workflowrecord should not be passed here it somehow be encapsulated in the definition class
	 */
	function publishNewRecord($table,$uid,$workflowRecord=Array()) {
		global $TCA;
		$dataArr = array();
		$cmdArr = array();
		if (is_array($TCA[$table]['ctrl']['enablecolumns']) &&  $TCA[$table]['ctrl']['enablecolumns']['disabled']) {
			$dataArr[$table][$uid][$TCA[$table]['ctrl']['enablecolumns']['disabled']] = 0;
		} else {
			debug('tried to unhide but table: '.$table.' does not support it ('.__FILE__.','.__LINE__.')');
		}

		$this->callTCE($dataArr,$cmdArr);
	} //publishNewRecord

	function publishNewVersion($table,$t3ver_oid,$uid) {
		$cmdArr = array();
		$cmdArr[$table][$t3ver_oid]['version']
		= array(
		'swapWith'    =>  $uid,
		'action'      => 'swap',
		'swapContent' => '1',
		);
		$this->callTCE(array(),$cmdArr);
	} //publicNewVersion

	function deleteRecord($table,$uid) {
		global $TCA;
		$dataArr = array();
		$cmdArr = array();
		if ($TCA[$table]['ctrl']['delete']) {
			$dataArr[$table][$uid][$TCA[$table]['ctrl']['delete']] = 1;
		} else {
			debug('tried to delete but table: '.$table.' does not support it ('.__FILE__.','.__LINE__.')');
		}

		$this->callTCE($dataArr,$cmdArr);
	} //publishNewRecord



	function callTCE($dataArr,$cmdArr,$useExistingTCE=false) {
		//sanity check - since the setting depends on the client.
		assert('is_a($this->BE_USER,\'t3lib_beuserauth\')');

		if($useExistingTCE && is_a($this->tce,'t3lib_TCEmain')) {
			$tce = &$this->tce;
		} else {
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$this->tce = &$tce;
		}
		$tce->stripslashes_values = 0;
		$TCAdefaultOverride = $this->BE_USER->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		// Set ADMIN permission for this operation.
		$this->BE_USER->user['admin'] = 1;
		$tce->start($dataArr, $cmdArr, $this->BE_USER);
		$this->BE_USER->user['admin'] = 0;

		if(is_array($dataArr)) {
			$tce->process_datamap();
		}
		if(is_array($cmdArr)) {
			$tce->process_cmdmap();
		}
	} //callTCE

	/**
	 * Creates the new record / version of the record
	 *
	 * @param array  $workflowRecord: the db record of the workflow as an array
	 * @return string  ...
	 *
	 * @todo dependent on the related record and the workflow record, move actions to executor
	 */
	function beginWorkflow($relRecord) {
		global $TCA;
		switch($relRecord['action']) {
			case 'new inside':
				if('pages'==$relRecord['tablename']) {
					return $this->createNewRecord($relRecord['tablename'],$relRecord['idref']);
				} else {
					die('Error in: '.__FILE__.','.__LINE__.debug_backtrace());
				}
				break;
			case 'new after':
				return $this->createNewRecord($relRecord['tablename'],-1*$relRecord['idref']);
				break;
			case 'version':
				return $this->createNewVersionOfRecord($relRecord['tablename'], $relRecord['idref']);
				break;
			case 'delete':
				return $relRecord['tablename'].':'.$relRecord['idref'];
				break;
			case 'move':
				break;
			default:
				debug('Error: record did not define any action',__FUNCTION__,__LINE__,__FILE__);
		}
	} //beginWorkflow

	/**
	 * [Describe function...]
	 *
	 * @param [type]  $workflowRecord: ...
	 * @param [type]  $relRecord: ...
	 * @return [type]  ...
	 *
	 * @todo some of this should probably be controlled by wfDef, move to wfExe
	 */
	function finalizeWorkflow($workflowRecord, $relRecord) {
		global $TCA;
		list($table, $uid) = explode(':', $relRecord['rec_reference']);
		if ($relRecord['tablename'] == $table && $TCA[$table]) {
			if ($relRecord['action']=='delete') {
				$this->deleteRecord($table,$uid);
			}
			$itemRecord = t3lib_BEfunc::getRecord($table, $uid);
			if (is_array($itemRecord)) {
				if('-1' == $itemRecord['pid']) {
					// this is a versionized page, should be published, not moved
					$this->publishNewVersion($table,$itemRecord['t3ver_oid'],$uid);
				} else {
					#						list($target_pid) = explode(',', $workflowRecord['final_target']);
					$this->publishNewRecord($table,$uid,$workflowRecord);
				}
				if ('pages'==$table) {
					$this->setFinalPerms($workflowRecord,$uid);
				}
				return true;
			} else {
				debug('ERROR: The reference record was not there!');
				return false;
			}
		} else {
			debug('ERROR: Strange thing, the table name was not valid!');
			return false;
		}
	}


	function addValue($field,$val) {
		$this->field_values[$field] = $val;
	}

	function saveRecord($logData,$row,$key) {
		if (isset($this->field_values['uid_foreign'])) $logData['uid_foreign'] = $this->field_values['uid_foreign'];

		$status_log = unserialize($row['status_log']);
		if (!is_array($status_log)) $status_log = array();
		$status_log[] = $logData;
		$this->field_values['status_log'] = serialize($status_log);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_todos_users_mm', "mm_uid=".intval($key), $this->field_values);
	}

}
//load XCLASS?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_executor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_executor.php']);
}

?>
