<?php get_header(); ?>
<?php setPostViews(get_the_ID()); ?>
<div id="primary">
	<?php if(have_posts()):while (have_posts()) : the_post();?>
		<div id="post-<?php the_ID(); ?>" <?php post_class();?>>
			<div class="post-header">
				<h2 class="post-title"><a href="<?php the_permalink(); ?>" title="<?php the_title();?>"><?php the_title();?></a></h2>
			</div>
			<div class="post-meta">
				<span class="post-date"><?php the_time('Y-m-d') ?>&#160;&#183;&#160;</span>
				<span class="post-category"><?php the_category(' '); ?>&#160;&#183;&#160;</span>
				<span class="post-view"><?php if(function_exists('the_views')) the_views();print_r(" views")?>&#160;&#183;&#160;</span>
				<span class="post-reply"><?php comments_popup_link('Leave a reply', '1 reply', '% replies'); ?></span>
			</div>
			<div class="post-body clearfix">
				<div class="post-content"><?php the_content(''); ?></div>
			</div>
		</div>
	<?php endwhile; endif;?>
	<div class="post-affiliate clearfix">
		<div class="post-share">
			<ul class="post-shareul clearfix">
				<li class="post-shareli"><a href="http://service.weibo.com/share/share.php?title=<?php the_title(); ?>&url=<?php the_permalink(); ?>" class="post-sharea psa-weibo" target="_blank" rel="nofollow" title="分享到新浪微博">新浪微博</a></li>
				<li class="post-shareli"><a href="http://v.t.qq.com/share/share.php?title=<?php the_title(); ?>&url=<?php the_permalink(); ?>&site=<?php bloginfo('url');?>" class="post-sharea psa-qqweibo" target="_blank" rel="nofollow" title="分享到腾讯微博">腾讯微博</a></li>
				<li class="post-shareli"><a href="http://www.douban.com/recommend/?url=<?php the_permalink(); ?>&title=<?php the_title(); ?>" class="post-sharea psa-douban" target="_blank" rel="nofollow" title="分享到豆瓣">豆瓣网</a></li>
				<li class="post-shareli"><a href="http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=<?php the_permalink(); ?>&title=<?php the_title(); ?>" class="post-sharea psa-qzone" target="_blank" rel="nofollow" title="分享到QQ空间">QQ空间</a></li>
				<li class="post-shareli"><a href="http://share.renren.com/share/buttonshare?link=<?php the_permalink(); ?>&title=<?php the_title(); ?>" class="post-sharea psa-renren" target="_blank" rel="nofollow" title="分享到人人网">人人网</a></li>
				<li class="post-shareli"><a href="http://twitter.com/share?url=<?php the_permalink(); ?>&text=<?php the_title(); ?>" class="post-sharea psa-twitter" target="_blank" rel="nofollow" title="分享到Twitter">Twitter</a></li>
			</ul>
		</div>
		<div class="post-tags">
			<?php if ( get_the_tags() ) { the_tags(); } else{ echo "暂无关键词！";  } ?>
		</div>
	</div>
	<div class="near-posts">
		<div>
			上一篇：<?php  if (get_next_post()) {next_post_link('%link');} else {echo "已是最新文章！";}; ?>
		</div>
		<div>
			下一篇：<?php  if (get_previous_post()) {previous_post_link('%link');} else {echo "已是最后一篇文章！";}; ?>
		</div>
	</div>
	<?php comments_template(); ?>
</div><!-- #primary -->
<?php if(!IsMobile) get_sidebar(); ?>
<?php get_footer(); ?>