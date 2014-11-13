<?php
function go_stats_overlay () { 
	echo '<div id="go_stats_page_black_bg" style="display:none !important;"></div><div id="go_stats_white_overlay" style="display:none;"></div>';
}
function go_admin_bar_stats () { 
 	global $wpdb;
	$table_name_go = $wpdb->prefix . "go";
	if ($_POST['uid']) {
		$current_user = get_userdata( $_POST['uid'] );
	} else {
		$current_user = wp_get_current_user();
	}
	?><input type="hidden" id="go_stats_hidden_input" value="<?php echo $_POST['uid'] ?>"/><?php
	$user_fullname = $current_user->first_name.' '.$current_user->last_name;
	$user_login =  $current_user->user_login;
	$user_display_name = $current_user->display_name;
	$user_id = $current_user->ID ;
	$user_website = $current_user->user_url;
 	$current_user_id = $current_user->ID;
	$user_avatar = get_avatar($current_user_id, 161);
	$user_focuses = go_display_user_focuses($current_user_id);
	
	// option names 
	$points_name = go_return_options('go_points_name');
	$currency_name = go_return_options('go_currency_name');
	$bonus_currency_name = go_return_options('go_bonus_currency_name');
	$penalty_name = go_return_options('go_penalty_name');
	$minutes_name = go_return_options('go_minutes_name');

	// user pnc 
	go_get_rank($current_user_id);
	$current_points = go_return_points($current_user_id);
	$current_currency = go_return_currency($current_user_id);
	$current_bonus_currency = go_return_bonus_currency($current_user_id);
	$current_penalty = go_return_penalty($current_user_id);
	$current_minutes = go_return_minutes($current_user_id);
	global $current_rank;
	global $current_rank_points;
	global $next_rank;
	global $next_rank_points;
	$display_current_rank_points = $current_points - $current_rank_points;
	$display_next_rank_points = $next_rank_points - $current_rank_points;
	$percentage_of_level = ($display_current_rank_points/$display_next_rank_points) * 100;
	?>
	<div id='go_stats_lay'>
		<div id='go_stats_gravatar'><?php echo $user_avatar;?></div>
		<div id='go_stats_header'>
			<div id='go_stats_user_info'>
				<?php echo "{$user_fullname}<br/>{$user_login}<br/><a href='{$user_website}' target='_blank'>{$user_display_name}</a><br/><div id='go_stats_user_points'><span id='go_stats_user_points_value'>{$current_points}</span> {$points_name}</div><div id='go_stats_user_currency'><span id='go_stats_user_currency_value'>{$current_currency}</span> {$currency_name}</div><div id='go_stats_user_bonus_currency'><span id='go_stats_user_bonus_currency_value'>{$current_bonus_currency}</span> {$bonus_currency_name}</div>{$current_penalty} {$penalty_name}<br/>{$current_minutes} {$minutes_name}"; ?>
			</div>
			<div id='go_stats_user_rank'><?php echo $current_rank;?></div>
			<div id='go_stats_user_progress'>
				<div id="go_stats_progress_text_wrap">
					<div id='go_stats_progress_text'><?php echo "<span id='go_stats_user_progress_top_value'>{$display_current_rank_points}</span>/<span id='go_stats_user_progress_bottom_value'>{$display_next_rank_points}</span>";?></div>
				</div>
				<div id='go_stats_progress_fill' style='width: <?php echo $percentage_of_level;?>%;<?php $color = barColor($current_bonus_currency); echo "background-color: {$color}";if($percentage_of_level >= 98){echo "border-radius: 15px";}?>'></div>
			</div>
            <?php if (go_return_options('go_focus_switch') == 'On') {?>
            <div id='go_stats_user_focuses'><?php echo ((!empty($user_focuses))?$user_focuses:'');?></div>
            <?php } ?>
			<div id='go_stats_user_tabs'>
            <!--
				<a href='javascript:;' id="go_stats_body_progress" class='go_stats_body_selectors' tab='progress'>
					WEEKLY PROGRESS
				</a> | 
            -->
            	<?php $is_admin = current_user_can('manage_options'); if($is_admin){ ?>
               		<a href='javascript:;' id='go_stats_admin_help' class='go_stats_body_selectors' tab='help'>
                    	HELP
                    </a> |
                <?php } ?>
				<a href='javascript:;' id="go_stats_body_tasks" class='go_stats_body_selectors' tab='tasks'>
					<?php echo strtoupper(go_return_options('go_tasks_plural_name'));?>
				</a> | 
				<a href='javascript:;' id="go_stats_body_items" class='go_stats_body_selectors' tab='items'>
					<?php echo strtoupper(go_return_options('go_inventory_name'));?>
				</a> | 
				<a href='javascript:;' id="go_stats_body_rewards" class='go_stats_body_selectors' tab='rewards'>
					REWARDS
				</a> | 
				<a href='javascript:;' id="go_stats_body_minutes" class='go_stats_body_selectors' tab='minutes'>
					<?php echo strtoupper($minutes_name);?>
				</a> |
				<a href='javascript:;' id="go_stats_body_penalties" class='go_stats_body_selectors' tab='penalties'>
					<?php echo strtoupper($penalty_name)?>
				</a> | 
				<a href='javascript:;' id="go_stats_body_badges" class='go_stats_body_selectors' tab='badges'>
					<?php echo strtoupper(go_return_options('go_badges_name'));?>
				</a> | 
				<a href='javascript:;' id="go_stats_body_leaderboard" class='go_stats_body_selectors' tab='leaderboard'>
					<?php echo strtoupper(go_return_options('go_leaderboard_name'));?>
				</a>
			</div>
		</div>
		<div id='go_stats_body'></div>
	</div>
	<?php 
	die();

}
function go_stats_task_list () {
	global $wpdb;
	$go_table_name = "{$wpdb->prefix}go";
	if (!empty($_POST['user_id'])) {
		$user_id = $_POST['user_id'];
	} else {
		$user_id = get_current_user_id();
	}
	$is_admin = current_user_can('manage_options');
	$task_list = $wpdb->get_results($wpdb->prepare("SELECT status, post_id, count, url FROM {$go_table_name} WHERE uid=%d AND (status = %d OR status = 2 OR status = 3 OR status = 4) ORDER BY id DESC", $user_id, 1));
	$counter = 1;
	?>
	<ul id='go_stats_tasks_list' <?php if ($is_admin){ echo "class='go_stats_tasks_list_admin'"; }?>>
		<?php
		foreach ($task_list as $task) {
			$task_urls = unserialize($task->url);
			$custom = get_post_meta($task->post_id);
			?>
			<li class='go_stats_task <?php if($counter%2 == 0){echo 'go_stats_right_task';}?>'>
				<a href='<?php echo get_permalink($task->post_id);?>' <?php echo (($user_id != get_current_user_id())? "target='_blank'":""); ?>class='go_stats_task_list_name'><?php echo get_the_title($task->post_id);?></a>
				<?php
				if ($is_admin) {
				?>
					<input type='text' class='go_stats_task_admin_message' id='go_stats_task_<?php echo $task->post_id ?>_message' name='go_stats_task_admin_message' placeholder='See me'/>
                    <button class='go_stats_task_admin_submit' task='<?php echo $task->post_id;?>'></button>
				<?php 
				}
				?>
				<div class='go_stats_task_status_wrap'>
				<?php
				
				$stage_count = (($custom['go_mta_three_stage_switch'][0] == 'on')?3:(($custom['go_mta_five_stage_switch'][0] == 'on')?5:4));
				
				$url_switch = array(
					1 => !empty($custom['go_mta_encounter_url_key'][0]),
					2 => !empty($custom['go_mta_accept_url_key'][0]),
					3 => !empty($custom['go_mta_completion_url_key'][0]),
					4 => !empty($custom['go_mta_mastery_url_key'][0])
				);
				
				for ($i = 5; $i > 0; $i--) {
				
					$stage_url = ((!empty($task_urls[$i])) ? $task_urls[$i] : (($i == 5 && !empty($task_urls[4]) && $task->status == 4 && $task->count >= 1) ? $task_urls[4] : ""));
					
					?>
					<a href='<?php echo ((!empty($stage_url))?$stage_url:"#");?>' class='<?php echo (($is_admin)?"go_stats_task_admin_stage_wrap":"go_stats_task_stage_wrap go_user");?> <?php echo ((!empty($stage_url))?"go_stats_task_stage_url":"");?>' <?php echo ((!empty($stage_url))?'target="_blank"':"");?>>
					<div task='<?php echo $task->post_id;?>' stage='<?php echo $i;?>' class='go_stats_task_status <?php if($task->status >= $i || $task->count >= 1){echo 'completed';} if($i > $stage_count){echo 'go_stage_does_not_exist';}?> <?php echo (($i <= 4 && $task->count < 1)?((!empty($stage_url))?"stage_url":""):(($i == 5 && $task->count >= 1 && !empty($stage_url))?"stage_url":""));?> <?php echo ((!empty($url_switch[$i-1]) && $task->status < $i && $task->count < 1 && $i <= $stage_count)?'future_url':"");?>' <?php if($task->count >=1){echo "count='{$task->count}'"; }?>><?php if($i == 5 && $task->count > 1){echo $task->count;}?></div>
					</a>
					<?php 
				}
				?>
				</div>
			<?php
			$counter++;
			?>
			</li>
			<?php
		}
	?>
	</ul>
	<?php
	die();
}

function go_stats_move_stage () {
	global $wpdb;
	$go_table_name = "{$wpdb->prefix}go";
	if (!empty($_POST['user_id'])) {
		$user_id = $_POST['user_id'];
	} else {
		$user_id = get_current_user_id();
	}
	$current_rank = get_user_meta($user_id, 'go_rank', true);
	$task_id = $_POST['task_id'];
	$status = $_POST['status'];
	$count = $_POST['count'];
	$message = $_POST['message'];
	$custom_fields = get_post_custom($task_id);
	$date_picker = ((unserialize($custom_fields['go_mta_date_picker'][0]))?array_filter(unserialize($custom_fields['go_mta_date_picker'][0])):false);
	$rewards = unserialize($custom_fields['go_presets'][0]);
	$current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$go_table_name} WHERE uid=%d AND post_id=%d",$user_id, $task_id));
	$page_id = $wpdb->get_var($wpdb->prepare("SELECT page_id FROM {$go_table_name} WHERE uid=%d AND post_id=%d", $user_id, $task_id));
	
	$changed = array('type' => 'json', 'points' => 0, 'currency' => 0, 'bonus_currency' => 0);
	
	if (!empty($date_picker)) {
		$dates = $date_picker['date'];
		$percentages = $date_picker['percent'];
		$unix_today = strtotime(date('Y-m-d'));

		$past_dates = array();
		
		foreach ($dates as $key => $date) {
			if ($unix_today >= strtotime($date)) {
				$past_dates[$key] = abs($unix_today - strtotime($date));
			}
		}
		
		if (!empty($past_dates)) {
			asort($past_dates);
			$update_percent = (float)(($percentages[key($past_dates)])/100);
		} else {
			$update_percent = 1;	
		}
	} else {
		$update_percent = 1;
	}
	
	if ($status == 1){
		$current_rewards = $wpdb->get_results($wpdb->prepare("SELECT points, currency, bonus_currency FROM {$go_table_name} WHERE uid=%d AND post_id=%d", $user_id, $task_id));
		go_task_abandon($user_id, $task_id, 
		$current_rewards[0]->points, 
		$current_rewards[0]->currency, 
		$current_rewards[0]->bonus_currency * $update_percent);
		
		$changed['points'] = -$current_rewards[0]->points;
		$changed['currency'] = -$current_rewards[0]->currency;
		$changed['bonus_currency'] = -$current_rewards[0]->bonus_currency;
		
		$current_points = go_return_points($user_id);
		$updated_rank = get_user_meta($user_id, 'go_rank', true);
		if ($current_rank[0][0] != $updated_rank[0][0]) {
			$changed['current_points'] = $current_points;
			$changed['rank'] = $updated_rank[0][0];
			$changed['rank_points'] = $updated_rank[0][1];
			$changed['next_rank_points'] = $updated_rank[1][1];
		}
		$changed['abandon'] = 'true';
		
		if ($message === 'See me') {
			go_message_user($user_id, $message.' about, <a href="'.get_permalink($task_id).'" style="display: inline-block; text-decoration: underline; padding: 0px; margin: 0px;">'.get_the_title($task_id).'</a>, please.');
		} else {
			go_message_user($user_id, 'RE: <a href="'.get_permalink($task_id).'">'.get_the_title($task_id).'</a> '.$message);
		}
	} else {
		 
		for ($count; $count > 0; $count--) {
			go_add_post($user_id, $task_id, $current_status, 
			floor(-$rewards['points'][$current_status] * $update_percent), 
			floor(-$rewards['currency'][$current_status] * $update_percent), 
			floor(-$rewards['bonus_currency'][$current_status] * $update_percent), 
			null, $page_id, 'on', -1, null, null, null, null);
			
			$changed['points'] += floor(-$rewards['points'][$current_status] * $update_percent);
			$changed['currency'] += floor(-$rewards['currency'][$current_status] * $update_percent);
			$changed['bonus_currency'] += floor(-$rewards['bonus_currency'][$current_status] * $update_percent);
		}
		
		while ($current_status != $status) {
			if ($current_status > $status) {
				$current_status--;
				
				go_add_post($user_id, $task_id, $current_status, 
				floor(-$rewards['points'][$current_status] * $update_percent), 
				floor(-$rewards['currency'][$current_status] * $update_percent), 
				floor(-$rewards['bonus_currency'][$current_status] * $update_percent), 
				null, $page_id, null, null, null, null, null, null);
				
				$changed['points'] += floor(-$rewards['points'][$current_status] * $update_percent);
				$changed['currency'] += floor(-$rewards['currency'][$current_status] * $update_percent);
				$changed['bonus_currency'] += floor(-$rewards['bonus_currency'][$current_status] * $update_percent);
				
			} elseif ($current_status < $status) {
				$current_status++;
				$current_count = $wpdb->get_var($wpdb->prepare("SELECT count FROM {$go_table_name} WHERE uid=%d AND post_id=%d", $user_id, $task_id));
				if ($current_status == 5 && $current_count == 0) {
					go_add_post($user_id, $task_id, $current_status-1, 
					floor($rewards['points'][$current_status-1] * $update_percent), 
					floor($rewards['currency'][$current_status-1] * $update_percent), 
					floor($rewards['bonus_currency'][$current_status-1] * $update_percent), 
					null, $page_id, 'on', 1, null, null, null, null);
					
					$changed['points'] += floor($rewards['points'][$current_status-1] * $update_percent);
					$changed['currency'] += floor($rewards['currency'][$current_status-1] * $update_percent);
					$changed['bonus_currency'] += floor($rewards['bonus_currency'][$current_status-1] * $update_percent);
					
				} elseif ($current_status < 5) {
					go_add_post($user_id, $task_id, $current_status, 
					floor($rewards['points'][$current_status-1] * $update_percent), 
					floor($rewards['currency'][$current_status-1] * $update_percent), 
					floor($rewards['bonus_currency'][$current_status-1] * $update_percent), 
					null, $page_id, null, null, null, null, null, null);
					
					$changed['points'] += floor($rewards['points'][$current_status-1] * $update_percent);
					$changed['currency'] += floor($rewards['currency'][$current_status-1] * $update_percent);
					$changed['bonus_currency'] += floor($rewards['bonus_currency'][$current_status-1] * $update_percent);
				}
			}
		}
		if ($message === 'See me') {
			go_message_user($user_id, $message.' about, <a href="'.get_permalink($task_id).'" style="display: inline-block; text-decoration: underline; padding: 0px; margin: 0px;">'.get_the_title($task_id).'</a>, please.');
		} else {
			go_message_user($user_id, 'RE: <a href="'.get_permalink($task_id).'">'.get_the_title($task_id).'</a> '.$message);
		}
		$current_points = go_return_points($user_id);
		$updated_rank = get_user_meta($user_id, 'go_rank', true);
		if ($current_rank[0][0] != $updated_rank[0][0]) {
			$changed['current_points'] = $current_points;
			$changed['rank'] = $updated_rank[0][0];
			$changed['rank_points'] = $updated_rank[0][1];
			$changed['next_rank_points'] = $updated_rank[1][1];
		}
	}
	
	echo json_encode($changed);
	die();
}
	
function go_stats_item_list () {
	global $wpdb;
	$go_table_name = "{$wpdb->prefix}go";
	if (!empty($_POST['user_id'])) {
		$user_id = $_POST['user_id'];
	} else {
		$user_id = get_current_user_id();
	}
	$items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$go_table_name} WHERE uid = %d AND status = %d AND gifted = %d ORDER BY timestamp DESC, reason DESC, id DESC", $user_id, -1, 0));
	$gifted_items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$go_table_name} WHERE uid = %d AND status = %d AND gifted = %d ORDER BY timestamp DESC, reason DESC, id DESC", $user_id, -1, 1));
	?>
	<ul id='go_stats_item_list_purchases' class='go_stats_body_list'>
		<li class='go_stats_body_list_head'>PURCHASES</li>
		<?php
		
		foreach ($items as $item) {
			$item_id = $item->post_id;
			$item_count_total = $wpdb->get_var($wpdb->prepare("SELECT SUM(count) FROM {$go_table_name} WHERE uid=%d AND status=%d AND post_id=%d ", $user_id, -1, $item_id));
			$count_before = $wpdb->get_var($wpdb->prepare("SELECT SUM(count) FROM {$go_table_name} WHERE uid=%d AND status=%d AND post_id=%d AND id<=%d", $user_id, -1, $item_id, $item->id));
			$purchase_date = $item->timestamp;
			$purchase_reason = $item->reason;
			?>
				<li class='go_stats_item go_stats_purchased_item'>
					<?php
						echo "<a href='#' onclick='go_lb_opener({$item_id})'>".get_the_title($item_id)."</a> ({$count_before} of {$item_count_total}) {$purchase_date} {$purchase_reason}";
					?>
				</li>
			<?php
		}
		?>
	</ul>
	<ul id='go_stats_item_list_recieved' class='go_stats_body_list'>
		<li class='go_stats_body_list_head'>RECEIVED</li>
        <?php
		
		if (!empty($gifted_items)) {		
			foreach ($gifted_items as $item) {
				$item_id = $item->post_id;
				$item_count_total = $wpdb->get_var($wpdb->prepare("SELECT SUM(count) FROM {$go_table_name} WHERE uid=%d AND status=%d AND post_id=%d ", $user_id, -1, $item_id));
				$count_before = $wpdb->get_var($wpdb->prepare("SELECT SUM(count) FROM {$go_table_name} WHERE uid=%d AND status=%d AND post_id=%d AND id<=%d", $user_id, -1, $item_id, $item->id));
				$purchase_date = $item->timestamp;
				$purchase_reason = $item->reason;
				?>
					<li class='go_stats_item go_stats_purchased_item'>
						<?php
							echo "<a href='#' onclick='go_lb_opener({$item_id})'>".get_the_title($item_id)."</a> ({$count_before} of {$item_count_total}) {$purchase_date}";
						?>
					</li>
				<?php
			}
		}
		?>
	</ul>
	<ul class='go_stats_body_list'>
		<li class='go_stats_body_list_head'>SOLD (coming soon)</li>
	</ul>
	<?php
	die();
}

function go_stats_rewards_list () {
	global $wpdb;
	$go_table_name = "{$wpdb->prefix}go";
	if (!empty($_POST['user_id'])) {
		$user_id = $_POST['user_id'];
	} else {
		$user_id = get_current_user_id();
	}
	$new_tab = ($user_id != get_current_user_id())?"target='_blank'":"";
	$rewards = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$go_table_name} WHERE uid = %d AND (points != %d OR currency != 0 OR bonus_currency != 0) ORDER BY id DESC", $user_id, 0));
	?>
	<ul id='go_stats_rewards_list_points' class='go_stats_body_list'>
		<li class='go_stats_body_list_head'><?php echo strtoupper(go_return_options('go_points_name'));?></li>
		<?php
			foreach ($rewards as $reward) {
				$reward_id = $reward->post_id;
				$reward_points = $reward->points;
				if ($reward_points != 0) {
					?>
						<li class='go_stats_reward go_stats_reward_points'><?php echo (!empty($reward->status)?(($reward->status == -1)?"<a href='#' onclick='go_lb_opener({$reward_id})'>".get_the_title($reward_id)."</a>":(($reward->status < 6)?"<a href='".get_permalink($reward_id)."' {$new_tab}>".get_the_title($reward_id)."</a>":"{$reward->reason}")):"")."<div class='go_stats_amount'>({$reward_points})</div>";?>
						</li>
					<?php
				}
			}
		?>
	</ul>
	<ul id='go_stats_rewards_list_currency' class='go_stats_body_list'>
		<li class='go_stats_body_list_head'><?php echo strtoupper(go_return_options('go_currency_name'));?></li>
		<?php
			foreach ($rewards as $reward) {
				$reward_id = $reward->post_id;
				$reward_currency = $reward->currency;
				if ($reward_currency != 0) {
					?>
						<li class='go_stats_reward go_stats_reward_currency'><?php echo (!empty($reward->status)?(($reward->status == -1)?"<a href='#' onclick='go_lb_opener({$reward_id})'>".get_the_title($reward_id)."</a>":(($reward->status < 6)?"<a href='".get_permalink($reward_id)."' {$new_tab}>".get_the_title($reward_id)."</a>":"{$reward->reason}")):"")."<div class='go_stats_amount'>({$reward_currency})</div>";?>
						</li>
					<?php
				}
			}
		?>
	</ul>
	<ul id='go_stats_rewards_list_bonus_currency' class='go_stats_body_list'>
		<li class='go_stats_body_list_head'><?php echo strtoupper(go_return_options('go_bonus_currency_name'));?></li>
		<?php
			foreach ($rewards as $reward) {
				$reward_id = $reward->post_id;
				$reward_bonus_currency = $reward->bonus_currency;
				if ($reward_bonus_currency != 0 && !empty($reward->status) && $reward->status !== 6) {
					echo "<li class='go_stats_reward go_stats_reward_bonus_currency'>".(($reward->status == -1) ? "<a href='#' onclick='go_lb_opener({$reward_id})'>".get_the_title($reward_id)."</a>" : (($reward->status < 6) ? "<a href='".get_permalink($reward_id)."' {$new_tab}>".get_the_title($reward_id)."</a>" : "{$reward->reason}"))."<div class='go_stats_amount'>({$reward_bonus_currency})</div></li>";
				}
			}
		?>
	</ul>
	<?php
	die();
}

function go_stats_minutes_list () {
	global $wpdb;
	$go_table_name = "{$wpdb->prefix}go";
	if (!empty($_POST['user_id'])) {
		$user_id = $_POST['user_id'];
	} else {
		$user_id = get_current_user_id();
	}
	$minutes = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$go_table_name} WHERE uid = %d AND (minutes != %d) ORDER BY id DESC", $user_id, 0)); 
	?>
	<ul id='go_stats_minutes_list' class='go_stats_body_list'>
		<?php 
			foreach ($minutes as $minute) {
				?>
					<li class='go_stats_minutes'>
						<span><?php echo (($minute->status == -1)?"<a href='#' onclick='go_lb_opener({$minute->post_id})'>".get_the_title($minute->post_id)."</a>":$minute->reason).' '.$minute->timestamp;?> </span>
						<div class='go_stats_amount'>(<?php echo $minute->minutes?>)</div>
					</li>
				<?php
			}
		?>
	</ul>
	<?php
	die();
}

function go_stats_penalties_list () {
	global $wpdb;
	$go_table_name = "{$wpdb->prefix}go";
	if (!empty($_POST['user_id'])) {
		$user_id = $_POST['user_id'];
	} else {
		$user_id = get_current_user_id();
	}
	$penalties = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$go_table_name} WHERE uid = %d AND (penalty != %d) ORDER BY id DESC", $user_id, 0)); 
	?>
	<ul id='go_stats_penalties_list' class='go_stats_body_list'>
		<?php 
			foreach ($penalties as $penalty) {
				?>
					<li class='go_stats_penalties'>
						<span><?php echo $penalty->reason.' '.$penalty->timestamp;?> </span>
						<div class='go_stats_amount'>(<?php echo $penalty->penalty?>)</div>
					</li>
				<?php
			}
		?>
	</ul>
	<?php
	die();
}

function go_stats_badges_list () {
	global $wpdb;
	$go_table_name = "{$wpdb->prefix}go";
	if (!empty($_POST['user_id'])) {
		$user_id = $_POST['user_id'];
	} else {
		$user_id = get_current_user_id();
	}
	$badges = get_user_meta($user_id, 'go_badges', true);
	if ($badges) {
		foreach ($badges as $id => $badge) {
			$img = wp_get_attachment_image($badge, array(100,100), false, $atts);
			echo "<div class='go_badge_wrap'><div class='go_badge_container'><div class='go_badge'>{$img}</div></div></div>";
		}
	}
	die();
}

function go_stats_leaderboard_choices () {
	?>
	<div id='go_stats_leaderboard_filters'>
		<div id='go_stats_leaderboard_filters_head'>FILTER</div>
		<div id='go_stats_leaderboard_classes'>
			<?php
			$classes = get_option('go_class_a');
			$first = 1;
			if ($classes) {
				foreach ($classes as $class_a) {
					?>
						<div class='go_stats_leaderboard_class_wrap'><input type='checkbox' class='go_stats_leaderboard_class_choice' value='<?php echo $class_a;?>'><?php echo $class_a;?></div>
					<?php
					$first++;
				}
			}
			?>
		</div>
		<div id='go_stats_leaderboard_focuses'>
			<?php
			$focuses = get_option('go_focus');
			if ($focuses) {
				foreach ($focuses as $focus) {
					?>
						<div class='go_stats_leaderboard_focus_wrap'><input type='checkbox' class='go_stats_leaderboard_focus_choice' value='<?php echo $focus;?>'><?php echo $focus;?></div>
					<?php
				}
			}
			?>
		</div>
		<div id='go_stats_leaderboard_dates'>
       		(coming soon)
			<div class='go_stats_leaderboard_date_wrap'><input type='radio' class='go_stats_leaderboard_date_choice' value='all' checked>All Time</div>
			<div class='go_stats_leaderboard_date_wrap'><input type='radio' class='go_stats_leaderboard_date_choice' value='30'>Last 30 Days</div>
			<div class='go_stats_leaderboard_date_wrap'><input type='radio' class='go_stats_leaderboard_date_choice' value='10'>Last 10 Days</div>
		</div>
	</div>
	<div id='go_stats_leaderboard'></div>
	<?php
	die();
}
function go_return_user_data ($id, $counter, $sort) {
	$points = go_return_points($id);
	$currency = go_return_currency($id);
	$bonus_currency = go_return_bonus_currency($id);
	$badge_count = go_return_badge_count($id);
	$user_data_key = get_userdata($id);
	$user_display = "<a href='#' onclick='go_admin_bar_stats_page_button(&quot;{$id}&quot;);'>{$user_data_key->display_name}</a>";
	switch ($sort) {
		case 'points':
			echo "<li>{$counter} {$user_display} <div class='go_stats_amount'>{$points}</div></li>";
			break;
		case 'currency':
			echo "<li>{$counter} {$user_display} <div class='go_stats_amount'>{$currency}</div></li>";
			break;
		case 'bonus_currency':
			echo "<li>{$counter} {$user_display} <div class='go_stats_amount'>{$bonus_currency}</div></li>";
			break;
		case 'badges':
			echo "<li>{$counter} {$user_display} <div class='go_stats_amount'>{$badge_count}</div></li>";
			break;
	}
}

function go_return_user_leaderboard ($users, $class_a_choice, $focuses, $type, $counter) {
	foreach ($users as $user_ids) {
		foreach ($user_ids as $user_id) {
			if (!user_can($user_id, 'manage_options')) {
				$class_a = get_user_meta($user_id, 'go_classifications', true);
				$focus = get_user_meta($user_id, 'go_focus', true);
				if ($class_a) {
					$class_keys = array_keys($class_a);
				}
				if (!empty($class_a_choice) && !empty($focuses)) {
					if (!empty($class_keys) && !empty($focus)) {
						$class_intersect = array_intersect($class_keys, $class_a_choice);
						if (is_array($focus)) {
							$focus_intersect = array_intersect($focus, $focuses);
						} else {
							$focus_intersect = in_array($focus, $focuses);
						}
						if (!empty($class_intersect) && !empty($focus_intersect)) {
							go_return_user_data($user_id, $counter, $type);
							$counter++;
						}
					}
				} elseif(!empty($class_a_choice)) {
					if (!empty($class_keys)) {
						$class_intersect = array_intersect($class_keys, $class_a_choice);
						if (!empty($class_intersect)) {
							go_return_user_data($user_id, $counter, $type);
							$counter++;
						}
					}
				} elseif(!empty($focuses)) {
					if (!empty($focus)) {
						if (is_array($focus)) {
							$focus_intersect = array_intersect($focus, $focuses);
						} else {
							$focus_intersect = in_array($focus, $focuses);
						}
						if (!empty($focus_intersect)) {
							go_return_user_data($user_id, $counter, $type);
							$counter++;
						}
					}
				}
			}
		}
	}	
}

function go_stats_leaderboard () {
	global $wpdb;
	$go_totals_table_name = "{$wpdb->prefix}go_totals";
	$class_a_choice = $_POST['class_a_choice'];
	$focuses = $_POST['focuses'];
	$date = $_POST['date'];
	?>
	<ul id='go_stats_leaderboard_list_points' class='go_stats_body_list go_stats_leaderboard_list'>
		<li class='go_stats_body_list_head'><?php echo strtoupper(go_return_options('go_points_name'));?></li>
		<?php 
		$counter = 1;
		$users_points = $wpdb->get_results("SELECT uid FROM {$go_totals_table_name} ORDER BY CAST(points as signed) DESC");
		go_return_user_leaderboard($users_points, $class_a_choice, $focuses, 'points', $counter)
		?>
	</ul>
	<ul id='go_stats_leaderboard_list_currency' class='go_stats_body_list go_stats_leaderboard_list'>
		<li class='go_stats_body_list_head'><?php echo strtoupper(go_return_options('go_currency_name'));?></li>
		<?php 
		$counter = 1;
		$users_currency = $wpdb->get_results("SELECT uid FROM {$go_totals_table_name} ORDER BY CAST(currency as signed) DESC");
		go_return_user_leaderboard($users_currency, $class_a_choice, $focuses, 'currency', $counter)
		?>
	</ul>
	<ul id='go_stats_leaderboard_list_bonus_currency' class='go_stats_body_list go_stats_leaderboard_list'>
		<li class='go_stats_body_list_head'><?php echo strtoupper(go_return_options('go_bonus_currency_name'));?></li>
		<?php 
		$counter = 1;
		$users_bonus_currency = $wpdb->get_results("SELECT uid FROM {$go_totals_table_name} ORDER BY CAST(bonus_currency as signed) DESC");
		go_return_user_leaderboard($users_bonus_currency, $class_a_choice, $focuses, 'bonus_currency', $counter)
		?>
	</ul>
	<ul id='go_stats_leaderboard_list_badge_count' class='go_stats_body_list go_stats_leaderboard_list'>
		<li class='go_stats_body_list_head'>BADGES</li>
		<?php 
		$counter = 1;
		$users_badge_count = $wpdb->get_results("SELECT uid FROM {$go_totals_table_name} ORDER BY CAST(badge_count as signed) DESC");
		go_return_user_leaderboard($users_badge_count, $class_a_choice, $focuses, 'badges', $counter)
		?>
	</ul>
	<?php 
	die();
}
	
 ?>