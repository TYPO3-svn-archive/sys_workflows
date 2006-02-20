<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2004-2005 Christian Jul Jensen <christian(at)jul(dot)net>
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
 * @author Kasper Sk�rh�j <kasperYYYY@typo3.com>
 * @author Christian Jul Jensen <christian(at)jul(dot)net>
 */

require_once (PATH_t3lib.'class.t3lib_tceforms.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');
require_once (PATH_t3lib.'class.t3lib_loadmodules.php');

class tx_sysworkflows extends mod_user_task {
	var $todoTypesCache = array();
	var $insCounter = 0;
	var $wfDef = null;
	var $wfExe = null;
	var $workflowExtIsLoaded = null;

	/**
	 * Generates the overview list of todo items for the taskcenter, called from task/overview.php
	 *
	 * @return string  HTML
	 */
	function overview_main() {
		return $this->mkMenuConfig(
		'<img src="'.$this->backPath.t3lib_extMgm::extRelPath('sys_workflows').'ext_icon.gif" width=18 height=16 class="absmiddle">'. $this->headLink('tx_sysworkflows', 1),
		'',
		$this->renderTaskList(),
		'linktitle' );

	}

	/**
	 * Main function called by the taskcenter framework
	 *
	 * @return [type]  ...
	 */
	function main() {
		$this->workflowExtIsLoaded = t3lib_extMgm::isLoaded('sys_workflows');
		return $this->renderTasks();


	} //main

	/**
	 * Make sure that the workflow definition class is loaded
	 *
	 * @return bool: returns true if class is loaded
	 */
	function loadDefinition() {
		if ($this->workflowExtIsLoaded) {
			if(!is_object($this->wfDef)) {
				require_once (t3lib_extMgm::extPath('sys_workflows').'class.tx_sysworkflows_definition.php');
				$this->wfDef = t3lib_div::makeInstance('tx_sysworkflows_definition');
				$this->wfDef->pObj = &$this;
			}
			return true;
		} else {
			return false;
		}
	} //loadDefinition

	/**
	  * Make sure that the workflow execution class is loaded
	  *
	  * @return bool: returns true if class is loaded
	  */
	function loadExecutor() {
		if ($this->workflowExtIsLoaded) {
			if(!is_object($this->wfExe)) {
				require_once (t3lib_extMgm::extPath('sys_workflows').'class.tx_sysworkflows_executor.php');
				$this->wfExe = t3lib_div::makeInstance('tx_sysworkflows_executor');
				$this->wfExe->BE_USER =& $this->BE_USER;
				$this->wfExe->pObj = &$this;
			}
			return true;
		} else {
			return false;
		}
	} //loadExecutor


	// ************************
	// TODO-tasks
	// ***********************
	/**
	 * Generate list of unfinished tasks for current user
	 *
	 * @return String  HTML
	 *
	 * @todo instead of showing picture depending on who created the record, it's probably more interesting to display who touched it last.
	 */

	function renderTaskList() {
		global $LANG;

		$res = $this->exec_todos_getQueryForTodoRels(' AND sys_todos_users_mm.finished_instance=0');
		$userImg  = '<img src="'.$this->backPath.'gfx/todoicon_user.gif" width="18" hspace=6 height="10" align=top border=0>';
		$groupImg = '<img src="'.$this->backPath.'gfx/todoicon_group.gif" width="18" hspace=6 height="10" align=top border=0>';

		$lines = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$lines[] = '<nobr>'.($row['cruser_id'] == $this->BE_USER->user['uid']?$userImg:$groupImg).$this->todos_link(htmlspecialchars($this->fixed_lgd($row['title'])), -$row['mm_uid']).'</nobr><BR>';
		}

		$res = $this->exec_todos_getQueryForTodoRels('', 'count(*)', 1);
		list($mc) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$lines[] = '<nobr>'.$this->todos_link(sprintf($LANG->getLL('todos_index_msgs'), $mc), '0').'</nobr><BR>';

		$out = implode('', $lines);
		return $out;
	} //renderTaskList

	/**
	 * Make $str link to task with $id
	 *
	 * @param String  $str: Text to wrap in link
	 * @param Int  $id: uid of task to link to
	 * @return String  link (HTML)
	 */
	function todos_link($str, $id) {
		$str = '<a href="index.php?SET[function]=tx_sysworkflows&sys_todos_uid='.$id.'" onClick="this.blur();">'.$str.'</a>';
		return $str;
	} //todos_link

	/**
	 * Generate list of possible target users and groups for the task
	 *
	 * @param  Array  $be_user_Array: Array of possible users, each entry containing the keys/fields: username, usergroup, usergroup_cached_list, uid, realName, email
	 * @param  Array  $be_group_Array:Array of possible groups, each entry containing the keys/fields: title, uid
	 * @param  String $type: ??? Is set to NEW on new tasks, but ...
	 * @param  Bool  $returnOptsOnly: return only the options, not the whole selectorbox
	 * @return String The selectorbox, possibly only the options (HTML)
	 *
	 * @todo should get possible targets from def-class / passed as arg
	 */
	function tasks_makeTargetSelector($be_user_Array, $be_group_Array, $type, $returnOptsOnly = 0) {
		global $LANG;
		// Plain todo
		$opt = array();
		reset($be_user_Array);
		$first = true;
		$opt[] = $LANG->getLL('todos_users');

		if(sizeof($be_user_Array)>0) {
			while (list($uid, $dat) = each($be_user_Array)) {
				$opt[] = '<a id="WF-target-'.$uid.'"class="'.($first?'selected':'not-selected').'" href="#" onclick="WF_setTarget(this,\''.$uid.'\');">'.htmlspecialchars($dat['username'].($dat['uid'] == $this->BE_USER->user['uid']?' ['.$LANG->getLL('lSelf').']':' ('.$dat['realName'].')')).'</a>';
				if ($first) {
					$defaultUid = $uid;
				}
				$first = false;
			}
			if (count($be_group_Array)) {
				$opt[] = $LANG->getLL('listSeparator_Groups');
				reset($be_group_Array);
				while (list($uid, $dat) = each($be_group_Array)) {
					$opt[] = '<a href="#" onclick="WF_setTarget(this,\'-'.$uid.'\');">'.htmlspecialchars($dat['title']).'</a>';
				}
			}
			if ($returnOptsOnly) return $opt;
			$jscode = '
		<script language="javascript">
		/*<![CDATA[*/
		var WF_activeTarget = document.getElementById(\'WF-target-'.$defaultUid.'\');
		
		function WF_setTarget(caller,uid) {
		  caller.className = \'selected\';
		  var target = document.getElementById(\'target\');
		  if(WF_activeTarget!=caller) {
		    WF_activeTarget.className = \'not-selected\';
		  }
		  target.value = uid;
		  WF_activeTarget = caller;
		  WF_activeTarget.blur();

		  }
		/*]]>*/
		</script>';
			return '<input id="target" type="hidden" name="data[sys_todos]['.$type.'][target_user]" value="'.$defaultUid.'"/><div id="target-list">'.implode('<br />', $opt).'</div>'.$jscode;
		} else {
			return '<div style="border: 2px solid black; color: red; background: white;">' . $LANG->getLL('todos_noavailable_targets') .'</div>';
		}
	} //todos_makeTargetSelector

	/**
	 * render a list of main tasks
	 *
	 * @return string  (HTML)
	 */
	function renderTasks() {
		global $LANG;

		// Setting up general things for the function:
		$tUid = intval(t3lib_div::_GP('sys_todos_uid'));

		$this->todos_processIncoming($RD_URL);

		// Get groupnames for todo-tasks
		$this->getUserAndGroupArrays(); // Users and groups blinded due to permissions, plus (third) the original user array with all users

		// Create Todo types array (workflows):http://moses/
		$todoTypes = array();
		#			$todoTypes['plain'] = '['.$LANG->getLL('todos_plain').']';
		if ($this->loadDefinition()) {
			$todoTypes += $this->wfDef->getWorkflowTypes($this->BE_USER,'','','create');
		}
		// Printing the todo list of a user:
		if(!$tUid && !is_string(t3lib_div::_GP('action'))) {
			return $this->todos_displayLists($todoTypes, $tUid);
		} else {

			// New todo:
			if(t3lib_div::_GP('workflow_type')) {
				return $this->getCSS().$this->todos_createForm($todoTypes);
			} else {

				$row = $this->getStateRecord($tUid,true);
				if(is_array($row)) {
					$menuItems[] = array(
					'label' => $LANG->getLL('todos_tabs_description'),
					'content' => $this->getDescription($row,$RD_URL).$this->getUpdateForm($tUid, $countOfInstances).$this->urlInIframe($RD_URL,1)
					);
					//				$menuItems[] = array(
					//				'label' => 'Action',
					//				'content' => $this->getUpdateForm($tUid, $countOfInstances)
					//				);
					$menuItems[] = array(
					'label' => $LANG->getLL('todos_tabs_history'),
					'content' => $this->getStatus($row,$tUid)
					);
$content = $this->getCSS().$this->pObj->doc->getDynTabMenu($menuItems,'tx_sysworkflows',0);
				} else {
					$content .= '';
				}
				return $content;

			}
		}
	} //renderTasks

	function getCSS() {
		$theCode .= '<style>
						.workflow-top, .action {
						  margin-top: 10px;
						  padding: 8px;
						  border: 1px solid black;
						  background: #e7dba8;
						  line-height: 20px;
						  border-radius: 8px; 
						  -moz-border-radius: 8px;
						}

						#target-list {
						  background: #FFFFFF;
						  border: 1px solid black;
						  padding: 5px;
						  width: 200px;
						}
						
						#target-list .selected {
						  font-weight: bold;
						}

						#target-list .not-selected {
						  font-color: gray;
						}

						div.buttons input[type="button"]{
						  width: 200px;
						}
						div.buttons {
						  float:left;
						}
						
						div.targets {
						  display: none;
						  float: left;
						  position: absolute;
						  border: 1px solid black;
						  background: white;
                          margin-left: 2px;
                          margin-top: -2px;
                          padding: 2px;
                          min-width: 600px:
						}
						div.targets .cancel-targets {
						  color: gray;
						}
						
						.workflow-top h1 {
						  text-align: left;
						}
						
						.workflow-top .header {
						  font-weight: bold;
						}
						.action {
						  margin-top: 10px;
						  padding: 8px;
						  margin-bottom: 18px;
					
						  border: 1px solid black;
						  background: #c4d4cd;
						  line-height: 20px;
						  border-radius: 8px; 
						  -moz-border-radius: 8px;
						}
						.action input, select, textarea {
						  border: 1px solid black;
						  margin: 2px;
						}
						
						.action .header {
						  font-weight: bold;
						  display: block;
						}
					
						
						</style>';

		return $theCode;
	}

	/**
	 * Procees the incoming data, writing logs changing target user,
	 *
	 * @param 	string	&$RD_URL:	URL to redirect (opening in iframe);
	 * @return 	HTML				code to display
	 */
	function todos_processIncoming(&$RD_URL) {

		// PROCESSING:
		$data = t3lib_div::_GP('data');
		// ********************************
		// Instance updated
		// ********************************
		if (t3lib_div::_GP('newStatus') && is_array($data['sys_todos_users_mm'])) {
			// A status is updated
			$this->updateInstance($data,$RD_URL);
		} //end instance updated

		// ***********************************
		// sys_todos are created/updated
		// ***********************************
		if (t3lib_div::_GP('create_todo') && is_array($data['sys_todos'])) {
			// A todo is created
			$theCode = $this->createTodo($data);
		} //end create todo

		// *************************************************
		// sys_todos / instances are updated regarding DONE
		// *************************************************
		if (t3lib_div::_GP('marked_todos')) {
			$this->markDone();
		}
		//		if(is_string($RD_URL)) {
		//			$theCode .= '<script language="javascript" > var iframe = document.getElementById(\'list_frame\'); iframe.src='.$RD_URL.'</script>';
		//		}


		return $theCode;
	} //todos_processIncoming

	/**
	 * updates the instance of the workflow to the new status
	 *
	 * @param	array	$data:	data passed by the user in _GP('data')
	 * @param 	string	&$RD_URL:	URL to redirect (openning in iframe);
	 * @return 	void
	 *
	 */

	function updateInstance($data,&$RD_URL) {

		$RD_URL = '';
		while (list($key) = each($data['sys_todos_users_mm'])) {
			$key = intval($key);
			$res = $this->exec_todos_getQueryForTodoRels(' AND sys_todos_users_mm.mm_uid='.$key, 'sys_todos_users_mm.*,sys_todos.cruser_id,sys_todos.type', 1);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if (trim($data['sys_todos_users_mm'][$key]['status']['code']) && $this->loadExecutor()) {
					$wF = $row['type'];
					// debug($row);
					$this->initLog($data['sys_todos_users_mm'][$key]['status']['code'],
					$row['uid_foreign'],
					$data['sys_todos_users_mm'][$key]['status']['comment']
					);

					$code = $data['sys_todos_users_mm'][$key]['status']['code'];
					$this->wfExe->addvalue('status',$code);
					$this->wfExe->addvalue('tstamp',time());
					$this->wfExe->addvalue('is_read',0); // Not used yet, but the point is that this flag is set when a target users looks at the item for the first time. This may later be used to inform the owner of the item whether it has been looked at or not. Here it's reset for every change to the log. Maybe it should be changed for each new target only.?
					// target:
					$funcName = 'exec_'.$code;
					if(substr($wF, 0, 3) == "wf_" && $this->loadDefinition() && method_exists ($this->wfDef,$funcName)){
						$wfUid = substr($wF, 3);
						$field_values = $this->wfDef->$funcName($row,$wfUid,$data['sys_todos_users_mm'][$key]['status'],$RD_URL,$field_values);
						$this->createNotification($code,$data['sys_todos_users_mm'][$key]['status']['newTarget'],$key);
					} elseif(method_exists ($this,$code)) {
						$this->$code();
					} else {
						die('Fatal error: no such action!');
					}
					if($data['sys_todos_users_mm'][$key]['observeWorkflow']) {
						$this->setUserAsObserver($key);
					} else {
						$this->unsetUserAsObserver($key);
					}

					$this->wfExe->saveRecord($this->logData,$row,$key);
				}

				// Finished?
				if (isset($data['sys_todos_users_mm'][$key]['finished_instance']) && $this->BE_USER->user['uid'] == $row['cruser_id']) {

					$field_values = array(
					'finished_instance' => $data['sys_todos_users_mm'][$key]['finished_instance']?1:0);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_todos_users_mm', 'mm_uid='.intval($key), $field_values);
				}
			}
		}
	} //updateInstance

	function createTodo($data) {
		global $LANG;

		reset($data['sys_todos']);
		$key = key($data['sys_todos']);
		if ($key == 'NEW') {
			if ($data['sys_todos'][$key]['target_user'] && $data['sys_todos'][$key]['type'] && $data['sys_todos'][$key]['title']) {

				$fields_values = array(
				'title' => $data['sys_todos'][$key]['title'],
				'type' => $data['sys_todos'][$key]['type'],
				'deadline' => $data['sys_todos'][$key]['deadline'],
				'description' => $data['sys_todos'][$key]['description'],
				'deleted' => 0,
				'finished' => 0,
				'tstamp' => time(),
				'crdate' => time(),
				'cruser_id' => $this->BE_USER->user['uid'] );
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_todos', $fields_values);
				$todoUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
				// Relation:
				if (!$GLOBALS['TYPO3_DB']->sql_error()) {
					$fields_values = array(
					'uid_local' => $todoUid,
					'uid_foreign' => $data['sys_todos'][$key]['target_user'],
					'tstamp' => time(),
					'action' => $data['sys_todos'][$key]['action'],
					'tablename' => $data['sys_todos'][$key]['table'],
					'idref' => $data['sys_todos'][$key]['uid'],
					'state' => 'initiated',
					'reject_user' => $this->BE_USER->user['uid'],
					'reject_state' => 'rejected'
					);
					//if the action is deletion, there is no begin step, as there is nothing to edit.
					if($data['sys_todos'][$key]['action']=='delete' && $this->loadExecutor()) {
						$recId = $this->wfExe->beginWorkFlow($fields_values);
						if ($recId) {
							$fields_values['rec_reference'] = $recId;
							$fields_values['state'] ='started';
						}
					}
					$GLOBALS['TYPO3_DB']->debugOutput = 1;
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_todos_users_mm', $fields_values);
				}
				if($data['sys_todos'][$key]['observeWorkflow']) {
					$this->setUserAsObserver($todoUid);
				}
				$this->createNotification('create',$data['sys_todos'][$key]['target_user'],$todoUid);
				$this->scheduleReminders($todoUid);
			}
		} else {
			// Edit todo:
			$editRow = t3lib_BEfunc::getRecordRaw('sys_todos', "uid=".$key);
			if (is_array($editRow) && $editRow['cruser_id'] == $this->BE_USER->user['uid'] && $data['sys_todos'][$key]['title']) {
				$fields_values = array(
				'title' => $data['sys_todos'][$key]['title'],
				'deadline' => $data['sys_todos'][$key]['deadline'],
				'description' => $data['sys_todos'][$key]['description'],
				'tstamp' => time()
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_todos', 'uid='.intval($key), $fields_values);
			}
		}
		return $theCode;
	} //createTodo

	function markDone() {
		$action = t3lib_div::_GP('marked_todos_action');
		$done = t3lib_div::_GP('DONE');
		if (is_array($done)) {
			while (list($uidKey, $value) = each($done)) {
				$uidKey = intval($uidKey);
				if ($uidKey < 0) {
					$uidKey = abs($uidKey);
					$sys_todos_users_mm_row = t3lib_BEfunc::getRecordRaw('sys_todos_users_mm', "mm_uid=".$uidKey);
					if (is_array($sys_todos_users_mm_row)) {
						$sys_todos_row = t3lib_BEfunc::getRecordRaw('sys_todos', "uid=".intval($sys_todos_users_mm_row['uid_local']));
						if (is_array($sys_todos_row) && $sys_todos_row['cruser_id'] == $this->BE_USER->user['uid']) {

							$fields_values = array(
							'finished_instance' => $value?1:
							0 );
							if ($action == 127 && $value) $fields_values['deleted'] = 1;

							$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_todos_users_mm', "mm_uid=".intval($uidKey), $fields_values);

							// Check if there are any sys_todos_users_mm left, which are not deleted. If there are not, delete the sys_todos item
							$isNotDeleted = t3lib_BEfunc::getRecordRaw('sys_todos_users_mm', "uid_local=".intval($sys_todos_row['uid']).' AND deleted=0');
							if (!is_array($isNotDeleted)) {
								// Delete sys_todos
								$fields_values = array(
								'finished' => 1,
								'deleted' => 1 );
								$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_todos', "uid=".intval($sys_todos_row['uid']), $fields_values);
							}
						}
					}

				} else {
					$sys_todos_row = t3lib_BEfunc::getRecordRaw('sys_todos', "uid=".intval($uidKey));
					if (is_array($sys_todos_row) && $sys_todos_row['cruser_id'] == $this->BE_USER->user['uid']) {
						$fields_values = array('finished' => $value?1:0);
						if ($action == 127 && $value) $fields_values['deleted'] = 1;

						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_todos', "uid=".$uidKey, $fields_values);

						// Also set status for instances, if they are checked for main item:
						if ($fields_values['deleted']) {
							$inst_fields_values = array('deleted' => 1);

							// Update all relations to the sys_todos
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_todos_users_mm', "uid_local=".intval($uidKey), $inst_fields_values);
						}
					}
				}
			}
		}
	} //end markedTodos


	/**
		* [Describe function...]
		*
		* @param [type]  $todoTypes: ...
		* @param [type]  $type: ...
		* @return [type]  ...
		*/
	function todos_workflowTitle($todoTypes, $type) {
		if (!isset($this->todoTypesCache[$type])) {
			if (isset($todoTypes[$type])) {
				$this->todoTypesCache[$type] = $todoTypes[$type];
			} elseif (substr($type, 0, 3) == "wf_" && $this->workflowExtIsLoaded) {
				$workflowRecord = t3lib_BEfunc::getRecord('sys_workflows', substr($type, 3));
				$this->todoTypesCache[$type] = $workflowRecord['title'];
			}
		}
		return $this->todoTypesCache[$type];
	} //todos_workflowTitle

	/**
		* [Describe function...]
		*
		* @param [type]  $todoTypes: ...
		* @param [type]  $tUid: ...
		* @return [type]  ...
		*
		* @todo refactor!
		*/
	function todos_displayTodo($todoTypes, $tUid) {
		global $LANG;

		if ($tUid > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_todos', 'uid='.intval($tUid).' AND cruser_id='.intval($this->BE_USER->user['uid']).' AND NOT deleted');
		} else {
			$res = $this->exec_todos_getQueryForTodoRels(' AND sys_todos_users_mm.mm_uid='.abs($tUid));
		}


		//		@TODO: HER: ro2 er forkert skal ikke være staterecord, sammenlign med $row i den udkommenterede linie nedenfor
		//		$row = $this->getStateRecord($tUid);
		$row = $this->getStateRecord($tUid);
		$msg = array();
		//		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
		if ($row) {

			if (count($msg)) {
				$theCode .= $this->pObj->doc->spacer(20);
				$theCode .= $this->pObj->doc->section('<a name="todo"></a>'.$LANG->getLL('todo_details', 1), implode('', $msg), 0, 1, 0, 1);
			}
			// Edit form:
			if (t3lib_div::_GP('editTodo') && $row['cruser_id'] == $this->BE_USER->user['uid']) {
				$theCode .= $this->todos_createForm($todoTypes, $row);
			}
		}
		return $theCode;
	} //displayTodo

	function getDescription($row) {
		global $LANG;
		$editIcon = $row['cruser_id'] == $this->BE_USER->user['uid'] ? '<a href="index.php?sys_todos_uid='.$tUid.'&editTodo=1"><img src="'.$this->backPath.'gfx/edit2.gif" width="11" height="12" vspace=2 border="0" align=top></a>' :
		'';
		$iconName = 'tc_todos'.($row['cruser_id'] == $this->BE_USER->user['uid']?'':'_foreign').'.gif';
		$header = '<nobr><img src="'.$this->backPath.'gfx/i/'.$iconName.'" width="18" height="16" hspace=2 border=0 align=top title="'.$LANG->getLL('todos_item').' #'.$row['uid'].'">'.$editIcon.' <strong>'.htmlspecialchars($row['title']).'</strong></nobr><BR>';

		$theCode .= '<div class="workflow-top">';
		$theCode .= '<span class="header">'.$LANG->getLL('todos_createdBy').': </span><span class="content">'.$this->userGroupArray[2][$row['cruser_id']]['username'].' ('.$this->userGroupArray[2][$row['cruser_id']]['realName'].'), '.$this->dateTimeAge($row['crdate'], -1).'</span><br />';
		$dLine = $this->dateTimeAge($row['deadline'], -1).'&nbsp;';
		if ($row['deadline'] < time()) $dLine = '<span class="typo3-red">'.$dLine.'</span>';
		$theCode .= '<span class="header">'.$LANG->getLL('todos_deadline').': </span><span class="content">'. $dLine.'</span><br />';
		$theCode .= '<span class="header">'.$LANG->getLL('todos_description').'</span><span class="content">'.nl2br($row['description']).'</span><br />';


		if ($row['type'] && $row['type'] != 'plain') {
			$theCode .= '<span class="header">'.$LANG->getLL('todos_type').': </span><span class="content">'.$this->todos_workflowTitle($todoTypes, $row['type']).'</span><br />';
			$wF = $row['type'];
			if (substr($wF, 0, 3) == "wf_" && $this->loadDefinition()) {
				$workflowDef = $this->wfDef->getWorkFlow(substr($wF, 3));

				if (is_array($workflowDef) && $workflowDef['tablename']) {
					$theCode .= '<span class="header">'.$LANG->getLL('todos_workflowDescr').': </span><span class="content">'.$workflowDef['description'].'</span><br />';
				}
			}
		}
		if ($row['uid_foreign']) {
			$theCode .= '<span class="header">'.$LANG->getLL('todos_logEntries_lTargetedAt').': </span><span class="content">'.$this->printUserGroupName($row['uid_foreign'], 1).'</span><br />';
		} else {
			$theCode .= '<span class="header">'.$LANG->getLL('todos_logEntries_lnoTarget').'</span><br />';
		}

		if ($row['rec_reference']) {
			$theCode .= '<span class="header">'.$LANG->getLL('todos_logEntries_lRecordRelated').':<br>';
			$recRelParts = explode(':', $row['rec_reference']);
			$recordRelated = t3lib_BEfunc::getRecord($recRelParts[0], $recRelParts[1]);

			if ($recordRelated) {
				$theCode .= '<a href="" onClick="'.$this->getEditOnClick($row['rec_reference']).'"><img src="'.$this->backPath.'gfx/edit2.gif" width="11" height="12" hspace=3 vspace=2 border="0" align=top></a>'. ($recRelParts[0] == "pages" ? $this->pObj->doc->viewPageIcon($recRelParts[1], $this->backPath, 'align=top hspace=2') : ""). t3lib_iconWorks::getIconImage($recRelParts[0], $recordRelated, $this->backPath, ' align="top" hspace="2"'). htmlspecialchars($recordRelated[$TCA[$recRelParts[0]]['ctrl']['label']]);
				#				$theCode .= '<a href="index.php?sys_todos_uid='.$tUid.'&editRel=1"><img src="'.$this->backPath.'gfx/edit2.gif" width="11" height="12" hspace=3 vspace=2 border="0" align=top></a>'. ($recRelParts[0] == "pages" ? $this->pObj->doc->viewPageIcon($recRelParts[1], $this->backPath, 'align=top hspace=2') : ""). t3lib_iconWorks::getIconImage($recRelParts[0], $recordRelated, $this->backPath, ' align="top" hspace="2"'). htmlspecialchars($recordRelated[$TCA[$recRelParts[0]]['ctrl']['label']]);
			} else {
				$theCode .= '<span class="typo3-red"><strong>'.$LANG->getLL('todos_errorNoRecord').'</strong></span>';
			}
			/*				 <a href="'.$this->getEditRedirectUrlForReference($row['rec_reference']).'"> */
		}
		$theCode .= '</div>';

		return $header.$theCode;
	} //getDescription



	function getStateRecord($tUid,$checkUser=false) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
		'*',
		'sys_todos',
		"sys_todos_users_mm",
		'',
		"AND NOT sys_todos_users_mm.deleted AND NOT sys_todos.deleted".($tUid < 0?" AND sys_todos_users_mm.mm_uid=":" AND sys_todos.uid=").abs($tUid).($checkUser?' AND sys_todos_users_mm.uid_foreign='.$this->BE_USER->user['uid']:'')
		);
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	}

	/**$msg[]
	* [Describe function...]
	*
	* @param [type]  $rel_row: ...
	* @param [type]  $todo_row: ...
	* @param [type]  $workflow_row: ...
	* @param [type]  $tUid: ...
	* @param [type]  $countOfInstances: ...
	* @return [type]  ...
	*
	* @todo REFACTOR!
	*/
	function getStatus($rel_row, $tUid, $countOfInstances = 0) {
		global $LANG, $TCA;



		$noExtraFields = 0;

		$theCode = '';


		$this->insCounter++;
		//$this->insCounter.' /
		$iSt = ' ('.$LANG->getLL('todos_instance').' #'.$rel_row['mm_uid'].')';

		$theCode .= '<BR><strong>'.$LANG->getLL('todos_logEntries').$iSt.':</strong><BR>';
		$log = unserialize($rel_row['status_log']);
		// 			$prevUsersGroups = array();
		if (is_array($log)) {
			$lines = array();

			reset($log);
			$c = 0;
			while (list(, $logDat) = each($log)) {
				// 					$prevUsersGroups[] = $logDat['uid_foreign_before'];
				//  debug($logDat);
				if ($logDat['status_log_clear']) {
					$c = 0;
					$lines = array();
				}

				$c++;
				$bgColorClass = ($c+1)%2 ? 'bgColor' :
				'bgColor-10';
				$lines[] = '<tr class="'.$bgColorClass.'">
						<td valign=top nowrap width=10%>'. '<strong>'.$LANG->getLL('todos_status_'.$logDat['code']).'</strong><BR>'. $this->printUserGroupName($logDat['issuer']).'<BR>'. $this->dateTimeAge($logDat['tstamp'], 1).'<BR>'. (isset($logDat['uid_foreign']) ? "<em>".$LANG->getLL('todos_logEntries_lTarget').'</em>:<BR>'.$this->printUserGroupName($logDat['uid_foreign']).'<BR>' : ''). '<BR></td>
						<td valign=top>&nbsp;&nbsp;&nbsp;</td>
						<td valign=top width=80%>'.nl2br(htmlspecialchars($logDat['comment'])).'</td>
						</tr>';
			}

			array_unshift($lines, '<tr class="bgColor5">
					<td nowrap><strong>'.$LANG->getLL('todos_logEntries_lStatusInfo').':</strong></td>
					<td>&nbsp;</td>
					<td nowrap><strong>'.$LANG->getLL('todos_logEntries_lDetails').':</strong></td>
					</tr>');

			$theCode .= '<table border=0 cellpadding=0 cellspacing=0 width="100%">'.implode('', $lines).'</table>';
		} else {
			$theCode .= $LANG->getLL('todos_logEntries_msg1').'<br>';
		}
		$theCode .= '<BR><BR>';

		//		return $theCode.$formCodeAccu;
		return $theCode;
	} //todos_printStatus

	function getUpdateForm($tUid, $countOfInstances = 0) {
		global $LANG;
		$row = $this->getStateRecord($tUid);

		$wF = $row['type'];
		if (substr($wF, 0, 3) == "wf_" && $this->loadDefinition()) {
			$workflow_row = $this->wfDef->getWorkFlow(substr($wF, 3));
			$transitions = $this->wfDef->getTransitions($row['state'],$workflow_row['uid'],$row['action']);
		}


		if(is_array($transitions) && sizeof($transitions)>0) {
			$code .= '<textarea style="float: left;"rows="10" name="data[sys_todos_users_mm]['.$row['mm_uid'].'][status][comment]"'.$this->pObj->doc->formWidthText(40, 'height: 100px;', '').'></textarea><div class="buttons">';
			foreach ($transitions as $key => $transition) {
				if(is_array($transition['targets'])) {
					$code .= '<input  id="'.$key.'" type="button" value="'.$transition['label'].'" onClick="displayTargets(this);" />';
					$code .= '<div id="'.$key.'-targets" class="targets">';
					foreach ($transition['targets'] as $target) {
						$code .= '<a href="javascript:clickTarget(\''.$key.'\',\''.$target['uid'].'\')" >'. $this->printUserGroupName($target['uid'], 1).'</a><br />';
					}
					$code .= '<a class="cancel-targets" href="javascript:hideTargets(\''.$key.'-targets\');">'.$LANG->getLL('lCancel').'</a></div><br />';

				} else {
					$code .= '<input  id="'.$key.'" type="button" value="'.$transition['label'].'" onClick="setAndSubmit(this);" /><br/>';
				}
			}
			$code .= '<input type="checkbox" name="data[sys_todos_users_mm]['.$row['mm_uid'].'][observeWorkflow]" value="1"'.($this->isUserObserving(abs($tUid))?' checked="checked" ':'').'>'.$LANG->getLL('email_notification').'</div><input id="workflow-code" type="hidden" name="data[sys_todos_users_mm]['.$row['mm_uid'].'][status][code]" value="" /><input type="hidden" name="newStatus" value="'.$LANG->getLL('todos_newStatus').'"><input id="workflow-uid" type="hidden" name="sys_todos_uid" value="'.$tUid.'"><input id="workflow-target"type="hidden" name="data[sys_todos_users_mm]['.$row['mm_uid'].'][status][newTarget]" value="">';
			$jscode = '
<script language="javascript">
function setAndSubmit(button) {
  var code = document.getElementById(\'workflow-code\');
  code.value = button.id;
  button.form.submit();
}

function displayTargets(button) {
  var targets = document.getElementById(button.id+\'-targets\');
  targets.style.display = \'block\';
  button.blur();
}

function hideTargets(id) {
 var targets = document.getElementById(id);
  targets.style.display = \'none\';
}

function clickTarget(buttonID,targetUID) {
  var button = document.getElementById(buttonID);
  var code = document.getElementById(\'workflow-code\');
  var target = document.getElementById(\'workflow-target\');
  var uid = document.getElementById(\'workflow-uid\');
  uid.value = \'\';
  target.value = targetUID;
  code.value = button.id;
  button.form.submit();
}
</script>
';

		} else {
			$code = $LANG->getLL('todos_already_published');
		}

		return '<div class="action">'.$jscode.$code.'<div style="width: 100%; height: 1px; clear: both;"></div></div>';
		/*
		if (substr($wF, 0, 3) == "wf_" && $this->loadDefinition()) {
		$workflow_row = $this->wfDef->getWorkFlow(substr($wF, 3));
		}
		if ($this->loadDefinition() && is_array($workflow_row)) {
		$revUsers = $this->wfDef->getReviewUsers($workflow_row['uid']);
		}
		else {
		$revUsers = array();
		}

		if ($this->loadDefinition() && is_array($workflow_row)) {
		$revUsers = t3lib_div::array_merge($revUsers,$this->wfDef->getPublishingUsers($workflow_row['uid']));
		}

		// ****************************
		// Status selector
		// ****************************
		$opt = Array();

		$statusLabels_copy = $this->wfDef->getTransitions($row['state']);


		// If finalized:
		if ($row['finalized']) {
		$statusLabels_copy = array();
		if ($this->BE_USER->user['uid'] == $row['cruser_id']) {
		//    $statusLabels_copy[101] = $statusLabels[101];
		$statusLabels_copy[102] = $statusLabels[102];
		$noExtraFields = 1;
		}
		}
		$allowedTargetCodes = 'end,passon,reject,review';

		$formCodeAccu = '';
		if (count($statusLabels_copy)) {
		reset($statusLabels_copy);
		if ($countOfInstances > 1 || $this->BE_USER->user['uid'] == $row['cruser_id']) $opt[] = '<option value="0"></option>';
		while (list($kk, $vv) = each(c_copy)) {
		$opt[] = '<option value="'.$kk.'">'.$vv.'</option>';
		}
		$onChange = "var allowedCodes=',".$allowedTargetCodes.",';
		if (allowedCodes.indexOf(','+this.options[this.selectedIndex].value+',')==-1) {
		document.editform['data[sys_todos_users_mm][".$row['mm_uid']."][status][newTarget]'].selectedIndex=0;
		}";
		$formCodeAccu .= htmlspecialchars($LANG->getLL('todos_status_addStatus')).':<BR><select name="data[sys_todos_users_mm]['.$row['mm_uid'].'][status][code]" onChange="'.$onChange.'">'.implode('', $opt).'</select><BR>';

		if (!$noExtraFields) {
		$opt = Array();
		$opt[] = '<option value="0"></option>';
		//    $opt[]='<option value="0">[ '.htmlspecialchars($LANG->getLL('todos_selectTargetUG')).' ]</option>';

		// Sender
		$revUserRec = t3lib_BEfunc::getRecord('be_users', $row['cruser_id']);
		$opt[] = '<option value="'.$row['cruser_id'].'">'.htmlspecialchars($LANG->getLL('todos_sender').': '.$revUserRec['username'].($revUserRec['realName']?" (".$revUserRec['realName'].")":"")).'</option>';

		// Review users:
		reset($revUsers);
		while (list($u_id, $revUserRec) = each($revUsers)) {
		// CHECK IF they
		$opt[] = '<option value="'.$u_id.'">'.htmlspecialchars($LANG->getLL('todos_reviewer').': '.$revUserRec['username'].($revUserRec['realName']?" (".$revUserRec['realName'].")":"")).'</option>';
		}

		// 					// Users through time:
		// 					$prevUsersGroups[] = $this->BE_USER->user['uid'];
		// 					$prevUsersGroups[] = $row['uid_foreign'];
		// 					if (is_array($prevUsersGroups) && count($prevUsersGroups)) {
		// 						$opt[] = '<option value="0"></option>';
		// 						$opt[] = '<option value="0">'.htmlspecialchars($LANG->getLL('todos_pastUG')).'</option>';
		// 						$prevUsersGroups = array_unique($prevUsersGroups);
		// 						reset($prevUsersGroups);
		// 						while (list(, $UGuid) = each($prevUsersGroups)) {
		// 							if ($UGuid) $opt[] = '<option value="'.$UGuid.'">'.htmlspecialchars(($UGuid > 0?$LANG->getLL('todos_user'):$LANG->getLL('todos_group')).': '.$this->printUserGroupName($UGuid)).'</option>';
		// 						}
		// 					}

		if ($this->BE_USER->user['uid'] == $row['cruser_id']) {
		$opt[] = '<option value="0"></option>';
		$opt[] = '<option value="0">'.htmlspecialchars($LANG->getLL('todos_allUG')).'</option>';

		if ($row['type'] == 'plain') {
		$opt = array_merge($opt, $this->tasks_makeTargetSelector($this->userGroupArray[0], $this->userGroupArray[1], 0, 1));
		} elseif (is_array($workflow_row)) {
		$grL = implode(',', t3lib_div::intExplode(',', $workflow_row['target_groups']));
		$wf_groupArray = t3lib_BEfunc::getGroupNames('title,uid', "AND uid IN (".($grL?$grL:0).')');
		$wf_userArray = $this->blindUserNames($this->userGroupArray[2], array_keys($wf_groupArray), 1);
		$opt = array_merge($opt, $this->tasks_makeTargetSelector($wf_userArray, $wf_groupArray, 0, 1));
		}
		}

		$onChange = "var allowedCodes=',".$allowedTargetCodes.",';
		if (allowedCodes.indexOf(
		','
		+ document.editform['data[sys_todos_users_mm][".$row['mm_uid']."][status][code]'].options[document.editform['data[sys_todos_users_mm][".$row['mm_uid']."][status][code]'].selectedIndex].value
		+',')==-1 || this.options[this.selectedIndex].value==0) {
		this.selectedIndex=0;
		}";
		$formCodeAccu .= htmlspecialchars($LANG->getLL('todos_status_selectTarget')).':<BR><select name="data[sys_todos_users_mm]['.$row['mm_uid'].'][status][newTarget]" onChange="'.$onChange.'">'.implode('', $opt).'</select><BR>';

		$formCodeAccu .= htmlspecialchars($LANG->getLL('todos_statusNote')).':<BR><textarea rows="10" name="data[sys_todos_users_mm]['.$row['mm_uid'].'][status][comment]"'.$this->pObj->doc->formWidthText(40, '', '').'></textarea><BR>';
		}
		}
		if ($this->BE_USER->user['uid'] == $row['cruser_id']) {
		$formCodeAccu .= '<input type="hidden" name="data[sys_todos_users_mm]['.$row['mm_uid'].'][finished_instance]" value="0"><input type="checkbox" name="data[sys_todos_users_mm]['.$row['mm_uid'].'][finished_instance]" value="1"'.($row['finished_instance']?" checked":"").'>'. htmlspecialchars($LANG->getLL('todos_finished'));
		}
		$formCodeAccu .= '<BR><input type="submit" name="newStatus" value="'.$LANG->getLL('todos_newStatus').'"> <input type="submit" name="cancel" value="'.$LANG->getLL('lCancel').'" onClick="document.editform.sys_todos_uid.value=0;"><input type="hidden" name="sys_todos_uid" value="'.$tUid.'"><BR>';

		return $formCodeAccu;
		*/
	}

	/**
	 * Negate commaseperated list of integers. Negative uids are used to indicate groups, in opposition to users, in various lists.
	 *
	 * @param  String  $list: commaseperated list of integers
	 * @return String      commaseperated list of negated integers
	 */
	function negateList($list) {
		$listArr = explode(',', $list);
		while (list($k, $v) = each($listArr)) {
			$listArr[$k] = $v * -1;
		}
		//  debug(implode(',',$listArr));
		return implode(',', $listArr);
	} //negateList

	/**
	 * Build and execute query for selecting todo items related to the current user
	 *
	 * @param  String      $extraWhere:   Extra where conditions
	 * @param  String      $selectFields: Fields to select.
	 * @param  Bool       $allowOwn:    If set tasks created by the user is also selected, else only tasks assigned to the user
	 * @return Resource pointer         Pointer to mysql result
	 */
	function exec_todos_getQueryForTodoRels($extraWhere = "", $selectFields = "", $allowOwn = 0) {
		$groupQ = $this->BE_USER->user['usergroup_cached_list'] ? " OR sys_todos_users_mm.uid_foreign IN (".$this->negateList($this->BE_USER->user['usergroup_cached_list']).")" :
		"";
		if ($allowOwn) $groupQ .= ' OR sys_todos.cruser_id='.intval($this->BE_USER->user['uid']);

		return $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
		$selectFields?$selectFields:
		"sys_todos.*,sys_todos_users_mm.mm_uid,sys_todos_users_mm.uid_foreign,sys_todos_users_mm.finished_instance",
		'sys_todos',
		"sys_todos_users_mm",
		'',
		" AND (sys_todos_users_mm.uid_foreign=".intval($this->BE_USER->user['uid']).$groupQ.')'. // UID foreign must match the current users id OR be within the group-list of the user
		" AND sys_todos.deleted=0 AND sys_todos_users_mm.deleted=0". // Todo AND it's relation must not be deleted
		" AND ((sys_todos.finished=0 AND sys_todos_users_mm.finished_instance=0) OR sys_todos.cruser_id=".intval($this->BE_USER->user['uid']).')'. // Either the user must own his todo-item (in which case finished items are displayed) OR the item must NOT be finished (which will remove it from others lists.)
		$extraWhere,
		'',
		'sys_todos.deadline' ); // Sort by deadline
	} //exec_todos_getQueryForTodoRels


	/**
	 * Generate dipslaying of main todo screen (right frame)
	 *
	 * @param  Array  $todoTypes: Array of available todo items (for generating list to make new items)
	 * @param  Int   $tUid: Uid for selected task (negative uid indicate ingoing task, positive outgoing)
	 * @return String HTML
	 * @todo clean this up!
	 */
	function todos_displayLists($todoTypes, $tUid) {
		global $LANG;

		$lines = array();
		$ownCount = 0;


		//Header
		$lines[] = '<tr>
				<td class="bgColor5">&nbsp;</td>
				<td class="bgColor5" width=50%><strong>'.$LANG->getLL('todos_title').':</strong></td>
				<td class="bgColor5" width=25%><strong>'.$LANG->getLL('todos_type').':</strong></td>
				<td class="bgColor5" width=25%><strong>'.$LANG->getLL('todos_deadline').':</strong></td>
				<td class="bgColor5"><strong>'.$LANG->getLL('todos_finished').':</strong></td>
				</tr>';

		// SELECT Incoming todos (incl. own todos):
		// Incoming todos are those set for a user which he must carry out. Those are the relations in sys_todos_users_mm where uid_foreign is either the users uid or the negative uid of a group his a member of
		$res = $this->exec_todos_getQueryForTodoRels();

		$out = '';
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			$c = 0;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$c++;
				if ($tUid == -$row['mm_uid']) {
					$bTb = '<B>';
					$bTe = '</B>';
					$active = '<img src="'.$this->backPath.'gfx/content_client.gif" width="7" height="10" border="0" align=top>';
				} else {
					$bTb = $bTe = '';
					$active = '';
				}
				$t_dL = $this->dateTimeAge($row['deadline'], -1);
				$t_dL = ($row['deadline'] > time()) ? $t_dL :
				'<span class="typo3-red">'.$t_dL.'</span>';
				$iconName = 'tc_todos'.($row['cruser_id'] == $this->BE_USER->user['uid']?'':'_foreign').($row['uid_foreign'] >= 0?'':'_group').'.gif';
				$bgColorClass = ($c+1)%2 ? 'bgColor' :
				'bgColor-10';
				$lines[] = '<tr>
						<td class="'.$bgColorClass.'">'.$this->linkTodos('<img src="'.$this->backPath.'gfx/i/'.$iconName.'" width="18" height="16" hspace=2 border=0 title="'.$LANG->getLL('todos_instance').' #'.$row['mm_uid'].',  '.htmlspecialchars($LANG->getLL('todos_createdBy').': '.$this->userGroupArray[2][$row['cruser_id']]['username'].' ('.$this->userGroupArray[2][$row['cruser_id']]['realName'].')').'">', -$row['mm_uid']).'</td>
						<td class="'.$bgColorClass.'" nowrap>'.$this->linkTodos($active.$bTb.'&nbsp;'.htmlspecialchars($this->fixed_lgd($row['title'])).'&nbsp;'.$bTb, -$row['mm_uid']).'</td>
						<td class="'.$bgColorClass.'" nowrap>&nbsp;'.t3lib_div::fixed_lgd($this->todos_workflowTitle($todoTypes, $row['type']), 15).'&nbsp;</td>
						<td class="'.$bgColorClass.'" nowrap>'.$t_dL.'&nbsp;</td>
						<td class="'.$bgColorClass.'" align=right>'.($row['cruser_id'] == $this->BE_USER->user['uid']?'<input type="hidden" name="DONE['.-$row['mm_uid'].']" value=0><input type="checkbox" name="DONE['.-$row['mm_uid'].']" value="1"'.($row['finished_instance']?" checked":"").'>':'&nbsp;').'</td>
						</tr>';

				if ($row['cruser_id'] == $this->BE_USER->user['uid']) $ownCount++;
			}
			// 			} else {
			// 				$lines[] = 'No incoming workflows<br />';
		}



		// SELECT Master todos for list:
		// A master todo is an OUTGOING todo you have created, in this case for other users.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
		'sys_todos.*,sys_todos_users_mm.uid_foreign',
		'sys_todos',
		"sys_todos_users_mm",
		'',
		" AND sys_todos_users_mm.uid_foreign!=".intval($this->BE_USER->user['uid']). ' AND sys_todos.cruser_id='.intval($this->BE_USER->user['uid']). " AND sys_todos.deleted=0",
		'sys_todos.uid',
		'sys_todos.deadline' );
		$out = '';
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {

			$lines[] = '<tr><td colspan=5>&nbsp;</td></tr>';
			$lines[] = '<tr>
					<td class="bgColor5">&nbsp;</td>
					<td class="bgColor5" colspan="4"><strong>'.$LANG->getLL('todos_list_master').':</strong></td>
					</tr>';

			$c = 0;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$c++;
				$bgColorClass = ($c+1)%2 ? 'bgColor' :
				'bgColor-10';
				if ($tUid == $row['uid']) {
					$bTb = '<B>';
					$bTe = '</B>';
					$active = '<img src="'.$this->backPath.'gfx/content_client.gif" width="7" height="10" border="0" align=top>';
				} else {
					$bTb = $bTe = '';
					$active = '';
				}
				$t_dL = $this->dateTimeAge($row['deadline'], -1);
				$t_dL = ($row['deadline'] > time()) ? $t_dL :
				'<span class="typo3-red">'.$t_dL.'</span>';
				$iconName = 'tc_todos'.($row['uid_foreign'] >= 0?'':'_group').'.gif';
				$bgColorClass = ($c+1)%2 ? 'bgColor' :
				'bgColor-10';
				$lines[] = '<tr>
						<td class="'.$bgColorClass.'">'.$this->linkTodos('<img src="'.$this->backPath.'gfx/i/'.$iconName.'" width="18" height="16" hspace=2 border=0 title="'.$LANG->getLL('todos_item').' #'.$row['uid'].', '.htmlspecialchars($LANG->getLL('todos_createdBy').': '.$this->userGroupArray[2][$row['cruser_id']]['username'].' ('.$this->userGroupArray[2][$row['cruser_id']]['realName'].')').'">', $row['uid']).'</td>
						<td class="'.$bgColorClass.'" nowrap>'.$this->linkTodos($active.$bTb.'&nbsp;'.htmlspecialchars($this->fixed_lgd($row['title'])).'&nbsp;'.$bTb, $row['uid']).'</td>
						<td class="'.$bgColorClass.'" nowrap>&nbsp;'.t3lib_div::fixed_lgd($this->todos_workflowTitle($todoTypes, $row['type']), 15).'&nbsp;</td>
						<td class="'.$bgColorClass.'" nowrap>'.$t_dL.'&nbsp;</td>
						<td class="'.$bgColorClass.'" align=right>'.($row['cruser_id'] == $this->BE_USER->user['uid']?'<input type="hidden" name="DONE['.$row['uid'].']" value=0><input type="checkbox" name="DONE['.$row['uid'].']" value="1"'.($row['finished']?" checked":"").'>':'&nbsp;').'</td>
						</tr>';

				if ($row['cruser_id'] == $this->BE_USER->user['uid']) $ownCount++;
			}

			//   $out = '<table border=0 cellpadding=0 cellspacing=0>'.implode('',$lines).'</table>';
			//   $theCode.= $this->pObj->doc->spacer(10);
			//   $theCode.= $this->pObj->doc->section($LANG->getLL('todos_list_master'),$out,1,0);
			// 			} else {
			// 				$lines[] = 'No outgoing workflows<br />';
		}

		if (count($lines) > 1) {
			$out = '<table border=0 cellpadding=0 cellspacing=0>'.implode('', $lines).'</table>';

			if ($ownCount) {
				$bMenu = '<BR><div align=right><select name="marked_todos_action">
						<option value=-1></option>
						<option value=127>'.$LANG->getLL('todos_purge').'</option>
						</select><input type="submit" name="marked_todos" value="'.$LANG->getLL('todos_updateMarked').'"></div>';
			}
			else $bMenu = '';


			$theCode .= $this->pObj->doc->section($LANG->getLL('todos_list'), $out.$bMenu, 0, 1);
		}
		return $theCode;
	} //todos_displayLists

	/**
	 * Generate forms for creating new todo items, if a workflow_type
	 * has been submitted or a record to edit has been passed, then the
	 * form is made, else a list of possible todos is shown
	 *
	 * @param  Array  $todoTypes: list of available todo items
	 * @param  Array  $editRec: Array containg the details of the record to be edited.
	 * @return String Form that let's you create/edit a workflow record (HTML)
	 *
	 * @todo clean up
	 */
	function todos_createForm($todoTypes, $editRec = '') {
		global $LANG;

		// CREATE/EDIT/VIEW TODO:
		$wF = is_array($editRec) ? $editRec['type'] : t3lib_div::_GP('workflow_type');

		if ($wF && isset($todoTypes[$wF])) {
			$type = is_array($editRec) ? $editRec['uid'] : "NEW";
			$formA = array();
			if (!is_array($editRec)) {
				// Making the target_user/group selector:
				if ($wF == 'plain') {
					// If the type is plain, the todo-item may be assigned to all users accessible for the current user.
					//	 Title selector:
					$formA[] = array($LANG->getLL('todos_type').":&nbsp;", $LANG->getLL('todos_plain'));
					$formA[] = $this->tasks_makeTargetSelector($this->userGroupArray[0], $this->userGroupArray[1], $type);

				} elseif (substr($wF, 0, 3) == "wf_" && $this->loadDefinition()) {
					// If	 it's a workflow from sys_workflows table, the list of target groups and users is re-fetched, according the the target_groups definition.
					$workflowDef = $this->wfDef->getWorkFlow(substr($wF, 3),t3lib_div::_GET('table'),t3lib_div::_GET('action'));
					if (is_array($workflowDef) && t3lib_div::_GET('table')) {

						$top .= '<div class="workflow-top"><h1>'. $LANG->getLL('todos_new_workflow') .'</h1>';
						$top .= '<span class="header">'.$LANG->getLL('todos_type').': 											</span><span class="content">' . htmlspecialchars($workflowDef['title']).'</span><br />';
						$top .= '<span class="header">'. $LANG->getLL('todos_description') .'</span><span class="content">' . htmlspecialchars($workflowDef['description']).'</span><br />';
						$refRecord = t3lib_BEfunc::getRecord(t3lib_div::_GET('table'),t3lib_div::_GET('uid'));
						/**
						 * @todo title-field of record shoud be looked up in TCA
						 */
						$top .= '<span class="header">Current action: </span><span class="content">' . t3lib_div::_GET('action').' - '.t3lib_iconWorks::getIconImage(t3lib_div::_GET('table'),$refRecord,$this->backPath).$refRecord['title'].'</span><br />';
						$top .= '</div>';
						$hidden = '<input type="hidden" name="data[sys_todos]['.$type.'][action]" value="'.t3lib_div::_GET('action').'">'.
						'<input type="hidden" name="data[sys_todos]['.$type.'][table]" value="'.t3lib_div::_GET('table').'">'.
						'<input type="hidden" name="data[sys_todos]['.$type.'][uid]" value="'.t3lib_div::_GET('uid').'">';
						// Get groupnames for todo-tasks

						/*
						* Action block
						*/

						$action = '<div class="action">';
						if (t3lib_div::_GET('action')=='delete') {
							$wf_userArray = $this->wfDef->getReviewUsers($workflowDef['uid']);
						} else {
							$grL = implode(',', t3lib_div::intExplode(',', $workflowDef['target_groups']));
							$wf_groupArray = t3lib_BEfunc::getGroupNames('title,uid', "AND uid IN (".($grL?$grL:0).')');
							$wf_userArray = $this->blindUserNames($this->userGroupArray[2], array_keys($wf_groupArray));
						}
						$action .= '<span class="header">'.$LANG->getLL('todos_target').'</span>'. $this->tasks_makeTargetSelector($wf_userArray, $wf_groupArray, $type);

						// 	Title selector:
						$action .= '<span class="header">'.$LANG->getLL('todos_title').'</span>'. '<input type="text" name="data[sys_todos]['.$type.'][title]" value="'.htmlspecialchars(is_array($editRec)?$editRec['title']:$todoTypes[$wF]).'" max=255'.$this->pObj->doc->formWidth(40).'>';

						//	 Deadline selector:
						$curTodoTime = time();
						$action .= '<span class="header">'.$LANG->getLL('todos_deadline').'</span>'. '<input type="text" name="data[sys_todos]['.$type.'][deadline]_hr'.'" onChange="typo3FormFieldGet(\'data[sys_todos]['.$type.'][deadline]\', \'datetime\', \'\', 0,0);"'.$this->pObj->doc->formWidth(20).'>
					<input type="hidden" value="'.intval($editRec['deadline']).'" name="data[sys_todos]['.$type.'][deadline]">
					<select name="_time_selector" onChange="
					document.forms[0][\'data[sys_todos]['.$type.'][deadline]\'].value=(this.options[this.selectedIndex].value>0?this.options[this.selectedIndex].value:(document.forms[0][\'data[sys_todos]['.$type.'][deadline]\'].value!=\'0\'?document.forms[0][\'data[sys_todos]['.$type.'][deadline]\'].value:'.time().')-this.options[this.selectedIndex].value);
					this.selectedIndex=0;
					typo3FormFieldSet(\'data[sys_todos]['.$type.'][deadline]\', \'datetime\', \'\', 0,0);
					">
					<option value="0"></option>
					<option value="'.(mktime(0, 0, 0)+3600 * 12).'">'.$LANG->getLL('todos_DL_today').'</option>
					<option value="'.(mktime(0, 0, 0)+3600 * 24+3600 * 12).'">'.$LANG->getLL('todos_DL_tomorrow').'</option>
					<option value="'.(mktime(0, 0, 0)+3600 * 24 * 7+3600 * 12).'">'.$LANG->getLL('todos_DL_weekLater').'</option>
					<option value="'.(mktime(0, 0, 0)+3600 * 24 * 31+3600 * 12).'">'.$LANG->getLL('todos_DL_monthLater').'</option>
					<option value="'.(-3600 * 24 * 1).'">+1 '.$LANG->getLL('todos_DL_day').'</option>
					<option value="'.(-3600 * 24 * 2).'">+2 '.$LANG->getLL('todos_DL_days').'</option>
					<option value="'.(-3600 * 24 * 4).'">+4 '.$LANG->getLL('todos_DL_days').'</option>
					<option value="'.(-3600 * 24 * 7).'">+7 '.$LANG->getLL('todos_DL_days').'</option>
					<option value="'.(-3600 * 24 * 14).'">+14 '.$LANG->getLL('todos_DL_days').'</option>
					<option value="'.(-3600 * 24 * 31).'">+31 '.$LANG->getLL('todos_DL_days').'</option>
					</select>
					';

						$t3lib_TCEforms = t3lib_div::makeInstance('t3lib_TCEforms');
						$t3lib_TCEforms->backPath = $this->backPath;

						$t3lib_TCEforms->extJSCODE .= 'typo3FormFieldSet("data[sys_todos]['.$type.'][deadline]", \'datetime\', "", 0,0);';

						// Description:
						$action .= '<span class="header">'.$LANG->getLL('todos_description').'</span>'. '<textarea rows="10" name="data[sys_todos]['.$type.'][description]"'.$this->pObj->doc->formWidthText(40, '', '').'>'.t3lib_div::formatForTextarea(is_array($editRec)?$editRec['description']:"").'</textarea>';

						// Notify 	email:
						if (!is_array($editRec) && $this->BE_USER->user['email'] && t3lib_extMgm::isLoaded('gabriel')) {
							$action .= '<br /><input type="checkbox" name="data[sys_todos]['.$type.'][observeWorkflow]" value="1">'.$LANG->getLL('email_notification');
						}

						$onClick = "if (document.forms[0]['data[sys_todos][".$type."][title]'].value=='') {alert(".$GLOBALS['LANG']->JScharCode($LANG->getLL('todos_mustFillIn')).');return false;}';
						$hidden .= '<input type=hidden name="data[sys_todos]['.$type.'][type]" value="'.htmlspecialchars($wF).'">';
						if ($type == 'NEW') {
							$action .= '<br /><input type="submit" name="create_todo" value="'.$LANG->getLL('lCreate').'" onClick="'.$onClick.'"> <input type="submit" value="'.$LANG->getLL('lCancel').'">';
						} else {
							$action .= '<input type="submit" name="create_todo" value="'.$LANG->getLL('lUpdate').'"><input type="hidden" name="sys_todos_uid" value="'.$editRec['uid'].'">';
						}
						$action .= '</div>';

						//						$theCode .= $this->pObj->doc->section('<a name="new"></a>'.$LANG->getLL(is_array($editRec)?"todos_update":"todos_new", 1),
						//						$theCode .= $top.$hidden.$action.$this->pObj->doc->table($formA).$hidden.$t3lib_TCEforms->JSbottom();
						$theCode .= $top.$hidden.$action.$hidden.$t3lib_TCEforms->JSbottom();
					}
				}
			}


			return $theCode;
		}
	} //todos_createForm

	/**
	 * [Describe function...]
	 *
	 * @param [type]  $str: ...
	 * @param [type]  $id: ...
	 * @return [type]  ...
	 */
	function linkTodos($str, $id) {
		$str = '<a href="index.php?sys_todos_uid='.$id.'">'.$str.'</a>';
		return $str;
	} //linkTodos

	/**
	 * [Describe function...]
	 *
	 * @param [type]  $recRef: ...
	 * @return [type]  ...
	 */
	function getEditRedirectUrlForReference($recRef,$new=false) {
		$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$loadModules->load($GLOBALS['TBE_MODULES']);

		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		if($newPageModule) {
			$pageModuleParts = explode('_',$newPageModule);
			if (is_array($loadModules->modules[$pageModuleParts[0]]['sub'][$pageModuleParts[1]])) {
				$pageModuleURL = $loadModules->modules[$pageModuleParts[0]]['sub'][$pageModuleParts[1]]['script'];
			}

		}
		if(!$pageModuleURL) {
			if (is_array($loadModules->modules['web']['sub']['layout'])) {
				$pageModuleURL = $loadModules->modules['web']['sub']['layout']['script'];
			} else {
				die('Fatal error: No access to pagemodule!');
			}
		}


		$parts = explode(':', $recRef);
		if ($parts[0] == 'pages') {
			if($new) {
				$outUrl = $this->backPath.$pageModuleURL.'?id='.$parts[1].'&SET[function]=0&edit_record='.rawurlencode($parts[0].':'.$parts[1]);
				//			TODO: Remove line below
				#.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			} else {
				$outUrl = $this->backPath.$pageModuleURL.'?id='.$parts[1].'&SET[function]=1'."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			}
		} else {
			$outUrl = $this->backPath.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).'&edit['.$parts[0].']['.$parts[1].']=edit';
		}
		return $outUrl;
	} //getEditRedirectUrlForReference

	function getEditOnClick($recRef) {
		return htmlspecialchars('list_frame.document.location="'.$this->getEditRedirectUrlForReference($recRef).'";return false;');
		#		$parts = explode(':', $recRef);
		#		return htmlspecialchars('list_frame.'.t3lib_BEfunc::editOnClick('&edit['.$parts[0].']['.$parts[1].']=edit',$GLOBALS['BACK_PATH'],t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir.'close.html'));
	}

	/**
	 * Display user or group name with
	 *
	 * @param [type]  $uid: ...
	 * @param [type]  $icon: ...
	 * @return [type]  ...
	 */
	function printUserGroupName($uid, $icon = 0) {
		if ($uid > 0) {
			return ($icon?t3lib_iconWorks::getIconImage('be_users', t3lib_BEfunc::getRecord('be_users', $uid), $this->backPath, $params = ' align=top'):""). htmlspecialchars($this->userGroupArray[2][$uid]['username'].($this->userGroupArray[2][$uid]['realName']?" (".$this->userGroupArray[2][$uid]['realName'].")":""));
		} else {
			$grRec = t3lib_BEfunc::getRecord('be_groups', abs($uid));
			return ($icon?t3lib_iconWorks::getIconImage('be_groups', $grRec, $this->backPath, ' align="top"'):''). htmlspecialchars($grRec['title']);
		}
	} //printUserGroupName

	function blindUserNames($users,$allowedGroups) {
		foreach($allowedGroups as $group) {
			foreach($users as $key => $user) {
				if(t3lib_div::inList($user['usergroup_cached_list'],$group) || t3lib_div::inList($user['usergroup'],$group)) {
					$ok[$key] = $user;
					unset($users[$key]);
				}
			}
		}
		return $ok;
	} //blindUserNames

	function initLog($code,$prev_user,$comment) {
		$this->logData = array('code' => $code,
		'issuer' => $GLOBALS['BE_USER']->user['uid'],
		'tstamp' => time(),
		'uid_foreign_before' => $prev_user,
		'comment' => $comment
		);
	} //initLog


	function createNotification($code,$target,$wfUid) {
		if(t3lib_extMgm::isLoaded('gabriel')) {
			$notifier = t3lib_div::getUserObj('EXT:sys_workflows/class.tx_sysworkflows_notifier.php:tx_sysworkflows_notifier');
			$funcName = 'exec_'.$code;
			if(method_exists($notifier,$funcName)) {
				$notifier->setStateRecord($this->getStateRecord($wfUid));
				$notifier->setTarget($target);

				$notifier->$funcName();
			}
		} else {
			/** @todo log missing notification */
		}
	}

	function scheduleReminders($wfUid) {
		if(t3lib_extMgm::isLoaded('gabriel')) {
			$notifier = t3lib_div::getUserObj('EXT:sys_workflows/class.tx_sysworkflows_notifier.php:tx_sysworkflows_notifier');
			$notifier->setStateRecord($this->getStateRecord($wfUid));
			$notifier->reminders(60);
		} else {
			/** @todo log missing notification */
		}
	}

	function setUserAsObserver($wfUid) {
		if(!$this->isUserObserving($wfUid)) {
			$fields_values = array(
			'uid_local' => $wfUid,
			'uid_foreign' => $this->BE_USER->user['uid'] );
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_todos_notify_users_mm', $fields_values);
		}

	}

	function isUserObserving($wfUid) {
		$WHERE = 'uid_local='.$wfUid.' AND	uid_foreign='.$this->BE_USER->user['uid'];
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('count(*)','sys_todos_notify_users_mm', $WHERE);
		return $rows[0]['count(*)']?true:false;
	}

	function unsetUserAsObserver($wfUid) {
		$WHERE = 'uid_local='.$wfUid.' AND	uid_foreign='.$this->BE_USER->user['uid'];
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_todos_notify_users_mm', $WHERE);
	}

} //class mod_user_task

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_workflows/class.tx_sysworkflows.php']);
}

?>
