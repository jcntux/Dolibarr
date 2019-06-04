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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/iccashdesk/include/function.inc';

icomm_boot();

$callback = isset($_GET['callback']) ? $_GET['callback'] : NULL;
if($callback == 'getproducts') {
	$string = '';

	if($name = GETPOST('name', '', 1)) {
		$string = GETPOST($name, '', 1);
	}

	if(!empty($string)) {
		$products = icomm_getproducts($string);

	} else {
		$products = array();
	}

	header("Content-Type: application/json; charset=utf-8");
	print json_encode($products);
	
} elseif($callback == 'loadproduct') {
	$pid = GETPOST('pid', 'int', 2);
	$product = icomm_theme_loadproduct($pid);

	header("Content-Type: application/json; charset=utf-8");
	print json_encode($product);

} elseif($callback == 'additem') {
	icomm_cart_additem();

	header('Location: ' . icomm_url('index.php', TRUE));

} elseif($callback == 'deleteitem') {
	icomm_cart_deleteitem();

	header('Location: ' . icomm_url('index.php', TRUE));

} elseif($callback == 'lock') {
	$_SESSION['iccashdesk_lockscreen'] = TRUE;

	print icomm_theme_lock_screen();

} elseif($callback == 'unlock') {
	unset($_SESSION['iccashdesk_lockscreen']);

} elseif($callback == 'adddate') {
	$name = GETPOST('name', '');
	$index = GETPOST('index', 'int');

	print icomm_theme_selectdate('DateEcheance', $name, $index);

} elseif($callback == 'payment') {
	$heads = icomm_add_js_var();
	$arrayofjs = array();
	$arrayofcss = array('/iccashdesk/css/style.css');
	top_htmlhead($heads, '', 0 ,0 ,$arrayofjs ,$arrayofcss);
	print "<body>\n";
	print "<div class=\"page\">\n";
	print icomm_theme_paymode();
	print "</div>\n";
	print "</body>\n";
	print "</html>\n";

} elseif($callback == 'invoice') {
	icomm_invoice();

} elseif($callback == 'delsusitem') {
	$sid = GETPOST('sid', 'int', 2);
	icomm_delete_suspended_item($sid);

} elseif($callback == 'action') {
	$action = GETPOST('action', '');
	if(empty($action)) $action = GETPOST('icomm-button-action', '', 2);

	if($action == $langs->transnoentities('NewSell')) {
		icomm_newsell();

		header('Location: ' . icomm_url('index.php', TRUE));

	} elseif($action == $langs->transnoentities('Suspend')) {
		icomm_suspend();

		header('Location: ' . icomm_url('index.php', TRUE));
	}

} elseif($callback == 'content') {
	$cid = GETPOST('cid', '', 1);

} elseif($callback == 'viewer') {
	$doctype = GETPOST('doctype', 'alpha', 1);
	$oid = GETPOST('oid', 'int', 1);
	print icomm_viewer($doctype, $oid);

} elseif($callback == 'addgrid-item') {
	$index = GETPOST('index', 'int');

	print icomm_theme_booking_newitem($index);

} elseif($callback == 'booking') {
	exit;
}

exit(0);

?>
