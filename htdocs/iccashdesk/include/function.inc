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

function icomm_boot() {
	global $user, $conf, $langs, $db, $form, $formproduct, $vars, $facturation;

	require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT . '/iccashdesk/class/ICommFacturation.class.php';

	$langs->load("iccashdesk@iccashdesk");
	$langs->load("main");

	$form = new Form($db);
	$formproduct = new FormProduct($db);

	$thirdpartyid = (GETPOST('socid','int') != '') ? GETPOST('socid','int') : $conf->global->CASHDESK_ID_THIRDPARTY;
	$warehouseid = (GETPOST("warehouseid") != '') ? GETPOST("warehouseid") : $conf->global->CASHDESK_ID_WAREHOUSE;
	$bankid_cash = (GETPOST("CASHDESK_ID_BANKACCOUNT_CASH") != '') ? GETPOST("CASHDESK_ID_BANKACCOUNT_CASH") : $conf->global->CASHDESK_ID_BANKACCOUNT_CASH;
	$bankid_cheque = (GETPOST("CASHDESK_ID_BANKACCOUNT_CHEQUE") != '') ? GETPOST("CASHDESK_ID_BANKACCOUNT_CHEQUE") : $conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE;
	$bankid_cb = (GETPOST("CASHDESK_ID_BANKACCOUNT_CB") != '') ? GETPOST("CASHDESK_ID_BANKACCOUNT_CB") : $conf->global->CASHDESK_ID_BANKACCOUNT_CB;

	if(!($thirdpartyid > 0)) {
		$retour = $langs->trans("ErrorFieldRequired",$langs->transnoentities("CashDeskThirdPartyForSell"));
		//header('Location: '.DOL_URL_ROOT.'/iccashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid.'&bankid_cash='.$bankid_cash.'&bankid_cheque='.$bankid_cheque.'&bankid_cb='.$bankid_cb);

		exit;
	}
	if(!empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_BILL && !($warehouseid > 0)) {
		$retour = $langs->trans("CashDeskSetupStock");
		//header('Location: '.DOL_URL_ROOT.'/iccashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid.'&bankid_cash='.$bankid_cash.'&bankid_cheque='.$bankid_cheque.'&bankid_cb='.$bankid_cb);

		exit;
	}

	$susitem = GETPOST('susitem', 'int');
	if($susitem >= 0) {
		icomm_load_suspended_item($susitem);
		
	} else {
		$_SESSION['uid'] = $user->id;
		$_SESSION['uname'] = $user->login;
		$_SESSION['nom'] = $user->lastname;
		$_SESSION['prenom'] = $user->firstname;
		$_SESSION['CASHDESK_ID_THIRDPARTY'] = $thirdpartyid;
		$_SESSION['CASHDESK_ID_WAREHOUSE'] = $warehouseid;
		$_SESSION['CASHDESK_ID_BANKACCOUNT_CASH'] = ($bankid_cash > 0 ? $bankid_cash : '');
		$_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE'] = ($bankid_cheque > 0 ? $bankid_cheque : '');
		$_SESSION['CASHDESK_ID_BANKACCOUNT_CB'] = ($bankid_cb > 0 ? $bankid_cb : '');
		//$_SESSION['urlfrom'] = DOL_URL_ROOT . '/iccashdesk/index.php';
	}

	if(isset($_SESSION['ICommFacturation'])) {
		$facturation = unserialize($_SESSION['ICommFacturation']);
		//unset($_SESSION['ICommFacturation']);

	} else {
		$facturation = new ICommFacturation($db);
	}
	$facturation->calculTotaux();
	$facturation->currency = $langs->getCurrencySymbol($conf->currency);
	$_SESSION['ICommFacturation'] = serialize($facturation);

	$vars = new StdClass();
	$vars->basepath = DOL_DOCUMENT_ROOT;
	$vars->baseurl = DOL_MAIN_URL_ROOT;
	$vars->facturation = $facturation;
	$vars->lock = $_SESSION['iccashdesk_lockscreen'] ? TRUE : FALSE;
}

function icomm_theme_selectproduct($name = NULL) {
	global $langs;

	$output = '';

	$url = DOL_URL_ROOT . '/iccashdesk/scripts/callback.php';
	$params = "callback=getproducts&name=$name";
	$placeholder = '';
	$selected = '';

	if(empty($name)) {
		return $output;
	}

	$output .= "<div class=\"icomm-search-item\">\n";
	$output .= ajax_autocompleter('', $name, $url, $params, 3) . "\n";
	$output .= "<input type=\"text\" name=\"search_" . $name . "\" id=\"search_" . $name . "\" />\n";
	$output .= "<div class=\"icomm-item-search-result\"></div>\n";
	$output .= "</div>\n";

	return $output;
}

function icomm_getproducts($string = '') {
	global $db;

	$products = array();

	if(empty($string)) {
		return $products;
	}

	$string = $db->escape($string);
	$where = array();
	$where[] = "ref LIKE '%$string%'";
	$where[] = "label LIKE '%$string%'";
	$where[] = "ref_ext LIKE '%$string%'";
	$where[] = "description LIKE '%$string%'";
	$where[] = "barcode LIKE '%$string%'";

	$query = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "product WHERE " . implode('OR ', $where);
	$result = $db->query($query);
	if($result && $db->num_rows($result) > 0) {
		while($product = $db->fetch_object($result)) {
			$product->key = $product->rowid;
			$product->value = $product->ref;
			$product->label = $product->ref . ' - ' . $product->label;

			$products[] = $product;
		}
		$db->free();
	}

	return $products;
}

function icomm_theme_loadproduct($pid = NULL) {
	global $user, $conf, $db, $facturation, $langs;

	require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

	$result = new StdClass();
	$result->product = new StdClass();
	$langs->load('product');

	$result->output = '';

	if(empty($pid)) {
		return $output;
	}

	$product = new Product($db);
	$product->fetch($pid);

/*
	$facturation = unserialize($_SESSION['ICommFacturation']);
	unset($_SESSION['ICommFacturation']);
*/
	$facturation->id($product->id);
	$facturation->ref($product->ref);
	$facturation->stock($product->stock_reel);
	$facturation->prix($product->price);
	$facturation->tva($product->tva_tx);
	$_SESSION['ICommFacturation'] = serialize($facturation);

	$result->product->stock = $product->stock_reel;
	$result->product->price = price2num($product->price, 'MU');

	$photo = $product->is_photo_available($conf->product->multidir_output[$product->entity]);
	$fields = array(
		'photo' => array(
			'#type' => ($photo ? 'value' : NULL),
			'#value' => ($photo ? $product->show_photos($conf->product->multidir_output[$product->entity], 1, 1, 0, 0, 0, 120, 120) : NULL),
			'#attributes' => array('class' => 'icomm-field-photo'),
		),
		'ref' => array(
			'#title' => $langs->trans('Reference'),
			'#type' => 'item',
			'#value' => $product->ref,
			'#prefix' => "<div class=\"icomm-field-detail\">",
		),
		'description' => array(
			'#title' => $langs->trans('Description'),
			'#type' => 'item',
			'#value' => $product->description,
		),
		'barcode' => array(
			'#title' => $langs->trans('BarCode'),
			'#type' => 'item',
			'#value' => $product->barcode,
		),
		'price' => array(
			'#title' => $langs->trans('Price'),
			'#type' => 'item',
			'#value' => price($product->price) . ' ' .  $facturation->currency,
		),
		'stock_reel' => array(
			'#title' => $langs->trans('Stock'),
			'#type' => 'item',
			'#value' => $product->stock_reel,
		),
		'note' => array(
			'#title' => $langs->trans('Note'),
			'#type' => 'item',
			'#value' => $product->note,
			'#suffix' => '<div>',
		),
	);

	$result->output .= "<div class=\"viewer-terminal boxtable\">\n";
	$result->output .= "<div class=\"icomm-viewer-title\">" . $langs->trans('Article') . "</div>\n";
	$result->output .= icomm_theme_form($fields);
	$result->output .= "</div>\n";

	return $result;
}

function icomm_theme_loadcart() {
	global $langs, $db, $facturation;

	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

	$output = '';

	$langs->load("bills");
	$langs->load("cashdesk");

	$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];
	$company = new Societe($db);
	$company->fetch($thirdpartyid);

	$output .= "<div class=\"icomm-cart\">\n";

	$output .= "<div class=\"icomm-cart-title\">" . $langs->trans("ShoppingCart") . "</div>\n";
	if($cart = $_SESSION['icommcart']) {
		foreach($cart as $item) {
			$output .= icomm_theme_cartitem($item);
		}

	} else {
		$output .= "<div class=\"icomm-field-title\">" . $langs->trans("NoArticle") . "</div>\n";
	}

	$output .= "<div class=\"icomm-cart-total\">\n";
	$output .= $langs->trans("Total") . ' : ' . price($facturation->prixTotalTtc()) . ' ' . $facturation->currency . "\n";
	$output .= "</div>\n";

	$output .= "</div>\n";

	return $output;
}

function icomm_theme_cartitem($item = array()) {
	global $langs, $facturation;

	$output = '';

	if(empty($item)) {
		return $output;
	}

	$output .= "<div class=\"icomm-cart-item\">\n";

	$output .= "<a href=\"" . icomm_url('scripts/callback.php?callback=deleteitem&id=' . $item['id']) . "\" title=\"" . $langs->trans("DeleteArticle") . "\">\n";
	$output .= "<img src=\"" . icomm_url('img/basket_delete.png') . "\" />\n";
	$output .= "</a>\n";

	$output .= "<div class=\"icomm-cart-item-title\">" . $item['ref'] . ' - ' . $item['label'];
	$output .= "</div>\n";

	if($item['remise_percent'] > 0) {
		$remise_percent = ' -' . $item['remise_percent'] . '%';

	} else {
		$remise_percent = '';
	}

	$remise = $item['remise'];
	$output .= "<div class=\"icomm-cart-item-price\">\n";
	// $output .= $item['qte'] . ' x ' . price($item['price']) . $remise_percent . ' = ' . price($item['total_ht']) . ' ' . $facturation->currency . ' ' . $langs->trans("HT") . ' (' . price($item['total_ttc']) . ' ' . $facturation->currency . ' ' . $langs->trans('TTC') . ")\n";
	$output .= $item['qte'] . ' x ' . price($item['price']) . $remise_percent . "\n";
	
	$output .= "</div>\n";

	$output .= "</div>\n";

	return $output;
}

function icomm_url($string = '', $http = FALSE) {
	$string = trim($string, '/');

	if($http == TRUE) {
		$url = DOL_URL_ROOT . "/iccashdesk/$string";

	} else {
		$url = $string;
	}

	return $url;
}

function icomm_theme_itempicker() {
	global $langs, $facturation, $db;

	$output = '';

	$output .= "<div class=\"icomm-fieldset\">\n";

	$output .= "<form id=\"frmFacturation\" class=\"formulaire1\" method=\"post\" action=\"facturation_verif.php\" autocomplete=\"off\">\n";
	$output .= "<input type=\"hidden\" name=\"token\" value=\"" . $_SESSION['newtoken'] ."\" />\n";
	$output .= "<input type=\"hidden\" name=\"hdnSource\" value=\"NULL\" />\n";
	$output .= "</form>\n";

	$output .= "<fieldset class=\"icomm-item-picker\">\n";
	$output .= "<legend>" . $langs->trans("Searching") . "</legend>\n";
	$output .= icomm_theme_selectproduct('productid');

	$output .= "<form id=\"frmQte\" class=\"formulaire1\" method=\"post\" action=\"" . icomm_url('scripts/callback.php?callback=additem') . "\" autocomplete=\"off\">\n";
	$output .= "<input type=\"hidden\" name=\"token\" value=\"" . $_SESSION['newtoken'] ."\" />\n";
	$output .= "<table>\n";
	$output .= "<tr>\n";
	$output .= "<th>" . $langs->trans('Qty') . "</th>\n";
	$output .= "<th>" . $langs->trans('Stock') . "</th>\n";
	$output .= "<th>" . $langs->trans('PriceUHT') . "</th>\n";
	$output .= "<th></th>\n";
	$output .= "<th>" . $langs->trans('Discount') . " (%) </th>\n";
	$output .= "<th>" . $langs->trans('Total') . "</th>\n";
	$output .= "</tr>\n";

	$output .= "<tr>\n";
	$output .= "<td><input class=\"texte1 icomm-item-input\" type=\"text\" name=\"ictxtQty\" value=\"1\" /></td>\n";
	$output .= "<td><input class=\"texte1_off\" type=\"text\" name=\"ictxtStock\" value=\"" . $facturation->stock() . "\" disabled=\"disabled\" /></td>\n";
	$output .= "<td><input class=\"texte1_off\" type=\"text\" name=\"ictxtPrixUnit\" value=\"" . price2num($facturation->prix(), 'MU') . "\" disabled=\"disabled\" /></td>\n";
	$output .= "<td>" . $facturation->currency . "</td>\n";
	$output .= "<td><input class=\"texte1 icomm-item-input\" type=\"text\" name=\"ictxtDiscount\" value=\"0\" /></td>\n";
	$output .= "<td><input class=\"texte1_off\" type=\"text\" name=\"ictxtTotal\" value=\"\" disabled=\"disabled\" /></td>\n";
	$output .= "<td>" . $facturation->currency . "</td>\n";
	$output .= "<td><input type=\"submit\" class=\"icomm-button\" id=\"additem\" name=\"additem\" value=\"" . $langs->trans("AddThisArticle") . "\" /></td>\n";
	$output .= "</tr>\n";
	$output .= "</table>\n";
	$output .= "</form>\n";
	$output .= "</fieldset>";
	$output .= "</div>\n";

	return $output;
}

function icomm_theme_cartoption() {
	global $langs, $facturation, $form, $conf, $formproduct;

	$output .= "<form id=\"frmpayment\" method=\"post\" action=\"" . icomm_url('scripts/callback.php?callback=payment') . "\" autocomplete=\"off\">\n";
	$output .= "<input type=\"hidden\" name=\"token\" value=\"" . $_SESSION['newtoken'] ."\" />\n";

	$output .= "<div class=\"icomm-fieldset\">\n";
	$output .= "<fieldset class=\"icomm-item-amount\">\n";
	$output .= "<legend>" . $langs->trans("Amount") . "</legend>\n";
	$output .= "<table class=\"center\">\n";
	$output .= "<tr>";
	$output .= "<th>" . $langs->trans("TotalTicket") . "</th>\n";
	$output .= "<th>" . $langs->trans("Received") . "</th>\n";
	$output .= "<th>" . $langs->trans("Change") . "</th>\n";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td><input class=\"texte2_off\" type=\"text\" name=\"ictxtInvoice\" value=\"" . price2num($facturation->prixTotalTtc(), 'MT') . "\" disabled=\"disabled\" /></td>\n";
	$output .= "<td><input class=\"texte2\" type=\"text\" name=\"ictxtReceive\" value=\"\" /></td>\n";
	$output .= "<td><input class=\"texte2_off\" type=\"text\" name=\"ictxtChange\" value=\"0\" disabled=\"disabled\" /></td>\n";
	$output .= "</tr>";
	$output .= "</table>\n";
	$output .= "</fieldset>";
	$output .= "</div>\n";

	$output .= "<div class=\"icomm-fieldset\">\n";

	$output .= "<fieldset class=\"icomm-item-paymode\">\n";
	$output .= "<legend>" . $langs->trans("Payment") . "</legend>\n";

	$output .= "<div class=\"field\">\n";
	$output .= $langs->trans("Warehouse");
	$output .= $formproduct->selectWarehouses($conf->global->CASHDESK_ID_WAREHOUSE, 'warehouseid', '', 1, 0);
	$output .= "</div>\n";

	$output .= "<div class=\"field\">\n";
	$output .= $langs->trans("ThirdParty");
	$output .= $form->select_company($conf->global->CASHDESK_ID_THIRDPARTY, 'socid', 's.client in (1,3)', 0, 0);
	$output .= "</div>\n";

	//$output .= "<table class=\"center\">\n";
	$output .= "<table>\n";
	$output .= "<tr>";
	if(empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CASH']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CASH'] < 0) {
		$output .= "<td><input id=\"cash\" class=\"icomm-button-disabled\" type=\"button\" name=\"icomm-button-paymode\" value=\"" . $langs->trans("Cash") . "\" title=\"" . dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")) . "\" /></td>\n";

	} else {
		$output .= "<td><input id=\"cash\" class=\"icomm-button\" type=\"submit\" name=\"icomm-button-paymode\" value=\"" . $langs->trans("Cash") . "\" /></td>\n";
	}

	if(empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE'] < 0) {
		$output .= "<td><input id=\"card\" class=\"icomm-button-disabled\" type=\"button\" name=\"icomm-button-paymode\" value=\"" . $langs->trans("CreditCard") . "\" title=\"" . dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")) . "\" /></td>\n";

	} else {
		$output .= "<td><input id=\"card\" class=\"icomm-button\" type=\"submit\" name=\"icomm-button-paymode\" value=\"" . $langs->trans("CreditCard") . "\" /></td>\n";
	}
	if(empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CB']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CB'] < 0) {
		$output .= "<td><input id=\"cheque\" class=\"icomm-button-disabled\" type=\"button\" name=\"icomm-button-paymode\" value=\"" . $langs->trans("Cheque") . "\" title=\"" . dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")) . "\" /></td>\n";

	} else {
		$output .= "<td><input id=\"cheque\" class=\"icomm-button\" type=\"submit\" name=\"icomm-button-paymode\" value=\"" . $langs->trans("Cheque") . "\" /></td>\n";
	}

	$output .= "<td>\n";
	$output .= "<input id=\"report\" class=\"icomm-button\" type=\"submit\" name=\"icomm-button-paymode\" value=\"" . $langs->trans("Reported") . "\" />\n";
	$output .= "</td>\n";
	$output .= "</tr>";
	$output .= "</table>";

	$output .= "<table>";
	$output .= "<tr>";
	$output .= "<td>";
	$output .= "<div id=\"icomm-reported-wrapper\">\n";
	$output .= icomm_theme_selectdate('DateEcheance', 'ictxtDatePaiement');
	$output .= "</div>\n";
	$output .= "</td>";
	$output .= "</tr>";
	$output .= "</table>";

	$output .= "</fieldset>";
	$output .= "</div>\n";

	$output .= "</form>\n";

	return $output;
}

function icomm_theme_selectdate($title = '', $name = NULL, $index = 0) {
	global $langs, $db, $form;

	$output = '';

	if(empty($name)) {
		return $output;
	}

	$output .= "<div class=\"icomm-reported-date\" id=\"$name-$index\" index=\"$index\">\n";
	$output .= $langs->trans("DateEcheance") . ' : ';
	$output .= $form->select_date(-1,$name . "[$index][]", 0, 0, 0, '', 1, 0, 1);
	if($index == 0) {
		$output .= "<button id=\"adddate\" type=\"button\" class=\"dpInvisibleButtons\">\n";
		$output .= img_picto($langs->trans('AddDate'), 'plus-12.png@iccashdesk');
		$output .= "</button>\n";

	} elseif($index > 0) {
		$output .= "<button type=\"button\" class=\"deldate dpInvisibleButtons\">\n";
		$output .= img_picto($langs->trans('Delete'), 'cancel-12.png@iccashdesk');
		$output .= "</button>\n";
	}
	$output .= "<button id=\"erasedate-" . $index . "\" type=\"button\" class=\"erasedate dpInvisibleButtons\" index=\"$index\">\n";
	$output .= img_picto($langs->trans('Erase'), 'eraser-12.png@iccashdesk');
	$output .= "</button>\n";
	$output .= "</div>\n";

	return $output;
}

function icomm_cart_additem() {
	global $facturation;

	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

	$qty = GETPOST('ictxtQty', 'int', 2);
	if($qty <= 0) {
		$facturation = new ICommFacturation();
		//$_SESSION['ICommFacturation'] = serialize($facturation);

		return;
	}

	$discount = GETPOST('ictxtDiscount', 'int', 2);
	$tva = NULL;
	if(!empty($facturation->id)) {
		$facturation->qte($qty);
		$facturation->tva($tva);
		$facturation->remisePercent($discount);
		$facturation->ajoutArticle();
	}
}

function icomm_cart_deleteitem() {
	global $facturation;

	$id = GETPOST('id', 'int', 1);

	if(isset($id) && $id >= 0) {
		$facturation->supprArticle($id);
	}
}

function icomm_validate_sellform() {
	global $langs, $facturation;

	$paymode = GETPOST('icomm-button-paymode', '', 2);
	if($paymode == $langs->transnoentities('Cash')) {
	} elseif($paymode == $langs->transnoentities('CreditCard')) {
	} elseif($paymode == $langs->transnoentities('Cheque')) {
	} elseif($paymode == $langs->transnoentities('Reported')) {
		$facturation->paiementLe('RESET');
		if($_POST['ictxtDatePaiement']) {
			foreach($_POST['ictxtDatePaiement'] as $date) {
				array_shift($date);
				list($d, $m, $y) = $date;
                                $datePaiement = dol_mktime(0, 0, 0, $m, $d, $y);
                                $facturation->paiementLe($datePaiement);

			}
		}
	}

	if($note = GETPOST('txtaNotes', '', 2) && !empty($note)) {
		$facturation->Note($note);
	}
	if(($socid = GETPOST('socid', 'int', 2)) && !empty($socid)) {
		$facturation->socId($socid);
	}
	if(($wid = GETPOST('warehouseid', 'int', 2)) && !empty($wid)) {
		$facturation->wareHouseId($wid);
	}
}

function icomm_prepare_payment() {
	global $facturation, $db, $langs;

	require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

	$mode = NULL;

	$paymode = GETPOST('icomm-button-paymode', '', 2);
	if($paymode == $langs->transnoentities('Cash')) {
		$mode = 'ESP';
		$facturation->bank = (empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"]) ? NULL : $_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"]);
		$facturation->bankfilter = 'courant = 2';

	} elseif($paymode == $langs->transnoentities('CreditCard')) {
		$mode = 'CB';
		$facturation->bank = (empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CB"]) ? NULL : $_SESSION["CASHDESK_ID_BANKACCOUNT_CB"]);
		$facturation->bankfilter = 'courant = 1';

	} elseif($paymode == $langs->transnoentities('Cheque')) {
		$mode = 'CHQ';
		$facturation->bank = (empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"]) ? NULL : $_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"]);
		$facturation->bankfilter = 'courant = 1';

	} elseif($paymode == $langs->transnoentities('Reported')) {
		$mode = 'DIF';
		$facturation->bank = NULL;
		$facturation->bankfilter = 'courant = 1 OR courant = 2';

	} else {
		$facturation->bank = NULL;
		$facturation->bankfilter = 'courant = 1 OR courant = 2';
	}

	$company = new Societe($db);
	$company->fetch($facturation->socId());

	$invoice = new Facture($db);
	$invoice->date = dol_now();
	$invoice->type = 0;
	$num = $invoice->getNextNumRef($company);

	$facturation->numInvoice($num);
	$facturation->getSetPaymentMode($mode);
	$facturation->paymode = $paymode;

	if($paymode != 'ESP') {
		$montant = $facturation->prixTotalTtc();

	} else {
		$montant = GETPOST('ictxtReceive', 'int', 2);
	}

	if($paymode != 'DIF') {
		$facturation->montantEncaisse($montant);
		$total = $facturation->prixTotalTtc();
		$receive = $facturation->montantEncaisse();
		$facturation->montantRendu($receive - $total);
	}

	$_SESSION['ICommFacturation'] = serialize($facturation);

}

function icomm_theme_paymode() {
	global $facturation, $db, $langs, $form;

	$output = '';

	icomm_validate_sellform();
	icomm_prepare_payment();
	
	$output .= "<form id=\"frminvoice\" method=\"post\" action=\"" . icomm_url('scripts/callback.php?callback=invoice', TRUE) . "\" autocomplete=\"off\">\n";
	$output .= "<div class=\"icomm-fieldset\">\n";
	$output .= "<fieldset><legend>" . $langs->trans("Summary") . "</legend>\n";
	$output .= "<table>\n";
	$output .= "<tr>\n";
	$output .= "<td>" . $langs->trans("Invoice") . "</td>\n";
	$output .= "<td>" . $facturation->numInvoice() . "</td>\n";
	$output .= "</tr>\n";
	$output .= "<tr>\n";
	$output .= "<td>" . $langs->trans("Total") . "</td>\n";
	$output .= "<td>" . price($facturation->prixTotalTtc()) . ' ' . $facturation->currency . "</td>\n";
	$output .= "</tr>\n";
	$output .= "<tr>\n";
	$output .= "<td>" . $langs->trans("PaymentMode") . "</td>\n";
	$output .= "<td>" . $facturation->paymode . "</td>\n";
	$output .= "</tr>\n";

	if($facturation->getSetPaymentMode() == 'DIF') {
		$output .= "<tr><td>\n";
		$output .= "<ul>\n";
		foreach($facturation->sortDate($facturation->paiementLe()) as $date) {
			$output .= "<li class=\"\">$date</li>\n";
		}
		$output .= "</ul>\n";
		$output .= "</td></tr>\n";
	}

	$output .= "</table>\n";
	$output .= "</fieldset>\n";
	$output .= "</div>\n";

	$output .= "<div class=\"icomm-fieldset\">\n";
	$output .= "<fieldset><legend>" . $langs->trans("Information") . "</legend>\n";

	$output .= "<div>\n";
	ob_start();
	$form->select_comptes($facturation->bank, 'bank', 0, $facturation->bankfilter);
	$output .= ob_get_contents();
	ob_end_clean();
	$output .= "</div>\n";

	$output .= "<div>\n";
	$output .= "<p>" . $langs->trans("Notes") . "</p>\n";
	$output .= "<textarea name=\"txtaNotes\"></textarea>\n";
	$output .= "</div>\n";

	$output .= "</fieldset>\n";
	$output .= "</div>\n";

	$output .= "<a class=\"button\" href=\"" . icomm_url('index.php', TRUE) . "\">" . $langs->trans("RestartSelling") . "</a>\n";;
	$output .= "<input class=\"bouton\" type=\"submit\" name=\"btnValider\" value=\"" . $langs->trans("ValidateInvoice"). "\" />\n";
	$output .= "</form>\n";

	return $output;
}

function icomm_theme_action() {
	global $langs;

	$output = '';

	$output .= "<form id=\"frmaction\" method=\"POST\" action=\"" . icomm_url('scripts/callback.php?callback=action', TRUE) . "\" autocomplete=\"off\">\n";

	$output .= "<div class=\"icomm-fieldset\">\n";
	$output .= "<fieldset class=\"icomm-item-action\">\n";
	$output .= "<legend>" . $langs->trans("Action") . "</legend>\n";

	$output .= "<div class=\"center\">\n";
	$output .= "<input id=\"suspend\" class=\"icomm-button\" type=\"submit\" name=\"icomm-button-action\" value=\"" . $langs->trans("Suspend") . "\" />\n";
	$output .= "<input id=\"newsell\" class=\"icomm-button\" type=\"submit\" name=\"icomm-button-action\" value=\"" . $langs->trans("NewSell") . "\" />\n";
	$output .= "<input id=\"lock\" class=\"icomm-button\" type=\"submit\" name=\"icomm-button-action\" value=\"" . $langs->trans("Lock") . "\" />\n";
	$output .= "</div>\n";
	$output .= "</fieldset>";
	$output .= "</div>\n";

	$output .= "</form>\n";

	return $output;
}

function icomm_invoice() {
	global $facturation, $db, $user, $langs;

	require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
	require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

	$id = NULL;
	$error = 0;
	$mode = 0;
	$cond = 0;
	$note = '';

	if(!is_object($facturation)) {
		dol_print_error('', 'Empty context');
		exit;
	}

	$now = dol_now();
	$date = dol_print_date($now,'day');
	$hour = dol_print_date($now,'hour');

	if($facturation->getSetPaymentMode() == 'ESP') {
		$mode = dol_getIdFromCode($db, 'LIQ', 'c_paiement');
		$note .= $langs->trans("Cash")."\n";
		$note .= $langs->trans("Received") . ' : ' . $facturation->montantEncaisse() . ' ' . $conf->currency."\n";
		$note .= $langs->trans("Rendu") . ' : ' . $facturation->montantRendu() . ' ' .$conf->currency . "\n";
		$note .= "\n";
		$note .= "--------------------------------------\n\n";

	} elseif($facturation->getSetPaymentMode() == 'CB') {
		$mode = dol_getIdFromCode($db, 'CB', 'c_paiement') || 0;

	} elseif($facturation->getSetPaymentMode() == 'CHQ') {
		$mode = dol_getIdFromCode($db, 'CHQ', 'c_paiement') || 0;

//ICOMM
	} elseif($facturation->getSetPaymentMode() == 'DIF') {
	}

	dol_syslog("facturation->getSetPaymentMode() = " . $facturation->getSetPaymentMode() . " mode = " . $mode . " cond = " . $cond);

	$db->begin();
	$user->fetch($_SESSION['uid']);
	$user->getrights();
	$company = new Societe($db);
	$company->fetch($facturation->socId());

	$invoice = new Facture($db);
	$cart = $_SESSION['icommcart'];

	$invoice->socid = $facturation->socId();
	$invoice->date_creation = $now;
	$invoice->date = $now;
	$invoice->date_lim_reglement = 0;
	$invoice->total_ht = $facturation->prixTotalHt();
	$invoice->total_tva = $facturation->montantTva();
	$invoice->total_ttc = $facturation->prixTotalTtc();
	$invoice->note = $facturation->Note();
	$invoice->cond_reglement_id = $cond;
	$invoice->mode_reglement_id = $mode;
	$invoice->type = 0;

	foreach($cart as $id => $item) {
		$product = new Product($db);
		$product->fetch($item['fk_article']);

		if(!empty($conf->global->PRODUIT_MULTIPRICES) && isset($product->multiprices[$company->price_level])) {
			$item['price'] = $product->multiprices[$company->price_level];
		}

		if(!empty($item['fk_tva']) && $item['fk_tva'] > 0) {
			$result = $db->query('SELECT taux FROM ' . MAIN_DB_PREFIX . 'c_tva WHERE rowid = ' . $item['fk_tva']);
			$item['taux'] = array_shift($db->fetch_row($result));
		}

		$invoiceline = new FactureLigne($db);
		$invoiceline->fk_product = $item['fk_article'];

		$invoiceline->label = implode('-', array($item['label'], 'Article mise'));
		$invoiceline->tva_tx = empty($item['taux']) ? 0 : $item['taux'];
		$invoiceline->qty = $item['qte'];
		$invoiceline->remise_percent = $item['remise_percent'];
		$invoiceline->price = $item['price'];
		$invoiceline->subprice = $item['price'];
		$invoiceline->total_ht = $item['total_ht'];
		$invoiceline->total_ttc = $item['total_ttc'];
		$invoiceline->total_tva = ($item['total_ttc'] - $item['total_ht']);
		$invoice->lines[] = $invoiceline;
	}
	$result = $invoice->create($user, 0, 0);

	$invoice->addline($invoice->id, 'Mise sur article - FQSDFSDFSDFWSDFD', 2220, 2, 0, 0, 0, 0, 0);

// ICOMM HERE

/*
	if($facturation->getSetPaymentMode() == 'DIF' && count($facturation->paiementLe() > 0)) {
		$description = array();
		$dates = $facturation->sortDate($facturation->paiementLe());
		foreach($dates as $ts => $date) {
			$description[] = dol_print_date($ts, '%d/%m/%Y');
		}
		//$invoiceline = new FactureLigne($db);
		//$invoiceline->desc = $facturation->paymode . ' ' . $langs->trans('NextPayDates') . ': ' . implode(', ', $description);
		//$invoice->lines[] = $invoiceline;
		$result = $invoice->create($user, 0, dol_stringtotime(array_shift($dates), 'dayrfc'));
		if($result) {
			$result = $invoice->validate($user, $facturation->numInvoice());
			if(!isset($id)) $id = $invoice->id;

		} else {
			$error++;
		}

	} else {
*/
		//$result = $invoice->create($user, 0, 0);
		if($result) {
			$invoice->validate($user, $facturation->numInvoice(), $facturation->wareHouseId());
			$id = $invoice->id;
			$payment = new Paiement($db);
			$payment->datepaye = $now;
			$payment->bank_account = $conf_fkaccount;
			$payment->amounts[$invoice->id] = $facturation->prixTotalTtc();
			$payment->note = $langs->trans("Payment") . ' ' . $langs->trans("Invoice") . ' ' . $facturation->numInvoice();
			$payment->paiementid = $invoice->mode_reglement_id;
			$payment->num_paiement = '';

			$paiement_id = $payment->create($user);
			if($paiement_id) {
				if(!$error) {
					$result = $payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccountid, '', '');
					if(!$result) {
						$errmsg = $payment->error;
						$error++;
					}
				}
				if(!$error) {
					if($invoice->total_ttc == $facturation->prixTotalTtc() && $facturation->getSetPaymentMode() != 'DIF') {
						$result = $invoice->set_paid($user);
					}
				}

			} else {
				$error++;
			}

		} else {
			$error++;
		}
//	}

	if($error) {
		$db->rollback();
		icomm_newmessage($langs->trans('CashierSellFail'));

	} else {
		$db->commit();
		icomm_newsell();
		icomm_newmessage($langs->trans('CashierSellSuccess', 'error'));
		icomm_redirect('index.php?tab=cashier', TRUE);
	}
}

// ICOMM popup a generaliser pour gestion plus simple (message et lock)
function icomm_popup() {
	global $langs;

	$output = '';

	$lock = $_SESSION['iccashdesk_lockscreen'] ? TRUE : FALSE;
	$output .= "<div id=\"overlay-container\"></div>\n";

	return $output;
}

function icomm_theme_lock_screen() {
	global $langs, $form;

	$output = '';


	$lock = $_SESSION['iccashdesk_lockscreen'] ? TRUE : FALSE;

	$output .= "<div class=\"icomm-popup ui-dialog ui-widget ui-widget-content ui-corner-all\">\n";
	$output .= "<div class=\"ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix\">" . $langs->trans('POSLocked') . "</div>\n";
	$output .= "<div class=\"modal-button\">\n";
	$output .= "<input type=\"button\" class=\"\" id=\"screen-unlock\" name=\"screen-unlock\" value=\"" .  $langs->trans('Unlock') ."\" />\n";
	$output .= "</div>\n";
	$output .= "</div>\n";

	return $output;
}

function icomm_suspend() {
	$suspended = array();

	if(isset($_SESSION['suspend'])) {
		$suspended = $_SESSION['suspend'];
		unset($_SESSION['suspend']);
	}
	$suspended[] = $_SESSION;

	icomm_newsell();
	$_SESSION['suspend'] = $suspended;

	header('Location: ' . icomm_url('index.php', TRUE));

	exit;
}

function icomm_newsell() {
	global $db, $facturation;

	$facturation = new ICommFacturation($db);
	unset($_SESSION['icommcart']);
	unset($_SESSION['ICommFacturation']);
}

function icomm_theme_suspend_list() {
	global $langs;

	$output = '';

	if(!isset($_SESSION['suspend']) || empty($_SESSION['suspend'])) {
		return $output;
	}

	$output .= "<div class=\"icomm-cart\">\n";
	$output .= "<div class=\"icomm-cart-title\">" . $langs->trans("SuspendList") . "</div>\n";
	foreach(array_keys($_SESSION['suspend']) as $id) {
		$total = 0;
		foreach($_SESSION['suspend'][$id]['icommcart'] as $item) {
			if(!empty($item['qte'])) $total += $item['qte'];
		}
		$output .= "<div class=\"suspended-item\" sid=\"$id\">\n";
		$output .= "<a href=\"\" class=\"suspended-item-delete\" sid=\"$id\">\n";
		$output .= img_picto($langs->trans('DelItem'), 'cancel-12.png@iccashdesk');
		$output .= "</a>\n";
		$output .= "<a href=\"" . icomm_url('index.php?susitem=' . ($id), TRUE) . "\">" . $langs->trans('SellID') . " $id (" . $total . ")</a>\n";
		$output .= "</div>\n";
	}

	$output .= "</div>\n";

	return $output;
}

function icomm_load_suspended_item($susitem = NULL) {
	if(!isset($susitem) || !isset($_SESSION['suspend'][$susitem])) return;
	
	$items = $_SESSION['suspend'];
	$_SESSION = $items[$susitem];
	unset($items[$susitem]);
	$_SESSION['suspend'] = $items;

}

function icomm_delete_suspended_item($sid = NULL) {
	if(isset($sid) && isset($_SESSION['suspend'][$sid])) {
		unset($_SESSION['suspend'][$sid]);
	}
}

function icomm_theme_tab_cashier() {
	$output = '';

	/*
	 * Tab left
	 */
	$output .=  "<div class=\"tab-left\">\n";
	$output .=  icomm_theme_action();
	$output .=  icomm_theme_itempicker();
	$output .=  icomm_theme_cartoption();
	$output .=  "</div> <!-- tab-left -->\n";

	/*
	 * Tab cart
	 */
	$output .=  "<div class=\"tab-cart\">\n";
	$output .=  icomm_theme_loadcart();
	$output .=  icomm_theme_suspend_list();
	$output .=  "</div> <!-- tab-cart -->\n";

	return $output;
}

function icomm_theme_tab_negsell() {
	global $db, $langs, $bc, $conf;

	require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

	$output = '';

	$query = "SELECT f.*, u.name FROM llx_icomm_facture icf INNER JOIN llx_icomm_flag icfl ON icf.fk_flag = icfl.rowid INNER JOIN llx_facture f ON icf.fk_facture = f.rowid LEFT JOIN llx_user u ON f.fk_user_author = u.rowid WHERE icf.fk_flag = 1 AND f.type IN (0,1,3) AND f.fk_statut <> 0";

	if(!($sortfield = GETPOST('sortfield', '', 1))) {
		$sortfield = 'f.facnumber';
	}
	if(!($sortorder = GETPOST('sortorder', '', 1))) {
		$sortorder = 'ASC';
	}
	$query .= $db->order($sortfield, $sortorder);
	
	$page = GETPOST("page",'int', 1);
	if($page == -1) $page = 0;
	$offset = $conf->liste_limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	$limit = $conf->liste_limit;
	$query .= $db->plimit($limit + 1, $offset);

	$param = "&tab=negsell";
	$fields = array(
		'facnumber' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans("Ref"), 0, $_SERVER["PHP_SELF"], 'f.facnumber', '', $param),
			'#attributes' => array(
				'class' => 'icomm-viewer bold linkobject',
				'icomm-doctype' => 'facture',
				'nowrap' => 'nowrap',
			),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
		'name' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans("Author"), 0, $_SERVER["PHP_SELF"], 'u.name', '', $param),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
		'status' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], 'f.fk_statut, paye, am', '', $param),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
		'datef' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans("Date"), 0, $_SERVER["PHP_SELF"], 'f.datef', '', $param),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
		'date_lim_reglement' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans("DateDue"), 0, $_SERVER["PHP_SELF"], 'f.date_lim_reglement', '', $param),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
	);

	$result = $db->query($query);
	if($result) {
		$facture = new Facture($db);
		$formfile = new FormFile($db);
		$output .= "<table class=\"icomm-negsell\" width=\"100%\">\n";
		$output .= icomm_theme_table_headers($fields);
		$flag = TRUE;

		while($row =  $db->fetch_object($result)) {
			$flag = !$flag;
			$filename = dol_sanitizeFileName($row->facnumber);
			$filedir = $conf->facture->dir_output . "/$filename";

			$output .= "<tr " . $bc[$flag] . ">\n";
			foreach($fields as $key => $field) {
				if($key == 'facnumber') {
					$field['#attributes']['oid'] = $row->rowid;
				}
				if($key == 'status') {
					$field['#value'] = $facture->LibStatut($row->paye, $row->fk_statut, 5, $row->am);

				} elseif(!isset($field['#value'])) {
					$field['#value'] = (isset($row->$key) ? $row->$key : (isset($field['#default']) ? $field['#default'] : NULL));
				}
				$output .= icomm_theme_field($field, $key);
			}
			$output .= "</tr>\n";
		}
		$output .= "</table>\n";
		$output .= "<div id=\"viewer-terminal\" class=\"hidden boxtable\">\n";
		$output .= "</div>\n";
		unset($facture);
	}
	$db->free($result);

	return $output;
}

function icomm_viewer($doctype = NULL, $oid = NULL) {
	global $langs;

	$viewer = '';

	if($doctype == 'facture' && !empty($oid)) {
		$title = $langs->trans('Invoice');
		$viewer = icomm_viewer_facture($title, $oid);;

	} elseif($doctype == 'booking' && !empty($oid)) {
		$title = $langs->trans('Booking');
		$viewer = icomm_viewer_booking($title, $oid);;
	}

	return $viewer;
}

function icomm_viewer_facture($title = NULL, $oid = NULL) {
	global $db, $langs, $conf, $mysoc, $form;

	require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

	$langs->load("companies");
	$langs->load("bills");

	$formfile = new FormFile($db);
	$facture = new Facture($db);
	$facture->fetch($oid);
	$soc = new Societe($db);
	$soc->fetch($facture->socid);

	$filename = dol_sanitizeFileName($facture->ref);
	$filedir = $conf->facture->dir_output . "/$filename";
	$fields = array(
		'ref' => array(
			'#type' => 'item',
			'#title' => $langs->trans('Ref'),
			'#value' => $facture->ref,
		),
		'name' => array(
			'#type' => 'item',
			'#title' => $langs->trans('Company'),
			'#value' => ($soc->name ? $soc->name : $soc->nom),
		),
		'type' => array(
			'#type' => 'item',
			'#title' => $langs->trans('Type'),
			'#value' => $facture->getLibType(),
		),
		'date' => array(
			'#type' => 'item',
			'#title' => $langs->trans('Date'),
			'#value' => dol_print_date($facture->date,'daytext'),
		),
		'duedate' => array(
			'#type' => 'item',
			'#title' => $langs->trans('DateMaxPayment'),
			'#value' => dol_print_date($facture->date_lim_reglement,'daytext'),
		),
		'paid' => array(
			'#type' => 'item',
			'#title' => $langs->trans('AlreadyPaidNoCreditNotesNoDeposits'),
			'#value' => price($facture->getSommePaiement()),
		),
		'invoice' => array(
			'#type' => 'item',
			'#title' => $langs->trans('Invoice'),
			'#value' => $formfile->getDocumentsLink('facture', $filename, $filedir),
		),
	);

	$output = "<div class=\"icomm-viewer-title\">\n";
	$output .= $title;
	$output .= img_picto($langs->trans('Close2'), 'cancel-12.png@iccashdesk', 'class="icomm-viewer-close"');
	$output .= "</div\n";
	$output .= "<div class=\"icomm-viewer-data\">\n";
	$output .= icomm_theme_form($fields);

	$fields = array(
		'product_ref' => array(
			'#type' => 'value',
			'#title' => $langs->trans('Article'),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
		'total_ht' => array(
			'#type' => 'value',
			'#title' => $langs->trans('PriceUHT'),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
		'qty' => array(
			'#type' => 'value',
			'#title' => $langs->trans('Qty'),
			'#prefix' => '<td>',
			'#suffix' => '</td>',
		),
	);
	if(!empty($facture->lines)) {
		$output .= "<table id=\"viewer-grid-invoice\" class=\"viewer-grid allwidth border\">";
		$output .= icomm_theme_table_headers($fields, '<td>', '</td>');

		$total = 0;
		foreach($facture->lines as $line) {
			$output .= "<tr>\n";
			foreach($fields as $key => $field) {
				if($key == 'total_ht') {
					$field['#value'] = price($line->total_ht);

				} elseif(property_exists($line, $key)) {
					$field['#value'] = $line->$key;
				}
				$output .= icomm_theme_field($field, $key);
			}
			$output .= "</tr>\n";
			$total += $line->qty;
		}

		$fields = array(
			'label' => array(
				'#type' => 'value',
				'#value' => $langs->trans('Total'),
				'#attributes' => array('class' => 'right'),
				'#prefix' => '<td>',
				'#suffix' => '</td>',
			),
			'ht' => array(
				'#type' => 'value',
				'#attributes' => array('class' => 'right'),
				'#value' => price($facture->total_ht),
				'#prefix' => '<td>',
				'#suffix' => '</td>',
			),
			'total' => array(
				'#type' => 'value',
				'#value' => $total,
				'#prefix' => '<td>',
				'#suffix' => '</td>',
			),
		);
		$output .= "<tr class=\"right\">\n";
		$output .= icomm_theme_form($fields);
		$output .= "</tr>\n";
		$output .= "</table>\n";
	}

	$output .= "</div\n";

	return $output;
}

function icomm_theme_tab_booking() {
	global $db, $langs, $conf, $bc, $vars;

	$output = '';

	$query = "SELECT s.rowid oid, b.rowid, s.nom nom, b.fk_societe, COUNT(b.fk_product) totalitem, SUM(b.qty) totalqty, MIN(b.expired) expired FROM llx_icomm_booking b INNER JOIN llx_societe s ON s.rowid = b.fk_societe INNER JOIN llx_product p ON p.rowid = b.fk_product GROUP BY b.fk_societe";

	if(!($sortfield = GETPOST('sortfield', 'nom', 1))) {
		$sortfield = '';
	}
	if(!($sortorder = GETPOST('sortorder', '', 1))) {
		$sortorder = 'ASC';
	}
	$query .= $db->order($sortfield, $sortorder);

	$page = GETPOST("page",'int', 1);
	if($page == -1) $page = 0;
	$offset = $conf->liste_limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	$limit = $conf->liste_limit;
	$query .= $db->plimit($limit + 1, $offset);

	$param = "&tab=booking";
	$fields = array(
		'nom' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans('Customer'), 0, $_SERVER["PHP_SELF"], 'nom', '', $param),
			'#attributes' => array('class' => 'icomm-viewer linkobject bold', 'icomm-doctype' => 'booking'),
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		),
		'totalitem' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans('TotalItem'), 0, $_SERVER["PHP_SELF"], 'totalitem', '', $param),
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		),
		'totalqty' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans('TotalQty'), 0, $_SERVER["PHP_SELF"], 'totalqty', '', $param),
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		),
		'expired' => array(
			'#type' => 'value',
			'#title' => getTitleFieldOfList($langs->trans('DateDue'), 0, $_SERVER["PHP_SELF"], 'expired', '', $param),
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		),
		'action' => array(
			'#type' => 'value',
			'#title' => "<th class=\"liste_titre\">" . $langs->trans('Action') . "</th>",
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		),
	);
	$output .= "<form id=\"frmtabbooking\" class=\"formulaire1\" method=\"post\" action=\"index.php?tab=booking\" autocomplete=\"off\">\n";
	$result = $db->query($query);
	if($result) {
		if($db->num_rows($result)) {
			$output .= "<table class=\"icomm-booking\" width=\"100%\">\n";
			$output .= icomm_theme_table_headers($fields);

			$flag = TRUE;
			while($row =  $db->fetch_object($result)) {
				$flag = !$flag;

				$output .= "<tr " . $bc[$flag] . " id=\"griditem-" . $row->fk_societe . "\">\n";
				foreach($fields as $key => $field) {
					if(property_exists($row, $key)) {
						if($key == 'nom') {
							$field['#attributes']['oid'] = $row->oid;
							$row->$key = strtoupper($row->$key);
						}
						if($key == 'expired') {
							$field['#value'] = dol_print_date($row->expired, '%Y-%m-%d');

						} else {
							$field['#value'] = $row->$key;
						}
					}
					if($key == 'action') {
						$field['#value'] = icomm_theme_gridaction($row->fk_societe,  FALSE, FALSE, TRUE);
					}
					$output .= icomm_theme_field($field, $key);
				}
				$output .= "</tr>\n";
			}
			$output .= "</table>\n";
		}
	}
	$output .= "<div class=\"center\">\n";
	$output .= "<a class=\"button\" href=\"index.php?tab=booking&action=addnew\" >Ajouter</a>\n";
	if($db->num_rows($result) > 0) {
		$output .= "<input class=\"button\" type=\"submit\" id=\"button-validate\" name=\"button-validate\" value=\"" . $langs->trans('Validate') . "\" />\n";
	}
	$output .= "</div>\n";

	$output .= "</form>\n";

	if($validate = GETPOST('button-validate', 'alpha', 2)) {
		if($_POST['delete']) {
			foreach($_POST['delete'] as $sid) {
				if($sid && is_numeric($sid)) {
					$query = "DELETE FROM llx_icomm_booking WHERE fk_societe = $sid";
					if($db->query($query)) {
						icomm_newmessage($langs->trans('BookingSuccess'));

					} else {
						icomm_newmessage($langs->trans('BookingFailed'), 'error');
					}
				}
			}

			icomm_redirect('index.php?tab=booking');
		}
	}
	if($oid = GETPOST('oid', 'int', 2)) {
		$output .= "<div id=\"viewer-terminal\" class=\"boxtable\">\n";
		$output .= icomm_viewer_booking($langs->trans('Booking'), $oid);
		$output .= "</div>\n";

	} elseif(GETPOST('action', 'alpha', 1) == 'addnew' || ($save = GETPOST('button-save', 'alpha', 2))) {
		$output .= "<div id=\"viewer-terminal\" class=\"boxtable\">\n";
		$output .= icomm_viewer_booking($langs->trans('Booking'));
		$output .= "</div>\n";

	} else {
		$output .= "<div id=\"viewer-terminal\" class=\"hidden boxtable\">\n";
		$output .= "</div>\n";
	}

	unset($_POST);

	return $output;
}

function icomm_redirect($url = NULL, $http = FALSE, $code = '302') {
	if($url) {
		header('Location:' . icomm_url($url, $http), TRUE, $code);
	}
		
	exit(0);
}

function icomm_viewer_booking_fields($new = FALSE) {
	global $langs, $user;

	$fields = array(
		'created' => array(
			'#title' => $langs->trans('Date'),
			'#type' => 'value',
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
			'#default' => dol_print_date(time(), '%Y-%m-%d'),
		),
		'name' => array(
			'#title' => $langs->trans('Author'),
			'#type' => 'value',
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
			'#default' => $user->nom,
		),
		'ref' => array(
			'#title' => $langs->trans('Item'),
			'#type' => 'numeric',
			'#required' => TRUE,
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
			'#alias' => 'fk_product',
		),
		'qty' => array(
			'#title' => $langs->trans('Qty'),
			'#type' => 'numeric',
			'#required' => TRUE,
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
			'#attributes' => array('class' => 'medium-size', 'maxLength' => 5),
		),
		'expired' => array(
			'#title' => $langs->trans('DateDue'),
			'#type' => 'datepicker',
			'#required' => TRUE,
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		),
		'action' => array(
			'#title' => $langs->trans('Action'),
			'#type' => 'value',
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		),
	);

	if($new) {
		$field['fk_societe'] = array(
			'#type' => 'societyref',
			'#title' => $langs->trans('Company'),
			'#required' => TRUE,
			'#prefix' => "<td class=\"center\">",
			'#suffix' => '</td>',
		);
		$fields = $field + $fields;
		$fields['ref']['#type'] = 'productref';
		unset($fields['action']);
		foreach($fields as $key => $field) {
			$fields[$key]['#id'] = $fields[$key]['#name'] = $key;
		}
	}

	return $fields;
}

function icomm_viewer_booking($title = NULL, $oid = NULL) {
	global $db, $langs, $user;

	$posted = FALSE;
	$redirect = FALSE;
	$booking = array();

	$bc = array('pair', 'impair');
	$extras = '';
	$edit = !empty($oid);
	if($edit && !is_numeric($oid)) {
		return '';
	}
	$update = (isset($_POST['button-save']) && $edit);
	$new = ((GETPOST('action', 'alpha', 1) == 'addnew') || (isset($_POST['button-save']) && !$edit));
	$fields = icomm_viewer_booking_fields($new);

	$output = "<div class=\"icomm-viewer-title\">\n";
	$output .= $title;
	//$output .= img_picto($langs->trans('Close2'), 'cancel-12.png@iccashdesk', 'class="icomm-viewer-close"');
	$output .= "</div\n";
	$output .= "<div class=\"icomm-viewer-data\">\n";

	$output .= "<form id=\"frmbooking\" class=\"formulaire1\" method=\"post\" action=\"index.php?tab=booking\" autocomplete=\"off\">\n";
	$output .= "<table id=\"viewer-grid-booking\" oid=\"$oid\" class=\"viewer-grid allwidth border\">";
	$output .= icomm_theme_table_headers($fields, '<td>', '</td>');

	if($new) {
		$fields = icomm_viewer_booking_fields(TRUE);
		$booking = icomm_get_form($fields);
		if($booking['new']) {
			$posted = TRUE;
			$keys = $values = array();
			if(!$booking['#error']) {
				foreach($booking['new'][0] as $key => $item) {
					if($item['#alias']) {
						$key = $item['#alias'];
					}
					$keys[] = "`$key`";
					$values[] = "'" . $db->escape($item['#value']) . "'";
				}
				if($keys && $values) {
					$keys[] = "`created`";
					$keys[] = "`fk_user`";
					$values[] = time();
					$values[] = $user->id;
					$keys = implode(', ', $keys);
					$values = implode(', ', $values);
					$query = "INSERT INTO llx_icomm_booking ($keys) VALUES ($values)";
					if(!$db->query($query)) icomm_newmessage($langs->trans('DbQueryFailed'), 'error');
				}
			}

			$redirect = TRUE;
		}

	} elseif($update) {
		$fields['ref']['#type'] = 'value';
		$booking = icomm_get_form($fields, FALSE);

		$posted = ($booking['update'] || $booking['new'] || $booking['delete']);
		if($booking['update']) {
			foreach($booking['update'] as $rid => $items) {
				$params = array();
				unset($booking['update'][$rid]['ref']);
				unset($items['ref']);
				foreach($items as $key => $item) {
					if($item['#error']) {
						$params = NULL;
						break;
					}
					$params[] = "$key = '" . $db->escape($item['#value']) . "'";
				}
				if($rid && (count($params) == count($items))) {
					$query = "UPDATE llx_icomm_booking SET " . implode(', ', $params) . " WHERE rowid = $rid";
					if(!$db->query($query)) icomm_newmessage($langs->trans('DbQueryFailed'), 'error');
				}
			}
		}
		if($booking['new']) {
			$fields = icomm_viewer_booking_fields();
			$booking = icomm_get_form($fields, FALSE);
			foreach($booking['new'] as $pos => $items) {
				$keys = $values = array();
				$error = FALSE;
				foreach($items as $key => $item) {
					if($item['#alias']) {
						$key = $item['#alias'];
					}
					if($item['#error']) {
						$error = TRUE;

					} else {
						$keys[] = "`$key`";
						$values[] = "'" . $db->escape($item['#value']) . "'";
					}
				}
				if(!$error && (count($keys) == count($values)) && (count($values) == count($items))) {
					$keys[] = "`created`";
					$keys[] = "`fk_user`";
					$keys[] = "`fk_societe`";
					$values[] = time();
					$values[] = $user->id;
					$values[] = $oid;
					$keys = implode(', ', $keys);
					$values = implode(', ', $values);
					$query = "INSERT INTO llx_icomm_booking ($keys) VALUES ($values)";
					if(!$db->query($query)) icomm_newmessage($langs->trans('DbQueryFailed'), 'error');
				}
			}
		}

		if($booking['delete']) {
			$posted = TRUE;
			foreach($booking['delete'] as $rid) {
				if(!empty($rid) && is_numeric($rid)) {
					$query = "DELETE FROM llx_icomm_booking WHERE rowid = $rid";
					if(!$db->query($query)) icomm_newmessage($langs->trans('DbQueryFailed'), 'error');
				}
			}
		}
	}

	if($edit) {
		$query = "SELECT b.rowid, s.nom, u.name, p.ref, b.qty, b.created, b.expired FROM llx_icomm_booking b INNER JOIN llx_product p ON p.rowid = b.fk_product INNER JOIN llx_societe s ON s.rowid = b.fk_societe INNER JOIN llx_user u ON u.rowid = b.fk_user WHERE b.fk_societe = $oid ORDER BY b.expired DESC, p.ref ASC";
		if($result = $db->query($query) && $db->num_rows($result) > 0) {
			$flag = TRUE;
			while($row = $db->fetch_object($result)) {
				$flag = !$flag;
				$fields['ref']['#type'] = 'value';
				
				$line = NULL;
				$class = '';
				foreach($fields as $key => $field) {
					if($booking['update'][$row->rowid][$key]) {
						$field = ($booking['update'][$row->rowid][$key] += $field);
					}
					$field['#id'] = $field['#name'] = 'items[' . $row->rowid . "][$key]";
					if(property_exists($row, $key)) {
						$field['#value'] = $row->$key;

					} else {
						$field['#value'] = NULL;
					}
					if($key == 'created' && isset($field['#value'])) {
						$field['#value'] = dol_print_date($field['#value'], '%Y-%m-%d');
					}
					if($key == 'action') {
						$field['#value'] = icomm_theme_gridaction($row->rowid);
					}
					if($field['#error']) {
						$class = 'edit';
					}
					$line .= icomm_theme_field($field, $key);
				}
				if($line) {
					$output .= "<tr id=\"griditem-" . $row->rowid . "\" class=\"edit-item $class " . $bc[$flag] . "\">\n";
					$output .= $line;
					$output .= "</tr>\n";
				}
			}
		}


	} else {
		$output .= icomm_theme_form($fields);
	}

	$output .= $extras;
	$output .= "</table>\n";

	if($edit) $output .= "<input type=\"hidden\" name =\"oid\" value=\"$oid\" />\n";
	$output .= "<input class=\"button\" type=\"submit\" name=\"button-save\" value=\"" . $langs->trans('Validate') . "\"/>\n";;
	$output .= "<a class=\"button\" href=\"index.php?tab=booking\">" .  $langs->trans('Close2') . "</a>\n";;
	$output .= "</form>\n";

	$output .= "</div>\n";

	if($posted) {
		if($booking['#error']) {
			icomm_newmessage($langs->trans('BookingFailed'), 'error');

		} else {
			icomm_newmessage($langs->trans('BookingSuccess'), 'error');
		}
		unset($_POST);
	}

	if($redirect) {
		icomm_redirect('index.php?tab=booking');
	}

	return $output;
}

function icomm_theme_gridaction($oid = NULL, $add = TRUE, $edit = TRUE, $del = TRUE) {
	global $langs;

	$output = '';

	if($add) {
		$output .= "<button type=\"button\" class=\"addgrid-item flat-button\">\n";
		$output .= img_picto($langs->trans('Add'), 'plus-12.png@iccashdesk');
		$output .= "</button>\n";
	}
	if($edit) {
		$output .= "<button type=\"button\" class=\"editgrid-item flat-button\" oid=\"$oid\">\n";
		$output .= img_picto($langs->trans('Edit'), 'edit.png');
		$output .= "</button>\n";
	}
	if($del) {
		$output .= "<button type=\"button\" class=\"delgrid-item flat-button\" oid=\"$oid\">\n";
		$output .= "<i class=\"on\">" . img_picto($langs->trans('Delete'), 'cancel-12.png@iccashdesk') . "</i>";
		$output .= "<i class=\"off\">" . img_picto($langs->trans('Delete'), 'uparrow.png') . "</i>";
		$output .= "</button>\n";
	}

	return $output;

}

function icomm_theme_booking_newitem($index = NULL) {
	global $user, $form;

	if(empty($index)) $index = 0;

	$fields = array();
	foreach(icomm_viewer_booking_fields() as $key => $field) {
		if($key == 'ref') {
			$field = array(
				'#type' => 'hidden',
				'#id' => "new[$index][ref]",
				'#name' => "new[$index][ref]",
			);
			$fields["ref-new-$index"] = array(
				'#type' => 'productref',
				'#id' => "ref-new-$index",
				'#name' => "ref-new-$index",
				'#update' => $field,
				'#required' => true,
				'#prefix' => "<td class=\"center\">",
				'#suffix' => '</td>',
			);

		} else {
			$fields[$key] = $field;
			$fields[$key]['#id'] = $fields[$key]['#name'] = "new[$index][$key]";
		}
	}
		
	$fields['expired']['#value'] = time();
	$fields['action']['#value'] = icomm_theme_gridaction("new-$index", TRUE, FALSE, TRUE);

	$output = icomm_theme_form($fields, "<tr id=\"griditem-new-$index\" class=\"new-item\" index=\"$index\">", '</tr>' . $t);

	return $output;
}

function icomm_get_form($fields = array(), $reset = TRUE) {
	$form = array();

	$error = FALSE;
	foreach($fields as $key => $field) {
		if((substr($key, 0, 1) != '#') && isset($_POST[$key])) {
			$field['#value'] = $_POST[$key];
			$field = icomm_field_validate($field);
			if(isset($field)) {
				$form['new'][0][$key] = $field;
				$error = ($error || !empty($field['#error']));
			}
		}
	}
	if($error) {
		$form['#error'] = TRUE;
	}
	if($_POST['items']) {
		foreach($_POST['items'] as $rid => $item) {
			$error = FALSE;
			foreach($fields as $key => $field) {
				if(substr($key, 0, 1) != '#') {
					$field['#value'] = (isset($item[$key]) ? $item[$key] : NULL);
					$field = icomm_field_validate($field);
					if(isset($field)) {
						$form['update'][$rid][$key] = $field;
						$error = ($error || !empty($field['#error']));
					}
				}
			}
			if($error) {
				$form['#error'] = TRUE;
			}
		}
	}
	if($_POST['new']) {
		foreach($_POST['new'] as $rid => $item) {
			$error = FALSE;
			foreach($fields as $key => $field) {
				if(substr($key, 0, 1) != '#') {
					$field['#value'] = (isset($item[$key]) ? $item[$key] : NULL);
					$field = icomm_field_validate($field);
					if(isset($field)) {
						$form['new'][$rid][$key] = $field;
						$error = ($error || !empty($field['#error']));
					}
				}
			}
			if($error) {
				$form['#error'] = TRUE;
			}
		}
	}
	if($_POST['delete']) {
		$form['delete'] = $_POST['delete'];
	}
	if($reset) unset($_POST);

	return $form;
}

function icomm_field_validate($field = array()) {
	$error = FALSE;
	$required = (isset($field['#required']) && $field['#required']);
	$value = $field['#value'];
	if($field['#type'] == 'numeric' || $field['#type'] == 'societyref' || $field['#type'] == 'productref') {
		$error = (!is_numeric($value) || ($required && !isset($value)));
		$value = NULL;

	} elseif($field['#type'] == 'text') {
		$error = ($required && ($value == '' || !isset($value)));
		$value = NULL;

	} elseif($field['#type'] == 'datepicker') {
		list($d, $m, $y) = preg_split('/[\/-]/', $value[0]);
		$error = !(is_numeric($d) && is_numeric($m) && is_numeric($y));
		$error = ($required && $error);
		if($error) {
			$value = -1;

		} else {
			$field['#value'] = strtotime("$m/$d/$y");
		}

	} else {
		$field = NULL;
	}

	if($error) {
		$field['#error'] = TRUE;
		$field['#value'] = $value;
	}

	return $field;
}

function icomm_theme_table_headers($fields = array(), $prefix = NULL, $suffix = NULL) {
	$output = '';

	$output .= '<tr class="table-title center">';
	foreach($fields as $field) {
		$output .= ($prefix) ? $prefix : NULL;
		$output .= (isset($field['#title']) ? "" . $field['#title'] . "\n" : NULL);
		$output .= ($suffix) ? $suffix : NULL;
	}
	$output .= "</tr>\n";

	return $output;
}

function icomm_theme_form($fields = array(), $prefix = NULL, $suffix = NULL) {
	$output  = '';

	$output .= (isset($prefix) ? $prefix : NULL);
	foreach($fields as $key => $field) {
		$output .= icomm_theme_field($field, $key);
	}
	$output .= (isset($suffix) ? $suffix : NULL);

	return $output;
}

function icomm_theme_field($field = array(), $key) {
	global $form;

	$output = '';

	if(substr($key, 0, 1) == '#') {
		return;
	}
	$class = array();
	$attributes = array();
	if(isset($field['#attributes']) && is_array($field['#attributes'])) {
		$attributes = array();
		foreach($field['#attributes'] as $key => $val) {
			if($key == 'class') {
				$class[] = $val;

			} else {
				$attributes[] = "$key=\"$val\"";
			}
		}
	}
	$attributes = implode(' ', $attributes);
	$class = implode(' ', $class);

	$id = (isset($field['#id']) ? $field['#id'] : $key);
	$name = (isset($field['#name']) ? $field['#name'] : $key);
	$value = (empty($field['#value']) ? (empty($field['#default']) ? NULL : $field['#default']) : $field['#value']);
	$error = ((isset($field['#error']) && $field['#error']) ? TRUE : FALSE);
	$output .= (isset($field['#prefix']) ? $field['#prefix'] : NULL);
	$fclass = preg_replace('/[_# \[\]]/', '-', $name);
	$fclass = trim(preg_replace('/-+/', '-', $fclass), '-');
	$type = $field['#type'];
	$output .= "<div class=\"icomm-field $fclass field-$type " . ($error ? 'error' : NULL) . "\" >\n";
	if($type == 'item') {
		if(isset($field['#title'])) $output .= "<div class=\"icomm-field-title $class\" $attributes>" . $field['#title'] . ": </div>\n";
		$output .= "<div class=\"icomm-field-value $class\" $attributes>$value</div>\n";

	} elseif($type == 'value') {
		if($value) $output .= "<div class=\"icomm-field-value $class\" $attributes>$value</div>\n";

	} elseif($type == 'hidden') {
		$output .= "<input type=\"hidden\" id=\"$id\" name=\"$name\" value=\"$value\" />\n";

	} elseif($type == 'numeric') {
		$output .= "<input type=\"text\" id=\"$id\" name=\"$name\" value=\"$value\" class=\"$class\" $attributes />\n";

	} elseif($type == 'societyref') {
		$output .= "<div class=\"icomm-field-value $class\" $attributes>\n";
		$output .= $form->select_company('', $name, '', 0, 0);
		$output .= "</div>\n";

	} elseif($type == 'productref') {
		$output .= "<div class=\"icomm-field-value $class\" $attributes>\n";
		$output .= icomm_theme_selectproduct($name);
		if($field['#update']) {
			$output .= "<input type=\"hidden\" id=\"$id-update\" name=\"$name-update\" value=\"" . $field['#update']['#id'] . "\" />\n";
			$output .= "<input type=\"hidden\" id=\"" . $field['#update']['#id'] . "\" name=\"" . $field['#update']['#name'] . "\" />\n";
		}
		$output .= "</div>\n";

	} elseif($type == 'datepicker') {
		$required = (isset($field['#required']) && $field['#required']);
		$output .= "<div class=\"icomm-field-value $class\" $attributes>\n";
		$output .= $form->select_date($value, $field['#id'] . '[]', 0, 0, !$required, '', 1, 0, 1);
		$output .= "</div>\n";

	} elseif($type == 'text') {
		if($value) $output .= "<div class=\"icomm-field-value $class\" $attributes>$value</div>\n";
	}
	$output .= "</div>";
	$output .= (isset($field['#suffix']) ? $field['#suffix'] : NULL);

	return $output;
}

?>
