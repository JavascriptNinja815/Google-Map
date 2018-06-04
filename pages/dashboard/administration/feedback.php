<?php

$session->ensureLogin();
$session->ensureRole('Administration');

$args = array(
	'title' => 'Logins',
	'breadcrumbs' => array(
		'Administration' => BASE_URI . '/dashboard/administration',
		'Logins' => BASE_URI . '/dashboard/administration/logins'
	)
);

Template::Render('header', $args, 'account');

$grab_feedback = $db->query("
	SELECT
		feedback.feedback_id,
		logins.initials AS submitted_by,
		readby_logins.initials AS read_by,
		feedback.submitted_on,
		feedback.read_on,
		feedback.topic,
		feedback.subject,
		feedback.memo
	FROM
		" . DB_SCHEMA_INTERNAL . ".feedback
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = feedback.login_id
	LEFT JOIN
		" . DB_SCHEMA_INTERNAL . ".logins AS readby_logins
		ON
		readby_logins.login_id = feedback.readby_login_id
	WHERE
		feedback.submitted_on >= DATEADD(day, -30, GETDATE())
	ORDER BY
		feedback.submitted_on
");
?>

<div class="padded" id="feedback-container">
	<h2>Feedback</h2>
	<p>Displaying feedback submitted within the past 30 days.</p>

	<table class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky">
		<thead>
			<tr>
				<th></th>
				<th>ID</th>
				<th class="filterable sortable">Submitted By</th>
				<th class="filterable sortable">Submitted On</th>
				<th class="filterable sortable">Topic</th>
				<th class="filterable sortable">Subject</th>
				<th class="filterable sortable">Memo</th>
				<th class="filterable sortable">Read By</th>
				<th class="filterable sortable">Read On</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_feedback as $feedback) {
				?>
				<tr class="feedback" feedback_id="<?php print htmlentities($feedback['feedback_id'], ENT_QUOTES);?>">
					<td><?php
					if(!empty($feedback['read_on'])) {
						?><button class="btn btn-warning unread">Mark Unread</button><?php
					} else {
						?><button class="btn btn-success read">Mark Read</button><?php
					}
					?></td>
					<td><?php print htmlentities($feedback['feedback_id']);?></td>
					<td><?php print htmlentities($feedback['submitted_by']);?></td>
					<td><?php print htmlentities($feedback['submitted_on']);?></td>
					<td><?php print htmlentities($feedback['topic']);?></td>
					<td><?php print htmlentities($feedback['subject']);?></td>
					<td><?php print htmlentities($feedback['memo']);?></td>
					<td class="read-by"><?php print htmlentities($feedback['read_by']);?></td>
					<td class="read-on"><?php print htmlentities($feedback['read_on']);?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$(document).off('click', '#feedback-container .unread');
	$(document).on('click', '#feedback-container .unread', function(event) {
		var $button = $(this);
		var $feedback = $button.closest('.feedback');
		var feedback_id = $feedback.attr('feedback_id');
		var $read_on = $feedback.find('.read-on');
		var $read_by = $feedback.find('.read-by');

		var $loading_overlay;

		$.ajax({
			'url': BASE_URI + '/dashboard/administration/feedback/status/unread',
			'method': 'POST',
			'data': {
				'feedback_id': feedback_id
			},
			'dataType': 'json',
			'beforeSend': function() {
				$loading_overlay = $.overlayz({
					'html': $ajax_loading_prototype.clone(),
					'css': ajax_loading_styles
				}).fadeIn('fast');
			},
			'success': function(response) {
				$button.removeClass('unread').addClass('read');
				$button.removeClass('btn-warning').addClass('btn-success');
				$button.text('Mark Read');
				$read_on.empty();
				$read_by.empty();
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});

	$(document).off('click', '#feedback-container .read');
	$(document).on('click', '#feedback-container .read', function(event) {
		var $button = $(this);
		var $feedback = $button.closest('.feedback');
		var feedback_id = $feedback.attr('feedback_id');
		var $read_on = $feedback.find('.read-on');
		var $read_by = $feedback.find('.read-by');

		var $loading_overlay;

		$.ajax({
			'url': BASE_URI + '/dashboard/administration/feedback/status/read',
			'method': 'POST',
			'data': {
				'feedback_id': feedback_id
			},
			'dataType': 'json',
			'beforeSend': function() {
				$loading_overlay = $.overlayz({
					'html': $ajax_loading_prototype.clone(),
					'css': ajax_loading_styles
				}).fadeIn('fast');
			},
			'success': function(response) {
				$button.removeClass('read').addClass('unread');
				$button.removeClass('btn-success').addClass('btn-warning');
				$button.text('Mark Unread');
				$read_on.text(response.read_on);
				$read_by.text(response.read_by);
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
	});
</script>

<?php Template::Render('footer', 'account');
