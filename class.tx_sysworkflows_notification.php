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

	var $rcp;
	var $subject;
	var $plainText;
	var $html;
	var $from_email;
	var $from_name;
	var	$returnPath;

	/**
	 * PHP4 wrapper for constructor
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
		$email->send($this->rcp);

	}

	function setRcp($rcp) {
		$this->rcp = $rcp;
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


}


?>