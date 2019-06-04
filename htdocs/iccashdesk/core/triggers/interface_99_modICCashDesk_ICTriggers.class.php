<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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

/**
 *  Class of triggers for ICCashDesk module
 */

// require_once DOL_DOCUMENT_ROOT . '/iccashdesk/class/ICommHelper.class.php';

class InterfaceICTriggers {
	var $db;

	/**
	*   Constructor
	*
	*   @param		DoliDB		$db      Database handler
	*/
	function __construct($db) {

		$this->db = $db;

		$this->name = preg_replace('/^Interface/i','',get_class($this));
		$this->family = "iccashdesk";
		$this->description = "Triggers of this module implement facture actions.";
		$this->version = 'dolibarr';
		$this->picto = 'trigger@iccashdesk';
	}


	/**
	*   Return name of trigger file
	*
	*   @return     string      Name of trigger file
	*/
	function getName() {
		return $this->name;
	}

	/**
	*   Return description of trigger file
	*
	*   @return     string      Description of trigger file
	*/
	function getDesc() {
		return $this->description;
	}

	/**
	*   Return version of trigger file
	*
	*   @return     string      Version of trigger file
	*/
	function getVersion() {
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("Development");
		elseif ($this->version == 'experimental') return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("Unknown");
	}

	/**
	*      Function called when a Dolibarrr business event is done.
	*      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	*
	*      @param	string		$action		Event action code
	*      @param  Object		$object     Object
	*      @param  User		$user       Object user
	*      @param  Translate	$langs      Object langs
	*      @param  conf		$conf       Object conf
	*      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
	*/
	function run_trigger($action, $object, $user, $langs, $conf) {
/*
		$error = 0;
		ICommHelper::init($this->db);

		// Bills
		if($action == 'BILL_CREATE' or $action == 'BILL_MODIFY' or $action == 'BILL_VALIDATE') {
			if($icinvoice = unserialize($_SESSION['ICommFacturation'])) {
				if(!ICommHelper::saveDetailSell($object->id, $icinvoice)) $error++;
			}

			if(ICommHelper::isNegSell($object) == TRUE) {
				if(!ICommHelper::saveNegSell($object->id)) $error++;
			}

		// See mouvementstock.class.php _create()
		} elseif ($action == 'STOCK_MOVEMENT') {

		} elseif ($action == 'LINEBILL_INSERT') {
		} elseif ($action == 'LINEBILL_UPDATE') {
		} elseif ($action == 'LINEBILL_DELETE') {

		} elseif ($action == 'BILL_DELETE') {
			if(!ICommHelper::deleteSell($object->id)) $error++;
		}

		return $error;
*/
	}
}
?>
