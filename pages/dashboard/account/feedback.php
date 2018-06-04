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

function get_feedback($page=1, $page_size=25, $tag_id=0, $type_id=0, $user_id=0, $feedback_ids=null){

	// Get existing feedback.

	// The query to execute.
	$db = DB::get();
	$main_query = "
		/* Declare Locals - Pagination */
		DECLARE @page int;
		DECLARE @page_size int;
		DECLARE @offset int;
		DECLARE @bound int;

		/* Set Locals - Pagination */
		SET @page = ".$db->quote($page).";
		SET @page_size = ".$db->quote($page_size)."
		SET @offset = @page_size*(@page-1)+1;
		SET @bound = @offset+@page_size;

		/* Declare a temporary table because why should this be easy? */
		DECLARE @results TABLE (

			/* Unique Row Value */
			id integer IDENTITY(1,1),

			/* Feedback Data */ 
			login_id integer,
			feedback_id integer,
			first_name VARCHAR(MAX),
			last_name VARCHAR(MAX),
			name VARCHAR(MAX),
			topic VARCHAR(MAX),
			subject VARCHAR(MAX),
			memo VARCHAR(MAX),

			/* Dates */
			submitted_on VARCHAR(MAX),
			submitted_on_unix BIGINT,

			/* Votes */
			votes_up integer,
			votes_down integer,
			votes_total integer,
			votes_score integer,

			/* Types */
			type VARCHAR(MAX),
			type_id integer,

			/* Comments */
			comment_count integer,
			comment_id integer,
			comment VARCHAR(MAX),
			comment_date VARCHAR(MAX),
			comment_first_name VARCHAR(MAX),
			comment_last_name VARCHAR(MAX),

			/* Dates */
			projected_completion_date DATE,
			assignee INTEGER

		);

		INSERT INTO @results (
			login_id,
			feedback_id,
			first_name,
			last_name,
			name,
			topic,
			subject,
			memo,
			submitted_on,
			submitted_on_unix,
			votes_up,
			votes_down,
			votes_score,
			votes_total,
			type,
			type_id,
			comment_count,
			comment_id,
			comment,
			comment_date,
			comment_first_name,
			comment_last_name,
			projected_completion_date,
			assignee
		)
		SELECT
			/* Feedback Data */
			l.login_id,
			feedback.feedback_id,
			l.first_name,
			l.last_name,
			l.first_name + ' ' + l.last_name AS name,
			topic,
			subject,
			memo,
			--CONVERT(VARCHAR(8), submitted_on, 10) AS submitted_on,
			CAST(
				CONVERT(char(3), submitted_on, 0) AS VARCHAR
			) + ' ' + 
			CAST(
				DATEPART(DAY, submitted_on) AS VARCHAR
			) + ' ' +
			CAST(
				CONVERT(varchar(15), CAST(CONVERT(VARCHAR(5), submitted_on, 108) AS time), 100)
				AS VARCHAR
			) AS submitted_on,
			DATEDIFF(s, '1970-01-01 00:00:00', submitted_on) AS submitted_on_unix,

			/* Votes */
			COALESCE(votes.up, 0) AS votes_up,
			COALESCE(votes.down, 0) AS votes_down,
			COALESCE(votes.up-votes.down, 0) AS votes_score,
			COALESCE(votes.up+votes.down, 0) AS votes_total,

			/* Feedback Type*/
			types.type,
			types.type_id,

			/* Comments */
			(
				SELECT COUNT(*)
				FROM Neuron.dbo.feedback_comments comments
				WHERE comments.feedback_id = feedback.feedback_id
			) AS comment_count,
			c.comment_id,
			c.comment,
			--CAST(c.created_on AS date),

			CAST(
				CONVERT(char(3), c.created_on, 0) AS VARCHAR
			) + ' ' + 
			CAST(
				DATEPART(DAY, c.created_on) AS VARCHAR
			) + ' ' +
			CAST(
				CONVERT(varchar(15), CAST(CONVERT(VARCHAR(5), c.created_on, 108) AS time), 100)
				AS VARCHAR
			) AS created_on,

			cl.first_name,
			cl.last_name,

			feedback.projected_completion_date,
			feedback.resolution_owner

		FROM Neuron.dbo.feedback
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = feedback.login_id
		LEFT JOIN Neuron.dbo.feedback_types types
			ON types.type_id = feedback.feedback_type_id
		LEFT JOIN Neuron.dbo.feedback_votes votes
			ON votes.feedback_id = feedback.feedback_id
		LEFT JOIN Neuron.dbo.feedback_tagged tagged
			ON tagged.feedback_id = feedback.feedback_id
			AND tagged.tag_id = ".$db->quote($tag_id)."

		/* Prevent Duplicat Rows Due to Comments */
		LEFT JOIN (
			SELECT DISTINCT feedback_id
			FROM Neuron.dbo.feedback_comments c
		) comments
		ON comments.feedback_id = feedback.feedback_id

		OUTER APPLY (
			SELECT TOP 1 *
			FROM Neuron.dbo.feedback_comments c
			WHERE c.feedback_id = feedback.feedback_id
			ORDER BY comment_id DESC
		) c
		LEFT JOIN Neuron.dbo.logins cl
			ON cl.login_id = c.login_id

		WHERE company_id = 1
		/* TODO: Remove this!!!*/
		--AND feedback.feedback_id = 105
	";

	// TODO: Uncomment this when tags are supported.
	// Constrain to a particular tag.
	// if(!$tag_id==0){
	// 	$main_query .= "
	// 		AND tagged.tag_id = ".$db->quote($tag_id)."
	// 	";
	// };

	// Constrain by type
	if(!$type_id==0){
		$main_query .= "
			AND type_id = ".$db->quote($type_id)."
		";
	};

	// Constrain by user.
	if(!$user_id==0){
		$main_query .= "
			AND l.login_id = ".$db->quote($user_id)."
		";
	};

	// Constrain to arbitrary feedback IDs.
	if(!is_null($feedback_ids)){

		// Make the array "queryable". - The IDs come from SQL server
		// so this is safe. No SQL injection vulnerabilities.
		$ids = implode(',', $feedback_ids);

		$main_query .= "
			AND feedback.feedback_id IN (".$ids.")
		";

	};

	// Get the order-by clause.
	$orderby = get_order_by();
	if($orderby){
		$main_query .= " ".$orderby;
	};

	// $query = $main_query."
	// 	)
	// 	SELECT *
	// 	FROM results
	// 	WHERE idx >= @offset
	// 		AND idx < @bound
	// ";

	$query = $main_query ."
	SELECT
		/* Index for pagination */
		ROW_NUMBER() OVER (ORDER BY id) AS idx,
		r.*,
		ph.profile_photo_id
	FROM @results r
	LEFT JOIN Neuron.profiles.profile p
		ON p.login_id = r.login_id
	LEFT JOIN Neuron.profiles.profile_photos ph
		ON ph.profile_id = p.profile_id
	ORDER BY idx
	";

	// Perform query.
	$q = $db->query($query);
	$q->nextRowset();

	return $q->fetchAll();
}

function get_order_by(){

	// Get the order-by clause for the feedback query.

	// Get default values.
	$user = null;
	$date = null;
	$orderby = null;

	// Get the user/date sort values.
	if(isset($_GET['user-sort'])){$user=$_GET['user-sort'];};
	if(isset($_GET['date-sort'])){$date=$_GET['date-sort'];};

	// Create the order-by.
	if($user or $date){
		$orderby = 'ORDER BY';

		// Add user sort.
		if($user){$orderby.=' name '.$user;};

		// Check for both sorts.
		if($user and $date){
			$orderby .= ', ';
		};

		// Add date sort.
		if($date){$orderby.=' submitted_on_unix '.$date;};

	};

	return $orderby;

}

function get_page_size(){

	// Get the page size.
	if(isset($_GET['page-size'])){
		$page_size = (int)$_GET['page-size'];
	}else{$page_size=25;};

	return $page_size;

}

function get_page(){

	// Get the current page.
	if(isset($_GET['page'])){
		$page = (int)$_GET['page'];
	}else{$page=1;};

	return $page;

}

function get_tag_id(){

	// Get the tag ID to constrain to.
	if(isset($_GET['tag-id'])){
		$tag_id = (int)$_GET['tag-id'];

		// If the tag ID is invalid, return 0 instead.
		$valid = get_valid_tags();
		if(!in_array($tag_id,$valid)){
			$tag_id = 0;
		};

	}else{$tag_id=0;};

	return $tag_id;

}

function get_type_id(){

	// Get the type to constrain to.
	if(isset($_GET['type-id'])){
		$type_id = $_GET['type-id'];
	}else{$type_id=0;};

	return $type_id;

}

function get_user_id(){

	// Get the user to constrain to.
	if(isset($_GET['user-id'])){
		$user_id = $_GET['user-id'];
	}else{$user_id=0;};

	return $user_id;

}

function get_valid_tags(){

	// Query for valid tag IDs so invalid tags can't be queried.

	$db = DB::get();
	$q = $db->query("
		SELECT tag_id
		FROM Neuron.dbo.feedback_tags
	");

	$results = $q->fetchAll();
	$valid = array();
	foreach($results as $tag){
		array_push($valid, $tag['tag_id']);
	};

	return $valid;

}

function get_page_count($page_size){

	// Query for the total number of pages.

	$db = DB::get();
	$q = $db->query("
		SELECT CEILING(CAST(COUNT(*) AS FLOAT)/".$db->quote($page_size).") AS pages
		FROM Neuron.dbo.feedback
		WHERE company_id = ".COMPANY."
	");

	return $q->fetch()['pages'];

}

function get_user_votes(){

	// Get the user-votes.

	// Get the user information.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	// Query for votes.
	$db = DB::get();
	$q = $db->query("
		SELECT
			feedback_id,
			CASE
				WHEN direction = 0 THEN 'down'
				ELSE 'up'
			END AS direction
		FROM Neuron.dbo.feedback_voted
		WHERE login_id = ".$db->quote($login_id)."
	");

	// Restructure the results.
	$voted = array();
	foreach($q as $row) {
		$voted[$row['feedback_id']] = $row['direction'];
	}

	return $voted;

}

function get_feedback_votes($feedback_id){

	// Query for the votes and score of a particular feedback row.

	$db = DB::get();
	$q = $db->query("
		SELECT
			up,
			down,
			up-down AS score
		FROM Neuron.dbo.feedback_votes
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

	return $q->fetch();

}

function get_avatar(){

	// This is a temporary function to return a logo instead of a user avatar.
	// The returned URL is based on company.

	// TODO: User user avatar.
	return '/interface/images/generic-profile.png';

	$avatars = array(
		1 => '/interface/images/casterdepot-cd-logo.png',
		2 => '/interface/images/dorodo-logo.png'
	);

	return $avatars[COMPANY];

}

function record_user_vote($login_id, $feedback_id, $direction){

	// Record the vote on the user.

	// Convert the direction to something useful.
	$direction = array(
		'up' => 1,
		'down' => 0
	)[$direction];

	// Record the vote.
	$db = DB::get();
	$db->query("
		INSERT INTO Neuron.dbo.feedback_voted (
			login_id, feedback_id, direction
		)
		SELECT
			".$db->quote($login_id).",
			".$db->quote($feedback_id).",
			".$db->quote($direction)."
		WHERE NOT EXISTS (
			SELECT 1
			FROM Neuron.dbo.feedback_voted
			WHERE login_id = ".$db->quote($login_id)."
				AND feedback_id = ".$db->quote($feedback_id)."
		);
		UPDATE Neuron.dbo.feedback_voted
		SET direction = ".$db->quote($direction)."
		WHERE login_id = ".$db->quote($login_id)."
			AND feedback_id = ".$db->quote($feedback_id)."
	");

}

function record_feedback_vote($feedback_id, $up, $down){

	// Record the vote on the feedback.

	$db = DB::get();
	$db->query("
		INSERT INTO Neuron.dbo.feedback_votes (
			feedback_id, up, down
		)
		SELECT
			".$db->quote($feedback_id).", 0, 0
		WHERE NOT EXISTS (
			SELECT 1
			FROM Neuron.dbo.feedback_votes
			WHERE feedback_id = ".$db->quote($feedback_id)."
		);
		UPDATE Neuron.dbo.feedback_votes
		SET up = up+".$db->quote($up).",
			down = down+".$db->quote($down)."
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function get_feedback_ids($feedback){

	// Helper function get the feedback IDs for tag-retrieval.

	// Get the IDs in an array.
	$ids = array();
	foreach($feedback as $row){
		array_push($ids, $row['feedback_id']);
	};

	return $ids;

}

function get_feedback_tags($feedback){

	// Get tags by feedback ID.
	// Note: The IDs do not need to be escaped because the values come
	// straight from SQL Server. This is safe - no SQL injection vulnerability.

	// Get the feedback ID array.
	$feedback_ids = get_feedback_ids($feedback);
	$feedback_in = implode(',', $feedback_ids);

	// If there is no feedback, there are no tags to find.
	if(empty($feedback_ids)){
		return array();
	};

	// Get the tags.
	$db = DB::get();
	$q = $db->query("
		SELECT
			tagged.feedback_id,
			tag.tag_id,
			tag.tag
		FROM Neuron.dbo.feedback_tagged tagged
		INNER JOIN Neuron.dbo.feedback_tags tag
			ON tag.tag_id = tagged.tag_id
		WHERE feedback_id IN (".$feedback_in.")
	");

	// Restructure the data.
	$results = $q->fetchAll();
	$tags = array();
	foreach($results as $tag){
		
		// Get feedback ID.
		$feedback_id = $tag['feedback_id'];

		// Handle first-encounters of feedback IDs.
		if(!array_key_exists($feedback_id, $tags)){
			// Create an array to push to.
			$tags[$feedback_id] = array();
		};

		// Push tag to the tag array.
		array_push($tags[$feedback_id], $tag);

	};

	return $tags;

}

function get_users(){

	// Query for users that have posted feedback.

	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT
			l.login_id,
			l.first_name,
			l.last_name
		FROM Neuron.dbo.feedback f
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = f.login_id
		ORDER BY last_name, first_name
	");

	return $q->fetchAll();

}

function get_all_tags(){

	// Query for all tags.

	$db = DB::get();
	$q = $db->query("
		SELECT tag_id, tag
		FROM Neuron.dbo.feedback_tags
		ORDER BY tag
	");

	return $q->fetchAll();

}

function get_all_types(){

	// Query for all feedback types.

	$db = DB::get();
	$q = $db->query("
		SELECT type_id, type
		FROM Neuron.dbo.feedback_types
		ORDER BY type
	");

	return $q->fetchAll();

}

function follow_feedback($feedback_id, $login_id){

	// Follow a feedback.

	$db = DB::get();
	$q = $db->query("
		INSERT INTO Neuron.dbo.feedback_following (
			feedback_id, login_id
		)
		SELECT
			".$db->quote($feedback_id).",
			".$db->quote($login_id)."
		WHERE NOT EXISTS (
			SELECT 1
			FROM Neuron.dbo.feedback_following
			WHERE feedback_id = ".$db->quote($feedback_id)."
			AND login_id = ".$db->quote($login_id)."
		)
	");

}

function unfollow_feedback($feedback_id, $login_id){

	// Unfollow a feedback.

	$db = DB::get();
	$q = $db->query("
		DELETE FROM Neuron.dbo.feedback_following
		WHERE login_id = ".$db->quote($login_id)."
			AND feedback_id = ".$db->quote($feedback_id)."
	");

}

function get_recent_ids(){

	// Query for feedback IDs created within the last 7 days.

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback
		WHERE submitted_on >= DATEADD(DAY, -7, GETDATE())
	");

	// Restructure the return value.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	}

	return $feedback_ids;

}

function get_following_ids(){

	// Query for the feedback IDs the user is following.

	// Get the user information.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback_following
		WHERE login_id = ".$db->quote($login_id)."
	");

	// Restructure the return value.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	}

	return $feedback_ids;

}

function get_archived_ids(){

	// Get the feedback IDs of archived feedback.

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback
		WHERE archived = 1
	");

	// Restructure the return value.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	}

	return $feedback_ids;

}

function get_killed_ids(){

	// Get the feedback IDs of killed feedback.

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback
		WHERE killed = 1
	");

	// Restructure the return value.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	}

	return $feedback_ids;

}

function get_all_assigned(){

	// Get the feedback IDs of feedback that has been assigned.

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback
		WHERE resolution_owner IS NOT NULL
	");

	// Restructure.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	}

	return $feedback_ids;

}

function get_assigned(){

	// Get all feedback IDs of the feedback that has been assigned to the user.

	// Get the user information.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback
		WHERE resolution_owner = ".$db->quote($login_id)."
	");

	// Strucure the data.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	};

	return $feedback_ids;

}

function get_completed(){

	// Get the IDs of completed feedbacks.

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback
		WHERE completed_on IS NOT NULL
	");

	// Strucure the data.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	};

	return $feedback_ids;

}

function get_started(){

	// Get the IDs of the started feedbacks.

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback
		WHERE active_start_date IS NOT NULL
	");

	// Strucure the data.
	$feedback_ids = array();
	$results = $q->fetchAll();
	foreach($results as $result){
		array_push($feedback_ids, $result['feedback_id']);
	};

	return $feedback_ids;

}

function archive_feedback($feedback_id){

	// Archive feedback.

	$db = DB::get();
	$q = $db->query("
		UPDATE Neuron.dbo.feedback
		SET archived = 1
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function kill_feedback($feedback_id){

	// Kill feedback.

	$db = DB::get();
	$q = $db->query("
		UPDATE Neuron.dbo.feedback
		SET killed = 1
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function unarchive_feedback($feedback_id){

	// Unarchive feedback.

	$db = DB::get();
	$q = $db->query("
		UPDATE Neuron.dbo.feedback
		SET archived = 0
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function unkill_feedback($feedback_id){

	// Unkill feedback.

	$db = DB::get();
	$q = $db->query("
		UPDATE Neuron.dbo.feedback
		SET killed = 0
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function complete_feedback($feedback_id){

	// Mark the feedback as complete.

	$db = DB::get();
	$db->query("
		UPDATE Neuron.dbo.feedback
		SET completed_on = GETDATE()
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function uncomplete_feedback($feedback_id){

	// Mark the feedback as incomplete.

	$db = DB::get();
	$db->query("
		UPDATE Neuron.dbo.feedback
		SET completed_on = NULL
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function start_feedback($feedback_id){

	// Mark feedback as started.

	$db = DB::get();
	$db->query("
		UPDATE Neuron.dbo.feedback
		SET active_start_date = GETDATE()
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function unstart_feedback($feedback_id){

	// Mark feedback is not been started.

	$db = DB::get();
	$db->query("
		UPDATE Neuron.dbo.feedback
		SET active_start_date = NULL
		WHERE feedback_id = ".$db->quote($feedback_id)."
	");

}

function get_is_admin(){


	// Return a boolean indicating whether the logged-in user can view/operate
	// admin-level icons/actions.

	return false;

	global $session;
	if($session->hasRole('Administration') || $session->hasRole('Supervisor')){
		return true;
	};

	return false;

}

function get_ids_from_feedback($feedback){

	$ids = array();
	foreach($feedback as $row){
		$feedback_id = $row['feedback_id'];
		array_push($ids, $feedback_id);

	};

	return $ids;

}

function get_comments($ids){

	// Get the comments for the feedback.

	// Create a structured object and get the IDs for the query.
	$comments = array();
	foreach($ids as $id){
		$comments[$id] = array();

	};

	// Get the comments.
	$db = DB::get();
	$q = $db->query("
		SELECT
			c.feedback_id,
			c.comment_id,
			c.comment,

			CAST(
				CONVERT(char(3), c.created_on, 0) AS VARCHAR
			) + ' ' + 
			CAST(
				DATEPART(DAY, c.created_on) AS VARCHAR
			) + ' ' +
			CAST(
				CONVERT(varchar(15), CAST(CONVERT(VARCHAR(5), c.created_on, 108) AS time), 100)
				AS VARCHAR
			) AS created_on,

			l.first_name,
			l.last_name
		FROM Neuron.dbo.feedback_comments c
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = c.login_id
		WHERE feedback_id IN (".implode(',', $ids).")
	");

	// Push the comments in the prepared data structure.
	$results = $q->fetchAll();
	foreach($results as $result){
		$feedback_id = $result['feedback_id'];
		array_push($comments[$feedback_id], $result);
	};

	return $comments;

}

function get_comment($comment_id){

	// Get the comment data for the comment ID.
	$db = DB::get();
	$q = $db->query("
		SELECT
			c.comment_id,
			c.comment,
			CAST(
				CONVERT(char(3), c.created_on, 0) AS VARCHAR
			) + ' ' + 
			CAST(
				DATEPART(DAY, c.created_on) AS VARCHAR
			) + ' ' +
			CAST(
				CONVERT(varchar(15), CAST(CONVERT(VARCHAR(5), c.created_on, 108) AS time), 100)
				AS VARCHAR
			) AS created_on,
			l.first_name,
			l.last_name,
			ph.profile_photo_id
		FROM Neuron.dbo.feedback_comments c
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = c.login_id
		LEFT JOIN Neuron.profiles.profile p
			ON p.login_id = l.login_id
		LEFT JOIN Neuron.profiles.profile_photos ph
			ON ph.profile_id = p.profile_id
		WHERE comment_id = ".$db->quote($comment_id)."

	");

	return $q->fetch();

}

function get_feedback_likes(){

	// Query for the feeback like counts.

	$db = DB::get();
	$q = $db->query("
		SELECT
			feedback_id,
			COUNT(*) AS like_count
		FROM Neuron.dbo.feedback_likes l
		GROUP BY feedback_id;
	");

	// Restructure the response.
	$likes = array();
	foreach($q as $row){
		$likes[$row['feedback_id']] = $row['like_count'];
	}

	return $likes;

}

function get_comment_likes(){

	// Query for the comment like counts.

	$db = DB::get();
	$q = $db->query("
		SELECT
			comment_id,
			COUNT(*) AS like_count
		FROM Neuron.dbo.feedback_comment_likes l
		GROUP BY comment_id;
	");

	// Restructure the response.
	$likes = array();
	foreach($q as $row){
		$likes[$row['comment_id']] = $row['like_count'];
	}

	return $likes;

}

function get_user_feedback_likes(){

	// Get the feedback IDs of the feedback the user has liked.

	// Get the user information.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	$db = DB::get();
	$q = $db->query("
		SELECT feedback_id
		FROM Neuron.dbo.feedback_likes
		WHERE login_id = ".$db->quote($login_id)."
	");

	// Put all IDs into an array.
	$ids = array();
	foreach($q as $row){
		array_push($ids, $row['feedback_id']);
	}

	return $ids;

}

function get_user_comment_likes(){

	// Get the comment IDs of the comments the user has liked.

	// Get the user information.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	$db = DB::get();
	$q = $db->query("
		SELECT comment_id
		FROM Neuron.dbo.feedback_comment_likes
		WHERE login_id = ".$db->quote($login_id)."
	");

	// Put all IDs into an array.
	$ids = array();
	foreach($q as $row){
		array_push($ids, $row['comment_id']);
	}

	return $ids;

}

function get_likes(){

	// Get the like counts by type.
	$flikes = get_feedback_likes();
	$clikes = get_comment_likes();
	return array(
		'feedback' => $flikes,
		'comments' => $clikes
	);

}

function get_user_likes(){

	// Get the feedback and comments the user has liked.

	$flikes = get_user_feedback_likes();
	$clikes = get_user_comment_likes();
	return array(
		'feedback' => $flikes,
		'comments' => $clikes
	);

}

function get_user_profile_photo(){

	// Query for the user's profile photo ID.

	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	$db = DB::get();
	$q = $db->query("
		SELECT profile_photo_id
		FROM Neuron.dbo.logins l
		INNER JOIN Neuron.profiles.profile p
			ON p.login_id = l.login_id
		INNER JOIN Neuron.profiles.profile_photos ph
			ON ph.profile_id = p.profile_id
		WHERE l.login_id = ".$db->quote($login_id)."
	");

	return $q->fetch()['profile_photo_id'];

}

function get_assignee($login_id){

	// Get the name of the assignee

	$db = DB::get();
	$q = $db->query("
		SELECT first_name, last_name
		FROM Neuron.dbo.logins
		WHERE login_id = ".$db->quote($login_id)."
	");

	$r = $q->fetch();
	$name = $r['first_name'].' '.$r['last_name'];

	return $name;

}

// Handle AJAX requests.
if(isset($_POST['action'])){

	// Get the user information.
	global $session;
	$user = $session->login;
	$login_id = $user['login_id'];

	if($_POST['action'] == 'vote'){

		// Get the feedback ID and vote direction.
		$feedback_id = $_POST['feedback_id'];
		$direction = $_POST['direction'];

		// Get initial vote adjustments.
		$up = 0;
		$down = 0;

		// Consider this vote.
		if($direction == 'up'){ $up += 1;}
		else{$down += 1;};

		// Consider previous votes.
		$prev = get_user_votes();
		if(array_key_exists($feedback_id, $prev)){

			// Adjust vote values accordingly.
			$pdirection = $prev[$feedback_id];
			if($pdirection == 'up'){$up -= 1;}
			else{$down -= 1;};

		};

		// Update the feedback vote.
		record_feedback_vote($feedback_id, $up, $down);

		// Record the vote on the user.
		record_user_vote($login_id, $feedback_id, $direction);

		// Get new vote values.
		$score = get_feedback_votes($feedback_id);

		// Return new vote count and score.
		print json_encode(array(
			'success' => true,
			'message' => $score
		));

		return;

	};

	if($_POST['action'] == 'follow'){

		// Get the POSTed data.
		$feedback_id = $_POST['feedback_id'];
		$follow = $_POST['value'];

		// Follow or unfollow the feedback.
		if($follow=='follow'){
			follow_feedback($feedback_id, $login_id);
		}else{
			unfollow_feedback($feedback_id, $login_id);
		}

		print json_encode(array(
			'success' => true
		));

		return;

	};

	if($_POST['action'] == 'archive'){

		// Archive feedback.

		// Get the POSTed data.
		$feedback_id = $_POST['feedback_id'];
		$archive = $_POST['value'];

		// Archvie the feedback.
		if($archive=='archive'){
			archive_feedback($feedback_id);
		}else{
			unarchive_feedback($feedback_id);
		}

		print json_encode(array(
			'success' => true
		));

		return;

	};


	if($_POST['action'] == 'kill'){

		// Kill feedback.

		// Get the POSTed data.
		$feedback_id = $_POST['feedback_id'];
		$kill = $_POST['value'];

		// Kill the feedback.
		if($kill=='kill'){
			kill_feedback($feedback_id);
		}else{
			unkill_feedback($feedback_id);
		}

		print json_encode(array(
			'success' => true
		));

		return;

	};

	if($_POST['action'] == 'complete'){

		// Complete the feedback.

		// Get POSTed data.
		$feedback_id = $_POST['feedback_id'];
		$complete = $_POST['value'];

		// Update the feedback.
		if($complete=='complete'){
			complete_feedback($feedback_id);
		}else{
			uncomplete_feedback($feedback_id);
		}

		print json_encode(array(
			'success' => true,
			'complete' => $complete
		));

		return;

	}

	if($_POST['action'] == 'start'){

		// Complete the feedback.

		// Get POSTed data.
		$feedback_id = $_POST['feedback_id'];
		$start = $_POST['value'];

		// Update the feedback.
		if($start=='start'){
			start_feedback($feedback_id);
		}else{
			unstart_feedback($feedback_id);
		}

		print json_encode(array(
			'success' => true
		));

		return;

	}

	if($_POST['action'] == 'add-comment') {


		// Get the POSTed data.
		$feedback_id = $_POST['feedback_id'];
		$login_id = $_POST['login_id'];
		$comment = $_POST['comment'];

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

		// Get the new comment ID.
		$comment_id = $q->fetch()['comment_id'];

		print json_encode(array(
			'success' => true,
			'comment_id' => $comment_id
		));

		return;

	}

	if($_POST['action'] == 'get-comment'){

		// Get the comment ID.
		$comment_id = $_POST['comment_id'];

		// Get the comment data.
		$comment = get_comment($comment_id);

		// Build the HTML.
		$html = '
			<div class="comment-container row-fluid" data-comment-id="'.$comment['comment_id'].'">
				
				<!-- User -->
				<div class="user-container span1">
					<div class="avatar-container text-center" data-photo-id="'.$comment['profile_photo_id'].'">
						<img class="avatar" src="'.get_avatar().'">
					</div>
					<div class="name-container">
						<div class="first-name-container">'.$comment['first_name'].'</div>
						<div class="last-name-container">'.$comment['last_name'].'</div>
					</div>
				</div>


				<div class="comment-content-container gray-background btop bleft bright bbottom span11">
					<div class="comment-body-container row-fluid">
						<p>'.$comment['comment'].'</p>
					</div>
					<div class="comment-footer-container row-fluid">
						<div class="comment-likes-container span1"><i class="fa fa-thumbs-o-up"></i><span class="like-count">0</span></div>
						<div class="comment-date-container date span2 offset9 text-center">'.$comment['created_on'].'</div>
					</div>									
				</div>


			</div>
		';

		print json_encode(array(
			'success' => true,
			'html' => $html,
			'photo-id' => $comment['profile_photo_id']
		));

		return;

	}

	if($_POST['action']=='like-feedback'){

		// Like the feedback.

		// Get the user information.
		global $session;
		$user = $session->login;
		$login_id = $user['login_id'];

		$db = DB::get();
		$db->query("
			INSERT INTO Neuron.dbo.feedback_likes (
				feedback_id, login_id
			)
			SELECT
				".$db->quote($_POST['feedback_id']).",
				".$db->quote($login_id)."
			WHERE NOT EXISTS (
				SELECT 1
				FROM Neuron.dbo.feedback_likes
				WHERE feedback_id = ".$db->quote($_POST['feedback_id'])."
					AND login_id = ".$db->quote($login_id)."
			)
		");

		print json_encode(array(
			'success' => true
		));

		return;

	}

	if($_POST['action']=='unlike-feedback'){

		// Like the feedback.

		// Get the user information.
		global $session;
		$user = $session->login;
		$login_id = $user['login_id'];

		$db = DB::get();
		$db->query("
			DELETE FROM Neuron.dbo.feedback_likes
			WHERE feedback_id = ".$db->quote($_POST['feedback_id'])."
				AND login_id = ".$db->quote($login_id)."
		");

		print json_encode(array(
			'success' => true
		));

		return;

	}

	if($_POST['action']=='like-comment'){

		// Like the comment.

		// Get the user information.
		global $session;
		$user = $session->login;
		$login_id = $user['login_id'];

		$db = DB::get();
		$db->query("
			INSERT INTO Neuron.dbo.feedback_comment_likes (
				comment_id, login_id
			)
			SELECT
				".$db->quote($_POST['comment_id']).",
				".$db->quote($login_id)."
			WHERE NOT EXISTS (
				SELECT 1
				FROM Neuron.dbo.feedback_comment_likes
				WHERE comment_id = ".$db->quote($_POST['comment_id'])."
					AND login_id = ".$db->quote($login_id)."
			)
		");

		print json_encode(array(
			'success' => true
		));

		return;

	}

	if($_POST['action']=='unlike-comment'){

		// Like the comment.

		// Get the user information.
		global $session;
		$user = $session->login;
		$login_id = $user['login_id'];

		$db = DB::get();
		$db->query("
			DELETE FROM Neuron.dbo.feedback_comment_likes
			WHERE comment_id = ".$db->quote($_POST['comment_id'])."
				AND login_id = ".$db->quote($login_id)."
		");

		print json_encode(array(
			'success' => true
		));

		return;

	}

};

// The user's profile photo ID.
$user_photo_id = get_user_profile_photo();

// Get like counts.
$likes = get_likes();

// Get the items the logged in user has liked.
$user_likes = get_user_likes();

// Check to see if the user is admin-level.
$is_admin = get_is_admin();

// The default page-size.
$page_size = get_page_size();

// Get the current page.
$page = get_page();

// Get the tag ID.
$tag_id = get_tag_id();

// Get the type ID.
$type_id = get_type_id();

// Get the user ID.
$user_id = get_user_id();

// Get the total number of pages.
$pages = get_page_count($page_size);

// Get feedback.
$feedback = get_feedback($page, $page_size, $tag_id, $type_id, $user_id, $feedback_ids=null);

$all_feedback_ids = get_ids_from_feedback($feedback);

// Get the IDs of feedback submitted in the last X-days.
$recent_ids = get_recent_ids();

// Get the feedback IDs of the feedback the user is following.
$following_ids = get_following_ids();

// Get the feedback IDs of archived feedback.
$archived_ids = get_archived_ids();

// Get the feedback IDs of killed feedback.
$killed_ids = get_killed_ids();

// Get the feedback IDs of feedback that has been assigned.
$all_assigned_ids = get_all_assigned();

// Get the IDs of completed feedback.
$completed_ids = get_completed();

// Get the IDs of the started feedback.
$started_ids = get_started();

// Get the feedback IDs of the feedback assigned to the user.
$assigned_ids = get_assigned();

// Get the comments on the feedback.
$all_ids = array_merge($all_feedback_ids, $recent_ids, $following_ids, $archived_ids, $killed_ids, $all_assigned_ids, $completed_ids, $started_ids, $assigned_ids);
$comments = get_comments($all_ids);

// Give following IDs a default value to prevent everything from being returned
// as "followed".
if(!$following_ids){$following_ids = array(0);};

// The page size to use for other tabs.
$tmp_page_size = 1000;

// Get the actual feedback the user is following.
$following = get_feedback($page=1, $tmp_page_size, $tag_id=0, $type_id=0, $user_id=0, $feedback_ids=$following_ids);

// Give assigned IDs a default value to prevent everything from being returned
// as "assigned".
if(!$assigned_ids){$assigned_ids = array(0);};

// Ge the actual feedback the user is assigned.
$assigned = get_feedback($page=1, $tmp_page_size, $tag_id=0, $type_id=0, $user_id=0, $feedback_ids=$assigned_ids);

// Give completed IDs a default value to prevent everything from being returned
// as "completed".
if(!$completed_ids){$completed_ids = array(0);};

$completed = get_feedback($page=1, $tmp_page_size, $tag_id=0, $type_id=0, $user_id=0, $feedback_ids=$completed_ids);

// Give killed IDs a default value to prevent everything from being returned
// as "killed".
if(!$killed_ids){$killed_ids = array(0);};

$killed = get_feedback($page=1, $tmp_page_size, $tag_id=0, $type_id=0, $user_id=0, $feedback_ids=$killed_ids);

// Give recent IDs a default value to prevent everything from being returned
// as "recent".
if(!$recent_ids){$recent_ids = array(0);};

$recent = get_feedback($page=1, $tmp_page_size, $tag_id=0, $type_id=0, $user_id=0, $feedback_ids=$recent_ids);

// Get the tags for each feedback.
$tags = get_feedback_tags($feedback);

// Get all tags.
$all_tags = get_all_tags();

// Get all types.
$all_types = get_all_types();

// Get the feedback the user has previously voted on.
$user_votes = get_user_votes();

// Get the logo instead of a user avatar.
$avatar = get_avatar();

// Get users that have posted feedback.
$users = get_users();

Template::Render('header', $args, 'account');
?>

<style type="text/css">

	@font-face {
		font-family: 'noto';
		src: url('http://dev.maven.local/interface/fonts/NotoSans-Regular.ttf');
	}
	.noto {
		font-family: noto;
	}

	.date {
		position: relative;;
		//border: 1px solid yellow;
		padding-left: 5px;
		height:20px;
	}

	/* Pagination */
	#pagination-container {
		padding-top: 100px;
		height: 45px;
		text-align: center;
		font-size: 20px;
		font-color: black;
	}

	/* Comments */
	.comment-container {
		margin-left: 0px !important;
	}
	.comment-count {
		padding-top: 5px;
		padding-right: 10px;
		cursor: pointer;
	}

	/* Controls - Filtering/Sorting */
	#controls-container {
		border: 1px solid black;
		border-radius: 10px;
		height: 60px;
		width: 65%;
		margin-bottom: 50px;
	}
	#controls-container button {
		width: 70px;
	}
	#filter-form-container {
		padding-top: 15px;
		padding-left: 15px;
	}

	/* Tabs */
	#existing-tab {
		display: none;
	}
	#following-tab {
		display: none;
	}
	#assigned-tab {
		display: none;
	}
	#completed-tab {
		display:none;
	}
	#killed-tab {
		display: none;
	}

	/* Following */
	.follow-icon-container {
	}
	.follow-icon {
		cursor: pointer;
	}
	.following {
		color: #58b158;
	}

	/* Actions */
	.actions-container {
		//border: 1px solid green;
		position: relative;
		height: 85px;
		width: 180px !important;
	}
	.actions-1-container {
		position: relative;
		height: 50px;
		width: 100% !important;
		margin-left: 0px !important;
	}
	.actions-2-container {
		position: relative;
		height: 50px;
		width: 100% !important;
		margin-left: 0px !important;
	}
	.archived {
		color: #f99e1f;
	}
	.archive-icon {
		cursor: pointer;
	}
	.killed {
		color: #d44944;
	}
	.kill-icon {
		cursor: pointer;
	}
	.assigned {
		color: #58b158;
	}
	.assign-icon {
		cursor: pointer;
	}
	.completed {
		color: #58b158;
	}
	.completed-icon {
		cursor: pointer;
	}
	.started {
		color: #58b158;
	}
	.started-icon {
		cursor: pointer;
	}

	/********************************/

	/* TMP */
	#recent-tab div {
		//border: 1px solid gray;
	}

	/* NEW STYLE CSS */
	.main-container {
		//border: 1px solid blue;
		min-height: 50px;
		margin-bottom: 50px;
	}
	.feedback-container {
		//border: 1px solid green;
	}
	.content-container {
		padding-top: 10px;
		padding-left: 10px;
		padding-bottom: 10px;
	}

	.border-div {
		border: 1px solid;
		border-color: #00000030;
	}

	/* User Box */
	.avatar {
		border-radius: 50px;
		max-height: 50px;
		max-width: 50px;
	}
	.name-container {
		text-align: center;
	}
	.first-name-container {
		font-weight: bold;
	}
	.last-name-container {
		color: #0000008c;
	}

	/* Feedback Header */
	.header-container {
		align-items: center;
		display: flex;
	}
	.feedback-icon {
		padding-left: 5px;
	}
	.feedback-icon {
		cursor: pointer;
	}
	.date {
		color: #0000008c;
		font-size: small;
	}
	.body-container {
		min-height: 50px;
	}

	/* Votes */
	.votes-container {
		font-size: 25px;
	}
	.vote-up-container {
		cursor: pointer;
	}
	.vote-score-container {
		cursor: pointer
	}
	.vote-down-container {
		cursor: pointer;
	}

	/* Icons */
	.feedback-icon {
		color: #00000045;
		padding-left: 10px;
		font-size: 9px;
	}
	.following {
		color: black;
	}
	.archived {
		color: black;
	}
	.killed {
		color: black;
	}
	.assigned {
		color: black;
	}
	.completed {
		color: black;
	}
	.started {
		color: black;
	}

	/* Background Colors and Borders*/
	.gray-background {
		background-color: #edefef;
	}
	.btop {
		border-top: 1px solid #00000030;
	}
	.bleft {
		border-left: 1px solid #00000030;
	}
	.bright {
		border-right: 1px solid #00000030;
	}
	.bbottom {
		border-bottom: 1px solid #00000030;
	}

	/* Comments */
	.comments-header {
		font-size: small;
		padding-top: 10px;
	}
	.more-comments-container {
		color: blue;
		cursor: pointer;
	}
	.total-comments-container {
		color: blue;
		cursor: pointer;
	}
	.comment-content-container {
		padding-top: 30px;
		padding-left: 20px;
	}
	.comment-body-container {
		min-height: 50px;
	}
	.comment-date-container {
		color: #0000008c;
	}
	.new-comment-contents-container {
		display: flex !important;
		align-items: center !important;
		min-height: 100px !important;
	}
	.new-comment-input-container {
		padding-top: 10px;
		padding-left: 20px;
		padding-right: 20px;
	}
	.all-comment {
		display:none;
	}

	/* Votes */
	.vote-up-container.voted {
		color: #3fc317;
	}
	.vote-down-container.voted {
		color: #e44141;
	}
	.score-indicator {
		display:none;
	}
	.score-divider {
		border: 1px solid #c0c0c0;
		width: 24%;
		position: relative;
		left: 36%; 
		margin-top: 5px;
		margin-bottom: 5px;
		display:none;
	}

	/* Likes */
	.likes-container {
		cursor: pointer;
	}
	.comment-likes-container{
		cursor: pointer;
	}
	.liked {
		color: #4267b2;
	}
	.like-count {
		padding-left: 5px;zz
	}

	#sort-form-submit {
		margin-right: 15px;
	}
	.status-bar {
		min-height: 20px !important;
		max-height: 20px !important;
	}
	.status-bar.green {
		background-color: green;
	}
	.status-bar.black {
		background-color: black;
	}
	.status-bar.cd {
		background-color: #fed105;
	}
	.status-bar.doro {
		background-color: red;
	}
	.user-container {
		padding-top: 20px;
	}
	.status-label {
		padding-left: 10px;
		color: white;
		text-align: center;
	}

</style>

<div id="page-nav">
	<h2>Feedback</h2>

	<ul class="nav nav-tabs">
		<li class="nav-item active" data-target="recent-tab">
 			<?php
				// Count the number of followed feedbacks.
				$recent_count = count($recent_ids);
				if($recent_count>0){
					$label = ' ('.$recent_count.')';
				}else{
					$label = '';
				};
			?>
			<a class="nav-link" href="#">Recent<?php print htmlentities($label) ?></a>
		</li>
		<li class="nav-item" data-target="existing-tab">
			<a class="nav-link" href="#">All Feedback</a>
		</li>
		<li class="nav-item" data-target="following-tab">
			<?php
				// Count the number of followed feedbacks.
				$following_count = count($following);
				if($following_count>0){
					$label = ' ('.$following_count.')';
				}else{
					$label = '';
				};
			?>
			<a class="nav-link" href="#">Following<?php print htmlentities($label) ?></a>
		</li>
		<li class="nav-item" data-target="assigned-tab">
			<?php
				// Count the number of followed feedbacks.
				$assigned_count = count($assigned);
				if($assigned_count>0){
					$label = ' ('.$assigned_count.')';
				}else{
					$label = '';
				};
			?>
			<a class="nav-link" href="#">Assigned<?php print htmlentities($label) ?></a>
		</li>
		<li class="nav-item" data-target="completed-tab">
 			<?php
				// Count the number of followed feedbacks.
				$completed_count = count($completed);
				if($completed_count>0){
					$label = ' ('.$completed_count.')';
				}else{
					$label = '';
				};
			?>
			<a class="nav-link" href="#">Completed<?php print htmlentities($label) ?></a>
		</li>
		<li class="nav-item" data-target="killed-tab">
 			<?php
				// Count the number of followed feedbacks.
				$killedcount = count($killed);
				if($killedcount>0){
					$label = ' ('.$killedcount.')';
				}else{
					$label = '';
				};
			?>
			<a class="nav-link" href="#">Killed<?php print htmlentities($label) ?></a>
		</li>
	</ul>
</div>

	<!-- Start New-Style Tab -->

	<div id="recent-tab" class="tab-target container-fluid">
		
		<?php foreach($recent as $row){

			// Highlight following.
			if(in_array($row['feedback_id'], $following_ids)){
				$is_following = 'following';
			}else{
				$is_following = '';
			}

			// Highlight archived.
			if(in_array($row['feedback_id'], $archived_ids)){
				$is_archived = 'archived';
			}else{
				$is_archived = '';
			}

			// Highlight killed.
			if(in_array($row['feedback_id'], $killed_ids)){
				$is_killed = 'killed';
			}else{
				$is_killed = '';
			}

			// Highlght assigned.
			if(in_array($row['feedback_id'], $all_assigned_ids)){
				$is_assigned = 'assigned';
			}else{
				$is_assigned = '';
			}

			// Highlight completed.
			if(in_array($row['feedback_id'], $completed_ids)){
				$is_completed = 'completed';
			}else{
				$is_completed = '';
			}

			// Highlight started.
			if(in_array($row['feedback_id'], $started_ids)){
				$is_started = 'started';
			}else{
				$is_started = '';
			}

		?>
			<div class="main-container container-fluid" data-feedback-id="<?php print htmlentities($row['feedback_id'])?>">
				<!-- Feedback Container -->
				<div class="feedback-container row-fluid">

					<!-- User -->
					<div class="user-container span1">
						<?php
							// Get the profile photo ID.
							$photo_id = htmlentities($row['profile_photo_id']);
						?>

						<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
							<img class="avatar" src="<?php print htmlentities($avatar) ?>">
						</div>
						<div class="name-container">
							<div class="first-name-container"><?php print htmlentities($row['first_name']) ?></div>
							<div class="last-name-container"><?php print htmlentities($row['last_name']) ?></div>
						</div>
					</div>
					<!-- Status Bar -->
					<?php
						// Get the proper status color

						$status = '';
						$text = '';
						if($is_killed!=''){
							$status = 'black';
							$text = 'Killed';
						}elseif ($is_completed!='') {
							$statuses = array(
								1 => 'cd',
								2 => 'dorodo'
							);
							$status = $statuses[COMPANY];
							$text = 'Completed';
						}elseif ($is_assigned!='') {
							$status = 'green';
							$assignee = get_assignee($row['assignee']);
							$text = 'Assigned '.' - '. $assignee .' - '.$row['projected_completion_date'];
						}

					?>
					<div class="status-bar <?php print htmlentities($status) ?> span11">
						<div class="status-label noto"><?php print htmlentities($text) ?></div>
					</div>

					<div class="border-div span11">
						

						<!-- Header -->
						<div class="content-container span11">
							<div class="header-container row-fluid">
								<div class="subject-container pull-left">
									<div class="subject"><h3><?php print htmlentities($row['subject']) ?></h3></div>
								</div>
								<div class="icon-container span2">
									<div class="feedback-icon follow-icon-container <?php print htmlentities($is_following) ?> pull-left">
										<i class="fa fa-star fa-2x follow-icon"></i>
									</div>
									<div class="feedback-icon assign-icon-container pull-left <?php print htmlentities($is_assigned)?>">
										<i class="fa fa-user fa-2x assign-icon"></i>
									</div>
									<div class="feedback-icon pull-left kill-icon-container <?php print htmlentities($is_killed) ?>">
										<i class="fa fa-times-circle fa-2x kill-icon"></i>
									</div>
									<div class="feedback-icon completed-icon-container pull-left <?php print htmlentities($is_completed) ?>">
										<i class="fa fa-trophy fa-2x completed-icon"></i>
									</div>
								</div>
								<div class="date-container span2 offset1">
									<div class="date"><?php print htmlentities($row['submitted_on']) ?></div>
								</div>
							</div>

							<!-- Body -->
							<div class="body-container row-fluid">
								<p><?php print htmlentities($row['memo']) ?></p>
							</div>

							<!-- Footer -->
							<?php
								// Get the like-count.
								$feedback_id = $row['feedback_id'];
								$flikes = $likes['feedback'];
								$count = '0';
								if(array_key_exists($feedback_id, $flikes)){
									$count = $flikes[$feedback_id];
								};

								// If the user liked the feedback, highlight it.
								$ulikes = $user_likes['feedback'];
								$liked = '';
								if(in_array($feedback_id, $ulikes)){
									$liked = 'liked';
								}

							?>
							<div class="footer-container row-fluid">
								<div class="likes-container <?php print htmlentities($liked) ?>">
									<i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span>
								</div>
							</div>
						</div>

						<!-- Votes -->
						<?php
							// Check if the user has voted on this feedback and
							// in which direction.
							$voted_up = '';
							$voted_down = '';
							if(array_key_exists($row['feedback_id'], $user_votes)){
								$direction = $user_votes[$row['feedback_id']];
								if($direction=='up'){
									$voted_up = 'voted';
								}
								if ($direction=='down') {
									$voted_down = 'voted';
								}
							}
						?>
						<div class="votes-container span1 text-center">
							<div class="vote vote-up-container <?php print htmlentities($voted_up)?>" data-vote-direction="up">
								<i class="fa fa-caret-up fa-2x"></i>
							</div>
							<div class="vote-score-container">

								<div class="score-indicator vote-score-upvotes"><?php print htmlentities($row['votes_up']) ?></div>
								<div class="score-divider"></div>
								<div class="score-indicator vote-score-downvotes"><?php print htmlentities($row['votes_down']) ?></div>

								<div class="vote-score"><?php print htmlentities($row['votes_score']) ?></div>

							</div>
							<div class="vote vote-down-container <?php print htmlentities($voted_down)?>" data-vote-direction="down">
								<i class="fa fa-caret-down fa-2x"></i>
							</div>
						</div>
					</div>
				</div>

				<!-- Comments -->
				<div class="comments-container">
					<div class="comments-header row-fluid">
						<?php
							$more_comments = '';
							$num_comments = '';
							if($row['comment_count']>0){
								$more_comments = 'More comments';
								$num_comments = '1 of '.$row['comment_count'];
							}
						?>
						<div class="more-comments-container span2 offset1 pull-left"><?php print htmlentities($more_comments) ?></div>
						<div class="total-comments-container span1 offset8 pull-left text-center"><?php print htmlentities($num_comments) ?></div>
					</div>

					<?php
						foreach($comments[$row['feedback_id']] as $comment){

							// Get the like-count.
							$comment_id = $comment['comment_id'];
							$flikes = $likes['comments'];
							$count = '0';
							if(array_key_exists($comment_id, $flikes)){
								$count = $flikes[$comment_id];
							};

							// If the user liked the feedback, highlight it.
							$ulikes = $user_likes['comments'];
							$liked = '';
							if(in_array($comment_id, $ulikes)){
								$liked = 'liked';
							}

							?>

							<div class="comment-container row-fluid all-comment" data-comment-id="<?php print htmlentities($comment['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($comment['first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($comment['last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($comment['comment']) ?></p>
									</div>
									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($comment['created_on']) ?></div>
									</div>									
								</div>


							</div>

							<?php
						};
					?>

					<!-- Only show an existing comment if it exists. -->
					<?php
						if($row['comment_id']){
						?>
							<div class="comment-container row-fluid latest-comment" data-comment-id="<?php print htmlentities($row['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($row['comment_first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($row['comment_last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($row['comment']) ?></p>
									</div>

									<?php
										// Get the like-count.
										$comment_id = $row['comment_id'];
										$flikes = $likes['comments'];
										$count = '0';
										if(array_key_exists($comment_id, $flikes)){
											$count = $flikes[$comment_id];
										};

										// If the user liked the feedback, highlight it.
										$ulikes = $user_likes['comments'];
										$liked = '';
										if(in_array($comment_id, $ulikes)){
											$liked = 'liked';
										}
									?>

									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($row['comment_date']) ?></div>
									</div>
								</div>


							</div>
						<?php
						}
					?>
					<div class="new-comment-container row-fluid">
						<?php
							// Get logged-in user's name and avatar.
							global $session;
							$login = $session->login;
							$first_name = $login['first_name'];
							$last_name = $login['last_name'];
							$avatar = $avatar;
						?>
						<div class="user-container span1">
							<?php
								// Get the profile photo ID.
								$photo_id = htmlentities($user_photo_id);
							?>

							<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
								<img class="avatar" src="<?php print htmlentities($avatar) ?>">
							</div>
							<div class="name-container">
								<div class="first-name-container"><?php print htmlentities($first_name) ?></div>
								<div class="last-name-container"><?php print htmlentities($last_name) ?></div>
							</div>
						</div>
						<?php
							// Add a top-border if there are not comments.
							if($row['comment_count']>0){
								$btop = '';
							}else{
								$btop = 'btop';
							};
						?>
						<div class="new-comment-contents-container gray-background bbottom bleft bright <?php print htmlentities($btop) ?> span11">
							<?php
								// Get the logged in user's login ID.
								global $session;
								$login = $session->login;
								$login_id = $login['login_id'];
							?>
							<input class="login-id-input" type="hidden" value="<?php print htmlentities($login_id) ?>">
							<div class="new-comment-input-container row-fluid">
								<input class="new-comment-input span12" type="text" name="new-comment" placeholder="Post a comment">
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php
		} ?>

	</div>






	<!-- ************************************************************** -->
	<!-- The tabs below here should be updated to reflect the tab above -->
	<!-- ************************************************************** -->


	<div id="existing-tab" class="tab-target">

		<div id="controls-container" class="row-fluid">
			<div id="filter-form-container">
				<form id="filter-form" class="form-inline pull-left">
					<select id="feedback-type-select" class="control-select">
						<option value="">Select Type</option>
						<?php foreach($all_types as $type){
							?>
							<option value="<?php print htmlentities($type['type_id']) ?>"><?php print htmlentities($type['type']) ?></option>
							<?php
						} ?>
					</select>
					<!-- TODO: Uncomment this whn tags are supported.
						<select id="feedback-tag-select" class="control-select">
							<option value="">Select Tag</option>
							<?php foreach($all_tags as $tag){
								?>
								<option value="<?php print htmlentities($tag['tag_id']) ?>"><?php print htmlentities($tag['tag']) ?></option>
								<?php
							} ?>
						</select>
					-->
					<!--
						<select id="feedback-user-select" class="control-select">
							<option value="">Select User</option>
							<?php foreach($users as $user){
								?>
								<option value="<?php print htmlentities($user['login_id']) ?>"><?php print htmlentities($user['first_name'] .' '. $user['last_name']) ?></option>
								<?php
							} ?>
						</select>
					-->
					<button id="filter-form-submit" class="btn">Filter</button>
				</form>
				<form id="sort-form" class="form-inline pull-right">
					<select id="sort-user-select">
						<option value="">Sort by User</option>
						<option value="asc">Ascending</option>
						<option value="desc">Descending</option>
					</select>
					<select id="sort-date-select">
						<option value="">Sort by Date</option>
						<option value="asc">Ascending</option>
						<option value="desc">Descending</option>
					</select>
					<button id="sort-form-submit" class="btn">Sort</button>
				</form>
			</div>
		</div>

		<?php foreach($feedback as $row){

			// Highlight following.
			if(in_array($row['feedback_id'], $following_ids)){
				$is_following = 'following';
			}else{
				$is_following = '';
			}

			// Highlight archived.
			if(in_array($row['feedback_id'], $archived_ids)){
				$is_archived = 'archived';
			}else{
				$is_archived = '';
			}

			// Highlight killed.
			if(in_array($row['feedback_id'], $killed_ids)){
				$is_killed = 'killed';
			}else{
				$is_killed = '';
			}

			// Highlght assigned.
			if(in_array($row['feedback_id'], $all_assigned_ids)){
				$is_assigned = 'assigned';
			}else{
				$is_assigned = '';
			}

			// Highlight completed.
			if(in_array($row['feedback_id'], $completed_ids)){
				$is_completed = 'completed';
			}else{
				$is_completed = '';
			}

			// Highlight started.
			if(in_array($row['feedback_id'], $started_ids)){
				$is_started = 'started';
			}else{
				$is_started = '';
			}

		?>
			<div class="main-container container-fluid" data-feedback-id="<?php print htmlentities($row['feedback_id'])?>">
				<!-- Feedback Container -->
				<div class="feedback-container row-fluid">

					<!-- User -->
					<div class="user-container span1">
						<?php
							// Get the profile photo ID.
							$photo_id = htmlentities($row['profile_photo_id']);
						?>

						<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
							<img class="avatar" src="<?php print htmlentities($avatar) ?>">
						</div>
						<div class="name-container">
							<div class="first-name-container"><?php print htmlentities($row['first_name']) ?></div>
							<div class="last-name-container"><?php print htmlentities($row['last_name']) ?></div>
						</div>
					</div>

					<!-- Status Bar -->
					<?php
						// Get the proper status color

						$status = '';
						$text = '';
						if($is_killed!=''){
							$status = 'black';
							$text = 'Killed';
						}elseif ($is_completed!='') {
							$statuses = array(
								1 => 'cd',
								2 => 'dorodo'
							);
							$status = $statuses[COMPANY];
							$text = 'Completed';
						}elseif ($is_assigned!='') {
							$status = 'green';
							$assignee = get_assignee($row['assignee']);
							$text = 'Assigned '.' - '. $assignee .' - '.$row['projected_completion_date'];
						}

					?>
					<div class="status-bar <?php print htmlentities($status) ?> span11">
						<div class="status-label noto"><?php print htmlentities($text) ?></div>
					</div>
					<div class="border-div span11">

						<!-- Header -->
						<div class="content-container span11">



							<div class="header-container row-fluid">
								<div class="subject-container pull-left">
									<div class="subject"><h3><?php print htmlentities($row['subject']) ?></h3></div>
								</div>
								<div class="icon-container span2">
									<div class="feedback-icon follow-icon-container <?php print htmlentities($is_following) ?> pull-left">
										<i class="fa fa-star fa-2x follow-icon"></i>
									</div>
									<div class="feedback-icon assign-icon-container pull-left <?php print htmlentities($is_assigned)?>">
										<i class="fa fa-user fa-2x assign-icon"></i>
									</div>
									<div class="feedback-icon pull-left kill-icon-container <?php print htmlentities($is_killed) ?>">
										<i class="fa fa-times-circle fa-2x kill-icon"></i>
									</div>
									<div class="feedback-icon completed-icon-container pull-left <?php print htmlentities($is_completed) ?>">
										<i class="fa fa-trophy fa-2x completed-icon"></i>
									</div>
								</div>
								<div class="date-container span2 offset1">
									<div class="date"><?php print htmlentities($row['submitted_on']) ?></div>
								</div>
							</div>

							<!-- Body -->
							<div class="body-container row-fluid">
								<p><?php print htmlentities($row['memo']) ?></p>
							</div>

							<!-- Footer -->
							<?php
								// Get the like-count.
								$feedback_id = $row['feedback_id'];
								$flikes = $likes['feedback'];
								$count = '0';
								if(array_key_exists($feedback_id, $flikes)){
									$count = $flikes[$feedback_id];
								};

								// If the user liked the feedback, highlight it.
								$ulikes = $user_likes['feedback'];
								$liked = '';
								if(in_array($feedback_id, $ulikes)){
									$liked = 'liked';
								}

							?>
							<div class="footer-container row-fluid">
								<div class="likes-container <?php print htmlentities($liked) ?>">
									<i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span>
								</div>
							</div>
						</div>

						<!-- Votes -->
						<?php
							// Check if the user has voted on this feedback and
							// in which direction.
							$voted_up = '';
							$voted_down = '';
							if(array_key_exists($row['feedback_id'], $user_votes)){
								$direction = $user_votes[$row['feedback_id']];
								if($direction=='up'){
									$voted_up = 'voted';
								}
								if ($direction=='down') {
									$voted_down = 'voted';
								}
							}
						?>
						<div class="votes-container span1 text-center">
							<div class="vote vote-up-container <?php print htmlentities($voted_up)?>" data-vote-direction="up">
								<i class="fa fa-caret-up fa-2x"></i>
							</div>
							<div class="vote-score-container">

								<div class="score-indicator vote-score-upvotes"><?php print htmlentities($row['votes_up']) ?></div>
								<div class="score-divider"></div>
								<div class="score-indicator vote-score-downvotes"><?php print htmlentities($row['votes_down']) ?></div>

								<div class="vote-score"><?php print htmlentities($row['votes_score']) ?></div>

							</div>
							<div class="vote vote-down-container <?php print htmlentities($voted_down)?>" data-vote-direction="down">
								<i class="fa fa-caret-down fa-2x"></i>
							</div>
						</div>
					</div>
				</div>

				<!-- Comments -->
				<div class="comments-container">
					<div class="comments-header row-fluid">
						<div class="more-comments-container span2 offset1 pull-left">More comments</div>
						<div class="total-comments-container span1 offset8 pull-left text-center">1 of <?php print htmlentities($row['comment_count']) ?></div>
					</div>

					<?php
						foreach($comments[$row['feedback_id']] as $comment){

							// Get the like-count.
							$comment_id = $comment['comment_id'];
							$flikes = $likes['comments'];
							$count = '0';
							if(array_key_exists($comment_id, $flikes)){
								$count = $flikes[$comment_id];
							};

							// If the user liked the feedback, highlight it.
							$ulikes = $user_likes['comments'];
							$liked = '';
							if(in_array($comment_id, $ulikes)){
								$liked = 'liked';
							}

							?>

							<div class="comment-container row-fluid all-comment" data-comment-id="<?php print htmlentities($comment['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($comment['first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($comment['last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($comment['comment']) ?></p>
									</div>
									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($comment['created_on']) ?></div>
									</div>									
								</div>


							</div>

							<?php
						};
					?>

					<!-- Only show an existing comment if it exists. -->
					<?php
						if($row['comment_id']){
						?>
							<div class="comment-container row-fluid latest-comment" data-comment-id="<?php print htmlentities($row['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($row['comment_first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($row['comment_last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($row['comment']) ?></p>
									</div>

									<?php
										// Get the like-count.
										$comment_id = $row['comment_id'];
										$flikes = $likes['comments'];
										$count = '0';
										if(array_key_exists($comment_id, $flikes)){
											$count = $flikes[$comment_id];
										};

										// If the user liked the feedback, highlight it.
										$ulikes = $user_likes['comments'];
										$liked = '';
										if(in_array($comment_id, $ulikes)){
											$liked = 'liked';
										}
									?>

									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($row['comment_date']) ?></div>
									</div>
								</div>


							</div>
						<?php
						}
					?>
					<div class="new-comment-container row-fluid">
						<?php
							// Get logged-in user's name and avatar.
							global $session;
							$login = $session->login;
							$first_name = $login['first_name'];
							$last_name = $login['last_name'];
							$avatar = $avatar;
						?>
						<div class="user-container span1">
								<?php
									// Get the profile photo ID.
									$photo_id = htmlentities($user_photo_id);
								?>

								<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
								<img class="avatar" src="<?php print htmlentities($avatar) ?>">
							</div>
							<div class="name-container">
								<div class="first-name-container"><?php print htmlentities($first_name) ?></div>
								<div class="last-name-container"><?php print htmlentities($last_name) ?></div>
							</div>
						</div>
						<?php
							// Add a top-border if there are not comments.
							if($row['comment_count']>0){
								$btop = '';
							}else{
								$btop = 'btop';
							};
						?>
						<div class="new-comment-contents-container gray-background bbottom bleft bright <?php print htmlentities($btop) ?> span11">
							<?php
								// Get the logged in user's login ID.
								global $session;
								$login = $session->login;
								$login_id = $login['login_id'];
							?>
							<input class="login-id-input" type="hidden" value="<?php print htmlentities($login_id) ?>">
							<div class="new-comment-input-container row-fluid">
								<input class="new-comment-input span12" type="text" name="new-comment" placeholder="Post a comment">
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php
		} ?>

		<div id="pagination-container" class="pagination">
			<ul>
				<?php

					$prev = $page-1;
					if($prev <=0){
						$prev = 1;
					};
					$href = '?page='.$prev.'&page-size='.$page_size;
				?>
				<li><a href="<?php print htmlentities($href) ?>"><i class="fa fa-chevron-left"></i></a></li>
				<?php foreach(range(1,$pages) as $pagenum){
				?>
					<?php

						// Add page values.
						$href = '?page='.$pagenum.'&page-size='.$page_size;

						// Add user-selections.
						if($type_id!=0){$href.="&type-id=".$type_id;};
						if($user_id!=0){$href.="&user-id=".$user_id;};
					?>
					<li><a href="<?php print htmlentities($href) ?>"><?php print htmlentities($pagenum) ?></a></li>
				<?php
				}?>
				<?php
					$next = $page+1;
					if($next > $pages){
						$next = (int)$pages;
					};
					$href = '?page='.$next.'&page-size='.$page_size;
				?>
				<li><a href="<?php print htmlentities($href) ?>"><i class="fa fa-chevron-right"></i></a></li>
			</ul>
		</div>

	</div>
	<div id="following-tab" class="tab-target">

		<?php foreach($following as $row){

			// Highlight following.
			if(in_array($row['feedback_id'], $following_ids)){
				$is_following = 'following';
			}else{
				$is_following = '';
			}

			// Highlight archived.
			if(in_array($row['feedback_id'], $archived_ids)){
				$is_archived = 'archived';
			}else{
				$is_archived = '';
			}

			// Highlight killed.
			if(in_array($row['feedback_id'], $killed_ids)){
				$is_killed = 'killed';
			}else{
				$is_killed = '';
			}

			// Highlght assigned.
			if(in_array($row['feedback_id'], $all_assigned_ids)){
				$is_assigned = 'assigned';
			}else{
				$is_assigned = '';
			}

			// Highlight completed.
			if(in_array($row['feedback_id'], $completed_ids)){
				$is_completed = 'completed';
			}else{
				$is_completed = '';
			}

			// Highlight started.
			if(in_array($row['feedback_id'], $started_ids)){
				$is_started = 'started';
			}else{
				$is_started = '';
			}

		?>
			<div class="main-container container-fluid" data-feedback-id="<?php print htmlentities($row['feedback_id'])?>">
				<!-- Feedback Container -->
				<div class="feedback-container row-fluid">

					<!-- User -->
					<div class="user-container span1">
						<?php
							// Get the profile photo ID.
							$photo_id = htmlentities($row['profile_photo_id']);
						?>

						<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
							<img class="avatar" src="<?php print htmlentities($avatar) ?>">
						</div>
						<div class="name-container">
							<div class="first-name-container"><?php print htmlentities($row['first_name']) ?></div>
							<div class="last-name-container"><?php print htmlentities($row['last_name']) ?></div>
						</div>
					</div>
					<!-- Status Bar -->
					<?php
						// Get the proper status color

						$status = '';
						$text = '';
						if($is_killed!=''){
							$status = 'black';
							$text = 'Killed';
						}elseif ($is_completed!='') {
							$statuses = array(
								1 => 'cd',
								2 => 'dorodo'
							);
							$status = $statuses[COMPANY];
							$text = 'Completed';
						}elseif ($is_assigned!='') {
							$status = 'green';
							$assignee = get_assignee($row['assignee']);
							$text = 'Assigned '.' - '. $assignee .' - '.$row['projected_completion_date'];
						}

					?>
					<div class="status-bar <?php print htmlentities($status) ?> span11">
						<div class="status-label noto"><?php print htmlentities($text) ?></div>
					</div>

					<div class="border-div span11">
						

						<!-- Header -->
						<div class="content-container span11">
							<div class="header-container row-fluid">
								<div class="subject-container pull-left">
									<div class="subject"><h3><?php print htmlentities($row['subject']) ?></h3></div>
								</div>
								<div class="icon-container span2">
									<div class="feedback-icon follow-icon-container <?php print htmlentities($is_following) ?> pull-left">
										<i class="fa fa-star fa-2x follow-icon"></i>
									</div>
									<div class="feedback-icon assign-icon-container pull-left <?php print htmlentities($is_assigned)?>">
										<i class="fa fa-user fa-2x assign-icon"></i>
									</div>
									<div class="feedback-icon pull-left kill-icon-container <?php print htmlentities($is_killed) ?>">
										<i class="fa fa-times-circle fa-2x kill-icon"></i>
									</div>
									<div class="feedback-icon completed-icon-container pull-left <?php print htmlentities($is_completed) ?>">
										<i class="fa fa-trophy fa-2x completed-icon"></i>
									</div>
								</div>
								<div class="date-container span2 offset1">
									<div class="date"><?php print htmlentities($row['submitted_on']) ?></div>
								</div>
							</div>

							<!-- Body -->
							<div class="body-container row-fluid">
								<p><?php print htmlentities($row['memo']) ?></p>
							</div>

							<!-- Footer -->
							<?php
								// Get the like-count.
								$feedback_id = $row['feedback_id'];
								$flikes = $likes['feedback'];
								$count = '0';
								if(array_key_exists($feedback_id, $flikes)){
									$count = $flikes[$feedback_id];
								};

								// If the user liked the feedback, highlight it.
								$ulikes = $user_likes['feedback'];
								$liked = '';
								if(in_array($feedback_id, $ulikes)){
									$liked = 'liked';
								}

							?>
							<div class="footer-container row-fluid">
								<div class="likes-container <?php print htmlentities($liked) ?>">
									<i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span>
								</div>
							</div>
						</div>

						<!-- Votes -->
						<?php
							// Check if the user has voted on this feedback and
							// in which direction.
							$voted_up = '';
							$voted_down = '';
							if(array_key_exists($row['feedback_id'], $user_votes)){
								$direction = $user_votes[$row['feedback_id']];
								if($direction=='up'){
									$voted_up = 'voted';
								}
								if ($direction=='down') {
									$voted_down = 'voted';
								}
							}
						?>
						<div class="votes-container span1 text-center">
							<div class="vote vote-up-container <?php print htmlentities($voted_up)?>" data-vote-direction="up">
								<i class="fa fa-caret-up fa-2x"></i>
							</div>
							<div class="vote-score-container">

								<div class="score-indicator vote-score-upvotes"><?php print htmlentities($row['votes_up']) ?></div>
								<div class="score-divider"></div>
								<div class="score-indicator vote-score-downvotes"><?php print htmlentities($row['votes_down']) ?></div>

								<div class="vote-score"><?php print htmlentities($row['votes_score']) ?></div>

							</div>
							<div class="vote vote-down-container <?php print htmlentities($voted_down)?>" data-vote-direction="down">
								<i class="fa fa-caret-down fa-2x"></i>
							</div>
						</div>
					</div>
				</div>

				<!-- Comments -->
				<div class="comments-container">
					<div class="comments-header row-fluid">
						<div class="more-comments-container span2 offset1 pull-left">More comments</div>
						<div class="total-comments-container span1 offset8 pull-left text-center">1 of <?php print htmlentities($row['comment_count']) ?></div>
					</div>

					<?php
						foreach($comments[$row['feedback_id']] as $comment){

							// Get the like-count.
							$comment_id = $comment['comment_id'];
							$flikes = $likes['comments'];
							$count = '0';
							if(array_key_exists($comment_id, $flikes)){
								$count = $flikes[$comment_id];
							};

							// If the user liked the feedback, highlight it.
							$ulikes = $user_likes['comments'];
							$liked = '';
							if(in_array($comment_id, $ulikes)){
								$liked = 'liked';
							}

							?>

							<div class="comment-container row-fluid all-comment" data-comment-id="<?php print htmlentities($comment['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($comment['first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($comment['last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($comment['comment']) ?></p>
									</div>
									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($comment['created_on']) ?></div>
									</div>									
								</div>


							</div>

							<?php
						};
					?>

					<!-- Only show an existing comment if it exists. -->
					<?php
						if($row['comment_id']){
						?>
							<div class="comment-container row-fluid latest-comment" data-comment-id="<?php print htmlentities($row['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($row['comment_first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($row['comment_last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($row['comment']) ?></p>
									</div>

									<?php
										// Get the like-count.
										$comment_id = $row['comment_id'];
										$flikes = $likes['comments'];
										$count = '0';
										if(array_key_exists($comment_id, $flikes)){
											$count = $flikes[$comment_id];
										};

										// If the user liked the feedback, highlight it.
										$ulikes = $user_likes['comments'];
										$liked = '';
										if(in_array($comment_id, $ulikes)){
											$liked = 'liked';
										}
									?>

									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($row['comment_date']) ?></div>
									</div>
								</div>


							</div>
						<?php
						}
					?>
					<div class="new-comment-container row-fluid">
						<?php
							// Get logged-in user's name and avatar.
							global $session;
							$login = $session->login;
							$first_name = $login['first_name'];
							$last_name = $login['last_name'];
							$avatar = $avatar;
						?>
						<div class="user-container span1">
							<?php
								// Get the profile photo ID.
								$photo_id = htmlentities($user_photo_id);
							?>

							<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
								<img class="avatar" src="<?php print htmlentities($avatar) ?>">
							</div>
							<div class="name-container">
								<div class="first-name-container"><?php print htmlentities($first_name) ?></div>
								<div class="last-name-container"><?php print htmlentities($last_name) ?></div>
							</div>
						</div>
						<?php
							// Add a top-border if there are not comments.
							if($row['comment_count']>0){
								$btop = '';
							}else{
								$btop = 'btop';
							};
						?>
						<div class="new-comment-contents-container gray-background bbottom bleft bright <?php print htmlentities($btop) ?> span11">
							<?php
								// Get the logged in user's login ID.
								global $session;
								$login = $session->login;
								$login_id = $login['login_id'];
							?>
							<input class="login-id-input" type="hidden" value="<?php print htmlentities($login_id) ?>">
							<div class="new-comment-input-container row-fluid">
								<input class="new-comment-input span12" type="text" name="new-comment" placeholder="Post a comment">
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php
		} ?>

	</div>
	<div id="assigned-tab" class="tab-target">


		<?php
			if(count($assigned_ids) == 0){
				?>
					<div id="" cla
				<?php
			}
		?>

		<?php foreach($assigned as $row){

			// Highlight following.
			if(in_array($row['feedback_id'], $following_ids)){
				$is_following = 'following';
			}else{
				$is_following = '';
			}

			// Highlight archived.
			if(in_array($row['feedback_id'], $archived_ids)){
				$is_archived = 'archived';
			}else{
				$is_archived = '';
			}

			// Highlight killed.
			if(in_array($row['feedback_id'], $killed_ids)){
				$is_killed = 'killed';
			}else{
				$is_killed = '';
			}

			// Highlght assigned.
			if(in_array($row['feedback_id'], $all_assigned_ids)){
				$is_assigned = 'assigned';
			}else{
				$is_assigned = '';
			}

			// Highlight completed.
			if(in_array($row['feedback_id'], $completed_ids)){
				$is_completed = 'completed';
			}else{
				$is_completed = '';
			}

			// Highlight started.
			if(in_array($row['feedback_id'], $started_ids)){
				$is_started = 'started';
			}else{
				$is_started = '';
			}

		?>
			<div class="main-container container-fluid" data-feedback-id="<?php print htmlentities($row['feedback_id'])?>">
				<!-- Feedback Container -->
				<div class="feedback-container row-fluid">

					<!-- User -->
					<div class="user-container span1">
						<?php
							// Get the profile photo ID.
							$photo_id = htmlentities($row['profile_photo_id']);
						?>

						<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
							<img class="avatar" src="<?php print htmlentities($avatar) ?>">
						</div>
						<div class="name-container">
							<div class="first-name-container"><?php print htmlentities($row['first_name']) ?></div>
							<div class="last-name-container"><?php print htmlentities($row['last_name']) ?></div>
						</div>
					</div>
					<!-- Status Bar -->
					<?php
						// Get the proper status color

						$status = '';
						$text = '';
						if($is_killed!=''){
							$status = 'black';
							$text = 'Killed';
						}elseif ($is_completed!='') {
							$statuses = array(
								1 => 'cd',
								2 => 'dorodo'
							);
							$status = $statuses[COMPANY];
							$text = 'Completed';
						}elseif ($is_assigned!='') {
							$status = 'green';
							$assignee = get_assignee($row['assignee']);
							$text = 'Assigned '.' - '. $assignee .' - '.$row['projected_completion_date'];
						}

					?>
					<div class="status-bar <?php print htmlentities($status) ?> span11">
						<div class="status-label noto"><?php print htmlentities($text) ?></div>
					</div>

					<div class="border-div span11">
						

						<!-- Header -->
						<div class="content-container span11">
							<div class="header-container row-fluid">
								<div class="subject-container pull-left">
									<div class="subject"><h3><?php print htmlentities($row['subject']) ?></h3></div>
								</div>
								<div class="icon-container span2">
									<div class="feedback-icon follow-icon-container <?php print htmlentities($is_following) ?> pull-left">
										<i class="fa fa-star fa-2x follow-icon"></i>
									</div>
									<div class="feedback-icon assign-icon-container pull-left <?php print htmlentities($is_assigned)?>">
										<i class="fa fa-user fa-2x assign-icon"></i>
									</div>
									<div class="feedback-icon pull-left kill-icon-container <?php print htmlentities($is_killed) ?>">
										<i class="fa fa-times-circle fa-2x kill-icon"></i>
									</div>
									<div class="feedback-icon completed-icon-container pull-left <?php print htmlentities($is_completed) ?>">
										<i class="fa fa-trophy fa-2x completed-icon"></i>
									</div>
								</div>
								<div class="date-container span2 offset1">
									<div class="date"><?php print htmlentities($row['submitted_on']) ?></div>
								</div>
							</div>

							<!-- Body -->
							<div class="body-container row-fluid">
								<p><?php print htmlentities($row['memo']) ?></p>
							</div>

							<!-- Footer -->
							<?php
								// Get the like-count.
								$feedback_id = $row['feedback_id'];
								$flikes = $likes['feedback'];
								$count = '0';
								if(array_key_exists($feedback_id, $flikes)){
									$count = $flikes[$feedback_id];
								};

								// If the user liked the feedback, highlight it.
								$ulikes = $user_likes['feedback'];
								$liked = '';
								if(in_array($feedback_id, $ulikes)){
									$liked = 'liked';
								}

							?>
							<div class="footer-container row-fluid">
								<div class="likes-container <?php print htmlentities($liked) ?>">
									<i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span>
								</div>
							</div>
						</div>

						<!-- Votes -->
						<?php
							// Check if the user has voted on this feedback and
							// in which direction.
							$voted_up = '';
							$voted_down = '';
							if(array_key_exists($row['feedback_id'], $user_votes)){
								$direction = $user_votes[$row['feedback_id']];
								if($direction=='up'){
									$voted_up = 'voted';
								}
								if ($direction=='down') {
									$voted_down = 'voted';
								}
							}
						?>
						<div class="votes-container span1 text-center">
							<div class="vote vote-up-container <?php print htmlentities($voted_up)?>" data-vote-direction="up">
								<i class="fa fa-caret-up fa-2x"></i>
							</div>
							<div class="vote-score-container">

								<div class="score-indicator vote-score-upvotes"><?php print htmlentities($row['votes_up']) ?></div>
								<div class="score-divider"></div>
								<div class="score-indicator vote-score-downvotes"><?php print htmlentities($row['votes_down']) ?></div>

								<div class="vote-score"><?php print htmlentities($row['votes_score']) ?></div>

							</div>
							<div class="vote vote-down-container <?php print htmlentities($voted_down)?>" data-vote-direction="down">
								<i class="fa fa-caret-down fa-2x"></i>
							</div>
						</div>
					</div>
				</div>

				<!-- Comments -->
				<div class="comments-container">
					<div class="comments-header row-fluid">
						<div class="more-comments-container span2 offset1 pull-left">More comments</div>
						<div class="total-comments-container span1 offset8 pull-left text-center">1 of <?php print htmlentities($row['comment_count']) ?></div>
					</div>

					<?php
						foreach($comments[$row['feedback_id']] as $comment){

							// Get the like-count.
							$comment_id = $comment['comment_id'];
							$flikes = $likes['comments'];
							$count = '0';
							if(array_key_exists($comment_id, $flikes)){
								$count = $flikes[$comment_id];
							};

							// If the user liked the feedback, highlight it.
							$ulikes = $user_likes['comments'];
							$liked = '';
							if(in_array($comment_id, $ulikes)){
								$liked = 'liked';
							}

							?>

							<div class="comment-container row-fluid all-comment" data-comment-id="<?php print htmlentities($comment['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($comment['first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($comment['last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($comment['comment']) ?></p>
									</div>
									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($comment['created_on']) ?></div>
									</div>									
								</div>


							</div>

							<?php
						};
					?>

					<!-- Only show an existing comment if it exists. -->
					<?php
						if($row['comment_id']){
						?>
							<div class="comment-container row-fluid latest-comment" data-comment-id="<?php print htmlentities($row['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($row['comment_first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($row['comment_last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($row['comment']) ?></p>
									</div>

									<?php
										// Get the like-count.
										$comment_id = $row['comment_id'];
										$flikes = $likes['comments'];
										$count = '0';
										if(array_key_exists($comment_id, $flikes)){
											$count = $flikes[$comment_id];
										};

										// If the user liked the feedback, highlight it.
										$ulikes = $user_likes['comments'];
										$liked = '';
										if(in_array($comment_id, $ulikes)){
											$liked = 'liked';
										}
									?>

									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($row['comment_date']) ?></div>
									</div>
								</div>


							</div>
						<?php
						}
					?>
					<div class="new-comment-container row-fluid">
						<?php
							// Get logged-in user's name and avatar.
							global $session;
							$login = $session->login;
							$first_name = $login['first_name'];
							$last_name = $login['last_name'];
							$avatar = $avatar;
						?>
						<div class="user-container span1">
							<?php
								// Get the profile photo ID.
								$photo_id = htmlentities($user_photo_id);
							?>

							<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
								<img class="avatar" src="<?php print htmlentities($avatar) ?>">
							</div>
							<div class="name-container">
								<div class="first-name-container"><?php print htmlentities($first_name) ?></div>
								<div class="last-name-container"><?php print htmlentities($last_name) ?></div>
							</div>
						</div>
						<?php
							// Add a top-border if there are not comments.
							if($row['comment_count']>0){
								$btop = '';
							}else{
								$btop = 'btop';
							};
						?>
						<div class="new-comment-contents-container gray-background bbottom bleft bright <?php print htmlentities($btop) ?> span11">
							<?php
								// Get the logged in user's login ID.
								global $session;
								$login = $session->login;
								$login_id = $login['login_id'];
							?>
							<input class="login-id-input" type="hidden" value="<?php print htmlentities($login_id) ?>">
							<div class="new-comment-input-container row-fluid">
								<input class="new-comment-input span12" type="text" name="new-comment" placeholder="Post a comment">
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php
		} ?>
	</div>

	<div id="completed-tab" class="tab-target">
		

		<?php foreach($completed as $row){

			// Highlight following.
			if(in_array($row['feedback_id'], $following_ids)){
				$is_following = 'following';
			}else{
				$is_following = '';
			}

			// Highlight archived.
			if(in_array($row['feedback_id'], $archived_ids)){
				$is_archived = 'archived';
			}else{
				$is_archived = '';
			}

			// Highlight killed.
			if(in_array($row['feedback_id'], $killed_ids)){
				$is_killed = 'killed';
			}else{
				$is_killed = '';
			}

			// Highlght assigned.
			if(in_array($row['feedback_id'], $all_assigned_ids)){
				$is_assigned = 'assigned';
			}else{
				$is_assigned = '';
			}

			// Highlight completed.
			if(in_array($row['feedback_id'], $completed_ids)){
				$is_completed = 'completed';
			}else{
				$is_completed = '';
			}

			// Highlight started.
			if(in_array($row['feedback_id'], $started_ids)){
				$is_started = 'started';
			}else{
				$is_started = '';
			}

		?>
			<div class="main-container container-fluid" data-feedback-id="<?php print htmlentities($row['feedback_id'])?>">
				<!-- Feedback Container -->
				<div class="feedback-container row-fluid">

					<!-- User -->
					<div class="user-container span1">
						<?php
							// Get the profile photo ID.
							$photo_id = htmlentities($row['profile_photo_id']);
						?>

						<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
							<img class="avatar" src="<?php print htmlentities($avatar) ?>">
						</div>
						<div class="name-container">
							<div class="first-name-container"><?php print htmlentities($row['first_name']) ?></div>
							<div class="last-name-container"><?php print htmlentities($row['last_name']) ?></div>
						</div>
					</div>
					<!-- Status Bar -->
					<?php
						// Get the proper status color

						$status = '';
						$text = '';
						if($is_killed!=''){
							$status = 'black';
							$text = 'Killed';
						}elseif ($is_completed!='') {
							$statuses = array(
								1 => 'cd',
								2 => 'dorodo'
							);
							$status = $statuses[COMPANY];
							$text = 'Completed';
						}elseif ($is_assigned!='') {
							$status = 'green';
							$assignee = get_assignee($row['assignee']);
							$text = 'Assigned '.' - '. $assignee .' - '.$row['projected_completion_date'];
						}

					?>
					<div class="status-bar <?php print htmlentities($status) ?> span11">
						<div class="status-label noto"><?php print htmlentities($text) ?></div>
					</div>

					<div class="border-div span11">
						

						<!-- Header -->
						<div class="content-container span11">
							<div class="header-container row-fluid">
								<div class="subject-container pull-left">
									<div class="subject"><h3><?php print htmlentities($row['subject']) ?></h3></div>
								</div>
								<div class="icon-container span2">
									<div class="feedback-icon follow-icon-container <?php print htmlentities($is_following) ?> pull-left">
										<i class="fa fa-star fa-2x follow-icon"></i>
									</div>
									<div class="feedback-icon assign-icon-container pull-left <?php print htmlentities($is_assigned)?>">
										<i class="fa fa-user fa-2x assign-icon"></i>
									</div>
									<div class="feedback-icon pull-left kill-icon-container <?php print htmlentities($is_killed) ?>">
										<i class="fa fa-times-circle fa-2x kill-icon"></i>
									</div>
									<div class="feedback-icon completed-icon-container pull-left <?php print htmlentities($is_completed) ?>">
										<i class="fa fa-trophy fa-2x completed-icon"></i>
									</div>
								</div>
								<div class="date-container span2 offset1">
									<div class="date"><?php print htmlentities($row['submitted_on']) ?></div>
								</div>
							</div>

							<!-- Body -->
							<div class="body-container row-fluid">
								<p><?php print htmlentities($row['memo']) ?></p>
							</div>

							<!-- Footer -->
							<?php
								// Get the like-count.
								$feedback_id = $row['feedback_id'];
								$flikes = $likes['feedback'];
								$count = '0';
								if(array_key_exists($feedback_id, $flikes)){
									$count = $flikes[$feedback_id];
								};

								// If the user liked the feedback, highlight it.
								$ulikes = $user_likes['feedback'];
								$liked = '';
								if(in_array($feedback_id, $ulikes)){
									$liked = 'liked';
								}

							?>
							<div class="footer-container row-fluid">
								<div class="likes-container <?php print htmlentities($liked) ?>">
									<i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span>
								</div>
							</div>
						</div>

						<!-- Votes -->
						<?php
							// Check if the user has voted on this feedback and
							// in which direction.
							$voted_up = '';
							$voted_down = '';
							if(array_key_exists($row['feedback_id'], $user_votes)){
								$direction = $user_votes[$row['feedback_id']];
								if($direction=='up'){
									$voted_up = 'voted';
								}
								if ($direction=='down') {
									$voted_down = 'voted';
								}
							}
						?>
						<div class="votes-container span1 text-center">
							<div class="vote vote-up-container <?php print htmlentities($voted_up)?>" data-vote-direction="up">
								<i class="fa fa-caret-up fa-2x"></i>
							</div>
							<div class="vote-score-container">

								<div class="score-indicator vote-score-upvotes"><?php print htmlentities($row['votes_up']) ?></div>
								<div class="score-divider"></div>
								<div class="score-indicator vote-score-downvotes"><?php print htmlentities($row['votes_down']) ?></div>

								<div class="vote-score"><?php print htmlentities($row['votes_score']) ?></div>

							</div>
							<div class="vote vote-down-container <?php print htmlentities($voted_down)?>" data-vote-direction="down">
								<i class="fa fa-caret-down fa-2x"></i>
							</div>
						</div>
					</div>
				</div>

				<!-- Comments -->
				<div class="comments-container">
					<div class="comments-header row-fluid">
						<div class="more-comments-container span2 offset1 pull-left">More comments</div>
						<div class="total-comments-container span1 offset8 pull-left text-center">1 of <?php print htmlentities($row['comment_count']) ?></div>
					</div>

					<?php
						foreach($comments[$row['feedback_id']] as $comment){

							// Get the like-count.
							$comment_id = $comment['comment_id'];
							$flikes = $likes['comments'];
							$count = '0';
							if(array_key_exists($comment_id, $flikes)){
								$count = $flikes[$comment_id];
							};

							// If the user liked the feedback, highlight it.
							$ulikes = $user_likes['comments'];
							$liked = '';
							if(in_array($comment_id, $ulikes)){
								$liked = 'liked';
							}

							?>

							<div class="comment-container row-fluid all-comment" data-comment-id="<?php print htmlentities($comment['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($comment['first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($comment['last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($comment['comment']) ?></p>
									</div>
									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($comment['created_on']) ?></div>
									</div>									
								</div>


							</div>

							<?php
						};
					?>

					<!-- Only show an existing comment if it exists. -->
					<?php
						if($row['comment_id']){
						?>
							<div class="comment-container row-fluid latest-comment" data-comment-id="<?php print htmlentities($row['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($row['comment_first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($row['comment_last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($row['comment']) ?></p>
									</div>

									<?php
										// Get the like-count.
										$comment_id = $row['comment_id'];
										$flikes = $likes['comments'];
										$count = '0';
										if(array_key_exists($comment_id, $flikes)){
											$count = $flikes[$comment_id];
										};

										// If the user liked the feedback, highlight it.
										$ulikes = $user_likes['comments'];
										$liked = '';
										if(in_array($comment_id, $ulikes)){
											$liked = 'liked';
										}
									?>

									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($row['comment_date']) ?></div>
									</div>
								</div>


							</div>
						<?php
						}
					?>
					<div class="new-comment-container row-fluid">
						<?php
							// Get logged-in user's name and avatar.
							global $session;
							$login = $session->login;
							$first_name = $login['first_name'];
							$last_name = $login['last_name'];
							$avatar = $avatar;
						?>
						<div class="user-container span1">
							<?php
								// Get the profile photo ID.
								$photo_id = htmlentities($user_photo_id);
							?>

							<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
								<img class="avatar" src="<?php print htmlentities($avatar) ?>">
							</div>
							<div class="name-container">
								<div class="first-name-container"><?php print htmlentities($first_name) ?></div>
								<div class="last-name-container"><?php print htmlentities($last_name) ?></div>
							</div>
						</div>
						<?php
							// Add a top-border if there are not comments.
							if($row['comment_count']>0){
								$btop = '';
							}else{
								$btop = 'btop';
							};
						?>
						<div class="new-comment-contents-container gray-background bbottom bleft bright <?php print htmlentities($btop) ?> span11">
							<?php
								// Get the logged in user's login ID.
								global $session;
								$login = $session->login;
								$login_id = $login['login_id'];
							?>
							<input class="login-id-input" type="hidden" value="<?php print htmlentities($login_id) ?>">
							<div class="new-comment-input-container row-fluid">
								<input class="new-comment-input span12" type="text" name="new-comment" placeholder="Post a comment">
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php
		} ?>

	</div>
	<div id="killed-tab" class="tab-target">
		
		<?php foreach($killed as $row){

			// Highlight following.
			if(in_array($row['feedback_id'], $following_ids)){
				$is_following = 'following';
			}else{
				$is_following = '';
			}

			// Highlight archived.
			if(in_array($row['feedback_id'], $archived_ids)){
				$is_archived = 'archived';
			}else{
				$is_archived = '';
			}

			// Highlight killed.
			if(in_array($row['feedback_id'], $killed_ids)){
				$is_killed = 'killed';
			}else{
				$is_killed = '';
			}

			// Highlght assigned.
			if(in_array($row['feedback_id'], $all_assigned_ids)){
				$is_assigned = 'assigned';
			}else{
				$is_assigned = '';
			}

			// Highlight completed.
			if(in_array($row['feedback_id'], $completed_ids)){
				$is_completed = 'completed';
			}else{
				$is_completed = '';
			}

			// Highlight started.
			if(in_array($row['feedback_id'], $started_ids)){
				$is_started = 'started';
			}else{
				$is_started = '';
			}

		?>
			<div class="main-container container-fluid" data-feedback-id="<?php print htmlentities($row['feedback_id'])?>">
				<!-- Feedback Container -->
				<div class="feedback-container row-fluid">

					<!-- User -->
					<div class="user-container span1">
						<?php
							// Get the profile photo ID.
							$photo_id = htmlentities($row['profile_photo_id']);
						?>

						<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
							<img class="avatar" src="<?php print htmlentities($avatar) ?>">
						</div>
						<div class="name-container">
							<div class="first-name-container"><?php print htmlentities($row['first_name']) ?></div>
							<div class="last-name-container"><?php print htmlentities($row['last_name']) ?></div>
						</div>
					</div>
					<!-- Status Bar -->
					<?php
						// Get the proper status color

						$status = '';
						$text = '';
						if($is_killed!=''){
							$status = 'black';
							$text = 'Killed';
						}elseif ($is_completed!='') {
							$statuses = array(
								1 => 'cd',
								2 => 'dorodo'
							);
							$status = $statuses[COMPANY];
							$text = 'Completed';
						}elseif ($is_assigned!='') {
							$status = 'green';
							$assignee = get_assignee($row['assignee']);
							$text = 'Assigned '.' - '. $assignee .' - '.$row['projected_completion_date'];
						}

					?>
					<div class="status-bar <?php print htmlentities($status) ?> span11">
						<div class="status-label noto"><?php print htmlentities($text) ?></div>
					</div>

					<div class="border-div span11">
						

						<!-- Header -->
						<div class="content-container span11">
							<div class="header-container row-fluid">
								<div class="subject-container pull-left">
									<div class="subject"><h3><?php print htmlentities($row['subject']) ?></h3></div>
								</div>
								<div class="icon-container span2">
									<div class="feedback-icon follow-icon-container <?php print htmlentities($is_following) ?> pull-left">
										<i class="fa fa-star fa-2x follow-icon"></i>
									</div>
									<div class="feedback-icon assign-icon-container pull-left <?php print htmlentities($is_assigned)?>">
										<i class="fa fa-user fa-2x assign-icon"></i>
									</div>
									<div class="feedback-icon pull-left kill-icon-container <?php print htmlentities($is_killed) ?>">
										<i class="fa fa-times-circle fa-2x kill-icon"></i>
									</div>
									<div class="feedback-icon completed-icon-container pull-left <?php print htmlentities($is_completed) ?>">
										<i class="fa fa-trophy fa-2x completed-icon"></i>
									</div>
								</div>
								<div class="date-container span2 offset1">
									<div class="date"><?php print htmlentities($row['submitted_on']) ?></div>
								</div>
							</div>

							<!-- Body -->
							<div class="body-container row-fluid">
								<p><?php print htmlentities($row['memo']) ?></p>
							</div>

							<!-- Footer -->
							<?php
								// Get the like-count.
								$feedback_id = $row['feedback_id'];
								$flikes = $likes['feedback'];
								$count = '0';
								if(array_key_exists($feedback_id, $flikes)){
									$count = $flikes[$feedback_id];
								};

								// If the user liked the feedback, highlight it.
								$ulikes = $user_likes['feedback'];
								$liked = '';
								if(in_array($feedback_id, $ulikes)){
									$liked = 'liked';
								}

							?>
							<div class="footer-container row-fluid">
								<div class="likes-container <?php print htmlentities($liked) ?>">
									<i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span>
								</div>
							</div>
						</div>

						<!-- Votes -->
						<?php
							// Check if the user has voted on this feedback and
							// in which direction.
							$voted_up = '';
							$voted_down = '';
							if(array_key_exists($row['feedback_id'], $user_votes)){
								$direction = $user_votes[$row['feedback_id']];
								if($direction=='up'){
									$voted_up = 'voted';
								}
								if ($direction=='down') {
									$voted_down = 'voted';
								}
							}
						?>
						<div class="votes-container span1 text-center">
							<div class="vote vote-up-container <?php print htmlentities($voted_up)?>" data-vote-direction="up">
								<i class="fa fa-caret-up fa-2x"></i>
							</div>
							<div class="vote-score-container">

								<div class="score-indicator vote-score-upvotes"><?php print htmlentities($row['votes_up']) ?></div>
								<div class="score-divider"></div>
								<div class="score-indicator vote-score-downvotes"><?php print htmlentities($row['votes_down']) ?></div>

								<div class="vote-score"><?php print htmlentities($row['votes_score']) ?></div>

							</div>
							<div class="vote vote-down-container <?php print htmlentities($voted_down)?>" data-vote-direction="down">
								<i class="fa fa-caret-down fa-2x"></i>
							</div>
						</div>
					</div>
				</div>

				<!-- Comments -->
				<div class="comments-container">
					<div class="comments-header row-fluid">
						<div class="more-comments-container span2 offset1 pull-left">More comments</div>
						<div class="total-comments-container span1 offset8 pull-left text-center">1 of <?php print htmlentities($row['comment_count']) ?></div>
					</div>

					<?php
						foreach($comments[$row['feedback_id']] as $comment){

							// Get the like-count.
							$comment_id = $comment['comment_id'];
							$flikes = $likes['comments'];
							$count = '0';
							if(array_key_exists($comment_id, $flikes)){
								$count = $flikes[$comment_id];
							};

							// If the user liked the feedback, highlight it.
							$ulikes = $user_likes['comments'];
							$liked = '';
							if(in_array($comment_id, $ulikes)){
								$liked = 'liked';
							}

							?>

							<div class="comment-container row-fluid all-comment" data-comment-id="<?php print htmlentities($comment['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($comment['first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($comment['last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($comment['comment']) ?></p>
									</div>
									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($comment['created_on']) ?></div>
									</div>									
								</div>


							</div>

							<?php
						};
					?>

					<!-- Only show an existing comment if it exists. -->
					<?php
						if($row['comment_id']){
						?>
							<div class="comment-container row-fluid latest-comment" data-comment-id="<?php print htmlentities($row['comment_id']) ?>">
								
								<!-- User -->
								<div class="user-container span1">
									<?php
										// Get the profile photo ID.
										$photo_id = htmlentities($row['profile_photo_id']);
									?>

									<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
										<img class="avatar" src="<?php print htmlentities($avatar) ?>">
									</div>
									<div class="name-container">
										<div class="first-name-container"><?php print htmlentities($row['comment_first_name']) ?></div>
										<div class="last-name-container"><?php print htmlentities($row['comment_last_name']) ?></div>
									</div>
								</div>


								<div class="comment-content-container gray-background btop bleft bright bbottom span11">
									<div class="comment-body-container row-fluid">
										<p><?php print htmlentities($row['comment']) ?></p>
									</div>

									<?php
										// Get the like-count.
										$comment_id = $row['comment_id'];
										$flikes = $likes['comments'];
										$count = '0';
										if(array_key_exists($comment_id, $flikes)){
											$count = $flikes[$comment_id];
										};

										// If the user liked the feedback, highlight it.
										$ulikes = $user_likes['comments'];
										$liked = '';
										if(in_array($comment_id, $ulikes)){
											$liked = 'liked';
										}
									?>

									<div class="comment-footer-container row-fluid">
										<div class="comment-likes-container span1 <?php print htmlentities($liked) ?>"><i class="fa fa-thumbs-o-up"></i><span class="like-count"><?php print htmlentities($count) ?></span></div>
										<div class="comment-date-container date span2 offset9 text-center"><?php print htmlentities($row['comment_date']) ?></div>
									</div>
								</div>


							</div>
						<?php
						}
					?>
					<div class="new-comment-container row-fluid">
						<?php
							// Get logged-in user's name and avatar.
							global $session;
							$login = $session->login;
							$first_name = $login['first_name'];
							$last_name = $login['last_name'];
							$avatar = $avatar;
						?>
						<div class="user-container span1">
							<?php
								// Get the profile photo ID.
								$photo_id = htmlentities($user_photo_id);
							?>

							<div class="avatar-container text-center" data-photo-id="<?php print $photo_id ?>">
								<img class="avatar" src="<?php print htmlentities($avatar) ?>">
							</div>
							<div class="name-container">
								<div class="first-name-container"><?php print htmlentities($first_name) ?></div>
								<div class="last-name-container"><?php print htmlentities($last_name) ?></div>
							</div>
						</div>
						<?php
							// Add a top-border if there are not comments.
							if($row['comment_count']>0){
								$btop = '';
							}else{
								$btop = 'btop';
							};
						?>
						<div class="new-comment-contents-container gray-background bbottom bleft bright <?php print htmlentities($btop) ?> span11">
							<?php
								// Get the logged in user's login ID.
								global $session;
								$login = $session->login;
								$login_id = $login['login_id'];
							?>
							<input class="login-id-input" type="hidden" value="<?php print htmlentities($login_id) ?>">
							<div class="new-comment-input-container row-fluid">
								<input class="new-comment-input span12" type="text" name="new-comment" placeholder="Post a comment">
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php
		} ?>

	</div>


<script type="text/javascript">	
$(document).ready(function(){

	// A cache for profile photos.
	profile_photos = {};

	// Activate a new tab.
	function activate_tab(tab, div){

		// JQuery makes things easy.
		var $tab = $(tab)
		var $div = $(div)

		// Remove active status from other tabs.
		var $tabs = $("li.nav-item")
		$tabs.removeClass('active')

		// Add active status to the selected tab.
		$tab.addClass('active')

		// Hide all tab targets.
		var $targets = $(".tab-target")
		$targets.hide()

		// Show the selected tab target.
		$div.show()

	}

	// Support tab switching.
	function switch_tabs(){

		// Get the selected tab.
		var $tab = $(this)

		// Get the ID of the target tab.
		var target_id = $tab.attr('data-target')

		// Get the proper div
		var $div = $('#'+target_id)

		// Switch to the selected tab.
		activate_tab($tab, $div)

	}

	function vote(){

		// Vote on a feedback item.

		// Get icon and vote direction.
		var $vote = $(this)
		var $container = $vote.parents('.votes-container')
		var direction = $vote.attr('data-vote-direction')

		// Get the feedback ID.
		var $row = $container.parents('.main-container')
		var feedback_id = $row.attr('data-feedback-id')

		// The data to POST.
		var data = {
			'action' : 'vote',
			'feedback_id' : feedback_id,
			'direction' : direction
		}

		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data
		}).success(function(rsp){

			// Hide indicators.
			var $indicators = $container.find('.score-indicator')
			var $divider = $container.find('.score-divider')
			$indicators.hide()
			$divider.hide()

			// Show score.
			$score = $container.find('.vote-score')
			$score.show()

			/* Handle Highlighting */
			// Remove any existing vote highlighting.
			var $up = $container.find('.vote-up-container')
			var $down = $container.find('.vote-down-container')
			$up.removeClass('voted')
			$down.removeClass('voted')
			$vote.addClass('voted')


			// Update the vote score.
			$score.text(rsp.message.score)

			// Get the individual vote-score indicators.
			var $upvotes = $container.find('.vote-score-upvotes')
			var $downvotes = $container.find('.vote-score-downvotes')

			// Update the indicators.
			$upvotes.text(rsp.message.up)
			$downvotes.text(rsp.message.down)

		}).error(function(rsp){
			console.log('error')
			console.log(rsp)
		})

	}

	function view_feedback(){

		// Navigate to ta feedback-specific page.

		// Get whatever it was that was clicked.
		var $this = $(this)

		// Get the row and feedback ID.
		var $row = $this.parents('.feedback-row')
		var feedback_id = $row.attr('data-feedback-id')

		// Set the feedback ID.
		var query = new URLSearchParams()
		query.set('feedback-id', feedback_id)

		// Create the URL for the feedback page.
		var host = location.hostname
		var path = location.pathname
		var url = 'http://'+host+path+'/item?'+query.toString()

		// Go to new URL.
		window.location.href = url

	}

	function filter_feedback(e){

		// Prevent the form submission.
		e.preventDefault();

		// Get the selects.
		var $type_select = $('#feedback-type-select')
		var $tag_select = $('#feedback-tag-select')
		var $user_select = $('#feedback-user-select')

		// Get the selected values.
		var type_val = $type_select.val()
		var tag_val = $tag_select.val()
		var user_val = $user_select.val()

		// Map the URL parameter name to its corresponding value.
		var param_map = {
			'tag-id' : tag_val,
			'type-id' : type_val,
			'user-id' : user_val
		}

		// Redirect based on selections.
		var query = new URLSearchParams(window.location.search)
		$.each(param_map, function(key, value){
			if(value){
				query.set(key, value)
			}else{
				query.delete(key)
			}
			window.location.search = query.toString()
		})
	}

	function sort_feedback(e){

		// Prevent an actual form submission.
		e.preventDefault()

		// Get the selects.
		var $user_select = $('#sort-user-select')
		var $date_select = $('#sort-date-select')

		// Get the selected values.
		var user_val = $user_select.val()
		var date_val = $date_select.val()

		// Map the URL parameter name to its corresponding value.
		var param_map = {
			'user-sort' : user_val,
			'date-sort' : date_val
		}

		// Redirect based on selections.
		var query = new URLSearchParams(window.location.search)
		$.each(param_map, function(key, value){
			if(value){query.set(key, value)}
			else{query.delete(key)}
			window.location.search = query.toString()
		})

	}

	function follow_feedback(){

		// Support following a feedback entry.

		// Get the parent div.
		var $icon = $(this)
		var $div = $icon.parents('.follow-icon-container')

		// Get the main container and feedback ID.
		var $row = $div.parents('.main-container')
		var feedback_id = $row.attr('data-feedback-id')

		// See if the feedback is already followed to determine whether a click
		// should follow or unfollow.
		var is_followed = $div.hasClass('following')

		// Get the follow value.
		var follow = {
			true : 'unfollow',
			false : 'follow'
		}[is_followed]

		// The data to POST.
		var data = {
			'action' : 'follow',
			'value' : follow,
			'feedback_id' : feedback_id
		}

		// Follow the feedback.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
			'success' : function(rsp){
				if(rsp.success){

					if(is_followed){
						$div.removeClass('following')
					}else{
						$div.addClass('following')
					}

				}
			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function archive_feedback(){

		// Mark feedback as archived.

		// Get the parent div.
		var $icon = $(this)
		var $div = $icon.parents('.archive-icon-container')

		// Get the main container and feedback ID.
		var $row = $div.parents('.feedback-row')
		var feedback_id = $row.attr('data-feedback-id')

		// Check if the feedback is already archived.
		var is_archived = $div.hasClass('archived')

		// Get the action value.
		var archive = {
			true : 'unarchive',
			false : 'archive'
		}[is_archived]

		// The data to POST.
		var data = {
			'action' : 'archive',
			'feedback_id' : feedback_id,
			'value' : archive
		}

		$.ajax({
			'url' : '',
			'method' : 'POST',
			'async' : false,
			'dataType' : 'json',
			'data' : data,
			'success' : function(){
				console.log('success')

				// Update the icon color.
				if(is_archived){
					$div.removeClass('archived')
				}else{
					$div.addClass('archived')
				}

			},
			'error' : function(){
				console.log('error')
			}
		})

	}

	function kill_feedback(){

		// Kill the feedback.
		var $icon = $(this)
		var $div = $icon.parents('.kill-icon-container')

		// Get the main container and feedback ID.
		var $row = $div.parents('.main-container')
		var feedback_id = $row.attr('data-feedback-id')

		// Get overlay vars.
		var data = {'feedback_id':feedback_id}

		// Get overlay vars.
		var data = {'feedback_id' : feedback_id}
		var uri = '/dashboard/account/feedback/kill-feedback'

		// Produce an overlay.
		var url = BASE_URI+uri
		activateOverlayZ(url, data)

	}

	function assign_feedback(){

		// Assign a user to feedback.
		var $icon = $(this)
		var $div = $icon.parents('.assign-icon-container')

		// Get the main container and feedback ID.
		var $row = $div.parents('.main-container')
		var feedback_id = $row.attr('data-feedback-id')

		// Get overlay vars.
		var data = {'feedback_id':feedback_id}
		var uri = '/dashboard/account/feedback/assign-feedback'

		// Produce an overlay.
		var url = BASE_URI+uri
		activateOverlayZ(url, data)

	}

	function complete_feedback(){

		// Mark fedback as complete.

		// Assign a user to feedback.
		var $icon = $(this)
		var $div = $icon.parents('.completed-icon-container')

		// Get the main container and feedback ID.
		var $row = $div.parents('.main-container')
		var feedback_id = $row.attr('data-feedback-id')

		// Get overlay vars.
		var data = {'feedback_id':feedback_id}
		var uri = '/dashboard/account/feedback/complete-feedback'

		// Produce an overlay.
		var url = BASE_URI+uri
		activateOverlayZ(url, data)

	}

	function start_feedback(){

		// Mark feedack as started.

		// Get the parent div.
		var $icon = $(this)
		var $div = $icon.parents('.started-icon-container')

		// Get the main container and feedback ID.
		var $row = $div.parents('.feedback-row')
		var feedback_id = $row.attr('data-feedback-id')

		// Check if the feedback is already complete.
		var is_started = $div.hasClass('started')

		// How to update the feedback.
		var start = {
			true: 'unstart',
			false: 'start'
		}[is_started]

		// The data to POST.
		var data = {
			'action' : 'start',
			'feedback_id' : feedback_id,
			'value' : start
		}

		// Complete the feedback.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'async' : false,
			'dataType' : 'json',
			'data' : data,
			'success' : function(){

				// Highlight the icon.
				if(is_started){
					$div.removeClass('started')
				}else{
					$div.addClass('started')
				}

			},
			'error' : function(){
				console.log('error')
			}
		})

	}

	function view_comments(){

		// Get the main comment container.
		var $container = $(this).parents('.comments-container')

		// Get the count indicator.
		var $header = $container.find('.comments-header')
		var $indicator = $container.find('.total-comments-container')

		// Find all previous comments.
		var $prev = $container.find('.all-comment')
		var $last = $container.find('.latest-comment')

		// Show or hide comments.
		var visible = $prev.is(':visible')
		if(visible){
			$prev.hide()
			$last.show()

			// Update the indicator.
			$indicator.text('1 of '+$prev.length)

		}else{
			$prev.show()
			$last.hide()

			// Update the indicator.
			$indicator.text($prev.length + ' of ' + $prev.length)

		}

	}

	function add_comment(e){


		// Make sure <Enter> was pressed.
		var key = e.which
		if(key!=13){return;}

		// Get the feedback ID and comment text.
		var $input = $(this)
		var $container = $input.parents('.main-container')
		var feedback_id = $container.attr('data-feedback-id')
		var comment = $input.val()

		// Get the ID of the posting user.
		var $linput = $container.find('.login-id-input')
		var login_id = $linput.val()

		// The data to POST.
		var data = {
			'action' : 'add-comment',
			'feedback_id' : feedback_id,
			'login_id' : login_id,
			'comment' : comment
		}

		// Add the comment.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
			'success' : function(rsp){

				// Clear the input.
				$input.val('')

				// Get the coment HTML.
				html = get_comment(rsp.comment_id)
				// response = get_comment(rsp.comment_id)
				// html = response.html
				// photo_id = response.photo_id

				// Insert the new comment just before the new-comment input.
				$input_div = $container.find('.new-comment-container')
				$input_div.before(html)

				// Get the profile photo.
				var $html = $(html)
				var $div = $input_div.prev('.comment-container').find('.avatar-container')
				var photo_id = $div.attr('data-photo-id')

				get_profile_photo($div, photo_id)

			},
			'error' : function(rsp){
				console.log('error')
			}
		})

	}

	function get_comment(comment_id){

		// Get the comment HTML for a comment ID.

		// The data to POST.
		var data = {
			'action' : 'get-comment',
			'comment_id' : comment_id
		}

		// Get the HTML.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'async' : false,
			'data' : data,
			'success' : function(rsp){
				html = rsp.html
			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

		return html
		// return {
		// 	'html' : html,
		// 	'photo_id' : photo_id
		// }

	}

	function toggle_score_view(){

		// Show or hide the vote breakdown.

		// Get all vote containers.
		var $container = $(this)
		var $score = $container.find('.vote-score')
		var $up = $container.find('.vote-score-upvotes')
		var $down = $container.find('.vote-score-downvotes')
		var $divider = $container.find('.score-divider')

		// Display the score breakdown.
		console.log($score)
		$score.hide()
		$up.show()
		$divider.show()
		$down.show()

	}

	function like_feedback(){

		// Support feedback "likes".
		
		// Get feedback ID.
		var $like = $(this)
		var $container = $like.parents('.main-container')
		var feedback_id = $container.attr('data-feedback-id')

		// Check if the feedback has already been liked.
		var is_liked = $like.hasClass('liked')

		// Get the action.
		var action = {
			true : 'unlike-feedback',
			false : 'like-feedback'
		}[is_liked]

		// The data to POST.
		var data = {
			'action' : action,
			'feedback_id' : feedback_id
		}

		// POST the like.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
			'success' : function(){

				// Like or unlike.
				if(is_liked){
					$like.removeClass('liked')
					update_like_count($like, -1)
				}else{
					$like.addClass('liked')
					update_like_count($like, 1)
				}

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function like_comment(){

		// Support comment "likes".

		// Get the comment ID.
		var $like = $(this)
		var $container = $like.parents('.comment-container')
		var comment_id = $container.attr('data-comment-id')

		// Check if the comment has already been liked.
		var is_liked = $like.hasClass('liked')

		// Get the action.
		var action = {
			true : 'unlike-comment',
			false : 'like-comment'
		}[is_liked]

		// The data to POST.
		var data = {
			'action' : action,
			'comment_id' : comment_id
		}

		// Post the like.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
			'success' : function(){

				// Like or unlike.
				if(is_liked){
					$like.removeClass('liked')
					update_like_count($like, -1)
				}else{
					$like.addClass('liked')
					update_like_count($like, 1)
				}
			},
			'error' : function(){
				console.log('error')
			}
		})

	}

	function update_like_count($like, value){

		// Update the like count.

		// Make sure a JQuery object is used.
		$like = $($like)

		// Get the like count.
		$count = $like.find('span')
		count = parseInt($count.text())

		// Update the count value.
		count += value

		// Update the like count display.
		$count.text(count)

	}

	function get_profile_photos(){

		// Get the profile photos for users.

		// Find all avatar containers.
		var $containers = $('.avatar-container')

		// Get the photo for each container.
		$.each($containers, function(idx, div){

			var $div = $(div)
			var photo_id = $div.attr('data-photo-id')

			// If there is not photo ID, there is not photo.
			if(!photo_id){
				return true;
			}

			// Use a cached photo if possible.
			if(photo_id in profile_photos){

				$img = profile_photos[photo_id]
				$div.empty()
				$div.html($img)

				return true

			}

			// Get the photo.
			get_profile_photo($div, photo_id)


		})

	}

	function get_profile_photo(container, photo_id){

		// Get a single profile photo.

		var $div = $(container)
		$.ajax({
			'url' : 'http://10.1.247.195/profiles/get-profile-photo?photo-id='+photo_id,
			'method' : 'GET',
			'dataType' : 'jsonp',
			'success' : function(rsp){

				// Create the image tag.
				var uri = 'data:image/jpg;base64,'+rsp.image
				var $img = $('<img/>',{
					'class' : 'avatar',
					'src' : uri
				})

				// Add the image to the photo container.
				$div.empty()
				$div.html($img)

				// Cache the photo.
				profile_photos[photo_id] = $img

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}

		})

	}

	// Enable submitting new comments
	$('.new-comment-input').keypress(add_comment)

	// Load profile photos.
	get_profile_photos()

	// Enable viewing all comments.
	$(document).on('click', '.more-comments-container', view_comments)
	$(document).on('click', '.total-comments-container', view_comments)

	// Enable tab switching.
	$(document).on('click', '.nav-item', switch_tabs)

	// Enable voting.
	$(document).on('click', '.vote', vote)

	// Clicking comments or header should display all of the feedback contents.
	$(document).on('click', '.comment-count', view_feedback)
	$(document).on('click', '.feedback-header', view_feedback)

	// Enable filtering.
	$(document).on('click', '#filter-form-submit', filter_feedback)

	// Enable sorting.
	$(document).on('click', '#sort-form-submit', sort_feedback)

	// Enable following.
	$(document).on('click', '.follow-icon', follow_feedback)

	// Enable archiving.
	$(document).on('click', '.archive-icon', archive_feedback)

	// Enablew killing
	$(document).on('click', '.kill-icon', kill_feedback)

	// Enable feedback assignment.
	$(document).on('click', '.assign-icon', assign_feedback)

	// Enable feedback completion.
	$(document).on('click', '.completed-icon', complete_feedback)

	// Enable feedback starting.
	$(document).on('click', '.started-icon', start_feedback)

	// Enable vote breakdown.
	$(document).on('click', '.vote-score-container', toggle_score_view)

	// Enable "likes".
	$(document).on('click', '.likes-container', like_feedback)
	$(document).on('click', '.comment-likes-container', like_comment)

})
</script>