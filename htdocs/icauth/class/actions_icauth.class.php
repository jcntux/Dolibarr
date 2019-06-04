<?php
/* Copyright (C) 2013 Jean-christophe NOMOREDJO <jcnrdjo@yahoo.fr>
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

class ActionsICAuth extends CommonObject {
	var $db;

	function __construct($db) {
		global $langs;

		$this->db = $db;
		$langs->load("icauth@icauth");

		return 1;
	}

	function getLoginPageOptions($parameters, &$object, &$action, $hookmanager) {
		global $langs;

		$output = '';
		$exclude = array(1);

		$form = new Form($this->db);

		$output .= '<script type="text/javascript" src="/icauth/js/module.js"></script>';
		$output .= "<tr>\n";
		$output .= "<td valign=\"top\" nowrap=\"nowrap\"> &nbsp; \n";
		$output .= "<strong><label for=\"icauthuid\">" . $langs->trans('ShortcutLogin') . "</label></strong>\n";
		$output .= "</td>\n";
		$output .= "<td valign=\"top\" nowrap=\"nowrap\">\n";
		$output .= $form->select_dolusers(-1, 'icauthuid', 1, $exclude);
		$output .= "</td>\n";
		$output .= "</tr>\n";

		$this->results['options']['table'] = $output;

		return 0;
	}
}

?>
