<?php
/**
 * Ajax comments in the theme.
 *
 *
 * @version 1.0
 * @package invoker
 * @copyright 2012 all rights reserved
 *
 */
add_action('init', 'mfthemes_ajax_comment');
function mfthemes_ajax_comment(){
/**
 * WordPress jQuery-Ajax-Comments v1.3 by Willin Kan.
 * URI: http://kan.willin.org/?p=1271
 * for WP3.5+ | modified version URI: http://mufeng.me/wordpress3-5-willin-ajax-comment.html
 */
	if($_POST['action'] == 'mfthemes_ajax_comment' && 'POST' == $_SERVER['REQUEST_METHOD']){
		global $wpdb;
		nocache_headers();
		$comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;

		$post = get_post($comment_post_ID);

		if ( empty($post->comment_status) ) {
			do_action('comment_id_not_found', $comment_post_ID);
			mfthemes_ajax_comment_err(__('Invalid comment status.')); // 將 exit 改為錯誤提示
		}

		// get_post_status() will get the parent status for attachments.
		$status = get_post_status($post);

		$status_obj = get_post_status_object($status);

		if ( !comments_open($comment_post_ID) ) {
			do_action('comment_closed', $comment_post_ID);
			mfthemes_ajax_comment_err(__('评论已关闭!')); // 將 wp_die 改為錯誤提示
		} elseif ( 'trash' == $status ) {
			do_action('comment_on_trash', $comment_post_ID);
			mfthemes_ajax_comment_err(__('Invalid comment status.')); // 將 exit 改為錯誤提示
		} elseif ( !$status_obj->public && !$status_obj->private ) {
			do_action('comment_on_draft', $comment_post_ID);
			mfthemes_ajax_comment_err(__('Invalid comment status.')); // 將 exit 改為錯誤提示
		} elseif ( post_password_required($comment_post_ID) ) {
			do_action('comment_on_password_protected', $comment_post_ID);
			mfthemes_ajax_comment_err(__('Password Protected')); // 將 exit 改為錯誤提示
		} else {
			do_action('pre_comment_on_post', $comment_post_ID);
		}

		$comment_author       = ( isset($_POST['author']) )  ? trim(strip_tags($_POST['author'])) : null;
		$comment_author_email = ( isset($_POST['email']) )   ? trim($_POST['email']) : null;
		$comment_author_url   = ( isset($_POST['url']) )     ? trim($_POST['url']) : null;
		$comment_content      = ( isset($_POST['comment']) ) ? trim($_POST['comment']) : null;
		$edit_id              = ( isset($_POST['edit_id']) ) ? $_POST['edit_id'] : null; // 提取 edit_id

		// If the user is logged in
		$user = wp_get_current_user();
		if ( $user->exists() ) {
			if ( empty( $user->display_name ) )
				$user->display_name=$user->user_login;
			$comment_author       = $wpdb->escape($user->display_name);
			$comment_author_email = $wpdb->escape($user->user_email);
			$comment_author_url   = $wpdb->escape($user->user_url);
			if ( current_user_can('unfiltered_html') ) {
				if ( wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment'] ) {
					kses_remove_filters(); // start with a clean slate
					kses_init_filters(); // set up the filters
				}
			}
		} else {
			if ( get_option('comment_registration') || 'private' == $status )
				mfthemes_ajax_comment_err(__('你必须要登陆之后才可以发表评论.')); // 將 wp_die 改為錯誤提示
		}

		$comment_type = '';

		if ( get_option('require_name_email') && !$user->exists() ) {
			if ( 6 > strlen($comment_author_email) || '' == $comment_author )
				mfthemes_ajax_comment_err( __('请填写昵称和邮箱.') ); // 將 wp_die 改為錯誤提示
			elseif ( !is_email($comment_author_email))
				mfthemes_ajax_comment_err( __('请填写一个有效的邮箱.') ); // 將 wp_die 改為錯誤提示
		}

		if ( '' == $comment_content )
			mfthemes_ajax_comment_err( __('请输入评论.') ); // 將 wp_die 改為錯誤提示


		// 增加: 檢查重覆評論功能
		$dupe = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = '$comment_post_ID' AND ( comment_author = '$comment_author' ";
		if ( $comment_author_email ) $dupe .= "OR comment_author_email = '$comment_author_email' ";
		$dupe .= ") AND comment_content = '$comment_content' LIMIT 1";
		if ( $wpdb->get_var($dupe) ) {
			mfthemes_ajax_comment_err(__('您已经发布过一条相同的评论!'));
		}

		// 增加: 檢查評論太快功能
		if ( $lasttime = $wpdb->get_var( $wpdb->prepare("SELECT comment_date_gmt FROM $wpdb->comments WHERE comment_author = %s ORDER BY comment_date DESC LIMIT 1", $comment_author) ) ) { 
		$time_lastcomment = mysql2date('U', $lasttime, false);
		$time_newcomment  = mysql2date('U', current_time('mysql', 1), false);
		$flood_die = apply_filters('comment_flood_filter', false, $time_lastcomment, $time_newcomment);
		if ( $flood_die ) {
			mfthemes_ajax_comment_err(__('请过一会再发表评论.'));
			}
		}

		$comment_parent = isset($_POST['comment_parent']) ? absint($_POST['comment_parent']) : 0;

		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

		// 增加: 檢查評論是否正被編輯, 更新或新建評論
		if ( $edit_id ){
			$comment_id = $commentdata['comment_ID'] = $edit_id;
			wp_update_comment( $commentdata );
		} else {
			$comment_id = wp_new_comment( $commentdata );
		}

		$comment = get_comment($comment_id);
		do_action('set_comment_cookies', $comment, $user);

		$comment_depth = 1;   //为评论的 class 属性准备的
		$tmp_c = $comment;
		while($tmp_c->comment_parent != 0){
			$comment_depth++;
			$tmp_c = get_comment($tmp_c->comment_parent);
		}
		
		//此处非常必要，无此处下面的评论无法输出 by mufeng
		$GLOBALS['comment'] = $comment;
		
		//以下是評論式樣, 不含 "回覆". 要用你模板的式樣 copy 覆蓋.
		if(!$comment->comment_parent){
		?>
		   <li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
				<div id="comment-<?php comment_ID(); ?>" class="comment-body">
					<div class="comment-avatar"><?php echo get_avatar( $comment, $size = '50'); ?></div>
						<span class="comment-floor">
							<?php 
								++$commentcount;
								switch($commentcount){
									case 1:
										print_r("沙发");
										break;
									case 2:	
										print_r("板凳");
										break;	
									case 3:	
										print_r("地板");
										break;		
									default:
										printf(__('%s楼'), $commentcount);
								}
							?>
						</span>					
					<div class="comment-data">
						<span class="comment-span"><?php printf(__('%s'), get_comment_author_link()) ?></span>
						<span class="comment-span comment-date"><?php echo time_since(abs(strtotime($comment->comment_date_gmt . "GMT")), true);?></span>
					</div>
					<div class="comment-text"><?php comment_text() ?></div>
					<div class="comment-reply"><?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => __('回复')))) ?></div>
				</div>
		<?php }else{?>
		   <li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
				<div id="comment-<?php comment_ID(); ?>" class="comment-body comment-children-body">
					<div class="comment-avatar"><?php echo get_avatar( $comment, $size = '30'); ?></div>
					<span class="comment-floor"><?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => __('回复')))) ?></span>					
					<div class="comment-data">
						<span class="comment-span">
							<?php $comment_parent_href = htmlspecialchars(get_comment_link( $comment->comment_parent ));$comment_parent = get_comment($comment->comment_parent);printf(__('%s'), get_comment_author_link()) ?>
						</span>
						<span class="comment-span comment-date"><?php echo time_since(abs(strtotime($comment->comment_date_gmt . "GMT")), true);?></span>
					</div>
					<div class="comment-text">
						<span class="comment-to"><a href="<?php echo $comment_parent_href;?>" title="<?php echo mb_strimwidth(strip_tags(apply_filters('the_content', $comment_parent->comment_content)), 0, 100,"..."); ?>">@<?php echo $comment_parent->comment_author;?></a>：</span>
						<?php echo get_comment_text(); ?>
					</div>
				</div>	
		<?php }	
		die(); //以上是評論式樣, 不含 "回覆". 要用你模板的式樣 copy 覆蓋.
	}else{return;}
}

/**
 * Ajax comments pagenavi in the theme.
 *
 *
 * @version 1.0
 * @package invoker
 * @copyright 2012 all rights reserved
 *
 */
add_action('init', 'mfthemes_ajax_pagenavi');
function mfthemes_ajax_pagenavi(){ // pagenavi
	if( isset( $_GET['action'] ) && $_GET['action']== 'mfthemes_ajax_pagenavi' ){
		global $post,$wp_query, $wp_rewrite;
		$postid = isset($_GET['post']) ? $_GET['post'] : null;
		$pageid = isset($_GET['page']) ? $_GET['page'] : null;
		if(!$postid || !$pageid){
			mfthemes_ajax_comment_err(__('Error post id or comment page id.'));
		}
		// get comments
		$comments = get_comments('post_id='.$postid);

		$post = get_post($postid);

		if(!$comments){
			mfthemes_ajax_comment_err(__('Error! can\'t find the comments'));
		}

		if( 'desc' != get_option('comment_order') ){
			$comments = array_reverse($comments);
		}

		// set as singular (is_single || is_page || is_attachment)
		$wp_query->is_singular = true;

		// base url of page links
		$baseLink = '';
		if ($wp_rewrite->using_permalinks()) {
			$baseLink = '&base=' . user_trailingslashit(get_permalink($postid) . 'comment-page-%#%', 'commentpaged');
		}
		
		echo '<ol class="commentlist">';
		wp_list_comments('callback=mfthemes_comment&type=comment&page=' . $pageid . '&per_page=' . get_option('comments_per_page'), $comments);
		echo '</ol><div class="comments-data comments-data-footer clearfix"><h2 class="comments-title">Leave a reply</h2><div class="comment-topnav">';
		paginate_comments_links('prev_text=« Previous&next_text=Next »&current=' . $pageid);
		echo '</div></div>';
		
		die;	
	}else{return;}
}

// 增加: 錯誤提示功能
function mfthemes_ajax_comment_err($a) { 
    header('HTTP/1.0 500 Internal Server Error');
	header('Content-Type: text/plain;charset=UTF-8');
    echo $a;
    exit;
}
?>