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

var ICCashDesk = ICCashDesk || {
	interfaces: {},
	events: {},
	Vars: {
		baseurl: '',
		basepath: '',
		message: {},
		settings: {},
	}
};
var CLASS_ERROR = 'error';
var index = 0;

$(document).ready(function() {

ICCashDesk.print = function (msg) {
	$('.page').append('<div>' + msg + '</div><br />');
};

ICCashDesk.executeHook = function(caller, hook, method, self) {
	if(ICCashDesk[caller].hooks[hook]) {
		return ICCashDesk[caller].hooks[hook](method, self);
	}

	return undefined;
};

ICCashDesk.sanitize = function(text) {
	text = '' + text;
	return text.replace(/ /g, '');
};

ICCashDesk.humanInteger = function(text, dx, sep) {
	var packed = [];
	text = '' + text;
	var pos = text.length;

	sep = (sep ? sep : ' ');
	dx = (dx ? dx : 3);
	while(pos > 0) {
		dx = (pos >= dx ? dx : pos);
		pos = pos - dx;
		packed.unshift(text.substr(pos, dx));
	}

	return packed.join(sep);
};

ICCashDesk.parseName = function(name, prefix) {
	if(prefix && name.indexOf(prefix) >= 0) {
		name = name.replace(prefix, '');
	}

	if((pos = name.search(/\d+$/)) && pos > 0) {
		name = name.substring(0, pos);
	}

	return name;
};

ICCashDesk.messages = function() {
	output = '';

	if(ICCashDesk.Vars.message.length > 0) {
		for(var i=0; i<ICCashDesk.Vars.message.length; i++) {
			output += ICCashDesk.Vars.message[i];
		}
	}
	
	if(output) {
		output = '<ul>' + output + '</ul>';
		$('#message-box').html(output);
		$('#message-box').show();
	}
};

ICCashDesk.checkEntry = function(entry, type, exclude) {
	var result = false;
	var isObject = (typeof entry == 'object');

	if(isObject) {
		value = entry.val();

	} else {
		value = entry;
	}

	if(typeof exclude === 'object' && exclude != null) {
		var match = false;
		var i = 0;
		while(!match && (i < exclude.length)) {
			if(exclude[i] == value) {
				match = true;
			}
			i++;
		}
		if(match)
			return true;
	}

	if(type == 'numeric') {
		result = ICCashDesk.isNumeric(value);

	} else if(type == 'positive' && ICCashDesk.isNumeric(value)) {
		result = (value * 1 >= 0);

	} else if(type == 'empty') {
		result = ICCashDesk.isEmpty(value);
	}

	return result;
}

ICCashDesk.isEmpty = function (value)  {
	if(typeof value === 'string') value = value.trim(' ');
	return (value.length == 0 || value == null || (typeof value === 'undefined'));
};

ICCashDesk.isNumeric = function(value) {
	return ((value != '') && (value * 1 == value));
};

ICCashDesk.getRow = function (self) {
	return self.parents('.grid-line[index]:first');
};

ICCashDesk.getBlock = function(self) {
	if(typeof self !== 'object') {
		self = $('div[name="' + self + '"]').parent('div.icomm-element-wrapper.block');
	}
	self.title = self.find('.block-header span.element-title');
	self.content = self.find('.block-content:first');

	return self;
}

ICCashDesk.getPopup = function() {
	return ICCashDesk.getBlock($('#popup'));
};

ICCashDesk.showPopup = function() {
	var popup = ICCashDesk.getPopup();
	popup.find('.block-close').click(function() {
		popup.parent('div.overlay').fadeOut(300);
	});
	popup.parent('div.overlay').fadeIn(300);
}

ICCashDesk.getBubble = function(self) {
	return ICCashDesk.getBlock(self.nextAll('div.icomm-element-wrapper.block:first'));
}

ICCashDesk.showBubble = function(self) {
	ICCashDesk.showBlock(self);
}

ICCashDesk.showBlock =  function(self) {
	ICCashDesk.hideBlocks();
	self.show();
}

ICCashDesk.hideBlocks = function() {
	$('div.icomm-element-wrapper.block:not(.sticky)').hide();
}

ICCashDesk.getGrid = function (element) {
	if(typeof element === 'string') {
		return $('#' + element);

	} else if(element.hasClass('grid')) {
		return element;

	} else {
		return element.parents('table.grid:first');
	}
};
 
ICCashDesk.getField = function(name) {
	if($('input[name="' + name + '"]').attr('name')) {
		return $('input[name="' + name + '"]');
	}
};

ICCashDesk.setField = function(name, value) {
	if($('input[name="' + name + '"]').attr('name')) {
		$('input[name="' + name + '"]').val(value);
	}
};

ICCashDesk.getRowField = function(row, name, indexed) {
	if(indexed && (index = row.attr('index'))) {
		name += index;
	}
	if(value = row.find('input[name="' + name + '"]:first').val()) {
		return ICCashDesk.sanitize(value);
	}

	return null;
};

ICCashDesk.setRowField = function(row, name, value, indexed) {
	if(indexed && (index = row.attr('index'))) {
		name += index;
	}
	row.find('input[name="' + name + '"]:first').val(value);
};

ICCashDesk.clearGrid = function(name) {
	var grid = ICCashDesk.getGrid(name);
	grid.attr('index', 1);
	grid.find('tr.grid-line').remove();
}

ICCashDesk.loadProduct = function(callback, pid, handler) {
	if(pid)
		ICCashDesk.call(callback, {'pid': pid}, 'get', handler);
};

ICCashDesk.focusNext = function(self) {
	var limit = 15;
	var level = 0;
	var match = null;

	while(match == null && level < limit) {
		self = self.parent();
		self.nextAll('[class]').each(function() {
			if(match == null) {
				var element = $(this).find('input:visible:not(:button):not(:disabled):first');
				if(element.attr('name')) {
					match = element;
				}
			}
		});

		level++;
	}

	if(match != null) {
		match.focus().select();
		return false;
	}

	return true;
};

ICCashDesk.call = function(callback, param, method, handler) {
	if(!method) method = 'get';
	if(callback) {
		$.ajax({
			url: ICCashDesk.Vars.baseurl + '/index.php?callback=' + callback,
			type: method,
			data: param,
			success: function(data) {
				if(handler && data)
					handler(data);
			} 
		});
	}
};

ICCashDesk.scanCode = function(scancode, type) {
	var match = false;

	if(type == 'numeric') {
		match = (scancode >= 96 && scancode <= 105) || match;

	}

	if(!match) {
		match = (scancode == 8) || match;
		match = (scancode == 46) || match;
		match = (scancode == 18) || match;
		match = (scancode >= 35 && scancode <= 37) || match;
		match = (scancode == 39) || match;
	}

	return match;
};

ICCashDesk.dispatchEvent = function() {
	for(var event in ICCashDesk.events) {
		ICCashDesk.events[event]();
	}
};

ICCashDesk.dispatchInterface = function() {
	$('div.page').on('click dblclick keyup keydown change', ':input', function(event) {
		var self = $(this);
		var name = self.attr('name');

		if(name === undefined)
			return;

		var realname = ICCashDesk.parseName(name, ICCashDesk.Vars.settings.grid.prefix);
		var interface = null;

		ICCashDesk.hideBlocks();
		if(ICCashDesk.Vars.settings.field && ICCashDesk.Vars.settings.field[name]) {
			interface = ICCashDesk.Vars.settings.field[name];

		} else if(ICCashDesk.Vars.settings.field[realname]) {
			interface = ICCashDesk.Vars.settings.field[realname];
		}

		if(interface != null) {
			var block = ICCashDesk.getBubble(self);

			if((event.type == 'click' || event.type == 'dblclick')) {
				if(self.hasClass(CLASS_ERROR) && event.type == 'dblclick') {
					ICCashDesk.showBubble(block);

				} else if(interface.action) {
					ICCashDesk.executeInterface('action', interface.action, event, self);
				}

			} else if(interface.handler) {
				var ir = ICCashDesk.executeInterface('handler', interface.handler, event, self);
				if(ir.success === false) {
					self.addClass(CLASS_ERROR);

				} else if(ir.success === null) {
					return false;

				} else if(ir.success !== undefined) {
					self.removeClass(CLASS_ERROR);
				}

				if(ir.infos.length > 0) {
					var html = "<ul>";
					for(var i=0; i<ir.infos.length; i++) {
						html += "<li>" + ir.infos[i] + "</li>";
					}
					html += "</ul>";
					block.content.html(html);
				}
			}
		}

		if(ICCashDesk.interfaces['trigger-' + realname])
			ICCashDesk.interfaces['trigger-' + realname](realname, event, self);
	});
};

ICCashDesk.executeInterface = function(type, interface, event, self) {
	var result = {
		success: undefined,
		infos: [],
	};

	for(var id in interface) {
		var name = type + '-' + id;
		if(ICCashDesk.interfaces[name]) {
			var success = ICCashDesk.interfaces[name](interface, event, self);
			if(success === null) {
				result.success = null;
				return result;

			} else if(success == false && ICCashDesk.Vars.settings[id])
				result.infos.push(ICCashDesk.Vars.settings[id]);

			if(success !== undefined) {
				if(result.success === undefined)
					result.success = true;
				result.success = (success && result.success);
			}
		}
	}

	return result;
};

ICCashDesk.bind = function(type, name, method) {
	var binded = false;

	if(type == 'interface') {
		if(!ICCashDesk.interfaces[name]) {
			ICCashDesk.interfaces[name] = method;
			binded = true;
		}

	} else if(type == 'event') {
		if(!ICCashDesk.events[name]) {
			ICCashDesk.events[name] = method;
			binded = true;
		}
	}

	return binded;
}

ICCashDesk.events = {
	'module': function() {
		$('div.page').on('keydown', '.icomm-element:input:not(:disabled)', function(event) {
			if(event.which == 9) {
				return ICCashDesk.focusNext($(this));
			}
		});

		$('div.page').on('click', 'div.multiple button.action', function(event) {
			var self = $(this);
			var line = self.parents('div.multiple:first');
			var index = line.attr('index');

			var callback = line.prev('div.icomm-element-wrapper').find('input:hidden:first').val();

			if(self.attr('name') == 'add') {
				index++;
				line.attr('index', index);
				ICCashDesk.call(callback, {'index': index}, 'get', function(content) {
					line.append(content);
					ICCashDesk.hideBlocks();
				});

			} else {
				line.remove();
			}
		});
	},
};

ICCashDesk.interfaces = {
	'action-reload': function(interface, event, self) {
		window.location = location.href;
	},
	'action-help': function(interface, event, self) {
		if(event.type == 'dblclick') {
			var block = ICCashDesk.getBubble(self);
			block.content.html(interface['help']);
			ICCashDesk.showBubble(block);
			return null;
		}
	},
	'handler-required': function(interface, event, self) {
		if(event.type == 'keyup') {
			if(ICCashDesk.checkEntry(self, 'empty')) {
				return false;

			} else {
				return true;
			}
		}
	},
	'handler-exclude': function(interface, event, self) {
		if(event.type == 'keyup')
			return !(ICCashDesk.checkEntry(self, 'exclude', interface['exclude'] || null));
	},
	'handler-numeric': function(interface, event, self, result) {
		if(event.type == 'keydown') {
			if(ICCashDesk.scanCode(event.which, 'numeric')) {
				return true;

			} else {
				return null;
			}

		} else if(event.type == 'keyup') {
			return ICCashDesk.checkEntry(self, 'numeric');
		}
	},
	'handler-positive': function(interface, event, self) {
		if(event.type == 'keyup')
			return ICCashDesk.checkEntry(self, 'positive');
	},
};

ICCashDesk.dispatchAll = function() {
	ICCashDesk.dispatchInterface();
	ICCashDesk.dispatchEvent();
};

});
