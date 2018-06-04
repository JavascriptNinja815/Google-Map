		<?php foreach($recent as $row){
		?>
			<div class="row-fluid feedback-row" data-feedback-id="<?php print htmlentities($row['feedback_id']) ?>">
				<div class="id-box span2">
					<div class="avatar"><img src="<?php print htmlentities($avatar) ?>"></div>
					<div class="name"><p><?php print htmlentities($row['name']) ?></p></div>
					<div class="date"><p><?php print htmlentities($row['submitted_on']) ?></p></div>
				</div>

				<div class="feedback-row-container span8 pull-left">
					<div class="feedback-header span8">
						<h3 class="feedback-header-text span10"><?php print htmlentities($row['subject']) ?></h3>
						<h6 class="feedback-type-text pull-right">Type: <?php print htmlentities($row['type']) ?></h6>
					</div>
					<div class="votes-container span2 pull-left">

						<?php
							// Check if the user has voted on this feedback and
							// in which direction.
							if(array_key_exists($row['feedback_id'], $user_votes)){
								$direction = $user_votes[$row['feedback_id']];
								$highlight = array(
									'up' => 'vote-highlighted-green',
									'down' => 'vote-highlighted-red'
								)[$direction];
							}else{$highlight = 'no-highlight';}
						?>

						<div class="vote-direction-container row pull-left <?php print htmlentities($highlight) ?>">

							<div class="vote vote-up span3 pull-left" data-vote-direction="up" data-color="green"><i class="fa fa-thumbs-o-up fa-3x"></i></div>
							<div class="vote-score span3 pull-left"><b><?php print htmlentities($row['votes_score']) ?></b></div>
							<div class="vote vote-down span3 pull-left" data-vote-direction="down" data-color="red"><i class="fa fa-thumbs-o-down fa-3x"></i></div>

						</div>
						<div class="vote-by-type-container row">
							<div class="vote-count vote-up-count span3 pull-left"><?php print htmlentities($row['votes_up']) ?></div>
							<div class="vote-sep span3 pull-left"></div>
							<div class="vote-count vote-down-count span3 pull-left"><?php print htmlentities($row['votes_down']) ?></div>
						</div>
					</div>
					<div class="feedback-body span8">
						<p class="feedback-text"><?php print htmlentities($row['memo']) ?></p>
					</div>

					<?php
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
					<div class="actions-container span2 pull-left">
						<div class="actions-1-container row span2 pull-left">

							<div class="span3 pull-left follow-icon-container <?php print htmlentities($is_following) ?>">
								<i rel="tooltip" title="Follow" class="fa fa-check-circle-o fa-3x follow-icon"></i>
							</div>

							<?php
							//Start
							if($is_admin){
								?>
									<div class="span3 pull-left archive-icon-container <?php print htmlentities($is_archived) ?>">
										<i rel="tooltip" title="Archive" class="fa fa-dot-circle-o fa-3x archive-icon"></i>
									</div>

									<div class="span3 pull-left kill-icon-container <?php print htmlentities($is_killed) ?>">
										<i rel="tooltip" title="Kill" class="fa fa-times-circle-o fa-3x kill-icon"></i>
									</div>
								<?php
							}
							//End
							?>


						</div>
						<div class="actions-2-container row span2 pull-left">

							<?php
							//Start
							if($is_admin){
								?>
								<div class="span3 pull-left assign-icon-container <?php print htmlentities($is_assigned) ?>">
									<i rel="tooltip" title="Assign" class="fa fa-bullseye fa-3x assign-icon"></i>
								</div>
								<div class="span3 pull-left started-icon-container <?php print htmlentities($is_started) ?>">
									<i rel="tooltip" title="Start" class="fa fa-arrow-right fa-3x started-icon"></i>
								</div>
								<div class="span3 pull-left completed-icon-container <?php print htmlentities($is_completed) ?>">
									<i rel="tooltip" title="Complete" class="fa fa-star-o fa-3x completed-icon"></i>
								</div>
								<?php
							}
							//End
							?>

						</div>
					</div>

					<div class="feedback-footer-container span8">
						<div class="feedback-tag-container span6 pull-left">

							<?php
								// Make sure the feedback ID has tags.
								$feedback_id = $row['feedback_id'];
								if(array_key_exists($feedback_id, $tags)){
									foreach($tags[$feedback_id] as $tag){
										$tag_id = $tag['tag_id'];
										$tag_text = $tag['tag'];
										?>
										<div class="tag pull-left">
											<span class="badge badge-info">
												<?php
													$href = "?page=".$page."&page-size=".$page_size."&tag-id=".$tag_id;
												?>
												<a href="<?php print htmlentities($href) ?>"><?php print htmlentities($tag_text) ?></a>
											</span>
										</div>
										<?php
									};
								};
							?>
						</div>
						<div class="comment-container span6 pull-right">
							<div class="comment-count pull-right">Comments (<?php print htmlentities($row['comment_count']) ?>)</div>
						</div>

					</div>
				</div>
			</div>
		<?php
		} ?>
