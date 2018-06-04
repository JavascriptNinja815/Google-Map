<!DOCTYPE html>
<html>
	<head>
		<title>Shipping Dashboard</title>
		<script type="text/javascript" src="/interface/js/jquery-1.11.0.min.js"></script>
		<script type="text/javascript">
			var server_time;
			var	one_minute = 1000 * 60;
			
			/**
			 * Reloads page every 5 minutes
			 */
			setInterval(
				function() {
					window.location.reload();
				},
				one_minute * 5
			);
			load_orders = function() {
				$.ajax({
					'url': '<?php print BASE_URI;?>/dashboard/shipping/orders',
					'type': 'POST',
					'data': {},
					'dataType': 'json',
					'success': function(data) {
						/**
						 * ORDERS: HOT TODAY
						 */
						var $hot_today_container = $('.container.container-hot-today');
						var $hot_today_count = $hot_today_container.find('.count');
						var $hot_today_orders = $hot_today_container.find('.orders');
						$hot_today_orders.empty();
						$hot_today_count.text(data['hot-today'].length);
						if(data['hot-today'].length) { // One or more orders encountered.
							$hot_today_count.removeClass('count-zero');
							$.each(data['hot-today'], function(index, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($hot_today_orders);
							});
						} else { // Zero orders
							$hot_today_count.addClass('count-zero');
						}

						/**
						 * ORDERS: HOT TOMORROW
						 */
						var $hot_tomorrow_container = $('.container.container-hot-tomorrow');
						var $hot_tomorrow_count = $hot_tomorrow_container.find('.count');
						var $hot_tomorrow_orders = $hot_tomorrow_container.find('.orders');
						$hot_tomorrow_orders.empty();
						$hot_tomorrow_count.text(data['hot-tomorrow'].length);
						if(data['hot-tomorrow'].length) { // One or more orders encountered.
							$hot_tomorrow_count.removeClass('count-zero');
							$.each(data['hot-tomorrow'], function(index, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($hot_tomorrow_orders);
							});
						} else { // Zero orders
							$hot_tomorrow_count.addClass('count-zero');
						}

						/**
						 * ORDERS: TODAY
						 */
						var $today_container = $('.container.container-today');
						var $today_count = $today_container.find('.count');
						var $today_orders = $today_container.find('.orders');
						$today_orders.empty();
						$today_count.text(data['today'].length);
						if(data['today'].length) { // One or more orders encountered.
							$today_count.removeClass('count-zero');
							$.each(data['today'], function(index, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($today_orders);
							});
						} else { // Zero orders
							$today_count.addClass('count-zero');
						}

						/**
						 * ORDERS: BACKLOG
						 */
						var $backlog_container = $('.container.container-backlog');
						var $backlog_count = $backlog_container.find('.count');
						var $backlog_orders = $backlog_container.find('.orders');
						$backlog_orders.empty();
						$backlog_count.text(data['backlog'].length);
						if(data['backlog'].length) { // One or more orders encountered.
							$backlog_count.removeClass('count-zero');
							$.each(data['backlog'], function(index, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($backlog_orders);
							});
						} else { // Zero orders
							$backlog_count.addClass('count-zero');
						}

						/**
						 * ORDERS: ISS - In Stock, Ship
						 */
						var $iss_container = $('.container.container-iss');
						var $iss_count = $iss_container.find('.count');
						var $iss_orders = $iss_container.find('.orders');
						$iss_orders.empty();
						$iss_count.text(data['iss'].length);
						if(data['iss'].length) { // One or more orders encountered.
							$iss_count.removeClass('count-zero');
							$.each(data['iss'], function(index, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($iss_orders);
							});
						} else { // Zero orders
							$iss_count.addClass('count-zero');
						}

						var miltime = server_time.milhour + '' + server_time.minute;
						var $carrier_container = $('.container.container-carrier')
						var $carrier_container_title = $carrier_container.find('.title');
						var $carrier_container_orders = $carrier_container.find('.orders');
						
						if(data.usps.length && miltime >= 700 && miltime <= 1100) {
							/**
							 * SHIPPING: USPS - 7am to 11am
							 */
							$carrier_container.fadeIn();
							$carrier_container_title.text('USPS');
							$carrier_container_orders.empty();
							$.each(data.usps, function(offset, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($carrier_container_orders);
							});
						} else if(data.fedex.length && miltime >= 1000 && miltime <= 1530) {
							/**
							 * SHIPPING: FEDEX - 10am to 3:30pm
							 */
							$carrier_container.fadeIn();
							$carrier_container_title.text('FedEx');
							$carrier_container_orders.empty();
							$.each(data.fedex, function(offset, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($carrier_container_orders);
							});
						} else if(data.ups.length && miltime >= 1300 && miltime <= 1700) {
							/**
							 * SHIPPING: UPS - 1pm to 5pm
							 */
							$carrier_container.fadeIn();
							$carrier_container_title.text('UPS');
							$carrier_container_orders.empty();
							$.each(data.ups, function(offset, order) {
								$('<div>').addClass('order').append(
									$('<div>').addClass('order-number').text(order['order-number']),
									$('<div>').addClass('customer').text('- ' + order['customer']),
									$('<div>').addClass('order-status').html('&nbsp;(' + order['order-status'] + ')')
								).appendTo($carrier_container_orders);
							});
						} else {
							$carrier_container.fadeOut();
							$carrier_container_orders.empty();
						}
					}
				});
			};
			setInterval(load_orders, one_minute); // Load orders every 60 seconds.
			load_orders();
			
			/**
			 * Reloads time every 20 seconds.
			 */
			get_time = function() {
				$.ajax({
					'url': '<?php print BASE_URI;?>/api/time',
					'type': 'POST',
					'data': {},
					'dataType': 'json',
					'success': function(data) {
						server_time = data;
						var $clock = $('.clock');
						var time = server_time.time.replace(/\:/g, '<span class="colon">:</span>');
						$clock.html(time);
						$clock.attr('time', server_time.time).attr('hour', server_time.hour).attr('minute', server_time.minute).attr('ampm', server_time.ampm).attr('milhour', server_time.milhour);
					}
				});
			};
			setInterval(get_time, 20 * 1000); // Get time every 20 seconds.
			get_time();

			/**
			* Make colon blink every second
			 */
			setInterval(function() {
				var $clock = $('.container.container-bar .clock');
				var $colon = $clock.find('.colon');

				// Set the color of the colon, to force a "blink".
				var color = $colon.css('color');
				if(color == 'rgb(255, 255, 255)') {
					color = 'rgb(0, 0, 0)';
				} else {
					color = 'rgb(255, 255, 255)';
				}
				$colon.css('color', color);
			}, 1 * 1000);
		</script>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Titillium+Web:400,700" />
		<style type="text/css">
			body {
				font-family:"Titillium Web";
				margin:0;
				overflow:hidden;
				cursor:none;
			}
			.container {
				position:absolute;
				height:40%;
			}
			.container.container-top {
				width:50%;
			}
			.container.container-bottom {
				width:33%;
			}
			.container.container-hot-today {
				top:0;
				left:0;
				border-bottom:1px solid #aaa;
				border-right:1px solid #aaa;
			}
			.container.container-today {
				top:0;
				right:0;
				border-bottom:1px solid #aaa;
			}
			.container.container-carrier {
				top:40%;
				left:0;
				width:100%;
				background-color:#fed105;
				position:absolute;
				z-index:100;
				display:none;
			}
			.container.container-iss {
				top:40%;
				left:0;
			}
			.container.container-hot-tomorrow {
				top:40%;
				left:33%;
				border-left:1px solid #aaa;
			}
			.container.container-backlog {
				top:40%;
				right:0;
				border-left:1px solid #aaa;
			}

			.container .count {
				/*font-size:400px;*/
				font-size:320px;
				line-height:320px;
				/*font-size:38vh;*/
				/*line-height:400px;*/
				/*line-height:44vh;*/
				/*top:-4vh;*/
				font-weight:bold;
				text-shadow:0px 0px 5px rgba(0, 0, 0, 1);
				text-align:center;
				position:relative;
			}
			.container.container-hot-today .count {
				color:#f00;
			}
			.container.container-hot-tomorrow .count {
				color:#ffec00;
			}
			.container.container-today .count {
				color:#000;
			}
			.container.container-backlog .count {
				color:#666;
			}
			.container .count.count-zero {
				color:#eee;
			}
			.container.container-hot-today .count,
			.container.container-today .count {
				/*font-size:320px;*/
				/*font-size:19vh;*/
				/*line-height:320px;*/
				/*line-height:22vh;*/
				top:-40px;
			}

			.container .title {
				margin-top:16px;
				/*margin-top:2vh;*/
				font-size:70px;
				/*font-size:8vh;*/
				line-height:70px;
				/*line-height:8vh;*/
				font-weight:bold;
				text-align:center;
			}
			.container .orders {
				/*height:25vh;*/
				/*width:50vw;*/
				height:23%;
				width:100%;
				//overflow:auto;
				position:absolute;
				bottom:0;
				left:0;
				padding-bottom: 100px;
			}
			.container .orders .order {
				/*width:14vw;*/
				/*font-size:1.5vw;*/
				/*margin-left:2vw;*/
				width:48%;
				margin-left:8px;
				font-size:30px;
				overflow:hidden;
				float:left;
			}
			.container .orders .order .order-number {
				float:left;
			}
			.container .orders .order .customer {
				float:left;
				padding-left:20px;
			}
			.container.container-carrier .title {
				float:left;
				width:15%;
				height:100%;
				line-height:400px;
				vertical-align:middle;
				margin-top:0;
				text-align:center;
			}
			.container.container-carrier .orders {
				float:right;
				position:initial;
				bottom:initial;
				left:initial;
				width:80%;
				height:100%;
				line-height:400px;
			}
			.container.container-carrier .orders .order {
				line-height:40px;
				width:30%;
			}
			
			/*
			@keyframes blink {
				to {
					visibility:hidden;
				}
			}
			@-webkit-keyframes blink {
				to {
					visibility:hidden;
				}
			}
			.remaining-order-count.alert {
				color:#f10;
				-webkit-animation:blink 1s steps(5, start) infinite;
				animation:blink 1s steps(5, start) infinite;
			}
			*/
			.container.container-bar {
				position:absolute;
				height:20%;
				bottom:0px;
				left:0px;
				width:100%;
				border-top:1px solid #aaa;
				text-align:center;
			}
			.container.container-bar .clock {
				font-size:140px;
			}
			.container.container-bar .logo {
				position:absolute;
				right:0px;
				bottom:0px;
				background-image:url("<?php print BASE_URI;?>/interface/images/casterdepot-cd-logo.png");
				background-size:contain;
				width:210px;
				height:210px;
				background-position:center;
				background-repeat:no-repeat;
				margin-right:40px;
			}
		</style>
	</head>
	<body>
		<div class="container container-top container-hot-today">
			<div class="title"><img src="<?php print BASE_URI;?>/interface/images/jalapeno.png" style="max-height:80px;" /> Today</div>
			<div class="count count-zero">0</div>
			<div class="orders"></div>
		</div>
		<div class="container container-top container-today">
			<div class="title">Today</div>
			<div class="count count-zero">0</div>
			<!--div class="orders"></div-->
		</div>
		<div class="container container-bottom container-carrier">
			<div class="title">USPS</div>
			<div class="orders">
				<div class="order">Order #1</div>
				<div class="order">Order #1</div>
				<div class="order">Order #1</div>
				<div class="order">Order #1</div>
				<div class="order">Order #1</div>
			</div>
		</div>
		<div class="container container-bottom container-iss">
			<div class="title">ISS</div>
			<div class="count count-zero">0</div>
			<!--div class="orders"></div-->
		</div>
		<div class="container container-bottom container-hot-tomorrow">
			<div class="title"><img src="<?php print BASE_URI;?>/interface/images/jalapeno.png" style="max-height:100px;" /> Tomorrow</div>
			<div class="count count-zero">0</div>
			<div class="orders"></div>
		</div>
		<div class="container container-bottom container-backlog">
			<div class="title">Backlog</div>
			<div class="count count-zero">0</div>
			<!--div class="orders"></div-->
		</div>
		<div class="container container-bar">
			<div class="logo"></div>
			<div class="clock">
				0<span class="colon">:</span>00 PM
			</div>
		</div>
	</body>
</html>