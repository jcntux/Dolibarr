<?php
/* Copyright (C) 2013 IComm NOMOREDJO Jean-christophe <jcnrdjo@yahoo.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

class modICAuth extends DolibarrModules {
	function __construct($db) {
		global $langs, $conf;

		$conf->icauth = new StdClass();
		$this->db = $db;
		$this->numero = 500101;
		$this->family = "IComm";
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "IComm authentication helper";
		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		$this->picto = 'module@icauth';
		$this->rights_class = 'icauth';

		$this->module_parts = array(
			'hooks' => array('mainloginpage'),
			'js' => array('/icauth/js/module.js'),
		);

		$this->dirs = array();
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(5,0);
		$this->need_dolibarr_version = array(3,0);
		$this->langfiles = array("icauth@icauth");
		$this->boxes = array();
		$this->menus = array();
		$this->rights = array();
		$this->const = array();

		if(!isset($conf->icauth->enabled)) $conf->icauth->enabled = 0;
	}

	function init($options='') {
		$sql = array();

		$this->remove($options);
		return $this->_init($sql, $options);
	}

	function remove($options='') {
		$sql = array();

		return $this->_remove($sql, $options);
	}
}

?>
