<?php
/* Copyright (C) 2014 IComm NOMOREDJO Jean-christophe <jcnrdjo@yahoo.fr>
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

include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/iccashdesk/class/ICommFacturation.class.php';

define('ICOMM_FLAG_NEGSELL', 'negsell');
define('ICOMM_FLAG_DETAIL', 'detail');

class ICommHelper {

	protected static $flags;
	protected static $db;

	public function __construct($db) {
		self::init($db);
	}

	public static function init($db) {
		if(!self::$db) self::$db = $db;
		if(!self::$flags) self::$flags = array();
	}

	public static function saveDetailSell($id = NULL, &$icinvoice) {
		$details = array();

		$invoice = new ICommFacturation(self::$db);
		$invoice = $icinvoice;

		$details['paiement_le'] = $invoice->paiementLe();

		if(($details = serialize($details)) && ($fid = self::getFlagId(ICOMM_FLAG_DETAIL))) {
			if($did = self::getDetailSellId($id)) {
				$query = "UPDATE llx_icomm_facture SET rawdata = '$details' WHERE rowid = $did AND fk_flag = $fid";
				return self::$db->query($query);

			} else {
				$query = "INSERT INTO llx_icomm_facture (fk_facture, fk_flag, rawdata) VALUES ($id, $fid, '$details')";
				return self::$db->query($query);
			}
		}

		return FALSE;
	}

	public static function getDetailSellId($id = NULL) {
		if($fid = self::getFlagId(ICOMM_FLAG_DETAIL)) {
			$query = "SELECT rowid FROM llx_icomm_facture WHERE fk_facture = $id AND fk_flag = $fid";
			return self::$db->query($query)->rowid;
		}

		return FALSE;
	}

	public static function getSell($id = NULL, $flag = NULL) {
		$sell = FALSE;

		if(empty($flag)) {
			$query = "SELECT icf.*, icfl.description FROM llx_icomm_facture icf INNER JOIN llx_icomm_flag icfl ON icf.fk_flag = icfl.rowid WHERE icf.fk_facture = $id";

		} elseif($fid = self::getFlagId($flag)) {
			$query = "SELECT rowid FROM llx_icomm_facture WHERE fk_facture = $id AND fk_flag = $fid";
		}

		if($query && $result = self::$db->query($query)) {
			$sell = self::$db->fetch_object($result);
			self::$db->free();
		}

		return $sell;
	}

	public static function getSellId($id = NULL, $flag = NULL) {
		static $sellid = array();

		if($flag) {
			if(isset($sellid["$id_$flag"])) {
				return $sellid["$id_$flag"];
			}

			if($fid = self::getFlagId($flag)) {
				$query = "SELECT rowid FROM llx_icomm_facture WHERE fk_facture = $id AND fk_flag = $fid";
				if($result = self::$db->query($query)) {
					return self::$db->fetch_object($result)->rowid;
				}
			}
		}

		return FALSE;
	}

	public static function isNegSell(&$invoice) {
		$result = FALSE;

		if(count($invoice->lines) == 0) return FALSE;

		$product = new Product(self::$db);
		while(!$result && count($invoice->lines) > 0 && (list($i, $line) = each($invoice->lines))) {
			$product->fetch($line->fk_product);

			$result = (empty($product->stock_reel) || ($line->qty > $product->stock_reel));
		}

		return $result;
	}

	public static function saveNegSell($id = NULL) {
		if(!self::getSellId($id, ICOMM_FLAG_NEGSELL) && ($fid = self::getFlagId(ICOMM_FLAG_NEGSELL))) {
			self::$db->begin();

			$query = "INSERT INTO " . MAIN_DB_PREFIX . "icomm_facture (fk_facture, fk_flag) VALUE ($id, $fid)";
			if($result = self::$db->query($query)) {
				self::$db->commit();
				return TRUE;

			} else {
				self::$db->rollback();
				return FALSE;
			}
		}

		return FALSE;
	}

	public static function deleteSell($id = NULL) {
		$result = FALSE;

		if(!empty($id) && is_numeric($id)) {
			$query = "DELETE FROM llx_icomm_facture WHERE fk_facture = $id";
			$result = self::$db->query($query);
		}

		return $result;
	}

	public static function deleteDetailSell($id = NULL) {
		$result = FALSE;

		if($sid = self::getSellId($id, ICOMM_FLAG_DETAIL)) {
			$query = "DELETE FROM llx_icomm_facture WHERE rowid = $sid";
			$result = self::$db->query($query);
		}

		return $result;
	}

	public static function deleteNegSell($id = NULL) {
		$result = FALSE;

		if(!empty($id) && ($flagid = self::getFlagId(ICOMM_FLAG_NEGSELL))) {
			$query = "DELETE FROM llx_icomm_facture WHERE fk_facture = $id AND fk_flag = $flagid";
			$result = self::$db->query($query);
		}

		return $result;
	}

	public static function getFlagId($name = '') {
		$id = NULL;

		if(isset(self::$flags[$name])) {
			return self::$flags[$name];
		}
		$query = "SELECT rowid FROM llx_icomm_flag WHERE description = '$name'";
		if($result = self::$db->query($query)) {
			if($id = self::$db->fetch_object($result)->rowid) {
				self::$flags[$name] = $id;
			}
			self::$db->free();
		}

		return self::$flags[$name];
	}
}

?>
