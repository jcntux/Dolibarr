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

require_once('ICCashDeskField.class.php');

define('ENCTYPE_URL', 'application/x-www-form-urlencoded');
define('ENCTYPE_FREE', 'multipart/form-data');
define('ENCTYPE_TEXT', 'text/plain');
define('METHOD_GET', 'GET');
define('METHOD_POST', 'POST');

class ICCashDeskForm {
	private $name;
	private $fields;
	private $type;
	private $action;
	private $method;
	private $enctype;
	private $html;
	private $token;
	public $validate;
	public $submit;
	public $post;

	public function __construct($name, $fields = array()) {
		$this->reset($name);

		ICCashDeskFieldHandler::init();
		$this->build($fields);
		$this->prepare();
		$this->validate();
	}

	private function reset($name) {
		$this->name = $name;
		$this->setToken();
		$this->setMethod();
		$this->setEnctype();
		$this->fields = array();
		$this->html = NULL;
		$this->action = NULL;
		$this->submit = NULL;
		$this->validate = NULL;
		$this->post = NULL;
	}

	private function build($items = array()) {
		while((list($name, $item) = each($items))) {
			if($field = ICCashDeskField::load($item)) {
				if($item['#type'] == 'button') {
					$this->submit = FALSE;
					$this->validate = FALSE;
				}
				$this->fields[$name] = $field;
			}
		}
	}

	public function setToken() {
		$this->token = sha1($this->name) . '-' . time();
	}

	public function setMethod($value = NULL) {
		if($value == 'post') {
			$this->method = METHOD_POST;

		} else {
			$this->method = METHOD_GET;
		}
	}

	public function setEnctype($value = NULL) {
		if($value == 'text') {
			$this->enctype = ENCTYPE_TEXT;

		} elseif($value == 'free') {
			$this->enctype = ENCTYPE_FREE;

		} else {
			$this->enctype = ENCTYPE_URL;
		}
	}

	public function setAction($uri = NULL, $params = array()) {
		if(!empty($_GET)) $params += $_GET;
		$url = ICCashDesk::url($uri, $params);
		$this->action = $url;
	}

	public function setHtml($html = NULL) {
		$this->html = $html;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getEnctype() {
		return $this->enctype;
	}

	public function getAction() {
		return $this->action;
	}

	public function getToken() {
		$result = NULL;

		if($this->token && (list($token , $ts) = explode('-', $this->token))) {
			$result = new StdClass();
			$result->token = $token;
			$result->ts = $ts;
		}
			
		return $result;
	}

	public function getField($name = NULL) {
		if($name) {
			return (isset($this->fields[$name]) ? $this->fields[$name] : NULL);
		}

		return $this->fields;
	}

	public function render() {
		$html = '';

		$html .= "<form id=\"" . $this->name . "\" method=\"" . $this->method . "\" action=\"" . $this->action . "\">\n" . $html;
		$html .= "<input type=\"hidden\" name=\"token\" value=\"$this->token\">\n";
		if($this->html) {
			$html .= $this->html;

		} else {
			foreach($this->fields as $name => $field) {
				$html .= $field->render();
			}
		}

		$html .= "</form> <!-- " . $this->name . "-->\n";

		return $html;
	}

	private function prepare() {
		$this->submit = (isset($this->submit) ? FALSE : NULL);

		if($_POST['token']) {
			$this->submit = TRUE;
			$this->validate = FALSE;
			$this->post = $_POST;
			unset($_POST);
		}

		foreach($this->fields as $name => $field) {
			if($this->submit) {
				$field->post = $this->post;
				$field->store();

			} else {
				$field->store();
			}
		}
		if($tab = ICCashDeskTab::getCurrent()) {
			$class = $tab->class;
			if(method_exists($class, 'hookPrepare'))
				$class::hookPrepare($this);
		}
	}

	private function validate() {
		$this->validate = (isset($this->validate) ? TRUE : NULL);

		if(empty($this->submit) || empty($this->post)) {
			return;
		}

		foreach($this->fields as $name => $field) {
			$this->validate = ($field->validate() && $this->validate);
		}

		if($tab = ICCashDeskTab::getCurrent()) {
			$class = $tab->class;
			if(method_exists($class, 'hookValidate'))
				$this->validate = ($class::hookValidate($this) && $this->validate);
		}
		if($this->validate === FALSE) {
			ICCashDesk::newmessage('Form Error', 'error');
		}
	}
}
