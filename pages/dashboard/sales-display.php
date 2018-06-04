<!DOCTYPE html>
<html>
	<head>
		<title>Shipping Dashboard</title>
		<script type="text/javascript" src="/interface/js/jquery-1.11.0.min.js"></script>
		<style type="text/css">
			body {
				position:absolute;
				background-color:#f00;
				top:0;
				right:0;
				bottom:0;
				left:0;
				margin:0;
				padding:0;
			}
			#top {
				position:absolute;
				top:0;
				left:0;
				right:0;
				bottom:10%;
			}
			#bottom {
				position:absolute;
				top:90%;
				left:0;
				right:0;
				bottom:0;
				background-color:#00f;
			}
			#images {
				position:absolute;
				top:0;
				right:0;
				width:33.333%;
				bottom:0;
			}
			#images img {
				position:absolute;
				top:0;
				left:0;
				bottom:0;
				right:0;
				width:100%;
				height:auto;
			}
			#dollars {
				position:absolute;
				top:0;
				left:0;
				bottom:0;
				width:33.333%;
				background-color:#0ff;
			}
			#clients {
				position:absolute;
				top:0;
				left:33.333%;
				right:33.333%;
				bottom:0;
				background-color:#f0f;
			}
		</style>
	</head>
	<body>
		<div id="top">
			<div id="dollars">

			</div>
			<div id="clients"></div>
			<div id="images"></div>
		</div>
		<div id="bottom">
			<div id="notifications"></div>
		</div>

		<script type="text/javascript">
			function loadDollars() {
				console.log('LoadDollars');
			};
			function loadClients() {
				console.log('LoadClients');
			}
			var last_image;
			function loadImages() {
				$.ajax({
					'url': '/dashboard/sales-display/images',
					'data': {
						'last-image': last_image
					},
					'method': 'POST',
					'dataType': 'json',
					'success': function(response) {
						if(response.success) {
							var $images = $('#images');
							var $old_image = $images.find('img');
							var $new_image = $('<img>').attr('src', '/interface/images/sales-display/' + response.image);
							$new_image.hide().appendTo($images);
							$new_image.fadeIn(5000, function(event) { // Fade in new image over old image.
								$old_image.remove(); // Now that new image is fully visible, remove old image from behind new image.
							});
							last_image = response.image; // Track the filename of the newly displayed image.
						}
					}
				});
			}
			function loadNotifications() {
				var $notifications = $('#notifications');
				$notifications.text('SCROLLING NOTIFICATIONS GO HERE');
			}

			loadDollars();
			loadClients();
			loadImages();
			loadNotifications();

			setInterval(loadDollars, 10000);
			setInterval(loadClients, 10000);
			setInterval(loadImages, 10000);
			setInterval(loadNotifications, 10000);
		</script>
	</body>
</html>
