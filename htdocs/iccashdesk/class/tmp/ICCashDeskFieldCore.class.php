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

abstract class ICCashDeskFieldCore {
	protected $name;
	protected $type;
	protected $element;
	protected $settings;
	protected $attributes;
	protected $widget;
	public $class;
	public $post;

	public function __construct($item = array()) {
		$this->element = array(
			'#type' => (isset($item['#type']) ? $item['#type'] : NULL),
			'#widget' => (isset($item['#widget']) ? $item['#widget'] : NULL),
			'#name' => (isset($item['#name']) ? $item['#name'] : NULL),
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
			'#prefix' => (isset($item['#prefix']) ? $item['#prefix'] : NULL),
			'#suffix' => (isset($item['#suffix']) ? $item['#suffix'] : NULL),
			'#info' => (isset($item['#info']) ? $item['#info'] : NULL),
			'#attributes' => (isset($item['#attributes']) ? $item['#attributes'] : array()),
			'#settings' => (isset($item['#settings']) ? $item['#settings'] : array()),
		);
		$this->init();
	}

	public static function load($item = array()) {
		$field = NULL;

		if($item['#type']) {
			if($class = ICCashDeskCore::getClass('ICCashDeskField', $item['#type'])) {
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

		if($field = ICCashDeskFieldCore::load($item)) {
			$field->store();
			$html = $field->render();
		}

		return $html;
	}

	private function init() {
		$this->type = $this->element['#type'];
		$this->widget = strtolower($this->element['#widget']);
		$this->settings = $this->element['#settings'];
		$this->attributes = $this->element['#attributes'];
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
	}

	public function store() {
		if($this->element['#input'] && isset($this->post)) {
			if(is_array($this->post) && isset($this->post[$this->name])) {
				$this->element['#value'] = $this->post[$this->name];

			} else {
				$this->element['#value'] = $this->post;
			}
			if(method_exists($this->class, 'hookStore')) {
				$this->hookStore();
			}

		} elseif(!isset($this->element['#value']) && isset($this->element['#default'])) {
			$this->element['#value'] = $this->element['#default'];
		}
		unset($this->post);
	}

	public function validate() {
		$result = TRUE;

		if(!$this->element['#input']) {
			$result = TRUE;

		} elseif($this->element['#required'] && !isset($this->element['#value'])) {
			$result = FALSE;

		} elseif(method_exists($this->class, 'hookValidate')) {
			$result = $this->hookValidate();
		}
		if($result == FALSE && $this->element['#input']) {
			$this->element['#error'] = TRUE;
		}

		return $result;
	}

	public function render() {
		$html = '';

		if($this->widget) {
			$widget = $this->widget . 'Widget';
			if(method_exists($this->class, $widget)) {
				$class = array(
					'icomm-element-wrapper',
				);
				if(is_array($this->element['#class'])) {
					$class = array_merge($class, $this->element['#class']);

				} else {
					$class[] = $this->type;
				}
				if($this->element['#error']) $this->attributes['class'][] = 'error';
				if($this->element['#hidden']) $class[] = 'hidden';

				if($this->element['#prefix']) $html .= $this->element['#prefix'];
				$html .= "<div class=\"" . implode(' ', $class) . "\">\n";
				$html .= $this->$widget();
				if($this->element['#suffix']) $html .= $this->element['#suffix'];
				if($this->element['#input']) {
					$html .= self::fieldInfo();
				}
				$html .= "</div>\n";

				if($this->settings['triggers'])
					$this->addTrigger($this->settings['triggers']);
			}
		}

		return $html;
        }

	private function fieldInfo() {
		$html = '';

		if($this->element['#required']) $html .= "<li>Obligatoire</li>\n";
		if($this->element['#widget'] == 'numeric') {
			$html .= "<li>Valeur numerique</li>\n";

		} elseif($this->element['#widget'] == 'text') {
			$html .= "<li>Valeur texte</li>\n";
		}

		if($this->element['#info']) {
			foreach($this->element['#info'] as $text) {
				$html .= "<li>$text</li>\n";
			}
		}
		if(!empty($html)) {
			$html = "<ul>$html</ul>\n";
			$html = self::bubble('field-input-info', '', $html);
		}

		return $html;
	}

	protected function addTrigger($trigger) {
		$entry = array();

		if(!is_array($trigger)) {
			$trigger = array($trigger);
		}

		$name = (isset($this->element['#realname']) ? $this->element['#realname'] : $this->name);
		if($this->element['#multiple']) {
			$ptr = &$entry['multiple'];

		} else {
			$ptr = &$entry['simple'];
		}
		$ptr[$name] = $trigger;
		ICCashDeskCore::addTrigger($entry);
	}

	private static function bubble($class, $title = '', $content = '') {
		$html = '';

		$html .= "<div class=\"block bubble $class hidden\">\n";
		$html .= "<i class=\"arrow top\"></i>\n";
		$html .= "<div class=\"block-block\">\n";
		$html .= "<div class=\"block-header\">\n";
		$html .= "<i class=\"icon-check-minus block-close\"></i>\n";
		$html .= "<span class=\"element-title\">$title</span>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"block-content\">$content</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		return $html;
	}

	public static function block($name = NULL, $title = NULL, $default = NULL, $options = array()) {
		$item = array(
			'#type' => 'block',
			'#name' => $name,
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
			'#widget' => 'numeric',
			'#name' => $name,
			'#title' => $title,
			'#default' => $default,
		);

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

	public static function multiple($name, $title = NULL, $options = array()) {
		$item = array(
			'#type' => 'multiple',
			'#name' => $name,
			'#title' => $title,
		);

		return array_merge($options, $item);
	}
}
