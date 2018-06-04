$(document).ready(function() {
	var one_minute = 60 * 1000; // Calculated in milliseconds.

	/**
	 * POPULATE "PERSONAL OPPORTUNITIES" TABLE
	 */
	var loadPersonalOpportunities = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/opportunities-by-individual',
			'dataType': 'json',
			'success': function(data) {
				var $container = $('#personalopportunities-container');
				var $datetime = $('#personalopportunities-datetime');
				var $table = $('#personalopportunities-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate opportunities.
				$tbody.empty();
				$.each(data.opportunities, function(offset, salesman) {
					var $tr = $('<tr>').append(
						$('<td>').text(salesman['salesman']),
						$('<td>').addClass('right').text('$' + salesman['amount-today']),
						$('<td>').addClass('right').text('$' + salesman['amount-week']),
						$('<td>').addClass('right').text('$' + salesman['amount-month']),
						$('<td>').addClass('right').text('$' + salesman['amount-year'])
					);
					$tr.appendTo($tbody);
				});
			}
		});
	};
	loadPersonalOpportunities();
	setInterval(loadPersonalOpportunities, one_minute);

	/**
	 * POPULATE "PERSONAL SALES" TABLE
	 */
	var loadPersonalSales = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/sales-by-individual',
			'dataType': 'json',
			'success': function(data) {
				var $container = $('#personalsales-container');
				var $datetime = $('#personalsales-datetime');
				var $table = $('#personalsales-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate sales.
				$tbody.empty();
				$.each(data.sales, function(offset, salesman) {
					var $tr = $('<tr>').append(
						$('<td>').text(salesman['salesman']),
						$('<td>').addClass('right').text('$' + salesman['sales-today']),
						$('<td>').addClass('right').text('$' + salesman['sales-week']),
						$('<td>').addClass('right').text('$' + salesman['sales-month']),
						$('<td>').addClass('right').text('$' + salesman['sales-year'])
					);
					$tr.appendTo($tbody);
				});
			}
		});
	};
	loadPersonalSales();
	setInterval(loadPersonalSales, one_minute);

	/**
	 * POPULATE "SALES BY TERRITORY" TABLE
	 */
	var loadSalesByTerritory = function() {
		var d3pie_config = {
			"header": {
				"title": {
					"text": "Sales By Territory",
					"fontSize": 22,
					"font": "verdana"
				},
				"subtitle": {
					"color": "#999999",
					"fontSize": 10,
					"font": "verdana"
				},
				"titleSubtitlePadding": 12
			},
			"footer": {
				"color": "#999999",
				"fontSize": 11,
				"font": "open sans",
				"location": "bottom-center"
			},
			"size": {
				"canvasHeight": 300,
				"canvasWidth": 350
			},
			"data": {
				"content": []
			},
			"labels": {
				"outer": {
					"pieDistance": 10
				},
				"mainLabel": {
					"font": "verdana"
				},
				"percentage": {
					"color": "#e1e1e1",
					"font": "verdana",
					"fontSize": 8,
					"decimalPlaces": 0
				},
				"value": {
					"color": "#e1e1e1",
					"font": "verdana"
				},
				"lines": {
					"enabled": true,
					"color": "#cccccc"
				}
			},
			"effects": {
				"pullOutSegmentOnClick": {
					"effect": "linear",
					"speed": 400,
					"size": 15
				}
			}
		};

		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/sales-by-territory',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'salesbyterritory';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				/*
				// Empty all pie chart containers.
				$('#today-pie').empty();
				$('#week-pie').empty();
				$('#month-pie').empty();
				$('#year-pie').empty();

				//var today_config = jQuery.extend(true, {}, d3pie_config); // Deep copy the object
				//today_config.data.content = data.data['sales-today'];
				//today_config.header.title.text = 'Today';
				//var pie = new d3pie('today-pie', month_config);

				//var week_config = jQuery.extend(true, {}, d3pie_config); // Deep copy the object
				//week_config.data.content = data.data['sales-thisweek'];
				//week_config.header.title.text = 'This Week';
				//var pie = new d3pie('week-pie', year_config);

				var current_date = new Date();
				var locale = "en-us";
				var current_month = current_date.toLocaleString(
					locale,
					{
						'month': 'long'
					}
				);
				var current_year = current_date.getFullYear();

				var month_config = jQuery.extend(true, {}, d3pie_config); // Deep copy the object
				month_config.data.content = data.data['sales-thismonth'];
				month_config.header.title.text = current_month + ' Sales';
				var pie = new d3pie('month-pie', month_config);

				var year_config = jQuery.extend(true, {}, d3pie_config); // Deep copy the object
				year_config.data.content = data.data['sales-thisyear'];
				year_config.header.title.text = current_year + ' Sales';
				var pie = new d3pie('year-pie', year_config);
				*/

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate territories.
				$tbody.empty();
				$.each(data.territories, function(offset, territory) {
					var $tr = $('<tr>').appendTo($tbody);

					$('<td>').text(territory['territory']).appendTo($tr);
					$('<td>').addClass('right').text('$' + territory['sales-today']).appendTo($tr);
					$('<td>').addClass('right').text('$' + territory['sales-thisweek']).appendTo($tr);
					$('<td>').addClass('right').text('$' + territory['sales-thismonth']).appendTo($tr);
					$('<td>').addClass('right').text('$' + territory['sales-thisyear']).appendTo($tr);
				});

				// Populate totals.
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-today']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-thisweek']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-thismonth']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-thisyear']).appendTo($total_tr);

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);
			}
		});
	};
	loadSalesByTerritory();
	setInterval(loadSalesByTerritory, one_minute);

	/**
	 * POPULATE "BILLING BY INDIVIDUAL" TABLE
	 */
	var loadBillByIndividual = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/billing-by-individual',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'billbyindividual';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate individuals.
				$tbody.empty();
				$.each(data.individuals, function(offset, individual) {
					var $tr = $('<tr>').addClass('individual').attr('salesman', individual['salesman']).appendTo($tbody);
					var highlight_class = individual['change-percentage'].replace(/\,/, '') >= 0.0 ? 'good' : 'bad';

					var $individual_td = $('<td>').text(individual['salesman']).appendTo($tr);
					var $prior_month_td = $('<td>').addClass('right').text('$' + individual['sales-priormonth']).appendTo($tr);
					var $this_month_td = $('<td>').addClass('right').text('$' + individual['sales-thismonth']).appendTo($tr);
					$('<td>').addClass('right').text('$' + individual['sales-thisyear']).appendTo($tr);
					$('<td>').addClass('right').text('$' + individual['sales-lastyear']).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text((individual['change-percentage'] > 0 ? '+' : '') + individual['change-percentage'] + '%')
					).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text('$' + individual['change-dollars'])
					).appendTo($tr);

					if(individual.clickable) {
						// Individual is marked as being clickable.
						$individual_td.addClass('clickable').addClass('initials');
						$prior_month_td.addClass('clickable').addClass('prior-month');
						$this_month_td.addClass('clickable').addClass('mtd');
					}
				});

				// Populate totals.
				var highlight_class = data.total['change-percentage'].toString().replace(/\,/, '') >= 0.0 ? 'good' : 'bad';
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-priormonth']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-thismonth']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-thisyear']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-lastyear']).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text((data.total['change-percentage'] > 0 ? '+' : '') + data.total['change-percentage'] + '%')
				).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text('$' + data.total['change-dollars'])
				).appendTo($total_tr);
			}
		});
	};
	loadBillByIndividual();
	setInterval(loadBillByIndividual, one_minute);

	/**
	 * BIND TO CLICKS ON BILLING BY INDIVIDUAL: "PRIOR MONTH" AND "MONTH TO DATE" VALUES.
	 */
	var invoice_margins_selectors = [
		'#billbyindividual-container .individual .prior-month',
		'#billbyindividual-container .individual .mtd',
		'#billbyterritory-container .territory .prior-month',
		'#billbyterritory-container .territory .mtd'
	].join(', ');
	$(document).off('click', invoice_margins_selectors);
	$(document).on('click', invoice_margins_selectors, function(event) {
		var $cell = $(this);
		var $row = $cell.closest('.individual, .territory');
		var $container = $row.closest('#billbyindividual-container, #billbyterritory-container');

		var data = {};

		// Define date start.
		if($cell.hasClass('prior-month')) {
			data.date = $container.attr('last-month');
		} else if($cell.hasClass('mtd')) {
			data.date = $container.attr('current-month');
		}

		if($row.hasClass('individual')) {
			data.type = 'individual';
			data.value = $row.attr('salesman');
		} else if($row.hasClass('territory')) {
			data.type = 'territory';
			data.value = $row.attr('territory');
		}

		var url = BASE_URI + '/dashboard/invoice/margins';
		var $overlay;
		var $layz = activateOverlayZ(
			url,
			data,
			undefined,
			function(data) { // Success Callback
				$overlay = $layz.find('.overlayz-body');
				$overlay.empty();

				$overlay.append(data.html);
			}
		);

	});

	/**
	 * POPULATE "BILLING BY TERRITORY" TABLE
	 */

	var loadBillByTerritory = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/billing-by-territory',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'billbyterritory';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate territories.
				$tbody.empty();
				$.each(data.territories, function(offset, territory) {
					var $tr = $('<tr>').addClass('territory').attr('territory', territory['territory']).appendTo($tbody);
					var highlight_class = territory['change-percentage'].replace(/\,/, '') >= 0.0 ? 'good' : 'bad';

					var $territory_td = $('<td>').text(territory['territory']).appendTo($tr);
					var $prior_month_td = $('<td>').addClass('right').text('$' + territory['sales-priormonth']).appendTo($tr);
					var $this_month_td = $('<td>').addClass('right').text('$' + territory['sales-thismonth']).appendTo($tr);
					$('<td>').addClass('right').text('$' + territory['sales-thisyear']).appendTo($tr);
					$('<td>').addClass('right').text('$' + territory['sales-lastyear']).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text((territory['change-percentage'] > 0 ? '+' : '') + territory['change-percentage'] + '%')
					).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text('$' + territory['change-dollars'])
					).appendTo($tr);

					// Individual is marked as being clickable.
					$territory_td.addClass('clickable').addClass('territory-name');
					$prior_month_td.addClass('clickable').addClass('prior-month');
					$this_month_td.addClass('clickable').addClass('mtd');
				});

				// Populate totals.
				var highlight_class = data.total['change-percentage'].toString().replace(/\,/, '') >= 0.0 ? 'good' : 'bad';
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-priormonth']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-thismonth']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-thisyear']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-lastyear']).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text((data.total['change-percentage'] > 0 ? '+' : '') + data.total['change-percentage'] + '%')
				).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text('$' + data.total['change-dollars'])
				).appendTo($total_tr);
			}
		});
	};

	loadBillByTerritory();
	setInterval(loadBillByTerritory, one_minute);

	/**
	 * POPULATE "CLIENT COUNT BY TERRITORY" TABLE
	 */
	var loadClientCountByTerritory = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/clientcount-by-territory',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'clientcountbyterritory';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate territories.
				$tbody.empty();
				$.each(data.territories, function(offset, territory) {
					var $tr = $('<tr>').appendTo($tbody);
					var highlight_class = territory['change-percentage'].replace(/\,/, '') >= 0.0 ? 'good' : 'bad';

					$('<td>').text(territory['territory']).appendTo($tr);
					$('<td>').addClass('right').text(territory['count-thismonth']).appendTo($tr);
					$('<td>').addClass('right').text(territory['count-thisyear']).appendTo($tr);
					$('<td>').addClass('right').text(territory['count-lastyear']).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text((territory['change-percentage'] > 0 ? '+' : '') + territory['change-percentage'] + '%')
					).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text(territory['change-number'])
					).appendTo($tr);

				});

				// Populate totals.
				var highlight_class = data.total['change-percentage'].toString().replace(/\,/, '') >= 0.0 ? 'good' : 'bad';
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['count-thismonth']).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['count-thisyear']).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['count-lastyear']).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text((data.total['change-percentage'] > 0 ? '+' : '') + data.total['change-percentage'] + '%')
				).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text(data.total['change-number'])
				).appendTo($total_tr);
			}
		});
	};

	loadClientCountByTerritory();
	setInterval(loadClientCountByTerritory, one_minute);

	/**
	 * POPULATE "CLIENT COUNT BY INDIVIDUAL" TABLE
	 */
	var loadClientCountBySalesman = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/clientcount-by-individual',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'clientcountbyindividual';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate territories.
				$tbody.empty();
				$.each(data.salesmen, function(offset, salesman) {
					var $tr = $('<tr>').appendTo($tbody);
					var highlight_class = salesman['change-percentage'].replace(/\,/, '') >= 0.0 ? 'good' : 'bad';

					$('<td>').text(salesman['salesman']).appendTo($tr);
					$('<td>').addClass('right').text(salesman['count-thismonth']).appendTo($tr);
					$('<td>').addClass('right').text(salesman['count-thisyear']).appendTo($tr);
					$('<td>').addClass('right').text(salesman['count-lastyear']).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text((salesman['change-percentage'] > 0 ? '+' : '') + salesman['change-percentage'] + '%')
					).appendTo($tr);
					$('<td>').addClass('right').addClass(highlight_class).append(
						$('<b>').text(salesman['change-number'])
					).appendTo($tr);
				});

				// Populate totals.
				var highlight_class = data.total['change-percentage'].toString().replace(/\,/, '') >= 0.0 ? 'good' : 'bad';
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['count-thismonth']).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['count-thisyear']).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['count-lastyear']).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text((data.total['change-percentage'] > 0 ? '+' : '') + data.total['change-percentage'] + '%')
				).appendTo($total_tr);
				$('<th>').addClass('right').addClass(highlight_class).append(
					$('<b>').text(data.total['change-number'])
				).appendTo($total_tr);
			}
		});
	};

	loadClientCountBySalesman();
	setInterval(loadClientCountBySalesman, one_minute);

	/**
	 * POPULATE "NEW CLIENTS BY INDIVIDUAL" TABLE
	 */
	var loadNewClientsBySalesman = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/newclients-by-individual',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'newclientsbyindividual';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate territories.
				$tbody.empty();
				$.each(data.salesmen, function(offset, salesman) {
					var $tr = $('<tr>').appendTo($tbody);
					$('<td>').text(salesman['salesman']).appendTo($tr);
					$('<td>').addClass('right').text(salesman['newclients-thismonth']).appendTo($tr);
					$('<td>').addClass('right').text(salesman['newclients-thisyear']).appendTo($tr);
					$('<td>').addClass('right').text('$' + salesman['sales-openorders']).appendTo($tr);
					$('<td>').addClass('right').text('$' + salesman['sales-billedorders']).appendTo($tr);
				});

				// Populate totals.
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['newclients-thismonth']).appendTo($total_tr);
				$('<th>').addClass('right').text(data.total['newclients-thisyear']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-openorders']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['sales-billedorders']).appendTo($total_tr);
			}
		});
	};
	loadNewClientsBySalesman();
	setInterval(loadNewClientsBySalesman, one_minute);

	/**
	 * POPULATE "BACK LOG BY INDIVIDUAL" TABLE
	 */
	var loadBackLogBySalesman = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/backlog-by-individual',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'backlogbyindividual';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate territories.
				$tbody.empty();
				$.each(data.salesmen, function(offset, entry) {
					var $tr = $('<tr>').appendTo($tbody);
					$('<td>').text(entry['salesman']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['before-today']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['today-plus-six']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['this-month']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['next-month']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['total']).appendTo($tr);
				});

				// Populate totals.
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['before-today']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['today-plus-six']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['this-month']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['next-month']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['total']).appendTo($total_tr);
			}
		});
	};
	loadBackLogBySalesman();
	setInterval(loadBackLogBySalesman, one_minute);

	/**
	 * POPULATE "BACK LOG BY TERRITORY" TABLE
	 */
	var loadBackLogByTerritory = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/backlog-by-territory',
			'dataType': 'json',
			'success': function(data) {
				var selector = 'backlogbyterritory';

				var $container = $('#' + selector + '-container');
				var $datetime = $('#' + selector + '-datetime');
				var $table = $('#' + selector + '-table');

				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				var $tbody = $table.find('tbody');
				var $tfoot = $table.find('tfoot');

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate territories.
				$tbody.empty();
				$.each(data.salesmen, function(offset, entry) {
					var $tr = $('<tr>').appendTo($tbody);
					$('<td>').text(entry['territory']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['before-today']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['today-plus-six']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['this-month']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['next-month']).appendTo($tr);
					$('<td>').addClass('right').text('$' + entry['total']).appendTo($tr);
				});

				// Populate totals.
				$tfoot.empty();
				var $total_tr = $('<tr>').appendTo($tfoot);
				$('<th>').append(
					$('<b>').text('TOTAL')
				).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['before-today']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['today-plus-six']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['this-month']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['next-month']).appendTo($total_tr);
				$('<th>').addClass('right').text('$' + data.total['total']).appendTo($total_tr);
			}
		});
	};
	loadBackLogByTerritory();
	setInterval(loadBackLogByTerritory, one_minute);

	/**
	 * Bind to clicks on a salesman's initials under Billing By Individual.
	 */
	$(document).off('click', '#billbyindividual-container .individual .initials');
	$(document).on('click', '#billbyindividual-container .individual .initials', function(event) {
		var salesman = $(this).text();
		var post_data = {
			'salesman': salesman
		};
		var url = BASE_URI + '/dashboard-widgets/salesman-sales-by-customer';
		var $overlay;
		var $layz = activateOverlayZ(
			url,
			post_data,
			undefined,
			function(data) { // Success Callback
				$overlay = $layz.find('.overlayz-body');
				$overlay.empty();

				$overlay.append(
					$('<h2>').text('Salesman: ').append(
						$('<span>').addClass('salesman').text(salesman)
					)
				);

				var $table = $('<table>').append(
					$('<thead>').append(
						$('<tr>').append(
							$('<th>').addClass('sortable').text('Customer Code'),
							$('<th>').addClass('sortable').text('Customer Name'),
							$('<th>').addClass('sortable').text('Open Orders'),
							$('<th>').addClass('sortable').text('Last Sale'),
							$('<th>').addClass('right sortable').text('Month to Date'),
							$('<th>').addClass('right sortable').text('Year To Date'),
							$('<th>').addClass('right sortable').text('Prev. Year To Date'),
							$('<th>').addClass('right sortable').text('% Change'),
							$('<th>').addClass('right sortable').text('$ Change')
						)
					)
				).appendTo($overlay);

				var $table_body = $('<tbody>').appendTo($table);
				$.each(data['customer-sales'], function(index, customer_sales) {
					/**
					 * Percent Change
					 */
					if(customer_sales['percent_change'] > 0) {
						var percent_change_class = 'good';
					} else if(customer_sales['percent_change'] < 0) {
						var percent_change_class = 'bad';
					} else {
						var percent_change_class = 'neutral';
					}

					/**
					 * Dollar Change
					 */
					if(customer_sales['dollar_change'] > 0) {
						var dollar_change_class = 'good';
					} else if(customer_sales['dollar_change'] < 0) {
						var dollar_change_class ='bad';
					} else {
						var dollar_change_class = 'neutral';
					}

					var client_details_json = JSON.stringify({
						'custno': customer_sales.customer_code.trim()
					});
					$('<tr>').append(
						$('<td>').text(customer_sales.customer_code).addClass('overlayz-link').attr('overlayz-url', '/dashboard/clients/details').attr('overlayz-data', client_details_json),
						$('<td>').text(customer_sales.customer_name).addClass('overlayz-link').attr('overlayz-url', '/dashboard/clients/details').attr('overlayz-data', client_details_json),
						$('<td>').text(customer_sales.open_orders),
						$('<td>').text(customer_sales.last_sale_date),
						$('<td>').addClass('right').text('$' + customer_sales['sales-thismonth-formatted']),
						$('<td>').addClass('right').text('$' + customer_sales['sales-thisyear-formatted']),
						$('<td>').addClass('right').text('$' + customer_sales['sales-lastyear-formatted']),
						$('<td>').addClass('right').addClass('bold').addClass(percent_change_class).text(customer_sales['percent-change-formatted'] + '%'),
						$('<td>').addClass('right').addClass('bold').addClass(dollar_change_class).text('$' + customer_sales['dollar-change-formatted'])
					).appendTo($table_body);
				});

				var options = {
					'selectorHeaders': [],
					'widgets': [],
					'widgetOptions': {}
				};
				options.selectorHeaders.push('> thead > tr > th.sortable');
				options.selectorHeaders.push('> thead > tr > td.sortable');
				options.onRenderHeader = function() {
					// Fixed a bug which causes some text to wrap when it shouldn't need
					// to.
					$(this).find('div').css('width', '100%');
				};
				// Convert array to comma-separated string.
				options.selectorHeaders = options.selectorHeaders.join(',');
				$table.tablesorter(options);
			}
		);
	});

	/**
	 * POPULATE "PAST DUE", "TODAY" and "AT RISK" TILES
	 */
	var loadSalesOrderCounts = function() {
		$.ajax({
			'url': BASE_URI + '/dashboard-widgets/sales-order-counts',
			'dataType': 'json',
			'success': function(data) {
				$.each(data['counts'], function(block_name, count) {
					var $so_block_container = $('.so-blocks .so-block-container.so-block-' + block_name);
					var $count = $so_block_container.find('.so-block-count');
					$count.text(count);

					if(count == 0) {
						$count.addClass('so-block-count-okay');
					} else {
						$count.removeClass('so-block-count-okay');
					}
				});
			}
		});
	};
	loadSalesOrderCounts();
	setInterval(loadSalesOrderCounts, one_minute);

	/**
	 * Bind to clicks on "At Risk" block.
	 */
	$(document).on('click', '.so-blocks .so-block-container.so-block-atrisk .so-block, .so-blocks .so-block-container.so-block-mtdclients .so-block', function() {
		var $so_block = $(this);
		var $so_block_container = $so_block.closest('.so-block-container');
		var $so_block_title = $so_block_container.find('.so-block-title');
		var block = $so_block_title.text();
		var post_data = {
			'block': block
		};

		var url = BASE_URI + '/dashboard-widgets/sales-order-details';

		var $overlay;
		var $layz = activateOverlayZ(
			url,
			post_data,
			undefined,
			function(data) { // Success Callback
				$overlay = $layz.find('.overlayz-body');
				$overlay.empty();

				$overlay.append(
					$('<h2>').text(
						(block == 'MTD Clients $250+' || block == 'MTD Clients') ? 'Clients' : block
					)
				);

				if(block == 'Past Due' || block == 'Today' || block == 'At Risk') {
					var $table = $('<table>').append(
						$('<thead>').append(
							$('<tr>').append(
								$('<th>').addClass('sortable').text('Order Status'),
								$('<th>').addClass('sortable').text('SO #'),
								$('<th>').addClass('sortable').text('Territory'),
								$('<th>').addClass('sortable').text('Location'),
								$('<th>').addClass('sortable').text('Customer'),
								$('<th>').addClass('sortable').text('Customer PO'),
								$('<th>').addClass('sortable').text('Who Entered'),
								$('<th>').addClass('sortable').text('Add Date'),
								$('<th>').addClass('sortable').text('Due Date')
							)
						)
					).appendTo($overlay);
				} else if(block == 'MTD Clients' || block == 'MTD Clients $250+') {
					var $table = $('<table>').append(
						$('<thead>').append(
							$('<tr>').append(
								$('<th>').addClass('sortable').text('Client'),
								$('<th>').addClass('right').addClass('sortable').text('Month to Date'),
								$('<th>').addClass('right').addClass('sortable').text('Year to Date'),
								$('<th>').addClass('right').addClass('sortable').text('Prev. Year to Date'),
								$('<th>').addClass('right').addClass('sortable').text('% Change'),
								$('<th>').addClass('right').addClass('sortable').text('$ Change')
							)
						)
					).appendTo($overlay);
				}

				var $table_body = $('<tbody>').appendTo($table);
				if(block == 'Past Due' || block == 'Today' || block == 'At Risk') {
					$.each(data['sales-orders'], function(index, sales_order) {
						$('<tr>').append(
							$('<td>').text(sales_order.status),
							$('<td>').text(sales_order.sales_order_number),
							$('<td>').text(sales_order.territory),
							$('<td>').text(sales_order.location),
							$('<td>').text(sales_order.customer_code),
							$('<td>').text(sales_order.customer_po),
							$('<td>').text(sales_order.who_entered),
							$('<td>').text(sales_order.input_date),
							$('<td>').text(sales_order.due_date)
						).appendTo($table_body);
					});
				} else if(block == 'MTD Clients' || block == 'MTD Clients $250+') {
					$.each(data['sales-orders'], function(index, sales_order) {
						var percent_symbol = sales_order.percent_change.replace(/,/g, '') >= 0 ? '+' : '';

						var change_class = sales_order.percent_change.replace(/,/g, '') >= 0 ? 'good' : 'bad';
						var $percent_change = $('<td>').addClass(change_class).text(percent_symbol + sales_order.percent_change + '%');
						var $number_change = $('<td>').addClass(change_class).text('$' + sales_order.number_change);

						$('<tr>').append(
							$('<td>').text(sales_order.client),
							$('<td>').addClass('right').text('$' + sales_order.sales_thismonth),
							$('<td>').addClass('right').text('$' + sales_order.sales_thisyear),
							$('<td>').addClass('right').text('$' + sales_order.sales_lastyear),
							$percent_change.addClass('right').addClass('bold'),
							$number_change.addClass('right').addClass('bold')
						).appendTo($table_body);
					});
				}

				var options = {
					'selectorHeaders': [],
					'widgets': [],
					'widgetOptions': {}
				};
				options.selectorHeaders.push('> thead > tr > th.sortable');
				options.selectorHeaders.push('> thead > tr > td.sortable');
				options.onRenderHeader = function() {
					// Fixed a bug which causes some text to wrap when it shouldn't need
					// to.
					$(this).find('div').css('width', '100%');
				};
				// Convert array to comma-separated string.
				options.selectorHeaders = options.selectorHeaders.join(',');
				$table.tablesorter(options);
			}
		);
	});

	function display_billing_by_territory_territory_overlay(){

		// Forgive the stupidly long name of this function, but I need something
		// unique and I don't have time to go review 1k+ lines of JS right now.
		//
		// This function will display the "Territory" overlay when clicking
		// the territory under "Billing By Territory".

		// Get the territory.
		var $td = $(this)
		var $tr = $td.parents('tr')
		var ter = $tr.attr('territory')

		// TODO: Start here
		console.log(ter)

		// Overlay items.
		var data = {'territory' : ter}
		var uri = '/dashboard-widgets/territory-billing-by-territory'

		var $layz = activateOverlayZ(uri+BASE_URI, data, undefined,
			function(data){ // Success callback.

				// Empty the overlay.
				var $overlay = $layz.find('.overlayz-body')
				$overlay.empty();

				// Create the header.
				var $header = $('<h2>',{
					'class' : 'foo',
					'text' : 'Territory: '+ter
				})

				// Create the table for the overlay.
				var $table = $('<table>',{
					'class' : 'table table-striped table-hover table-small'
				}).append(
					$('<thead>').append(
						$('<tr>').append(
							$('<th>',{'text':'Customer Code'}),
							$('<th>',{'text':'Customer Name'}),
							$('<th>',{'text':'Open Orders'}),
							$('<th>',{'text':'Last Sale'}),
							$('<th>',{'class':'right', 'text':'Month to Date'}),
							$('<th>',{'class':'right', 'text':'Year to Date'}),
							$('<th>',{'class':'right', 'text':'Prev. Year to Date'}),
							$('<th>',{'class':'right', 'text':'% Change'}),
							$('<th>',{'class':'right', 'text':'$ Change'})
						)
					)
				)

				// Add each row to the table.
				var $tbody = $('<tbody>')
				$.each(data['territory-sales'], function(idx, sales){

					// Get the proper class for percent change.
					var pc_class = 'neutral'
					var pc = sales['percent_change']
					if(pc>0){pc_class = 'good'}
					else if(pc<0){pc_class = 'bad'}

					// Get the proper class for dollar change.
					dc_class = 'neutral'
					var dc = sales['dollar_change']
					if(dc>0){dc_class = 'good'}
					else if(dc<0){dc_class='bad'}

					// Create the table row.
					var $tr = $('<tr>').append(
						$('<td>',{'class' : 'overlayz-link', 'text': sales.customer_code}),
						$('<td>',{'class' : 'overlayz-link', 'text': sales.customer_name}),
						$('<td>',{'text': sales.open_orders}),
						$('<td>',{'text': sales.last_sale_date}),
						$('<td>',{'class' : 'right', 'text': sales['sales-thismonth-formatted']}),
						$('<td>',{'class' : 'right', 'text': sales['sales-thisyear-formatted']}),
						$('<td>',{'class' : 'right', 'text': sales['sales-lastyear-formatted']}),
						$('<td>',{'class' : 'right bold '+pc_class, 'text': sales['percent-change-formatted']+'%'}),
						$('<td>',{'class' : 'right bold '+dc_class, 'text': '$'+sales['dollar-change-formatted']}),
					).appendTo($tbody)

				})
				$table.append($tbody)

				console.log($table)

				// Add the items to the overlay.
				$overlay.append($header)
				$overlay.append($table)

		})

	}

	// Support territory overlays.
	$(document).off('click', '.territory-name')
	$(document).on('click', '.territory-name', display_billing_by_territory_territory_overlay)

});
