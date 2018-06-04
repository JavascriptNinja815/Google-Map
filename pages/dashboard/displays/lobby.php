<!DOCTYPE html>
<html>
	<head>
		
		<style type="text/css">
			html, body {
				margin:0;
				padding:0;
				width:100%;
				height:100%;
			}
			iframe {
				position:absolute;
				width:100%;
				height:100%;
				margin:0;
				padding:0;
				border:none;
				overflow:hidden;
			}
			#cyfe {
				display:none;
			}
		</style>
	</head>
	<body>
		<script src="/interface/js/jquery-1.11.0.min.js"></script>
		<iframe id="corporate" src="/dashboard/displays/corporate"></iframe>
		<iframe id="cyfe" src="https://app.cyfe.com/dashboards/77546/580502029c93d101202382388919"></iframe>
		<script type="text/javascript">
			var one_second = 1 * 1000;
			var one_minute = one_second * 60;
			var one_hour = one_minute * 60;

			// Reload page every 60 mins (3600 seconds)
			// Switch between screens every 60 seconds.
			var $iframe_corporate = $('#corporate');
			var $iframe_cyfe = $('#cyfe');

			function loadIframe() {
				if($iframe_corporate.is(':visible')) {
					$iframe_corporate.fadeOut('slow', function() {
						// Now that corporate dashboard is hidden, let's reload it
						// so the info is ready by the next time it is displayed.
						$iframe_corporate.attr('src', function (i, val) {
							return val;
						});
					});
					$iframe_cyfe.fadeIn('slow');
				} else {
					$iframe_cyfe.fadeOut('slow', function() {
						// Cyfe dashboard has live running data, so we will not
						// be reloading it every time it is hidden like we are
						// with the corporate dashboard.
					});
					$iframe_corporate.fadeIn('slow');
				}

			}

			// Switch between iframe sources every minute.
			setInterval(loadIframe, one_minute);

			// Reload the web page once an hour.
			setTimeout(function() {
				window.location = '/dashboard/displays/lobby';
			}, one_hour);
		</script>
	</body>
</html>