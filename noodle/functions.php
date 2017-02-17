<?php
/**
 * Theme functions file
 *
 *
 * @package LovePhoto
 * @author Mufeng
 */
 
// Theme-specific files
require( dirname(__FILE__) . '/functions/mfthemes-function.php' );

function getPostViews($postID){
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    return '共 '.$count.' 次浏览';
}
function setPostViews($postID) {
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}
