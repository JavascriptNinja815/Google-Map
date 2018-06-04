<?php


$session->ensureLogin();

$args = array(
	'title' => 'Feedback',
	'breadcrumbs' => array(
		'My Account' => BASE_URI . '/dashboard/account',
		'Feedback' => BASE_URI . '/dashboard/account/feedback'
	),
	'body-class' => 'padded'
);

function get_feedback($feedback_id){

	// Get the feedback for the feedback ID.

	// Get the user's login ID.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	$db = DB::get();
	$query = "
		SELECT
			/* Feedback */
			f.feedback_id,
			f.login_id,
			f.topic,
			f.subject,
			f.memo,
			CAST(f.submitted_on AS date) AS submitted_on,
			
			/* User */
			logins.first_name +' '+ logins.last_name AS name,

			/* Type */
			types.type,

			/* Votes */
			votes.up,
			votes.down,
			votes.up + votes.down AS votes_total,
			votes.up - votes.down AS votes_score,
			voted.direction

		FROM Neuron.dbo.feedback f

		/* User */
		INNER JOIN Neuron.dbo.logins
			ON logins.login_id = f.login_id

		/* Types */
		INNER JOIN Neuron.dbo.feedback_types types
			ON types.type_id = f.feedback_type_id

		/* Votes */
		LEFT JOIN Neuron.dbo.feedback_votes votes
			ON votes.feedback_id = f.feedback_id
		LEFT JOIN Neuron.dbo.feedback_voted voted
			ON voted.feedback_id = f.feedback_id
			AND voted.login_id = ".$db->quote($login_id)."

		WHERE f.feedback_id = ".$db->quote($feedback_id)."
			AND f.company_id = ".$db->quote(COMPANY)."
	";
	$q = $db->query($query);
	return $q->fetch();

}

function get_comments($feedback_id){

	// Get the existing comments.

	$db = DB::get();
	$q = $db->query("
		SELECT

			comments.comment_id,
			comments.comment,
			comments.created_on,
			
			logins.first_name,
			logins.last_name,
			logins.first_name +' '+ logins.last_name AS name

		FROM Neuron.dbo.feedback_comments comments
		INNER JOIN Neuron.dbo.logins
			ON logins.login_id = comments.login_id
		ORDER BY comment_id
	");

	return $q->fetchAll();

}

function get_tags($feedback_id){

	// Query for the tags on the feedback item.

	$db = DB::get();
	$q = $db->query("

		SELECT
			tags.tag_id,
			tags.tag
		FROM Neuron.dbo.feedback_tagged tagged
		LEFT JOIN Neuron.dbo.feedback_tags tags
			ON tags.tag_id = tagged.tag_id
		WHERE tagged.feedback_id = ".$db->quote($feedback_id)."
	");

	return $q->fetchAll();

}

function get_feedback_id(){

	// Get the feedback ID from the URL.
	if(isset($_GET['feedback-id'])){
		$feedback_id = $_GET['feedback-id'];
	}else{$feedback_id=0;};

	return $feedback_id;

}

function get_avatar(){

	// Query for the user's avatar.

	// TODO: User user avatar.
	return '/interface/images/generic-profile.png';

}

function add_comment($feedback_id, $comment){

	// Add a comment to the feeedback.

	// Get the user's login ID.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	$db = DB::get();
	$q = $db->query("
		INSERT INTO Neuron.dbo.feedback_comments (
			feedback_id, login_id, comment
		)
		OUTPUT Inserted.comment_id
		SELECT
			".$db->quote($feedback_id).",
			".$db->quote($login_id).",
			".$db->quote($comment)."
	");

	return $q->fetch()['comment_id'];

}

// Handle AJAX requests.
if(isset($_POST['action'])){

	// New comments.
	if($_POST['action']=='add-comment'){

		// Get the POSTed data.
		$feedback_id = $_POST['feedback_id'];
		$comment = $_POST['comment'];

		// Add the comment.
		$comment_id = add_comment($feedback_id, $comment);

		print json_encode(array(
			'success' => true,
			'comment-id' => $comment_id
		));
		return;

	}

};

// Get the feedback ID.
$feedback_id = get_feedback_id();

// Get the feedback.
$feedback = get_feedback($feedback_id);

// Get the feedback comments.
$comments = get_comments($feedback_id);

// Get the tags for the feedback.
$tags = get_tags($feedback_id);

// Get the user's avatar.
$avatar = get_avatar();

Template::Render('header', $args, 'account');

?>

<style type="text/css">

	/* Feedback */
	#feedback-container {
		padding-bottom: 50px;
	}
	.feedback-row {
		//border:	1px solid blue;
		padding-top: 15px;
		margin-top: 15px;
		height: 230px;
	}
	.feedback-row-container {
		height: 100%;
		margin-left: -5% !important;
		//border: 1px solid yellow;
	}
	.feedback-header {
		border: 1px solid #fed105;
		border-radius: 10px;
		background-color: #fed10514;
		position: relative;
		height: 75px;
		top: 0;
		right: 0;
		left: 0;
		cursor: pointer;
	}
	.feedback-header-text {
		padding-top: 7px;
		padding-left: 25px;


		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.feedback-type-text {
		margin-top: 25px;
		margin-right: 25px;
	}
	.feedback-body {
		border: 1px solid #646567;
		border-radius: 10px;
		background-color: #64656717;
		position: relative;
		height: 40%;
		margin-left: 0px !important;
		overflow: hidden; // TODO: Address this at some point.;

	}
	.feedback-text {
		padding-top: 10px;
		padding-left: 10px;
	}

	/* User */
	.id-box {
		//border: 1px solid blue;
		height: 100%;
	}
	.avatar {
		position: relative;
		//border: 1px solid black;
		height: 100px;
		width: 100px;
		margin-top: 5px;
		margin-left: 5px;
	}
	.name {
		position: relative;
		//border: 1px solid green;
		margin-top: 5px;
		margin-left: 5px;
		height: 20px;
	}
	.date {
		position: relative;;
		//border: 1px solid yellow;
		padding-left: 5px;
		height:20px;
	}

	/* Votes */
	.votes-container {
		//border: 1px solid blue;
		height: 85px;
		width: 180px !important;
	}
	.vote-direction-container {
		//border: 1px solid silver;
		position: relative;
		height: 50px;
		width: 100%;
		margin-left: 0px !important;
	}
	.vote-up {
		//border: 1px solid green;
		cursor: pointer;
	}
	.vote-down {
		//border: 1px solid red;
		cursor: pointer;
	}
	.vote-by-type-container {
		width: 100%;
		margin-left: 0px !important;
	}
	.vote-score {
		//border: 1px solid black;
		position: relative;
		font-size: 40px;
		height: 100%;
		justify-content: center;
		align-items: center;
		display: flex !important;
	}
	.vote-up-count {
		//border: 1px solid green;
		position: relative;
		width: 40px;
		text-align: center;
	}
	.vote-sep {
		//border: 1px solid black;
	}
	.vote-down-count {
		//border: 1px solid red;
		position: relative;
		width: 40px;
		text-align: center;
	}
	.vote-highlighted-green .vote-up {
		color: green;
	}
	.vote-highlighted-red .vote-down {
		color: red;
	}

	/* Tags and Comments Container */
	.feedback-footer-container {
		margin-left: 0px !important;
	}

	/* Tags */
	.feedback-tag-container {
		padding-top: 5px;
		margin-left: 5px !important;
	}
	.tag {
		margin-right: 5px;
	}
	.tag a {
		color: white;
	}

	/* Comments */
	.comment-row {
		//border: 1px solid blue;
		padding-top: 15px;
		margin-top: 15px;
		margin-left: 100px;
		height: 150px;
		width: 80%;
	}
	#new-comment-input-container {
		position: relative;
		//border: 1px solid green;
		height: 85%;
		width: 65%;
		margin-left: 15%;
	}
	#new-comment-input {
		position: relative;
		height: 90%;
		width: 100%;
	}
	#new-comment-button-container {
		padding-top: 5px;
	}
	.comment-body {
		border: 1px solid #646567;
		border-radius: 10px;
		background-color: #64656717;
		position: relative;;
		height: 80%;
		width: 80%;
		margin-left: 0px !important;
		overflow: auto;
	}
	.comment-text {
		padding-top: 10px;
		padding-left: 10px;
	}

	/* Prevent Textarea Resizing */
	textarea {
		resize: none;
	}

</style>

<div id="feedback-container" class="container-fluid">
	<h2>Feedback</h2>

	<div class="row-fluid feedback-row" data-feedback-id="<?php print htmlentities($feedback['feedback_id']) ?>">
		<div class="id-box span2">
			<div class="avatar"><img src="<?php print htmlentities($avatar) ?>"></div>
			<div class="name"><p><?php print htmlentities($feedback['name']) ?></p></div>
			<div class="date"><p><?php print htmlentities($feedback['submitted_on']) ?></p></div>
		</div>

		<div class="feedback-row-container span8 pull-left">
			<div class="feedback-header span8">
				<h3 class="feedback-header-text span10"><?php print htmlentities($feedback['subject']) ?></h3>
				<h6 class="feedback-type-text pull-right">Type: <?php print htmlentities($feedback['type']) ?></h6>
			</div>
			<div class="votes-container span2 pull-left">

				<?php
					// Check if the user has voted on this feedback and
					// in which direction.
					if(isset($feedback['direction'])){
						$direction = $feedback['direction'];
						$highlight = array(
							'1' => 'vote-highlighted-green',
							'0' => 'vote-highlighted-red'
						)[$direction];
					}else{$highlight = 'no-highlight';}
				?>

				<div class="vote-direction-container row pull-left <?php print htmlentities($highlight) ?>">

					<div class="vote vote-up span3 pull-left" data-vote-direction="up" data-color="green"><i class="fa fa-thumbs-o-up fa-3x"></i></div>
					<div class="vote-score span3 pull-left"><b><?php print htmlentities($feedback['votes_score']) ?></b></div>
					<div class="vote vote-down span3 pull-left" data-vote-direction="down" data-color="red"><i class="fa fa-thumbs-o-down fa-3x"></i></div>

				</div>
				<div class="vote-by-type-container row">
					<div class="vote-count vote-up-count span3 pull-left"><?php print htmlentities($feedback['up']) ?></div>
					<div class="vote-sep span3 pull-left"></div>
					<div class="vote-count vote-down-count span3 pull-left"><?php print htmlentities($feedback['down']) ?></div>
				</div>
			</div>
			<div class="feedback-body span8">
				<p class="feedback-text"><?php print htmlentities($feedback['memo']) ?></p>
			</div>
			<div class="feedback-footer-container span8">
				
				<div class="feedback-tag-container span6 pull-left">

					<?php
						foreach($tags as $tag){
							$tag_id = $tag['tag_id'];
							$tag_text = $tag['tag'];
							?>
							<div class="tag pull-left">
								<span class="badge badge-info">
									<a href=""><?php print htmlentities($tag_text) ?></a>
								</span>
							</div>
							<?php
						};
					?>
				</div>

			</div>
		</div>

	</div>
	<!-- TODO: Add existing comments here -->

	<div id="existing-comment-container">
		<?php
		foreach($comments as $comment){
		?>
			<div id="comment-<?php print htmlentities($comment['comment_id']) ?>" class="row-fluid comment-row">
				<div class="id-box span2">
					<div class="avatar"><img src="<?php print htmlentities($avatar) ?>"></div>
					<div class="name"><p><?php print htmlentities($comment['name']) ?></p></div>
					<div class="date"><p><?php print htmlentities($comment['created_on']) ?></p></div>
				</div>

				<div class="comment-body span8">
					<p class="comment-text"><?php print htmlentities($comment['comment']) ?></p>
				</div>

			</div>
		<?php
		}
		?>
	</div>


	<div id="new-comment-row" class="comment-row row-fluid" data-feedback-id="<?php print htmlentities($feedback['feedback_id']) ?>">
		

		<div class="id-box span2">
			<div class="avatar"><img src="<?php print htmlentities($avatar) ?>"></div>
			<?php
				$login = $session->login;
				$first = $login['first_name'];
				$last = $login['last_name'];
				$name = $first.' '.$last;
			?>
			<div class="name"><p><?php print htmlentities($name) ?></p></div>
		</div>
		<div id="new-comment-input-container">
			<textarea id="new-comment-input" placeholder="New Comment"></textarea>
		</div>
		<div id="new-comment-button-container">
			<button id="new-comment-button" class="btn btn-primary">Submit</button>
		</div>

	</div>

</div>

<script type="text/javascript">
$(document).ready(function(){

	function hack_anchors_chrome(){

		// If the user is using Google Chrome, anchors won't always work on
		// load for some stupid reason. Resetting the hash seems to force it to
		// work as expected.

		var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
		if (window.location.hash && isChrome) {
			setTimeout(function () {
				var hash = window.location.hash;
				window.location.hash = "";
				window.location.hash = hash;
			}, 1);
		}

	}

	function vote(){

		// Vote on a feedback item.

		// Get the vote button, vote direction and highlight color.
		var $vote = $(this)
		var direction = $vote.attr('data-vote-direction')
		var color = $vote.attr('data-color')

		// Get the feedback ID.
		var $row = $vote.parents('.feedback-row')
		var feedback_id = $row.attr('data-feedback-id')

		// Check if the feedback has already been voted on.
		var $votes_container = $vote.parents('div.vote-direction-container')
		var isup = $votes_container.hasClass('vote-highlighted-green')
		var isdown = $votes_container.hasClass('vote-highlighted-red')
		var isnew = !(isup||isdown)

		// The data to POST.
		var data = {
			'action' : 'vote',
			'feedback_id' : feedback_id,
			'direction' : direction
		}

		$.ajax({
			'url' : '/dashboard/account/feedback',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data
		}).success(function(rsp){

			/* Handle Highlighting */
			// Remove any existing vote highlighting.
			$votes_container.removeClass('vote-highlighted-green')
			$votes_container.removeClass('vote-highlighted-red')

			// Highlight the selected vote.
			var highlight = {
				'up' : 'vote-highlighted-green',
				'down' : 'vote-highlighted-red'
			}[direction]
			$votes_container.addClass(highlight)

			/* Adjust Vote Counts */
			// Get vote containers.
			var $count_container = $row.find('.vote-by-type-container')
			var $upcount = $count_container.find('.vote-up-count')
			var $downcount = $count_container.find('.vote-down-count')

			// Update the vote counts.
			$upcount.text(rsp.message.up)
			$downcount.text(rsp.message.down)

			/* Update the Overall Score */
			// Get score container..
			var $score_container = $votes_container.find('b')

			// Sum the new scores.
			$score_container.text(rsp.message.score)

		}).error(function(rsp){
			console.log('error')
			console.log(rsp)
		})

	}

	function submit_comment(){

		// Submit a new comment.

		// Get the button for success highlighting.
		var $button = $(this)

		// Get the feedback ID.
		var $container = $('#new-comment-row')
		var feedback_id = $container.attr('data-feedback-id')

		// Get the comment text.
		var $comment_container = $('#new-comment-input')
		var comment = $comment_container.val()

		// The data to POST.
		var data = {
			'action' : 'add-comment',
			'feedback_id' : feedback_id,
			'comment' : comment
		}

		// POST the data.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'async' : false,
			'data' : data,
			'success' : function(rsp){

				// Highlight the button according to success.
				var klass = {
					true: 'btn-success',
					false: 'btn-danger'
				}[rsp.success]

				$button.removeClass('btn-primary')
				$button.removeClass('btn-danger')
				$button.removeClass('btn-success')
				$button.addClass(klass)

				// Get the new comment ID.
				var comment_id = rsp['comment-id']
				console.log(comment_id)

				// Get a new URL.
				var loc = window.location
				var org = loc.origin
				var pth = loc.pathname
				var sch = loc.search
				var url = org+pth+sch+'#comment-'+comment_id

				// Navigate to new URL.
				window.location.href = url
				window.location.reload(true)

			},
			'error' : function(){

				// Highlight the button.
				$button.removeClass('btn-primary')
				$button.removeClass('btn-danger')
				$button.removeClass('btn-success')
				$button.addClass('btn-danger')

				// Log an error.
				console.log('error')
			}
		})

	}

	// Hack in support for URL anchors.
	hack_anchors_chrome()

	// Enable commenting.
	$(document).on('click' , '#new-comment-button', submit_comment)

	// Enable voting.
	$(document).on('click', '.vote', vote)

})
</script>