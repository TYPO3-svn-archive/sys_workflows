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
	 
	 
	class tx_sysworkflows_executor {
		var $BE_USER;

		function createNewRecord($table,$pid) {
			$data[$table]['NEW'] = array();
			$data[$table]['NEW']['pid'] = $pid;
			$tce = $this->callTCE($data, array(), true);
			if ($tce->substNEWwithIDs['NEW']) {
				return $table.':'.$tce->substNEWwithIDs['NEW'];
			}
		} //createNewRecord

		function createNewVersionOfRecord($table,$uid) {
			$cmd[$table][$uid]['version']['action'] = 'new';
			$cmd[$table][$uid]['version']['label'] = 'workflow';
			$tce = $this->callTCE(array(), $cmd, true);
			return $table.':'.$tce->copyMappingArray[$table][$uid];
		} //createNewVersionOfRecord

		/**
		 * @todo workflowrecord should not be passed here it somehow be encapsulated in the definition class
		 */
		function publishNewRecord($table,$uid,$newPid=null,$doNotUnhide=false,$workflowRecord=Array()) {
			$dataArr = array();
			$cmdArr = array();
			if (!$doNotUnhide) {
				if (is_array($TCA[$table]['ctrl']['enablecolumns']) &&  $TCA[$table]['ctrl']['enablecolumns']['disabled']) {
					$dataArr[$table][$uid][$TCA[$table]['ctrl']['enablecolumns']['disabled']] = 0;
				} else {
					debug('tried to unhide but table: '.$table.' does not support it ('.__FILE__.','.__LINE__.')');
				}
			}
			if($newPid) {
				$targetPage = t3lib_BEfunc::getRecord('pages', $newPid);
				if (is_array($targetPage)) {
					$cmdArr[$table][$uid]['move'] = $targetPage['uid'];
				}
			}
			if ($table == "pages" && $workflowRecord['final_set_perms']) {
				$dataArr[$table][$uid]['perms_userid'] = $workflowRecord['final_perms_userid'];
				$dataArr[$table][$uid]['perms_groupid'] = $workflowRecord['final_perms_groupid'];
				$dataArr[$table][$uid]['perms_user'] = $workflowRecord['final_perms_user'];
				$dataArr[$table][$uid]['perms_group'] = $workflowRecord['final_perms_group'];
				$dataArr[$table][$uid]['perms_everybody'] = $workflowRecord['final_perms_everybody'];
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
		
		function callTCE($dataArr,$cmdArr,$return=null) {
			//sanity check - since the setting depends on the client.
			assert('is_a($this->BE_USER,\'t3lib_beuserauth\')');

			// Perform it (as ADMIN)
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$TCAdefaultOverride = $this->BE_USER->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride)) {
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			} 
			$tce->start($dataArr, $cmdArr, $this->BE_USER);
			$tce->admin = 1; // Set ADMIN permission for this operation.
			if(is_array($dataArr)) {
				$tce->process_datamap();
			}
			if(is_array($cmdArr)) {
				$tce->process_cmdmap();
			}
			if($return) {
				return $tce;
			} else {
				unset($tce);
			}
		} //callTCE

	}
//load XCLASS?
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_executor.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_executor.php']);
	}
	 
?>
