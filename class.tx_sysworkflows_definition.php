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

	}
	
//load XCLASS?
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_definition.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows_definition.php']);
	}
	 
?>
