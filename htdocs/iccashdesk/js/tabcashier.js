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


$(document).ready(function() {

ICCashDesk.Cashier = {
	paymode: null,
};

ICCashDesk.Cashier.getProduct = function(self, callback) {
	var row = ICCashDesk.getRow(self);
	var pid = row.find('div.select-autocomplete input:hidden').val() || undefined;

	ICCashDesk.loadProduct(callback, pid, function(product) {
		var block = ICCashDesk.getPopup(row);
		block.content.html(product);
		ICCashDesk.showPopup();
	});

};

ICCashDesk.Cashier.clearRow = function(index) {
	if(row = $('.grid-line[index="' + index + '"]')) {
		row.find('input[name]:hidden').first().val('');
		row.find('input.icomm-element').val('');
		ICCashDesk.setRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'qty', 1, 1);
	}
};

ICCashDesk.Cashier.setPaymode = function() {
	ICCashDesk.Cashier.paymode = ICCashDesk.getField('paymode').val();
}

ICCashDesk.Cashier.setDue = function() {
	var totalht = (ICCashDesk.sanitize(ICCashDesk.getField('totalht').val()) * 1) || 0;
	var reveive = (ICCashDesk.sanitize(ICCashDesk.getField('received').val()) * 1) || 0;
	var due = ICCashDesk.getField('due');
	var amount = reveive - totalht;
	due.val(ICCashDesk.humanInteger(amount));
	if(ICCashDesk.checkEntry(amount, 'positive', ['0'])) {
		due.removeClass(CLASS_ERROR);
	} else {
		due.addClass(CLASS_ERROR);
	}
};

ICCashDesk.Cashier.newLine = function(self) {
	var grid = ICCashDesk.getGrid(self);
	var name = grid.attr('id');
	var index = grid.attr('index') || 0;
	var param = {'index': index, 'name': name};

	ICCashDesk.call('cashline', param, 'get', function(content) {
		$('#' + name + ' .grid-header').parent().append(content);
		$('#' + name + ' .grid-header').parent().find('.ui-autocomplete-input').focus();
		grid.attr('index', (index * 1) + 1);
		ICCashDesk.hideBlocks();
		//ICCashDesk.dispatchAll();
	});
};

ICCashDesk.Cashier.flushGrid = function(element) {
	var grid = ICCashDesk.getGrid(element);
	var name = grid.attr('id');

	ICCashDesk.clearGrid(grid);
	ICCashDesk.Cashier.newLine(grid);
	ICCashDesk.Cashier.refreshSellPanel();
};

ICCashDesk.Cashier.setRowTotal = function(row) {
	var total = '';

	var pu = ICCashDesk.getRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'pu', true) * 1;
	var qty = ICCashDesk.getRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'qty', true) * 1;
	if(pu > 0 && qty > 0) {
		total = qty * pu;

	} else {
		total = '';
	}
	ICCashDesk.setRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'total', ICCashDesk.humanInteger(total), true);
	ICCashDesk.Cashier.refreshSellPanel();
};

ICCashDesk.Cashier.refreshSellPanel = function() {
	var total = 0;
	var totalht = ICCashDesk.getField('totalht');
	var due = ICCashDesk.getField('due');

	$('input.icomm-element[name^="' + ICCashDesk.Vars.settings.grid.prefix + 'total"]').each(function() {
		total = total + (ICCashDesk.sanitize($(this).val(), ' ') * 1);
	});
	if(total != totalht.val() * 1)
		totalht.val(ICCashDesk.humanInteger(total));

	ICCashDesk.executeHook('Cashier', ICCashDesk.Cashier.paymode, 'refreshSellPanel');
};

ICCashDesk.bind('event', 'tabcashier', function() {
	$('input[name^="search_"]').each(function() {
		var self = $(this);
		var row = ICCashDesk.getRow(self);
		var name = self.attr('name').substring('search_'.length);

		if(pid = $('#' + name).val())
			ICCashDesk.call(ICCashDesk.Vars.settings.callbacks.getproduct, {'pid': pid}, 'get', function(product) {
				self.val(product.ref);
				ICCashDesk.setRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'pu', product.pu, true);
				ICCashDesk.Cashier.setRowTotal(row);
			});
	});

	$('div.page').on('click', '.fullview-switch', function () {
		self = $(this).prevAll('input.icomm-element:first');
		if(self.val() && (block = ICCashDesk.getBubble(self))) {
			block.content.html(self.val());
			ICCashDesk.showBubble(block);
		}
	});

	$('div.page').on('change', '.select-autocomplete input:hidden', function(event) {
		if($(this).val().length) {
			var row = ICCashDesk.getRow($(this));

			ICCashDesk.loadProduct('product', $(this).val(), function(product) {
				ICCashDesk.hideBlocks();
				ICCashDesk.setRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'pu', product.pu, true);
				ICCashDesk.Cashier.setRowTotal(row);
			});
		}
	});
});

ICCashDesk.bind('interface', 'action-grid-newline', function(interface, event, self) {
	ICCashDesk.Cashier.newLine(self);
});

ICCashDesk.bind('interface', 'action-grid-delline', function(interface, event, self) {
	var grid = ICCashDesk.getGrid(self);

	if(grid.find('.grid-line').length == 1) {
		ICCashDesk.Cashier.clearRow(grid.find('.grid-line:first').attr('index'));
		ICCashDesk.setField('due', 0);
		ICCashDesk.setField('received', 0);

	} else {
		ICCashDesk.getRow(self).remove();
	}
	ICCashDesk.Cashier.refreshSellPanel();
});

ICCashDesk.bind('interface', 'action-new', function(interface, event, self) {
	ICCashDesk.Cashier.flushGrid(self.attr('grid'));
	ICCashDesk.call(interface['new']);
});

ICCashDesk.bind('interface', 'action-suspend', function(interface, event, self) {
	ICCashDesk.Cashier.flushGrid(self.attr('grid'));
	ICCashDesk.call(interface['suspend']);
});

ICCashDesk.bind('interface', 'action-product-photo', function(interface, event, self) {
	var row = ICCashDesk.getRow(self);
	var pid = ICCashDesk.getRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'product', true);

	if(pid)
		ICCashDesk.loadProduct(interface['product-photo'], pid, function(product) {
			var popup = ICCashDesk.getPopup();
			popup.content.html(product);
			ICCashDesk.showPopup();
		});
});

ICCashDesk.bind('interface', 'action-product-info', function(interface, event, self) {
	var row = ICCashDesk.getRow(self);
	var pid = ICCashDesk.getRowField(row, ICCashDesk.Vars.settings.grid.prefix + 'product', true);

	if(pid)
		ICCashDesk.loadProduct(interface['product-info'], pid, function(product) {
			var popup = ICCashDesk.getPopup();
			popup.content.html(product);
			ICCashDesk.showPopup();
		});
});

ICCashDesk.bind('interface', 'trigger-qty', function(interface, event, self) {
	if(event.type == 'keyup')
		ICCashDesk.Cashier.setRowTotal(ICCashDesk.getRow(self));
});

ICCashDesk.bind('interface', 'trigger-received', function(interface, event, self) {
	if(event.type == 'keyup' || event.type == 'change')
		ICCashDesk.Cashier.setDue();
});

ICCashDesk.bind('interface', 'action-paymode', function(interface, event, self) {
	var target = ICCashDesk.Vars.settings[interface['paymode'].params].target;
	var callback = ICCashDesk.Vars.settings[interface['paymode'].params].callback;

	ICCashDesk.call(callback, {'mode': interface['paymode'].mode}, 'get', function(info) {
		ICCashDesk.Cashier.paymode = interface['paymode'].mode;
		var block = ICCashDesk.getBlock(target);
		block.title.html(info.title);
		block.content.html(info.content);
		ICCashDesk.showBlock(block);
	});
});

ICCashDesk.Cashier.hooks = {
	'cash': function(hook) {
		if(hook == 'refreshSellPanel') {
			if(ICCashDesk.Cashier.paymode == 'cash')
				ICCashDesk.Cashier.setDue()
		}
	},
};

ICCashDesk.Cashier.setPaymode();
ICCashDesk.Cashier.refreshSellPanel();

});
