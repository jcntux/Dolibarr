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

require_once('ICCashDeskFieldHandler.class.php');

abstract class ICCashDeskField {
	protected $name;
	protected $element;
	protected $widget;
	protected $settings;
	protected $attributes;
	public $type;
	public $class;
	public $post;

	public function __construct($item = array()) {
		$this->element = array(
			'#type' => (isset($item['#type']) ? $item['#type'] : NULL),
			'#id' => (isset($item['#id']) ? $item['#id'] : NULL),
			'#name' => (isset($item['#name']) ? $item['#name'] : NULL),
			'#widget' => (isset($item['#widget']) ? $item['#widget'] : NULL),
			'#handler' => (isset($item['#handler']) ? $item['#handler'] : NULL), 
			'#realname' => (isset($item['#realname']) ? $item['#realname'] : NULL),
			'#class' => (isset($item['#class']) ? $item['#class'] : NULL),
			'#title' => (isset($item['#title']) ? $item['#title'] : NULL),
			'#default' => (isset($item['#default']) ? $item['#default'] : NULL),
			'#value' => (isset($item['#value']) ? $item['#value'] : NULL),
			'#input' => (isset($item['#input']) ? $item['#input'] : TRUE),
			'#multiple' => (isset($item['#multiple']) ? $item['#multiple'] : FALSE),
			'#hidden' => (isset($item['#hidden']) ? $item['#hidden'] : FALSE),
			'#disabled' => (isset($item['#disabled']) ? $item['#disabled'] : FALSE),
			'#required' => (isset($item['#required']) ? $item['#required'] : FALSE),
			'#exclude' => (isset($item['#exclude']) ? $item['#exclude'] : NULL),
			'#js' => (isset($item['#js']) ? $item['#js'] : NULL),
			'#settings' => (isset($item['#settings']) ? $item['#settings'] : NULL),
			'#prefix' => (isset($item['#prefix']) ? $item['#prefix'] : NULL),
			'#suffix' => (isset($item['#suffix']) ? $item['#suffix'] : NULL),
			'#fullview' => (isset($item['#fullview']) ? $item['#fullview'] : NULL),
			'#error' => (isset($item['#error']) ? $item['#error'] : NULL),
			'#help' => (isset($item['#help']) ? $item['#help'] : NULL),
			'#mainattrs' => (isset($item['#mainattrs']) ? $item['#mainattrs'] : NULL),
		);
		if($item['#widget'])
			$this->widget = $item['#widget'];
		if($item['#settings'])
			$this->settings = $item['#settings'];
		if($item['#attributes'])
			$this->attributes = $item['#attributes'];

		$this->init();
	}

	public static function load($item = array()) {
		$field = NULL;

		if($item['#type']) {
			if($class = ICCashDesk::getClass('ICCashDeskField', $item['#type'])) {
				$field = new $class($item);
			}

		} elseif(is_array($item)) {
			$field = array();
			foreach($item as $name => $child) {
				if(isset($child['#type'])) {
					$field[$name] = self::load($child);
				}
			}
		}

		return $field;
	}

	public function display($item = array()) {
		$html = '';

		if($field = self::load($item)) {
			$field->store();
			$html = $field->render();
		}

		return $html;
	}

	private function init() {
		$this->type = $this->element['#type'];
		$this->post = NULL;
		$this->class = get_class($this);
		$this->name = str_replace('_', '-', $this->element['#name']);
		$this->name = preg_replace('/-+/', '-', $this->name);
		$this->name = trim($this->name, '-');
		$this->attributes['class'][] = 'icomm-element';
		if($this->element['#disabled']) {
			$this->element['#input'] = FALSE;
			$this->attributes['disabled'] = 'yes';
		}

		if(method_exists($this->class, 'hookInit')) {
			$this->hookInit();
		}

		if($this->element['#required']) {
			$this->element['#handler'][] = 'required';
			if($this->element['#title'])
				$this->element['#title'] .= '<span>*</span>';
		}
		if(is_array($this->element['#exclude'])) {
			$this->element['#handler']['exclude'] = $this->element['#exclude'];
			unset($this->element['#exclude']);
		}
		if($this->element['#help']) {
			$this->element['#js']['help'] = $this->element['#help'];
			unset($this->element['#help']);
		}
	}

	public function getSetting($key = NULL) {
		if($key && $this->settings[$key])
			return $this->settings['key'];

		return $this->settings;
	}

	private function addSettings() {
		$this->parseSettings('handler', $this->element['#handler']);
		$this->parseSettings('action', $this->element['#js']);
	}

	private function parseSettings($type = 'action', $source = NULL) {
		if(isset($source)) {
			if(!is_array($source))
				$source = array($source);
			$settings = array();
			$name = (isset($this->element['#realname']) ? $this->element['#realname'] : $this->name);
			foreach($source as $key => $values) {
				if($key[0] == '#') {
					$settings[substr($key, 1)] = $values;

				} else {
					if(is_numeric($key)) {
						$id = $values;
						$values = 1;

					} else {
						$id = $key;
					}

					if($type != 'handler') {
						$settings['field'][$name][$type][$id] = $values;

					} elseif($handlerler = ICCashDeskFieldHandler::getController($id)) {
						$settings[$id] = $handlerler->description;
						$settings['field'][$name][$type][$id] = $values;
					}
				}
			}
			if(!empty($settings)) {
				ICCashDesk::addSettings($settings);
			}
		}
	}

	public function store() {
		if($this->element['#input'] && isset($this->post)) {
			if(is_array($this->post) && isset($this->post[$this->name])) {
				$this->element['#value'] = $this->post[$this->name];

			} else {
				$this->element['#value'] = $this->post;
			}
			if(method_exists($this->class, 'hookStore'))
				$this->hookStore();

		} elseif(!isset($this->element['#value']) && isset($this->element['#default'])) {
			$this->element['#value'] = $this->element['#default'];
		}
		unset($this->post);
	}

	public function validate() {
		$result = TRUE;

		if(!$this->element['#input']) {
			return TRUE;
		}

		if(method_exists($this->class, 'hookValidate')) {
			$result = $this->hookValidate();
		}
		if($this->element['#handler']) {
			foreach($this->element['#handler'] as $name => $value) {
				$name = (is_numeric($name) ? $value : $name);
				if($handlerler = ICCashDeskFieldHandler::getController($name)) {
					$class = $handlerler->class;
					$res = $class::$name($this->element['#value'], $value);
					$res = (isset($res) ? $res : TRUE);
					if($res == FALSE)
						$this->element['#error'][] = $handlerler->description;

					$result = ($res && $result);
				}
			}
		}

		if(!$result)
			$this->attributes['class'][] = 'error';

		return $result;
	}

	public function render() {
		$html = '';

		if($this->widget) {
			$bubble = '';
			$widget = $this->widget . 'Widget';

			if(method_exists($this->class, $widget)) {
				$inputable = !$this->element['#hidden'] && $this->element['#input'] && !$this->element['#disabled'];
				$class = array(
					'icomm-element-wrapper',
				);
				if(is_array($this->element['#class'])) {
					$class = array_merge($class, $this->element['#class']);

				} else {
					$class[] = $this->type;
				}
				if($this->element['#error']) {
					$this->attributes['class'][] = 'error';
					$bubble = self::htmlList($this->element['#error']);
				}
				if($this->element['#hidden'])
					$class[] = 'hidden';
				if(isset($this->element['#id'])) {
					$this->element['#idstring'] = "id=\"" . $this->element['#id'] . "\"";

				} else {
					$this->element['#idstring'] = "name=\"" . $this->name . "\"";
				}
				if($this->element['#prefix']) $html .= $this->element['#prefix'];

				if($this->element['#mainattrs'])
					$mainattrs = ICCashDesk::serialize($this->element['#mainattrs']);
				else
					$mainattrs = '';

				$html .= "<div class=\"" . implode(' ', $class) . "\" $mainattrs >\n";
				$html .= $this->$widget();
				if(isset($this->element['#fullview']))
					$html .= "<span class=\"fullview-switch icon-collapse\"></span>\n";

				if($inputable) {
					$html .= self::display(self::bubble($this->name, NULL, $bubble));
				}
				if($this->element['#suffix'])
					$html .= $this->element['#suffix'];
				$html .= "</div>\n";

				$this->addSettings();
			}
		}

		return $html;
        }

	public static function bubble($name, $title = NULL, $default = NULL, $options = array()) {
		$options['#widget'] = 'bubble';
		$options['#attributes']['class'][] = 'bubble';

		return self::block($name, $title, $default, $options);
	}

	public static function popup($name, $title = NULL, $default = NULL, $options = array()) {
		$options['#id'] = $name;
		$options['#class'][] = 'overlay';
		$options['#class'][] = 'sticky';
		$options['#settings']['icon'] = 'icon-arrow top';
		$options['#settings']['close'] = 'icon-check-minus';

		return self::block($name, $title, $default, $options);
	}

	public static function block($name, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#name' => $name,
			'#type' => 'block',
			'#title' => $title,
			'#default' => $default,
		);

		return array_merge($options, $item);
	}

	public static function item($name, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#type' => 'item',
			'#name' => $name,
			'#title' => $title,
			'#default' => $default,
		);

		return array_merge($options, $item);
	}

	public static function button($name, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#type' => 'button',
			'#name' => $name,
			'#title' => $title,
			'#default' => $default,
		);

		return array_merge($options, $item);
	}

	public static function action($name, $icon = NULL, $class = NULL, $options = array()) {
		$title = NULL;
		$options['#attributes']['class'][] = 'action';
		if($class) $options['#attributes']['class'][] = $class;
		if($icon) {
			$title = "<i class=\"$icon\"></i>";

		} elseif($options['#title']) {
			$title = $options['#title'];
		}

		return self::button($name, $title, '', $options);
	}

	public static function product($name, $callback = NULL, $options = array()) {
		$options['#settings']['callback'] = $callback;
		$options['#settings']['switch'] = TRUE;
		$item = array(
			'#type' => 'product',
			'#name' => $name,
		);

		return array_merge($options, $item);
	}

	public static function text($name, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#type' => 'text',
			'#name' => $name,
			'#title' => $title,
			'#default' => $default,
		);

		return array_merge($options, $item);
	}

	public static function numeric($name, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#type' => 'text',
			'#name' => $name,
			'#title' => $title,
			'#default' => $default,
		);
		$options['#handler'][] = 'numeric';

		return array_merge($options, $item);
	}

	public static function hidden($name, $value = NULL, $options = array()) {
		$item = array(
			'#type' => 'text',
			'#widget' => 'hidden',
			'#name' => $name,
			'#value' => $value,
		);

		return array_merge($options, $item);
	}

	public static function selectClient($name, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#type' => 'select',
			'#widget' => 'client',
			'#name' => $name,
			'#title' => $title,
			'#default' => $default,
		);

		return array_merge($options, $item);
	}

	public static function selectDate($name, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#type' => 'select',
			'#widget' => 'date',
			'#name' => $name,
			'#title' => $title,
			'#default' => $default,
		);

		return array_merge($options, $item);
	}

	public static function multiple($name, $title = NULL, $callback = NULL, $options = array()) {
		$options['#settings']['callback'] = $callback;
		$item = array(
			'#type' => 'multiple',
			'#name' => $name,
			'#title' => $title,
		);

		return array_merge($options, $item);
	}

	public static function htmlList($items = array()) {
		$html = '';

		$html .= "<ul>\n";
		foreach($items as $item) {
			$html .= "<li>$item</li>\n";
		}
		$html .= "</ul>\n";

		return $html;
	}
}
