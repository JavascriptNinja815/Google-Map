<!DOCTYPE html>
<?php

global $session;

function get_off_today(){

	// Query for employees that have today off.

	$db = DB::get();
	$q = $db->query("
		DECLARE @today date;
		SET @today = GETDATE();

		SELECT DISTINCT
			l.first_name,
			l.last_name,
			CONVERT(
				varchar(15),
				CAST(t.from_datetime AS time),
				100
			) AS from_time,
			CONVERT(
				varchar(15),
				CAST(t.to_datetime AS time),
				100
			) AS to_time
		FROM ".DB_SCHEMA_ERP.".timesheets t
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = t.login_id
		WHERE CAST(t.from_datetime AS date) = @today
			AND t.status = 1
			AND l.status = 1
		ORDER BY l.last_name, l.first_name DESC
	");

	return $q->fetchAll();

}

function get_is_dorodo(){

	// Check to see if the display is for DoRodo.

	global $session;

	if(isset($_GET['dorodo'])){
		if($_GET['dorodo']=='true'){
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}

}

// Check if the page is for DoRodo.
$is_dorodo = get_is_dorodo();

// The names of people who have today off.
$off_today = get_off_today();

// print(COMPANY);

?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Titillium+Web:400,700" />
		<style type="text/css">
			html, body {
				font-family:"Titillium Web";
				margin:0;
				padding:0;
				width:100%;
				height:100%;
				overflow:hidden;
				cursor:none;
			}

			#top,
			#middle,
			#bottom {
				height:33.33%;
				position:fixed;
				left:0;
				right:0
			}
			#top {
				top:0;
				bottom:66.66%;
			}
			#middle {
				top:33.33%;
				bottom:33.33%;
			}
			#bottom {
				top:66.66%;
				bottom:0;
			}

			.left,
			.center,
			.right {
				position:absolute;
				top:0;
				bottom:0;
			}
			.left {
				left:0;
				right:66.66%;
			}
			.center {
				left:33.33%;
				right:33.33%
			}
			.right {
				left:66.66%;
				right:0;
			}

			.title {
				font-size:70px;
				text-align:center;
				font-weight:bold;
				line-height:70px;
			}
			.left.share .title {
				line-height:120px;
				text-align:left;
				position:absolute;
				top:0;
				right:0;
				left:35%;
			}
			.count {
				font-size:250px;
				line-height:230px;
				font-weight:bold;
				text-shadow:0px 0px 5px rgba(0, 0, 0, 1);
				text-align:center;
				position:absolute;
				left:0;
				top:0;
				right:50%;
			}
			.left.share .count {
				font-size:160px;
				line-height:120px;
				position:absolute;
				left:0;
				top:0;
				right:65%;
			}
			.left.share .entries {
				margin-top:135px;
			}
			.left.share .entries .entry {
				width:48%;
				display:inline-block;
				margin-left:2%;
				font-size:20px;
			}
			.left.share .entries .entry > * {
				width:33.33%;
				display:inline-block;
				border-bottom:1px solid #ccc;
			}



			.right.share .count {
				font-size:160px;
				line-height:120px;
				position:absolute;
				left:0;
				top:0;
				right:65%;
			}
			.right.share .entries {
				//margin-top:65px;
				//margin-left:50px;
				margin-top:15px;
				margin-left:170px;
			}
			.right.share .entries .entry {
				display:inline-block;
				margin-left:2%;
				font-size:20px;
			}
			.right.share .entries .entry > * {
				width:33.33%;
				display:inline-block;
				border-bottom:1px solid #ccc;
			}



			#bottom .center-cd {
				margin:1%;
				background-image:url("/interface/images/casterdepot-cd-logo.png");
				background-repeat:no-repeat;
				background-size:contain;
				background-position-x:center;
				background-position-y:middle;
			}
			#bottom .center-dr {
				margin:1%;
				background-image:url("/interface/images/dorodo-logo-large.png");
				background-repeat:no-repeat;
				background-size:contain;
				background-position-x:center;
				background-position-y:middle;
			}
			
			table {
				border-collapse:collapse;
				width:100%;
			}
			th {
				font-weight:bold;
				border-bottom:3px solid #000;
				text-align:left;
			}
			td {
				border-bottom:1px solid #ccc;
				margin:6px;
			}

			#quote-container {
				padding:36px;
			}
			#quote-container .quote::before {
				content:'"';
				font-size:28px;
			}
			#quote-container .quote::after {
				content:'"';
				font-size:28px;
			}
			#quote-container .quote {
				font-size:24px;
			}
			#quote-container .author::before {
				content:"-";
				font-size:48px;
			}
			#quote-container .author {
				text-align:right;
				font-size:32px;
				padding-left:64px;
			}

			#chat-container {
				margin-left: 20px;
				margin-right: 20px;
			}

		</style>
		<script src="/interface/js/jquery-1.11.0.min.js"></script>
	</head>
	<body>
		<div id="top">
			<div class="left share" id="hot-container">
				<div class="title"><img src="<?php print BASE_URI;?>/interface/images/jalapeno.png" style="max-height:130px;" /></div>
				<div class="count"></div>
				<div class="entries"></div>
			</div>
			<div class="center">
				<div class="title">Print</div>
				<table>
					<thead>
						<tr>
							<th>SO #</th>
							<th>Location</th>
							<th>Who Entered</th>
							<th>Add Date/Time</th>
						</tr>
					</thead>
					<tbody id="print-container"></tbody>
				</table>
			</div>
			<div id="chat-container" class="right share">
				<div class="title">Active Chat</div>
			</div>
		</div>
		<div id="middle">
			<div class="left share" id="vic-container">
				<div class="title">VIC</div>
				<div class="count"></div>
				<div class="entries"></div>
			</div>
			<div class="center">
				<div class="title">Late</div>
				<table>
					<thead>
						<tr>
							<th>Location</th>
							<th>Order Count</th>
						</tr>
					</thead>
					<tbody id="late-container"></tbody>
				</table>
			</div>
			<div class="right share">
				<div class="title">Off Today</div>
				<div class="entries">
					<?php foreach($off_today as $off){
						?>
						<div class="entry">
							<?php print htmlentities($off['first_name'].' '.$off['last_name']).' '.$off['from_time'].' '.$off['to_time'] ?>
						</div>
						<?php
					} ?>
				</div>
			</div>
		</div>
		<div id="bottom">
			<div class="left share" id="today-container">
				<div class="title">Today</div>
				<div class="count"></div>
				<div class="entries"></div>
			</div>
			<?php
			// Get the class for CD or Dorodo.
			if($is_dorodo==true){
				$class = 'center-dr';
			}else{
				$class = 'center-cd';
			}
			?>
			<div class="center <?php print htmlentities($class) ?>"></div>
			<div class="right" id="quote-container">
				<div class="quote"></div>
				<div class="author"></div>
			</div>
		</div>
		
		<script type="text/javascript">
			var	one_minute = 1000 * 60;
			var	one_hour = one_minute * 60;

			$(function() {
				var $hot_container = $('#hot-container');
				var $print_container = $('#print-container');
				var $so_container = $('#so-container');
				var $vic_container = $('#vic-container');
				var $billing_container = $('#billing-container');
				var $today_container = $('#today-container');
				var $late_container = $('#late-container');
				var $quote_container = $('#quote-container');

				// Stores AJAX requests (key = block) while the request is
				// working. Upon completion, key is deleted.
				var ajaxRequests = {};

				var loaders = {
					'hot': function() {
						if('hot' in ajaxRequests && ajaxRequests.hot) {
							ajaxRequests.hot.abort(); // Abort existing
							delete ajaxRequests.hot;
						}
						ajaxRequests.hot = $.ajax({
							'url': '/dashboard/sales-orders/hot/retrieve',
							'type': 'GET',
							'data': {
								'type': 'today'
							},
							'dataType': 'json',
							'success': function(response) {
								delete ajaxRequests.hot; // Delete pending ajax request now that it has finished.
								var $hot_count = $hot_container.find('.count');
								var $hot_entries = $hot_container.find('.entries');

								// Set count.
								$hot_count.text(response.hot.length);
								if(!response.hot.length) {
									$hot_count.addClass('count-zero');
								} else {
									$hot_count.removeClass('count-zero');
								}

								// Populate table.
								$hot_entries.empty();
								var ct = 0;
								$.each(response.hot, function(offset, hot) {
									ct++;
									if(ct > 10) {
										// Display a max of 10.
										return false;
									}
									var hot_text = hot.sales_order_number + ' - ' + hot.customer_code;
									if(hot.status) {
										hot_text += ' ' + ' (' + hot.status + ')';
									}
									$hot_entries.append(
										$('<div class="entry">').text(hot_text)
									);
								});
							}
						});
					},
					'print': function() {
						if('print' in ajaxRequests && ajaxRequests.print) {
							ajaxRequests.print.abort(); // Abort existing
							delete ajaxRequests.print;
						}
						ajaxRequests.print = $.ajax({
							'url': '/dashboard/sales-orders/not-printed/retrieve',
							'type': 'GET',
							'dataType': 'json',
							'success': function(response) {
								delete ajaxRequests.print; // Delete pending ajax request now that it has finished.
								$print_container.empty();
								if(response['not-printed']) {
									var ct = 0;
									$.each(response['not-printed'], function(offset, print) {
										ct++;
										if(ct > 8) {
											// Only display the first 8.
											return false;
										}
										$print_container.append($('<tr>').append(
											$('<td>').text(print.sales_order_number),
											$('<td>').text(print.location),
											$('<td>').text(print.input_date),
											$('<td>').text(print.due_date)
										));
									});
								}
							}
						});
					},
					'so': function() {
						if('so' in ajaxRequests && ajaxRequests.so) {
							ajaxRequests.so.abort(); // Abort existing
							delete ajaxRequests.so;
						}
						ajaxRequests.so = $.ajax({
							'url': '/dashboard/displays/corporate/so',
							'type': 'GET',
							'dataType': 'json',
							'success': function(response) {
								if(response.success) {
									delete ajaxRequests.so; // Delete pending ajax request now that it has finished.
									// DO JOB.
								}
							}
						});
					},
					'vic': function() {
						if('vic' in ajaxRequests && ajaxRequests.vic) {
							ajaxRequests.vic.abort(); // Abort existing
							delete ajaxRequests.vic;
						}
						ajaxRequests.vic = $.ajax({
							'url': '/dashboard/displays/corporate/vic',
							'type': 'GET',
							'dataType': 'json',
							'success': function(response) {
								delete ajaxRequests.vic; // Delete pending ajax request now that it has finished.

								var $vic_count = $vic_container.find('.count');
								var $vic_entries = $vic_container.find('.entries');

								// Set count.
								$vic_count.text(response.vic.length);
								if(!response.vic.length) {
									$vic_count.addClass('count-zero');
								} else {
									$vic_count.removeClass('count-zero');
								}

								// Populate table.
								$vic_entries.empty();
								var ct = 0;
								$.each(response.vic, function(offset, vic) {
									ct++;
									if(ct > 10) {
										// Display a max of 10.
										return false;
									}
									console.log('VIC:', vic);
									var vic_text = vic.sales_order_number + ' - ' + vic.customer_code;
									if(vic.sales_order_status) {
										vic_text += ' ' + ' (' + vic.sales_order_status + ')';
									}
									$vic_entries.append(
										$('<div class="entry">').text(vic_text)
									);
								});
							}
						});
					},
					'billing': function() {
						if('billing' in ajaxRequests && ajaxRequests.billing) {
							ajaxRequests.billing.abort(); // Abort existing
							delete ajaxRequests.billing;
						}
						ajaxRequests.billing = $.ajax({
							'url': '/dashboard/displays/corporate/billing',
							'type': 'GET',
							'dataType': 'json',
							'success': function(response) {
								if(response.success) {
									delete ajaxRequests.billing; // Delete pending ajax request now that it has finished.
									// DO JOB.
								}
							}
						});
					},
					'today': function() {
						if('today' in ajaxRequests && ajaxRequests.today) {
							ajaxRequests.today.abort(); // Abort existing
							delete ajaxRequests.today;
						}
						ajaxRequests.today = $.ajax({
							'url': '/dashboard/displays/corporate/today',
							'type': 'GET',
							'dataType': 'json',
							'success': function(response) {
								delete ajaxRequests.today; // Delete pending ajax request now that it has finished.

								var $today_count = $today_container.find('.count');
								var $today_entries = $today_container.find('.entries');

								// Set count.
								$today_count.text(response.today.length);
								if(!response.today.length) {
									$today_count.addClass('count-zero');
								} else {
									$today_count.removeClass('count-zero');
								}

								// Populate table.
								$today_entries.empty();
								var ct = 0;
								$.each(response.today, function(offset, today) {
									ct++;
									if(ct > 10) {
										// Display a max of 10.
										return false;
									}
									var today_text = today.sales_order_number + ' - ' + today.customer_code;
									//console.log('Today:', today);
									if(today.sales_order_status) {
										today_text += ' ' + ' (' + today.sales_order_status + ')';
									}
									$today_entries.append(
										$('<div class="entry">').text(today_text)
									);
								});
							}
						});
					},
					'late': function() {
						if('late' in ajaxRequests && ajaxRequests.late) {
							ajaxRequests.late.abort(); // Abort existing
							delete ajaxRequests.late;
						}
						ajaxRequests.late = $.ajax({
							'url': '/dashboard/displays/corporate/late',
							'type': 'GET',
							'dataType': 'json',
							'success': function(response) {
								if(response.success) {
									delete ajaxRequests.quote; // Delete pending ajax request now that it has finished.
									$late_container.empty();
									if(response.data.late) {
										var ct = 0;
										$.each(response.data.late, function(offset, late) {
											ct++;
											if(ct > 8) {
												// Only display the first 8.
												return false;
											}
											$late_container.append($('<tr>').append(
												$('<td>').text(late.location),
												$('<td>').text(late.count)
											))
										});
									}
								}
							}
						});
					}
				};

				function quoteLoader() {
					var $quote = $quote_container.find('.quote');
					var $author = $quote_container.find('.author');

					ajaxRequests.quote = $.ajax({
						'url': '/dashboard/displays/corporate/quote',
						'type': 'GET',
						'dataType': 'json',
						'success': function(response) {
							if(response.success) {
								delete ajaxRequests.quote; // Delete pending ajax request now that it has finished.
								$quote.text(response.quote);
								$author.text(response.author);
							}
						}
					});
				}

				function dataLoader() {
					// Iterate over our list of loaders, and call each.
					$.each(loaders, function(loader_name, loader) {
						loader(); // Call the loader's function.
					});
				}

				// Query fisplay data for first time on page load.
				dataLoader();
				quoteLoader();

				// Re-query/update data displayed every minute.
				setInterval(dataLoader, one_minute);
				
				setInterval(quoteLoader, one_hour);
			});

			function get_chat_details(){

				// Get the active chat count by agent.

				// The container for chat data.
				var $container = $('#chat-container')

				// Temporarily prevent calls for DoRodo
				var is_dorodo = '<?php print $is_dorodo ?>'
				if(is_dorodo == '1'){
					console.log("Do not load chat details for DoRodo")
					return
				}

				$.ajax({
					'url' : 'http://10.1.247.195/livechat/get-active-counts',
					'method' : 'GET',
					'dataType' : 'JSONP',
					'success' : function(rsp){
						console.log('success')

						console.log(rsp)

						// Create a table with the chat info.
						var $table = $('<table>',{
							'id' : 'active-chat-table'
						})

						// Create the table header.
						var $head = $('<thead>')
						var $ath = $('<th>',{'text':'Agent'})
						var $cth = $('<th>',{'text':'Active Chats'})
						$head.append($ath)
						$head.append($cth)
						$table.append($head)

						$.each(rsp, function(name, count){

							console.log(name)
							console.log(count)

							// Create a table row for each entry.
							var $tr = $('<tr>')
							var $ntd = $('<td>', {'text':name})
							var $ctd = $('<td>', {'text':count})
							$tr.append($ntd)
							$tr.append($ctd)

							// Add the row to the table.
							$table.append($tr)

						})

						// Empty the container and add the new table.
						$container.find('#active-chat-table').remove()
						$container.append($table)

					},
					'error' : function(rsp){
						console.log('error')
						console.log(rsp)
					}
				})

			}

			// Get initial chat values.
			get_chat_details()

			// Reload chat values every minute.
			setInterval(function(){
				get_chat_details()
			},one_minute)

			// Force re-load of page every hour.
			setInterval(
				function() {
					window.location.reload();
				},
				one_minute * 60
			);
		</script>
	</body>
</html>