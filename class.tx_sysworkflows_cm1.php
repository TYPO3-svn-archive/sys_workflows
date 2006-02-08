<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Christian Jul Jensen (christian(at)jul(dot)net)
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
 * Addition of an item to the clickmenu
 *
 * @author	Christian Jul Jensen <christian(at)jul(dot)net>
 */


class tx_sysworkflows_cm1 {

	function loadDefinition() {
		if(!is_object($this->wfDef)) {
			require_once (t3lib_extMgm::extPath('sys_workflows').'class.tx_sysworkflows_definition.php');
			$this->wfDef = t3lib_div::makeInstance('tx_sysworkflows_definition');
		}
	}


	function main(&$backRef,$menuItems,$table,$uid)	{
		global $BE_USER,$TCA,$LANG;

		$LL = $this->includeLL();

		$this->loadDefinition();
		$localItems = Array();
		if (!$backRef->cmLevel)	{
			if($this->wfDef->anyUserWorkFlows($table)) {
			
				$localItems[]="spacer";
				$localItems["moreoptions_tx_sysworkflows_cm1"]=$backRef->linkItem(
							$GLOBALS["LANG"]->getLLL("cm1_title_activate",$LL),
							$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath("sys_workflows").'cm1/cm_icon_activate.gif" width="15" height="12" border=0 align=top>'),
							"top.loadTopMenu('".t3lib_div::linkThisScript()."&cmLevel=1&subname=moreoptions_tx_sysworkflows_cm1');return false;",
							0,
							1
							);
				
				
				
				// Find position of "delete" element:
				reset($menuItems);
				$c=0;
				while(list($k)=each($menuItems))	{
					$c++;
					if (!strcmp($k,"delete"))	break;
				}
				// .. subtract two (delete item + divider line)
				$c-=2;
				// 	... and insert the items just before the delete element.
				array_splice(
										 $menuItems,
										 $c,
										 0,
										 $localItems
										 );
			}
		}
		else {
			$this->loadDefinition();
			$actions = array('new inside','new after','version','delete','move');

			foreach($this->wfDef->getUserWorkFlows($table) as $row) {
				foreach($actions as $action) {
					if($row[$action]) {					
						$url = t3lib_extMgm::extRelPath("taskcenter").'task/index.php?SET[function]=tx_sysworkflows&action='.$action.'&table='.$table.'&uid='.$uid.'&workflow_type=wf_'.$row['uid'];
						$localItems[] = $backRef->linkItem(
					  $row['title'].' '.$action,
					  $backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath("sys_workflows").'cm1/cm_icon.gif" width="15" height="12" border=0 align=top>'),
						str_replace('(top.content.list_frame)?top.content.list_frame:top.content','top.content',$backRef->urlRefForCM($url)).'top.fsMod.currentMainLoaded="user";', // this is a hack, ask Kasper how to do it properly
						$backRef->urlRefForCM($url).'top.fsMod.currentMainLoaded="user";',
					  1	// Disables the item in the top-bar. Set this to zero if you with the item to appear in the top bar!
						);
					}
				}
			}
#			debug($localItems,'$localItems',__LINE__,__FILE__);//julle
#			die();

			$menuItems=array_merge($menuItems,$localItems);
		}
		return $menuItems;
	}
	
	/**
	 * Includes the [extDir]/locallang_tca.php and returns the $LOCAL_LANG array found in that file.
	 */
	function includeLL()	{
		include(t3lib_extMgm::extPath("sys_workflows")."locallang_tca.php");
		return $LOCAL_LANG;
	}

}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sys_workflows/class.tx_sysworkflows_cm1.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sys_workflows/class.tx_sysworkflows_cm1.php"]);
}

?>