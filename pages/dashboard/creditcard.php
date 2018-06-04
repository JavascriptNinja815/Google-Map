<?php

ini_set('max_execution_time', 300);

$session->ensureLogin();
$args = array(
	'title' => 'Credit Card',
	'breadcrumbs' => array(
		'Credit Card' => BASE_URI . '/dashboard/creditcard'
	)
);

Template::Render('header', $args, 'account');

if($session->hasRole('Administration')) {
	$permission_constraint = "1 = 1"; // Show all.
} else if($session->hasRole('Sales')) {
	$permissions = $session->getPermissions('Sales', 'view-orders');
	if(!empty($permissions)) {
		// Sanitize values for DB querying.
		$permissions = array_map(function($value) {
			$db = \PM\DB\SQL::connection();
			return $db->quote($value);
		}, $permissions);
		$permission_constraint = "logins.initials IN (" . implode(',', $permissions) . ")";
	} else {
		// If the user has not been granted any privs, then finish the query which will already return nothing.
		// TODO: There has to be a more elegant method of handling this...
		$permission_constraint = "1 != 1";
	}
} else {
	$permission_constraint = "1 != 1"; // Don't show any.
}
$grab_logins = $db->query("
	SELECT
		logins.login_id,
		logins.initials,
		logins.first_name,
		logins.last_name
	FROM
		" . DB_SCHEMA_INTERNAL . ".logins
	WHERE
		logins.login_id = " . $db->quote($session->login['login_id']) . "
		OR
		" . $permission_constraint . "
	ORDER BY
		logins.initials
");

$grab_clients = $db->query("
	SELECT
		arcust.custno,
		arcust.company,
		arcust.salesmn
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.initials = " . $db->quote($session->login['initials']) . "
	WHERE
		" . $permission_constraint . "
	ORDER BY
		arcust.custno
");
$clients = [];
foreach($grab_clients as $client) {
	$clients[trim($client['custno'])] = strtoupper(
		trim($client['custno']) . ' - ' . trim($client['company'])
	);
}

/**
 * Client related logic.
 */
if($session->hasRole('Administration')) {
	$permission_constraint = "1 = 1"; // Show all.
} else if($session->hasRole('Sales')) {
	$permissions = $session->getPermissions('Sales', 'view-orders');
	if(!empty($permissions)) {
		// Sanitize values for DB querying.
		$permissions = array_map(function($value) {
			$db = \PM\DB\SQL::connection();
			return $db->quote($value);
		}, $permissions);
		$permission_constraint = "arcust.salesmn IN (" . implode(',', $permissions) . ")";
	} else {
		// If the user has not been granted any privs, then finish the query which will already return nothing.
		// TODO: There has to be a more elegant method of handling this...
		$permission_constraint = "1 != 1";
	}
} else {
	$permission_constraint = "1 != 1"; // Don't show any.
}
$grab_clients = $db->query("
	SELECT
		arcust.custno,
		arcust.company,
		arcust.salesmn
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		" . $permission_constraint . "
	ORDER BY
		arcust.custno
");
$clients = [];
foreach($grab_clients as $client) {
	$clients[trim($client['custno'])] = strtoupper(
		trim($client['custno']) . ' - ' . trim($client['company'])
	);
}

/**
 * Date search specific logic.
 */
$date_from_input = date('n/j/Y', strtotime('-1 week'));
$date_to_input = date('n/j/Y', time());

?>

<style type="text/css">
	#cc-addbutton-container {
		padding:32px 0 0 32px;
	}
	#cc-addbutton-container i.fa {
		text-shadow: 0px 0px 2px #000;
	}
	#cc-search-container .section {
		display:inline-block;
		padding:12px;
		vertical-align:top;
	}
	#cc-search-container input[name="date_from"],
	#cc-search-container input[name="date_to"],
	#cc-search-container input[name="amount[from]"],
	#cc-search-container input[name="amount[to]"] {
		width:84px;
	}
	#cc-search-container .transactions-container {
		display:none;
	}
	#cc-search-container [name="amount[operator]"] {
		width:100px;
	}
	#cc-search-container .amount-to-container {
		display:none;
	}
	#cc-search-container .transaction .void,
	#cc-search-container .transaction .refund,
	#cc-search-container .transaction .receipt {
		color:#00f;
		cursor:pointer;
		padding-right:4px;
	}
	#cc-search-container .transaction .void .fa-undo {
		color:#f90;
	}
	#cc-search-container .transaction .refund .fa-undo {
		color:#f00;
	}
	#cc-search-container .transaction .void:hover,
	#cc-search-container .transaction .refund:hover,
	#cc-search-container .transaction .receipt:hover {
		text-decoration:underline;
	}
	#cc-search-container .invno .action-edit,
	#cc-search-container .sono .action-edit {
		float:left
	}
	#cc-search-container .invnos-container,
	#cc-search-container .sonos-container {
		margin-left:36px;
	}
	#cc-search-container .invnos-container .invno,
	#cc-search-container .sonos-container .sono {
		display:block;
	}
	#cc-search-container .transaction .fa-print {
		font-size:2em;
	}
</style>

<div id="cc-addbutton-container">
	<button class="btn btn-primary cc-onetime">
		<i class="action action-add fa fa-plus"></i>
		One-Time Payment
	</button>
	<button class="btn btn-primary cc-existing">
		<i class="action action-add fa fa-plus"></i>
		Existing Client Payment
	</button>
</div>

<div class="padded" id="cc-search-container">
	<hr />
	<h2>Search Transactions</h2>

	<form id="cc-search-form" class="form-horizontal" action="<?php print BASE_URI;?>/dashboard/creditcard/search" method="POST">
		<input type="hidden" name="search" value="1" />
		<div class="section">
			<div class="control-group">
				<label class="control-label" for="cc-search-salesmn">Entered By</label>
				<div class="controls">
					<select name="salesmn" id="cc-search-salesmn">
						<option value="">-- Any --</option>
						<?php
						foreach($grab_logins as $login) {
							?><option value="<?php print htmlentities($login['initials'], ENT_QUOTES);?>" <?php print isset($_POST['salesmn']) && $_POST['salesmn'] == $login['initials'] ? 'selected' : Null;?>><?php print htmlentities($login['initials'] . ' - ' . $login['first_name'] . ' ' . $login['last_name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="datepicker-from">Transaction Date</label>
				<div class="controls">
					Between
					<input class="span2" type="text" name="date_from" id="datepicker-from" value="<?php print htmlentities($date_from_input, ENT_QUOTES);?>">
					and
					<input class="span2" type="text" name="date_to" id="datepicker-to" value="<?php print htmlentities($date_to_input, ENT_QUOTES);?>">
				</div>
			</div>
			<?php
			if($session->hasRole('Administration')) {
				?>
				<div class="control-group">
					<label class="control-label" for="cc-search-status">Transaction Status</label>
					<div class="controls">
						<select name="status" id="cc-search-status">
							<option value="">-- Any Status --</option>
							<option value="1">Success</option>
							<option value="0">Running</option>
							<option value="-1">Declined</option>
						</select>
					</div>
				</div>
				<?php
			}
			?>
			<div class="control-group">
				<label class="control-label" for="cc-search-auth_id">Authorize ID<br /><small>Transaction ID or Auth Code</small></label>
				<div class="controls">
					<input type="text" name="auth_id" id="cc-search-auth_id" />
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="cc-search-amount">Original Amount</label>
				<div class="controls">
					<select name="amount[operator]">
						<option value="<">&lt;</option>
						<option value="=" selected>=</option>
						<option value=">">&gt;</option>
						<option value="between">Between</option>
					</select>
					<input name="amount[from]" id="cc-search-amount" type="number" step="0.01" min="0.00" />
					<div class="amount-to-container">
						and <input name="amount[to]" type="number" step="0.01" min="0.00" />
					</div>
				</div>
			</div>
		</div>

		<div class="section">
			<div class="control-group">
				<label class="control-label" for="cc-search-name">Buyer Name/Company</label>
				<div class="controls">
					<input type="text" name="name" id="cc-search-name" />
					<br />
					<small><small>(Search by Name On Card, Buyer Name, company, or AP Name)</small></small>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="cc-search-custno">Client</label>
				<div class="controls">
					<input type="text" name="custno" id="cc-search-custno" />
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="cc-search-invno">Invoice #</label>
				<div class="controls">
					<input type="number" name="invno" step="1" min="0" id="cc-search-invno" />
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="cc-search-sono">SO #</label>
				<div class="controls">
					<input type="number" step="1" min="0" name="sono" id="cc-search-sono" />
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="cc-search-memo">Memo</label>
				<div class="controls">
					<input type="text" name="memo" id="cc-search-memo" />
				</div>
			</div>
		</div>

		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">
					<i class="fa fa-search"></i>
					Search
				</button>
			</div>
		</div>
	</form>

	<fieldset class="transactions-container">
		<legend>
			<div class="padded-x">
				Transactions Found:
				<span class="transactions-found"></span>
			</div>
		</legend>
	</fieldset>
</div>

<script type="text/javascript">
	// Populate list of clients for later reference.
	var clients = <?php print json_encode($clients);?>;

	var $transaction_count = $('#cc-search-container .transaction-count');
	var $transactions_tbody = $('#');

	// Implement Clients autocomplete
	var $sono_autocomplete = $('#cc-onetime-form :input[name="custno"]').autoComplete({
		'minChars': 1,
		'source': function(term, response) {
			term = term.toUpperCase();
			var matches = [];
			$.each(clients, function(i, client) {
				if(client.toUpperCase().indexOf(term) > -1) {
					matches.push(client);
				}
			});
			response(matches);
		},
		'onSelect': function(event, term, item) {
			$('#cc-onetime-form :input[name="custno"]').blur().trigger('change');
		}
	});

	/**
	 * Bind to clicks on One Time button.
	 */
	$(document).off('click', '#cc-addbutton-container button.cc-onetime');
	$(document).on('click', '#cc-addbutton-container button.cc-onetime', function(event) {
		activateOverlayZ(
			BASE_URI + '/dashboard/creditcard/onetime',
			{}
		);
	});

	/**
	 * Bind to clicks on Existing Customer button.
	 */
	$(document).off('click', '#cc-addbutton-container button.cc-existing');
	$(document).on('click', '#cc-addbutton-container button.cc-existing', function(event) {
		activateOverlayZ(
			BASE_URI + '/dashboard/creditcard/client',
			{}
		);
	});
	
	/**
	 * Implement Date-Picker.
	 */
	$(function() {
		/**
		 * Bind DatePickers to Date Range "From" and "To" input boxes.
		 */
		var $datetime_from = $('#datepicker-from');
		var $datetime_to = $('#datepicker-to');

		var datepicker_config = {
			'lang': 'en',
			'datepicker': true,
			'timepicker': false,
			'formatDate': 'n/j/Y', //'formatDate': 'n/j/Y g:ia',
			'format': 'n/j/Y',
			'closeOnDateSelect': true
		};

		var datepicker_from_config = {};
		$.extend(datepicker_from_config, datepicker_config, {
			'onShow': function(selected_datetime) {
				this.setOptions({
					'maxDate': $datetime_to.val(),
					'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
				});
			},
			'onChangeDateTime': function(selected_datetime) {
				this.setOptions({
					'maxDate': $datetime_to.val(),
					'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
				});
			}
		});

		var datepicker_to_config = {};
		$.extend(datepicker_to_config, datepicker_config, {
			'onShow': function(selected_datetime) {
				this.setOptions({
					'minDate': $datetime_from.val(),
					'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
				});
			},
			'onChangeDateTime': function(selected_datetime) {
				this.setOptions({
					'minDate': $datetime_from.val(),
					'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
				});
			}
		});

		$datetime_from.datetimepicker(datepicker_from_config);
		$datetime_to.datetimepicker(datepicker_to_config);
	});

	/**
	 * Bind to clicks on Search button.
	 */
	function renderTransaction(transaction) {
		if(transaction.status == -1) {
			var status = 'Declined';
		} else if(transaction.status == 0) {
			var status = 'UNKNOWN';
		} else if(transaction.status == 1) {
			var status = 'Successful';
		}

		var amount = '$' + transaction.amount;
		var auth_code = transaction.auth_code ? transaction.auth_code : '';
		var last4 = transaction.last4;
		var action = transaction.action;
		var auth_trans_id = transaction.authorize_transaction_id ? transaction.authorize_transaction_id : '';
		var salesmn = transaction.salesmn;
		var memo = transaction.memo ? transaction.memo : '';

		var $invnos = $('<div class="invnos-container">');
		$.each(transaction.invnos, function(offset, invno) {
			$invnos.append(
				$('<span class="invno">').text(invno)
			);
		});

		var $sonos = $('<div class="sonos-container">');
		$.each(transaction.sonos, function(offset, sono) {
			$sonos.append(
				$('<a class="sono overlayz-link" overlayz-url="' + BASE_URI + '/dashboard/sales-order-status/so-details">').text(sono).attr('overlayz-data', JSON.stringify({'so-number': sono}))
			);
		});

		var actions = [];
		if(transaction.status == 1) {
			actions.push(
				$('<a class="receipt" title="Receipt">').append(
					'[receipt]'
					//$('<i class="fa fa-print">')
				).attr('target', '_receipt_' + transaction.transaction_id).attr('href', BASE_URI + '/dashboard/creditcard/receipt?transaction_id=' + transaction.transaction_id)
			);
			if(transaction.action === 'charge') {
				if(transaction.allow_void) {
					actions.push(
						' ',
						$('<a class="void overlayz-link" title="Void" overlayz-url="' + BASE_URI + '/dashboard/creditcard/void" overlayz-response-type="html">').attr('overlayz-data', JSON.stringify({'transaction_id': transaction.transaction_id})).text('[void]')
						//$('<span class="fa-stack fa-lg void overlayz-link" title="Void" overlayz-url="' + BASE_URI + '/dashboard/creditcard/void" overlayz-response-type="html">').attr('overlayz-data', JSON.stringify({'transaction_id': transaction.transaction_id})).append(
						//	$('<i class="fa fa-undo fa-stack-2x">'),
						//	$('<i class="fa fa-usd fa-stack-1x">')
						//)
					);
				}
				if(transaction.allow_refund) {
					actions.push(
						' ',
						$('<a class="refund overlayz-link" title="Void" overlayz-url="' + BASE_URI + '/dashboard/creditcard/refund" overlayz-response-type="html">').attr('overlayz-data', JSON.stringify({'transaction_id': transaction.transaction_id})).text('[refund]')
						//$('<span class="fa-stack fa-lg refund overlayz-link" title="Refund" overlayz-url="' + BASE_URI + '/dashboard/creditcard/refund" overlayz-response-type="html">').attr('overlayz-data', JSON.stringify({'transaction_id': transaction.transaction_id})).append(
						//	$('<i class="fa fa-undo fa-stack-2x">'),
						//	$('<i class="fa fa-usd fa-stack-1x">')
						//)
					);
				}

			}
		}
		
		var custno = '';
		if(transaction.custno) {
			custno = $('<div class="custno overlayz-link" overlayz-url="/dashboard/clients/details">').attr('overlayz-data', JSON.stringify({'custno': transaction.custno})).text(transaction.custno);
		}

		var $transaction = $('<tr class="transaction">').attr('transaction_id', transaction.transaction_id).attr('last4', transaction.last4).append(
			$('<td class="actions">').append(actions),
			$('<td class="added-on">').text(transaction.added_on),
			$('<td class="custno">').append(custno),
			$('<td class="amount">').text(amount),
			$('<td class="auth-code">').text(auth_code),
			$('<td class="last4">').text(last4),
			$('<td class="action">').text(action),
			$('<td class="status">').text(status),
			$('<td class="auth-transaction-id">').text(auth_trans_id),
			$('<td class="salesman">').text(salesmn),
			$('<td class="memo">').text(memo),
			$('<td class="invno">').append(
				$('<a class="action action-edit" >').append(
					$('<i class="fa fa-search action action-edit overlayz-link" overlayz-url="' + BASE_URI + '/dashboard/creditcard/transaction/associate">').attr('overlayz-data', JSON.stringify({'transaction_id': transaction.transaction_id})),
				),
				$invnos
			),
			$('<td class="sono">').append(
				$('<a class="action action-edit" >').append(
					$('<i class="fa fa-search action action-edit overlayz-link" overlayz-url="' + BASE_URI + '/dashboard/creditcard/transaction/associate">').attr('overlayz-data', JSON.stringify({'transaction_id': transaction.transaction_id})),
				),
				$sonos
			),
			//$('<td class="transaction-id">').text(transaction.transaction_id),
			//$('<td class="payment_profile">').text(transaction.payment_profile ? transaction.payment_profile : ''),
		);

		return $transaction;
	}

	$(document).off('submit', '#cc-search-form');
	$(document).on('submit', '#cc-search-form', function(event) {
		var form = this;
		var $form = $(form);
		
		if($form.find('[name="download"]').length) {
			return true; // Allow normal form submission for "Download" button press.
		}
		
		var $page = $form.closest('#cc-search-container');
		var data = new FormData(form);

		var $transactions_container = $page.find('.transactions-container');
		$transactions_container.find('.transactions-table').remove();
		var $transactions_found = $page.find('.transactions-found');

		var $transactions_table = $('<table class="transactions-table table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky">').appendTo($transactions_container);
		var $transactions_thead = $('<thead>').append(
			$('<tr>').append(
				$('<th>').append(
					$('<button type="button" class="btn btn-success download-results">').text('Download')
				),
				//$('<th class="filterable sortable">').text('ID'),
				$('<th class="filterable sortable" data-sorter="shortDate">').text('Date'),
				$('<th class="filterable sortable">').text('Client Code'),
				$('<th class="filterable sortable">').text('Amount'),
				$('<th class="filterable sortable">').text('Auth Code'),
				$('<th class="filterable sortable">').text('Last 4'),
				$('<th class="filterable sortable">').text('Action'),
				$('<th class="filterable sortable">').text('Status'),
				$('<th class="filterable sortable">').text('Transaction ID'),
				$('<th class="filterable sortable">').text('User'),
				$('<th class="filterable sortable">').text('Memo'),
				$('<th class="filterable sortable">').text('Invoice'),
				$('<th class="filterable sortable">').text('SO')
				//$('<th class="filterable sortable">').text('Payment Profile')
			)
		).appendTo($transactions_table);
		var $transactions_tbody = $('<tbody class="transactions-tbody">').appendTo($transactions_table);

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': $form.attr('action'),
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'beforeSend': function() {
				$transactions_container.slideUp('fast');
			},
			'success': function(response) {
				if(response.success) {
					$transactions_found.text(response.transactions.length);
					$.each(response.transactions, function(offset, transaction) {
						$transactions_tbody.append(
							renderTransaction(transaction)
						);
					});
					$transactions_container.slideDown('fast');

					$.each($([
						'#cc-search-container table.columns-filterable',
						'#cc-search-container table.columns-sortable',
						'#cc-search-container table.headers-sticky'
					]), function(index, table) {
						var $table = $(table);
						var options = {
							'selectorHeaders': [],
							'widgets': [],
							'widgetOptions': {}
						};

						if($table.hasClass('columns-sortable')) {
							options.selectorHeaders.push('> thead > tr > th.sortable');
							options.selectorHeaders.push('> thead > tr > td.sortable');
							options.onRenderHeader = function() {
								// Fixes text that wraps when it doesn't necessarily need to.
								$(this).find('div').css('width', '100%');
							};
						}
						if($table.hasClass('columns-filterable')) {
							options.selectorHeaders.push('> thead > tr > th.filterable');
							options.selectorHeaders.push('> thead > tr > td.filterable');
							options.widgets.push('filter');
							options.widgetOptions.filter_ignoreCase = true;
							options.widgetOptions.filter_searchDelay = 100;
							options.widgetOptions.filter_childRows = true;
						}
						if($table.hasClass('headers-sticky')) {
							options.widgets.push('stickyHeaders');
							options.widgetOptions.stickyHeaders_attachTo = $('#body');
						}

						// Convert array to comma-separated string.
						options.selectorHeaders = options.selectorHeaders.join(',');

						$table.tablesorter(options);
					});
				}
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});

		return false;
	});

	/**
	 * Bind to changes on Amount Operator field.
	 */
	$(document).off('change', '#cc-search-form :input[name="amount[operator]"]');
	$(document).on('change', '#cc-search-form :input[name="amount[operator]"]', function(event) {
		var $amount_operator = $(this);
		var $amount_to_container = $('#cc-search-form .amount-to-container');
		var amount_operator = $amount_operator.val();
		if(amount_operator === 'between') {
			$amount_to_container.css('display', 'inline-block');
		} else {
			$amount_to_container.hide();
		}
	});

	$(document).off('click', '#cc-search-container .download-results');
	$(document).on('click', '#cc-search-container .download-results', function(event) {
		var $form = $('#cc-search-form');
		
		// Add download flag to the search form.
		var $input = $('<input type="hidden" name="download" value="1" />');
		$input.appendTo($form);
		
		// Submit the search form.
		$form.submit();
		
		// Remove the download flag from the search form.
		$input.remove();
	});

</script>
<?php

Template::Render('footer', 'account');
