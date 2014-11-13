<?php
/////////////////////////////////////////////
//////////// Front-End Lightbox! ////////////
///////// Module by Vincent Astolfi /////////
/////////////////////////////////////////////
///////////// Special Thanks To /////////////
///////// http://www.ajaxload.info/ /////////
////////////// For Loading Icons ////////////
/////////////////////////////////////////////
//Includes
include ('buy-ajax.php'); // Ajax run when buying something
// Main Lightbox Ajax Function
function go_the_lb_ajax(){
    check_ajax_referer( 'go_lb_ajax_referall', 'nonce' );
	global $wpdb;
	$table_name_go = $wpdb->prefix."go";
	$the_id = $_POST["the_item_id"];
	$the_post = get_post($the_id);
	$the_title = $the_post->post_title;
	$item_content = get_post_field('post_content', $the_id);
	$the_content = wpautop($item_content);
	$custom_fields = get_post_custom($the_id);
	if (isset($custom_fields['go_mta_penalty_switch'])) {
		$penalty = true;
	}
	
	$store_cost = unserialize($custom_fields['go_mta_store_cost'][0]);
	if (!empty($store_cost)) {
		$req_currency = $store_cost[0];
		$req_points = $store_cost[1];
		$req_bonus_currency = $store_cost[2];
		$req_penalty =  $store_cost[3];
		$req_minutes = $store_cost[4];
	}

	$store_filter = unserialize($custom_fields['go_mta_store_filter'][0]);
	$is_filtered = $store_filter[0];
	if ($is_filtered) {
		$req_rank = $store_filter[1];
		$bonus_filter = ($store_filter[2].length > 0 ? (int)$store_filter[2] : null);
		$penalty_filter = ($store_filter[3].length > 0 ? (int)$store_filter[3] : null);
	}
	$store_limit = unserialize($custom_fields['go_mta_store_limit'][0]);
	$is_limited = $store_limit[0];
	if ($is_limited == 'true') {
		$purchase_limit = $store_limit[1];
	}
	
	$user_id = get_current_user_id();
	$user_points = go_return_points($user_id);
	$user_bonus_currency = go_return_bonus_currency($user_id);
	$user_currency = go_return_currency($user_id);
	$user_penalties = go_return_penalty($user_id);
	$user_minutes = go_return_minutes($user_id);
	$purchase_count = $wpdb->get_var("SELECT SUM(count) FROM {$table_name_go} WHERE post_id={$the_id} AND uid={$user_id} LIMIT 1");
	$is_giftable = $custom_fields['go_mta_store_giftable'][0];

	echo '<h2>'.$the_title.'</h2>';
	echo '<div id="go-lb-the-content">'.do_shortcode($the_content).'</div>';
	if ($user_points >= $req_rank || $req_rank <= 0 || $penalty) {
		$lvl_color = "g"; 
		$output_level = $req_lvl *= -1;
	} else { 
		$lvl_color = "r";
	}

	if ($req_currency == 0) { 
		$gold_color = "n"; 
	} else if ($req_currency < 0) {
		$gold_color = "g"; 
		$output_currency = $req_currency *= -1;
	} else { 
		$gold_color = "r";
	}
	
	if ($req_points == 0) {
		$points_color = "n"; 
	} else if ($req_points < 0) {
		$points_color = "g";
		$output_points = $req_points *= -1;
	} else { 
		$points_color = "r"; 
	}
	
	if ($req_bonus_currency == 0) {
		$bonus_currency_color = "n";
	} else if ($req_bonus_currency < 0) {
		$bonus_currency_color = "g";
		$output_bonus_currency = $req_bonus_currency *= -1;
	} else {
		$bonus_currency_color = "r";
	} 
	
	if ($req_penalty == 0){
		$penalty_color = "n";
	} else if ($req_penalty < 0) {
		$penalty_color = "r";
		$output_penalty = $req_penalty *= -1;
	} else {
		$penalty_color = "g";
	}

	if ($req_minutes == 0) {
		$minutes_color = "n";
	} else if ($req_minutes < 0) {
		$minutes_color = "g";
		$output_minutes = $req_minutes *= -1;
	} else {
		$minutes_color = "r";
	} 
	
	if ($lvl_color == "g" && $gold_color == "g" && $points_color == "g") { 
		$buy_color = "gold"; 
	} else { 
		$buy_color = "gold"; 
	}
		
	$user_focuses = array();

	if ($is_filtered === 'true' && !is_null($penalty_filter) && $user_penalties >= $penalty_filter) {
		$penalty_diff = $user_penalties - $penalty_filter;
		if ($penalty_diff > 0) {
			die("You have {$penalty_diff} too many ".go_return_options('go_penalty_name').".");	
		} else if ($penalty_diff == 0) {
			die("You need less than {$penalty_filter} ".go_return_options('go_penalty_name')." to buy this item.");
		}
	}
	
	// Get focus options associated with item
	$item_focus_array = unserialize($custom_fields['go_mta_store_focus'][0]);
	
	// Check if item actually has focus
	$is_focused = (bool) filter_var($item_focus_array[0], FILTER_VALIDATE_BOOLEAN);
	if ($is_focused) {
		$item_focus = $item_focus_array[1];
	}
	
	// Check if user has a focus
	if (get_user_meta($user_id, 'go_focus', true) != null) {
		$user_focuses = (array) get_user_meta($user_id, 'go_focus', true);
	}
	
	// Check if item is locked by focus
	if ($custom_fields['go_mta_store_focus_lock'][0]){
		$focus_category_lock = true;
	}
	
	// Grab which focuses are chosen as the locks
	if(get_the_terms($the_id, 'store_focus_categories') && $focus_category_lock) {
		$categories = get_the_terms($the_id, 'store_focus_categories');
		$category_names = array();
		foreach ($categories as $category) {
			array_push($category_names, $category->name);	
		}
	}
	
	// Check to see if the user has any of the focuses
	if($category_names && $user_focuses){
		$go_ahead = array_intersect($user_focuses, $category_names);
	}
	
	if ($is_focused && !empty($item_focus) && !empty($user_focuses) && in_array($item_focus, $user_focuses)) {
		die('You already have this '.go_return_options('go_focus_name').'!');	
	}
	if (empty($go_ahead) && $focus_category_lock) {
		die('Item only available to those in '.implode(', ', $category_names).' '.strtolower(go_return_options('go_focus_name')));
	}
	if ($is_filtered === 'true' && !is_null($bonus_filter) && $user_bonus_currency < $bonus_filter) {
		die('You require more '.go_return_options('go_bonus_currency_name').' to view this item.');
	}
	if (!empty($purchase_limit) && $purchase_count >= $purchase_limit) {
		die("You've reached the maximum purchase limit.");
	}
	if ($user_points < $req_rank) {
		die("You need to reach {$req_rank_key} to purchase this item.");
	}
	
	?>
	<div id="golb-fr-price" class="golb-fr-boxes-<?php echo $gold_color; ?>" req="<?php echo $req_currency; ?>" cur="<?php echo $user_currency; ?>"><?php echo go_return_options('go_currency_name').': '.(empty($req_currency) ? 0 : ($req_currency < 0 ? $output_currency : $req_currency)); ?></div>
	<div id="golb-fr-points" class="golb-fr-boxes-<?php echo $points_color; ?>" req="<?php echo $req_points; ?>" cur="<?php echo $user_points; ?>"><?php echo go_return_options('go_points_name').': '.(empty($req_points) ? 0 : ($req_points < 0 ? $output_points : $req_points)); ?></div>
	<div id="golb-fr-bonus_currency" class="golb-fr-boxes-<?php echo $bonus_currency_color; ?>" req="<?php echo $req_bonus_currency; ?>" cur="<?php echo $user_bonus_currency; ?>"><?php echo go_return_options('go_bonus_currency_name').': '.(empty($req_bonus_currency) ? 0 : ($req_bonus_currency < 0 ? $output_bonus_currency : $req_bonus_currency)); ?></div>
    <div id='golb-fr-penalty' class='golb-fr-boxes-<?php echo $penalty_color; ?>' req='<?php echo $req_penalty; ?>' cur='<?php echo $user_penalties; ?>'><?php echo go_return_options('go_penalty_name').': '.(empty($req_penalty) ? 0 : ($req_penalty < 0 ? $output_penalty : $req_penalty));?></div>
    <div id="golb-fr-minutes" class="golb-fr-boxes-<?php echo $minutes_color; ?>" req="<?php echo $req_minutes; ?>" cur="<?php echo $user_minutes; ?>"><?php echo go_return_options('go_minutes_name').': '.(empty($req_minutes) ? 0 : ($req_minutes < 0 ? $output_minutes : $req_minutes)); ?></div>
	<div id="golb-fr-qty" class="golb-fr-boxes-n">Qty: <input id="go_qty" style="width: 40px;font-size: 11px; margin-right:0px; margin-top: 0px; bottom: 3px; position: relative;" value="1" disabled="disabled" /></div>
	
	<div id="golb-fr-buy" class="golb-fr-boxes-<?php echo $buy_color; ?>" onclick="goBuytheItem('<?php echo $the_id; ?>', '<?php echo $buy_color; ?>', '<?php echo $purchase_count?>'); this.removeAttribute('onclick');">Buy</div>
	<div id="golb-fr-purchase-limit" val="<?php echo $purchase_limit;?>"><?php if($purchase_limit == 0){echo 'No limit';} else{ echo 'Limit '.$purchase_limit; }?> </div>
	<div id="golb-purchased">
	<?php 
		if (is_null($purchase_count)) { 
			echo 'Quantity purchased: 0';
		} else {
			echo "Quantity purchased: {$purchase_count}";
		} 
	 if(!$item_focus && !$penalty && $is_giftable == "on"){?>
 		<br />
		Gift this item <input type='checkbox' id='go_toggle_gift_fields'/>
        <div id="go_recipient_wrap" class="golb-fr-boxes-giftable">Gift To: <input id="go_recipient" type="text"/></div>
        <div id="go_search_results"></div>
        
     <script>   
		var go_gift_check_box = jQuery("#go_toggle_gift_fields");
		var go_gift_text_box = jQuery("#go_recipient_wrap");
		go_gift_text_box.prop("hidden", true);
		go_gift_check_box.click(function () {
			if (jQuery(this).is(":checked")) {
				go_gift_text_box.prop("hidden", false);
			} else {
				go_gift_text_box.prop("hidden", true);
				jQuery('#go_search_results').hide();
				jQuery("#go_recipient").val('');
			}
		});
	</script>
    
	<?php }?>
	</div>
	<?php
	
	
    die();
	
}

add_action('wp_ajax_go_lb_ajax', 'go_the_lb_ajax');
add_action('wp_ajax_nopriv_go_lb_ajax', 'go_the_lb_ajax');
////////////////////////////////////////////////////
function go_frontend_lightbox_css() {
	$go_lb_css_dir = plugins_url( '/css/go-lightbox.css' , __FILE__ );
	echo '<link rel="stylesheet" href="'.$go_lb_css_dir.'" />';
}
add_action('wp_head', 'go_frontend_lightbox_css');

function go_frontend_lightbox_html() {
?>
<script type="text/javascript">

function go_lb_closer() {
	document.getElementById('light').style.display='none';
	document.getElementById('fade').style.display='none';
	document.getElementById('lb-content').innerHTML = '';
}
function go_lb_opener(id) {
	jQuery('#light').css('display', 'block');
	if(jQuery('#go_stats_page_black_bg').css('display') == 'none'){
		jQuery('#fade').css('display', 'block');
	}
	if( !jQuery.trim( jQuery('#lb-content').html() ).length ) {
	var get_id = id;
	var gotoSend = {
                action:"go_lb_ajax",
                nonce: "<?php echo esc_js( wp_create_nonce('go_lb_ajax_referall') ); ?>",
				the_item_id: get_id,
    };
	var url_action = "<?php echo admin_url('/admin-ajax.php'); ?>";
            jQuery.ajaxSetup({cache:true});
            jQuery.ajax({
                url: url_action,
                type:'POST',
                data: gotoSend,
				beforeSend: function() {
				jQuery("#lb-content").append('<div class="go-lb-loading"></div>');
					},
                cache: false,
                success:function(results, textStatus, XMLHttpRequest){  
					jQuery("#lb-content").innerHTML = "";
					jQuery("#lb-content").html('');  
					jQuery("#lb-content").append(results);
					window.go_req_currency = jQuery('#golb-fr-price').attr('req');
					window.go_req_points = jQuery('#golb-fr-points').attr('req');
					window.go_req_bonus_currency = jQuery('#golb-fr-bonus_currency').attr('req');
					window.go_req_minutes = jQuery('#golb-fr-minutes').attr('req');
					window.go_cur_currency = jQuery('#golb-fr-price').attr('cur');
					window.go_cur_points = jQuery('#golb-fr-points').attr('cur');
					window.go_cur_bonus_currency = jQuery('#golb-fr-bonus_currency').attr('cur');
					window.go_cur_minutes = jQuery('#golb-fr-minutes').attr('cur');
					window.go_purchase_limit = jQuery('#golb-fr-purchase-limit').attr('val');
					if(go_purchase_limit == 0){
						go_purchase_limit = Number.MAX_VALUE;
					} 
					jQuery('#go_qty').spinner({
			
						max: Math.min(Math.floor(go_cur_currency/go_req_currency),Math.floor(go_cur_points/go_req_points),go_purchase_limit),
						min: 1,
						stop: function(event, ui){
							jQuery(this).change();
						}
					});
					jQuery('#go_qty').change(function(){
						var price_raw = jQuery('#golb-fr-price').html();
						var price_sub = price_raw.substr(price_raw.indexOf(":")+2);
						if (price_sub.length > 0) {
							var price = price_raw.replace(price_sub, go_req_currency * jQuery(this).val());
							jQuery('#golb-fr-price').html(price);
						}
						
						var points_raw = jQuery('#golb-fr-points').html();
						var points_sub = points_raw.substr(points_raw.indexOf(":")+2);
						if (points_sub.length > 0) {
							var points = points_raw.replace(points_sub, go_req_points * jQuery(this).val());
							jQuery('#golb-fr-points').html(points);
						}
						
						var bonus_currency_raw = jQuery('#golb-fr-bonus_currency').html();
						var bonus_currency_sub = bonus_currency_raw.substr(bonus_currency_raw.indexOf(":")+2);
						if (bonus_currency_sub.length > 0) {
							var bonus_currency = bonus_currency_raw.replace(bonus_currency_sub, go_req_bonus_currency * jQuery(this).val());
							jQuery('#golb-fr-bonus_currency').html(bonus_currency);
						}
						
						var minutes_raw = jQuery('#golb-fr-minutes').html();
						var minutes_sub = minutes_raw.substr(minutes_raw.indexOf(":")+2);
						if (minutes_sub.length > 0) {
							var minutes = minutes_raw.replace(minutes_sub, go_req_minutes * jQuery(this).val());
							jQuery('#golb-fr-minutes').html(minutes);
						}
					});
					if(jQuery('.white_content').css('display') != 'none'){
						jQuery(document).keyup(function(e) { 
							if (e.keyCode == 27) { // If keypressed is escape, run this
								go_lb_closer();
							} 
						});
						jQuery('.black_overlay').click(function(){
							go_lb_closer();
						});
					}
					var done_typing = 0;
					var typing_timer;
					var recipient = jQuery('#go_recipient');
					var search_res = jQuery('#go_search_results');
					recipient.keyup(function(){
						clearTimeout(typing_timer);
						if(recipient.val().length != 0){
							typing_timer = setTimeout(function(){
								go_search_for_user(recipient.val());
							}, done_typing);
						} else {
							jQuery('#go_search_results').hide();
						}
					});
					recipient.focus(function(){
						if(search_res.is(':hidden')){
							search_res.empty();
							search_res.show();	
						}
					});
  				}, 
            });
	}
}
function go_fill_recipient(el){
	var el = jQuery(el);
	var val = el.text();
	var recipient = jQuery('#go_recipient');
	recipient.val(val);
	el.parent().hide();
}

function go_close_this(el){
	jQuery(el).parent().hide();	
}

function go_search_for_user(user){
	var url_action = "<?php echo admin_url('/admin-ajax.php'); ?>";
	jQuery.ajax({
		url: url_action,
		type: "POST",
		data: {
			action: 'go_search_for_user',
			user: user
		},
		success: function(data){
			var recipient = jQuery('#go_recipient');
			var search_res = jQuery('#go_search_results');
			var position = recipient.position();
			search_res.css({top: position.top + recipient.height() + 1 + "px" , left: position.left + "px", width: recipient[0].getBoundingClientRect().width - 2 + "px"});
			search_res.html(data);
		}
	});
}

</script>
	<div id="light" class="white_content">
    	<a href="javascript:void(0)" onclick="go_lb_closer();" class="go_lb_closer">Close</a>
        <div id="lb-content"></div>
    </div>
	<div id="fade" class="black_overlay"></div>
<?php
}
add_action('wp_head', 'go_frontend_lightbox_html');

function go_search_for_user(){
	global $wpdb;
	$user = $_POST['user'];
	$users = $wpdb->get_results("SELECT display_name FROM ".$wpdb->users." WHERE display_name LIKE '%".$user."%' LIMIT 0, 4");
	if($users){
		foreach($users as $user_name){
			echo '<a href="javascript:;" class="go_search_res_user" onclick="go_fill_recipient(this)">'.$user_name->display_name."</a><br/>";
		}
	}else{
		echo '<a href="javascript:;" class="go_search_res_user" onclick="go_close_this(this)">No users found</a>';	
	}
	die();	
}

function go_get_purchase_count(){
	global $wpdb;
	$table_name_go = $wpdb->prefix."go";
	$the_id = $_POST["the_item_id"];
	$user_id = get_current_user_id();
	$purchase_count = $wpdb->get_var("SELECT SUM(count) FROM {$table_name_go} WHERE post_id={$the_id} AND uid={$user_id} LIMIT 1");
	if($purchase_count == NULL){ 
		echo '0';
	} else{
		echo $purchase_count;
	}
	die();
}
?>