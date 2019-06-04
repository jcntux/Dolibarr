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

$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('iccashdesk'));

$parameters = array();
$reshook = $hookmanager->executeHooks('getLogin',$parameters);

/**
 *  Description and activation class for module ICCashDesk
 */
class modICCashDesk extends DolibarrModules {
	function __construct($db) {
		global $langs, $conf;

		$conf->iccashdesk = new StdClass();
		$this->db = $db;
		$this->numero = 500100;
		$this->family = "IComm";
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "IComm Cashdesk";
		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		$this->picto = 'module@iccashdesk';
		$this->rights_class = 'iccashdesk';

		$this->module_parts = array(
			'triggers' => TRUE,
			'css' => array('/iccashdesk/css/module.css.php'),
			'js' => array('/iccashdesk/js/module.js'),
			'hooks' => array('getLogin'),
		);

		$this->dirs = array();
		//$this->config_page_url = array("iccashdesk.php@iccashdesk");
		$this->depends = array("modBanque","modFacture","modProduct");
		$this->requiredby = array();
		$this->phpmin = array(5,0);
		$this->need_dolibarr_version = array(3,0);
		$this->langfiles = array("icashdesk@icashdesk");
		$this->const = array();
		$this->tabs = array();
		$this->dictionnaries = array();
		$this->boxes = array();

		if(!isset($conf->iccashdesk->enabled)) $conf->iccashdesk->enabled = 0;

		// Permissions
		$this->rights = array();
		$r = 0;

		$r++;
		$this->rights[$r][0] = 500101;
		$this->rights[$r][1] = 'Use IComm point of sale';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'use';
		//$this->rights[$r][5] = 'level2';

		// Main menu entries
		$this->menu = array();
		$r = 0;

		$this->menu[$r] = array(
			'fk_menu' => 0,
			'type' => 'top',
			'titre'=> 'ICCashDesk',
			'mainmenu' => 'iccashdesk',
			'url' => '/iccashdesk/index.php?user=__LOGIN__',
			'langs' => 'iccashdesk@icashdesk',
			'position' => 100,
			'enabled' => '$conf->iccashdesk->enabled',
			'perms' => '$user->rights->iccashdesk->use',
			'target' => 'icommpointofsale',
			'user' => 0,
		);
	}

	/**
	 * Function called when module is enabled.
	 */
	function init($options='') {
		$sql = array();

		$this->remove($options);
		$this->load_tables();

		addDocumentModel('vao', 'invoice');
		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 */
	function remove($options='') {
		$sql = array();

		delDocumentModel('vao', 'invoice');
		return $this->_remove($sql, $options);
	}

	function load_tables() {
		return $this->_load_tables('/iccashdesk/sql/');
	}
}

?>
