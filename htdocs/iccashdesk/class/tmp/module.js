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
	callback: {},
	action: {},
	event: {},
	trigger: {},
	Vars: {
		baseurl: '',
		basepath: '',
		message: {},
		settings: {},
		triggers: {}
	}
};

$(document).ready(function() {

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

ICCashDesk.checkEntry = function(self, type, exclude) {
	var result = false;
	var value = self.val();

	if(exclude) {
		var match = false;
		var i = 0;
		while(!match && (i < exclude.length)) {
			if(exclude[i] == value) {
				match = true;
			}
			i++;
		}
		if(match) {
			self.addClass('error');
			return false;
		}
	}

	if(typeof type == 'object') {
		result = true;
		var i = 0;
		while(result && (i < type.length)) {
			result = ICCashDesk.checkEntry(self, type[i], exclude);
			i++;
		}

	} else {
		if(type == 'int') {
			result = ICCashDesk.isNumeric(value);

		} else if(type == 'positive') {
			result = ((value * 1) >= 0);

		} else if(type == 'string') {
			result = ! ICCashDesk.isEmpty(value);
		}
	}

	if(result == true){
		self.removeClass('error');
		return true;

	} else {
		self.addClass('error');
		return false;
	}
}

ICCashDesk.isEmpty = function (value)  {
	if(typeof value === 'string') value = value.trim(' ');
	return (value.length == 0 || value == null || (typeof value === 'undefined'));
};

ICCashDesk.isNumeric = function(value) {
	return ((value * 1) == value);
};

ICCashDesk.getGrid = function (element) {
	if(typeof element === 'string') {
		return $('#' + element);

	} else {
		return element.parents('.grid:first');
	}
};
 
ICCashDesk.getRow = function (self) {
	return self.parents('.table-line[index]:first');
};

ICCashDesk.getBlock = function(name) {
	block = $('div[name="' + name + '"]');
	block.title = block.find('.block-header span.element-title');
	block.content = block.find('.block-content');

	return block;
};

ICCashDesk.getRowBlock = function(self) {
	var block = ICCashDesk.getRow(self).next('tr.table-line').find('.block');
	block.content = block.find('.block-content');

	return block;
};

ICCashDesk.hideBlocks = function() {
	$('.block').hide();
};

ICCashDesk.hideBlock = function(name) {
	if(name) {
		$('#' + name).hide();

	} else {
		$('.block').hide();
	}
}

ICCashDesk.showBlock = function(block) {
	block.find('div .block-close').click(function() {
		block.fadeOut(300);
	});
	block.fadeIn(300);
}

ICCashDesk.getCell = function(name) {
	if($('input[name="' + name + '"]').attr('name')) {
		return $('input[name="' + name + '"]');
	}
};

ICCashDesk.setCell = function(name, value) {
	if($('input[name="' + name + '"]').attr('name')) {
		$('input[name="' + name + '"]').val(value);
	}
};

ICCashDesk.getRowCell = function(row, name, indexed) {
	if(indexed && (index = row.attr('index'))) {
		name += index;
	}
	if(value = row.find('input[name="' + name + '"]:first').val()) {
		return value.replace(' ', '');
	}

	return null;
};

ICCashDesk.setRowCell = function(row, name, value, indexed) {
	if(indexed && (index = row.attr('index'))) {
		name += index;
	}
	row.find('input[name="' + name + '"]:first').val(value);
};

ICCashDesk.setRowTotal = function(row) {
	var total = '';

	var pu = ICCashDesk.getRowCell(row, ICCashDesk.Vars.settings.tableSuffix + 'pu', true) * 1;
	var qty = ICCashDesk.getRowCell(row, ICCashDesk.Vars.settings.tableSuffix + 'qty', true) * 1;
	if(qty > 0) {
		total = qty * pu;
	}

	ICCashDesk.setRowCell(row, ICCashDesk.Vars.settings.tableSuffix + 'total', total, true);
		
};

ICCashDesk.clearGrid = function(name) {
	var grid = $('#' + name);
	grid.attr('index', 0);
	$('#' + name + ' .table-line').remove();
	ICCashDesk.setCell('received', 0);
	ICCashDesk.computeSellPanel();
	ICCashDesk.callback['cashline'](name, 'cashline');
}

ICCashDesk.computeSellPanel = function() {
	var total = 0;
	var received = ICCashDesk.getCell('received');
	var totalht = ICCashDesk.getCell('totalht');
	var due = ICCashDesk.getCell('due');

	$('input.icomm-element[name^="' + ICCashDesk.Vars.settings.tableSuffix + 'total"]').each(function() {
		total = total + ($(this).val() * 1);
	});
	ICCashDesk.setCell('totalht', total);

	if(total == 0 && received.val() == 0) {
		return;
	}

	if(ICCashDesk.checkEntry(received, ['int', 'positive'], [0]) && total) {
		ICCashDesk.setCell('due', received.val() - total);

	} else {
		ICCashDesk.setCell('due', 0);
	}
	ICCashDesk.checkEntry(received, ['int', 'positive'], [0]);
	ICCashDesk.checkEntry(due, 'positive');
	ICCashDesk.checkEntry(totalht, 'positive', [0]);
};

ICCashDesk.loadProduct = function(self, callback) {
	var row = ICCashDesk.getRow(self);

	if(pid = row.find('div.select-autocomplete input:hidden').val()) {
		var block = ICCashDesk.getRowBlock(self);
		if(result = ICCashDesk.call(callback, {'pid': pid})) {
			block.content.html(result);
			ICCashDesk.showBlock(block);
		}
	}
};

ICCashDesk.focusNext = function(self, parent) {
	var element = null;

	if(parent) {
		element = self.parents(parent).next(parent);

	} else {
		//element = self.parent().next('.icomm-element-wrapper') || self.find('.icomm-element-wrapper:first');
		element = self.find('.icomm-element-wrapper:first') || self.parent().next('.icomm-element-wrapper') || null;
	}

	if(element != null && typeof element != undefined) {
		element.find('input, select, textarea').first().focus().select();
	}
};

ICCashDesk.events = function() {
	$('input[name^="search_"]').each(function() {
		var row = ICCashDesk.getRow($(this));
		var name = $(this).attr('name').substring('search_'.length);
		if((pid = $('#' + name).val()) && (product = ICCashDesk.call('product', {'pid': pid}))) {
			$(this).val(product.ref);
			ICCashDesk.setRowCell(row, ICCashDesk.Vars.settings.tableSuffix + 'pu', product.pu, true);
			ICCashDesk.setRowTotal(row);
		}
	});

	$('.icomm-element').bind('click keyup keydown change', function(event) {
		ICCashDesk.hideBlocks();
		if(event.type == 'click' && $(this).hasClass('error')) {
			bubble = $(this).next('.bubble');
			ICCashDesk.showBlock(bubble);

		} else {
			var context = ICCashDesk.getGrid($(this)).attr('id');
			var self = $(this);
			var row = ICCashDesk.getRow(self);
			var name = self.attr('name').replace(ICCashDesk.Vars.settings.tableSuffix, '');
			if(index = row.attr('index')) {
				name = name.substr(0, name.length - index.length);
			}

			if(ICCashDesk.event[name]) {
				return ICCashDesk.event[name](event, context, row, self);
			}
		}
	});

	$('.select-switch').click(function(e) {
		e.stopImmediatePropagation();

		ICCashDesk.hideBlocks();
		var block = ICCashDesk.getRowBlock($(this));
		if(info = $(this).prevAll('.ui-autocomplete-input').val()) {
			block.content.html(info);
			ICCashDesk.showBlock(block);
		}
		
		return false;
	});

	$('.action').click(function(e) {
		e.stopImmediatePropagation();

		ICCashDesk.hideBlocks();
		var self = $(this);
		var row = ICCashDesk.getRow(self);

		if((callback = self.attr('callback')) && ICCashDesk.callback[callback]) {
			ICCashDesk.callback[callback](self, callback);

		} else if((action = self.attr('name')) && ICCashDesk.action[action]) {
			ICCashDesk.action[action](self, row, action);
		}

		return false;
	});

	$('.select-autocomplete input:hidden').change(function() {
		if($(this).val().length) {
			var product = ICCashDesk.call('product', {pid: $(this).val()});
			ICCashDesk.hideBlocks();
			var row = ICCashDesk.getRow($(this));
			ICCashDesk.setRowCell(row, ICCashDesk.Vars.settings.tableSuffix + 'pu', product.pu, true);
			ICCashDesk.setRowTotal(row);
			ICCashDesk.computeSellPanel();
			ICCashDesk.focusNext($(this), '.table-cell');
		}
	});
};

ICCashDesk.action = {
	delete: function(self, row, action) {
		var grid = ICCashDesk.getGrid(self);
		row.next('tr').remove();
		row.remove();
		if(grid.find('.table-line').length == 0) {
			ICCashDesk.clearGrid(grid.attr('id'));
		}
		ICCashDesk.computeSellPanel();
	},
	reload: function(self, row, action) {
		window.location = location.href;
	},
};

ICCashDesk.call = function(callback, param, method) {
	var result;

	if(!method) method = 'get';
	if(callback) {
		$.ajax({
			url: ICCashDesk.Vars.baseurl + '/index.php?callback=' + callback,
			type: method,
			data: param,
			async: false,
			success: function(data) {
				if(data) result = data;
			} 
		});
	}

	return result;
};

ICCashDesk.callback = {
	payblock: function(self, callback) {
		var mode = self.attr('mode');
		var target = self.attr('target');

		if(info = ICCashDesk.call(callback, {'mode': mode})) {
			var block = ICCashDesk.getBlock(target);
			block.title.html(info.title);
			block.content.html(info.content);
			ICCashDesk.showBlock(block);
			ICCashDesk.focusNext(block.content);
		}
	},
	cashline: function(self, callback) {
		var grid = ICCashDesk.getGrid(self);
		var name = grid.attr('id');
		var index = grid.attr('index') || 0;
		var param = {'index': index, 'name': name};

		if(result = ICCashDesk.call(callback, param)) {
			$('#' + name + ' .table-header').parent().append(result);
			$('#' + name + ' .table-header').parent().find('.ui-autocomplete-input').focus();
			grid.attr('index', (index * 1) + 1);
			ICCashDesk.events();
		}
	},
	resetsell: function(self, callback) {
		ICCashDesk.clearGrid(self.attr('grid'));
		ICCashDesk.call(callback);
	},
	photo: function(self, callback) {
		ICCashDesk.loadProduct(self, callback);
	},
	info: function(self, callback) {
		ICCashDesk.loadProduct(self, callback);

	},
};

ICCashDesk.event = {
	'search_product': function(event, context, row, self) {
		if(event.type == 'keyup') {
			ICCashDesk.checkEntry(self, 'string');
		}
	},
	received: function(event, context, row, self) {
		if(event.type == 'keyup') {
			ICCashDesk.computeSellPanel();
		}
	},
	qty: function(event, context, row, self) {
		if(event.type == 'keydown') {
			if(event.which == 9) {
				var block = ICCashDesk.getBlock('block-payment');
				ICCashDesk.focusNext(block.content);

				return false;

			} else if(event.which >= 96 && event.which <= 105 || event.which == 8 || event.which == 46) {
				return true;

			} else {
				return false;
			}

		} else if(event.type == 'keyup') {
			ICCashDesk.checkEntry(self, ['int', 'positive'], [0]);
			var pu = ICCashDesk.getRowCell(row, ICCashDesk.Vars.settings.tableSuffix + 'pu', true);
			if(pu > 0) {
				ICCashDesk.setRowTotal(row);
				ICCashDesk.computeSellPanel();
			}
		}
	}
};

ICCashDesk.trigger = {
	'numeric': function(event, self) {
	},
	'positive': function(event, self) {
	}
};

ICCashDesk.events();
ICCashDesk.computeSellPanel();
ICCashDesk.messages();
alert(ICCashDesk.Vars.triggers.simple['due'][0]);

});
