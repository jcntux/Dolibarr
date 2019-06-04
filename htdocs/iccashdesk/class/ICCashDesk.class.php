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

require_once('../main.inc.php');

require_once('class/ICCashDeskTab.class.php');
require_once('class/ICCashDeskCallback.class.php');

class ICCashDesk {
	private static $vars;
	public static $baseurl;
	public static $db;

	public static function debug($msg = '', $exit = FALSE) {
		print "<pre>\n";
		if($msg) {
			print_r($msg);
	
		} else {
			var_dump($msg);
		}
		print "</pre>\n";

		if($exit) {
			exit();
		}
	}

	public static function init() {
		global $db, $langs;

		if(!self::checkaccess()) {
			accessforbidden('',0,0,1);
			exit(0);
		}

		self::$db = $db;
		self::$baseurl = DOL_MAIN_URL_ROOT . '/iccashdesk';
		self::$vars = new StdClass();
		self::$vars->basepath = DOL_DOCUMENT_ROOT;
		self::$vars->baseurl = self::$baseurl;
		//$vars->lock = $_SESSION['iccashdesk_lockscreen'] ? TRUE : FALSE;

		ICCashDeskCallback::init();
		ICCashDeskTab::init();

		$langs->load("iccashdesk@iccashdesk");
		$langs->load("main");

		ICCashDeskCallback::handle();
	}

	public static function addSettings($settings = array()) {
		if(!self::$vars->settings) {
			self::$vars->settings = new StdClass();
		}
		foreach($settings as $name => $values) {
			if(is_array(self::$vars->settings->$name)) {
				self::$vars->settings->$name = array_merge_recursive(self::$vars->settings->$name, $values);

			} else {
				self::$vars->settings->$name = $values;
			}
		}
	}

	public static function checkaccess() {
		global $user;

		if(isset($user->id) && is_numeric($user->id) && $user->societe_id == 0) {
			return TRUE;
		}

		return FALSE;
	}

	public static function header() {
		$includes = array(
			'css' => array(
				'style.css',
				'font-awesome.min.css',
			),
		);

		foreach(ICCashDeskTab::getTabs() as $tab) {
			if(method_exists($tab->class, 'hookHeader')) {
				$class = $tab->class;
				if(($files = $class::hookHeader()) && is_array($files)) {
					$includes = array_merge_recursive($includes, $files);
				}
			}
		}
		$includes['js'][] = 'boot.js';

		foreach($includes as $type => $files) {
			if($includes[$type])
				$includes[$type] = array_unique($includes[$type]);
			foreach($files as $id => $file) {
				$file = "/iccashdesk/$type/$file";
				if(file_exists(DOL_DOCUMENT_ROOT . $file)) {
					$includes[$type][$id] = $file;

				} else {
					unset($includes[$type][$id]);
				}
			}
		}

		top_htmlhead(self::addjs_var(), '', 0 ,0 , $includes['js'], $includes['css']);

		print "<body>\n";
		print "<div class=\"page\">\n";
	}

	public static function content() {
		$url = ICCashDesk::url(NULL, $_GET);

		print "<div id=\"message-box\" class=\"message\"></div>\n";
		print "<div class=\"content\">\n";

		print ICCashDeskField::load(ICCashDeskField::popup('popup'))->render();

		print "<div class=\"content-block\">\n";
		print ICCashDeskTab::display();
		print "</div><!-- content-block -->\n";

		print "</div><!-- content -->\n";
	}

	public static function footer() {
		print "</div> <!-- page -->\n";
		print self::addjs_var();
		print "</body>\n";
		print "</html>\n";
	}

	public static function pagenotfound($msg = '') {
		$html = "<div class=\"icomm-pagenotfound\">\n";
		$html .= "<p>404 Not found</p>\n";
		if($msg) $html .= "<p>$msg</p>\n";
		$html .= "</div>\n";

		return $html;
	}

	public static function newmessage($text = '', $type = 'info') {
		if($type != 'error' && $type != 'info') return;

		if(!isset(self::$vars->message)) self::$vars->message = array();
		$text = "<li class=\"message-$type\">$text</li>\n";
		array_push(self::$vars->message, $text);
	}

	private static function addjs_var() {
		$settings = array();
		$javascript = '';

		if(empty(self::$vars)) {
			return '';
		}

		$prefix = "\n<!--//--><![CDATA[//><!--\n";
		$suffix = "\n//--><!]]>\n";
		$javascript .= '<script type="text/javascript">';
		$javascript .= $prefix;
		$javascript .= "jQuery.extend(ICCashDesk.Vars, " . self::var_tojs(self::$vars) . ");";
		$javascript .= $suffix . "</script>\n";
		self::$vars = new StdClass();

		return $javascript;
	}

	private static function var_tojs($var) {
		return json_encode($var,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
	}

	public static function url($url = NULL, $params = array()) {
		if(empty($url)) {
			$url = ICCashDesk::$baseurl . '/index.php';

		} else {
			$url = '/' . trim($url, '/');
		}

		if($params = ICCashDesk::serialize($params, '&', FALSE)) {
			$url .= '?' . $params;
		}

		return $url;
	}

	public static function getClasses($name) {
		$classes = array();

		if($files = glob(__DIR__ . "/$name.*.class.php")) {
			foreach ($files as $file) {
				if(list($prefix, $suffix) = explode('.', basename($file))) {
					include_once($file);
					$classes[] = $prefix . $suffix;
				}
			}
		}

		return $classes;
	}

	public static function getClass($type, $name) {
		static $classes = array();
		$class = NULL;
		$name = ucfirst($name);

		if(isset($classes["$type$name"])) {
			$class = "$type$name";

		} elseif(is_readable(__DIR__ . "/$type.$name.class.php")) {
			$class = "$type$name";
			include_once("$type.$name.class.php");
			$classes[$class] = TRUE;
		}

		if($class) return $class;

		return NULL;
	}

	public static function mserialize() {
		$serials = array();

		foreach(func_get_args() as $arg) {
			foreach($arg as $k => $v) {
				$serials[$k][$v] = $v;
			}
		}
		foreach($serials as $k => $v) {
			$serials[$k] = implode(' ', $v);
		}

		return $serials;
	}

	public static function serialize($items = array(), $sep = ' ', $quote = '"') {
		$serial = array();

		foreach($items as $k => $v) {
			if(is_array($v)) {
				$v = implode($sep, $v);
			}
			$serial[] = "$k=" . $quote . $v . $quote;
		}

		return (empty($serial) ? NULL : implode($sep, $serial));
	}
}
