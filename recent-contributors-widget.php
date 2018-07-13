<?php
/*
Plugin Name: Recent Contributors Widget
Plugin URI: https://github.com/theukedge/recent-contributors-widget
Description: Displays a list of everyone that has contributed to your site recently (time period can be defined)
Version: 1.2
Author: Dave Clements
Author URI: https://www.davidclements.me
License: GPLv2
Text Domain: recent-contributors-widget
*/

/*  Copyright 2016  Dave Clements  (email : https://www.theukedge.com/contact/)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, A  02110-1301  USA
*/

/* ---------------------------------- *
 * constants
 * ---------------------------------- */

if ( !defined( 'RCW_PLUGIN_DIR' ) ) {
	define( 'RCW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( !defined( 'RCW_PLUGIN_URL' ) ) {
	define( 'RCW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}


// Start class recent_contributors_widget //

class recent_contributors_widget extends WP_Widget {

	// Constructor //

	function recent_contributors_widget() {
		load_plugin_textdomain( 'recent-contributors-widget' );
		parent::__construct(false, $name = __('Recent Contributors Widget', 'recent-contributors-widget'), array('description' => __('Displays a list of recent contributors to your site', 'recent-contributors-widget')) );
	}

	// Extract Args //

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']); // the widget title
		$timestring = $instance['timestring']; // How recent to pull contributors from
		$linkdestination = $instance['linkdestination']; // Where to link author name to
		$postcount = $instance['postcount']; // Whether to show number of posts by each author
		$showavatar = $instance['showavatar']; // Whether to show the author's avatar
		$showname = $instance['showname']; // whether to show the author's name
		$blacklist = array_map('trim', explode(',', $instance['blacklist'])); // user IDs to exclude

	// Before widget //

		echo $before_widget;

	// Title of widget //

		if ( $title ) { echo $before_title . $title . $after_title; }

	// Widget output //

	// get default avatar size
	$recent_contributors_avatar_size = apply_filters( 'recent_contributors_avatar_size', 48 );

		// get all contributors and their display names
		$args = array(
			'orderby'      => 'ID',
			'order'        => 'ASC',
			'exclude'      => $blacklist,
			'fields'       => ['ID', 'display_name'],
			'who'          => 'authors'
		);
		$authors = get_users( $args );
		?>

		<ul>
		<?php
		// check whether a user has contributed in the time specified
		// and save the date of their last post
		foreach( $authors as $key => &$author ) {

			$args = array(
				'author'         => $author->ID,
				'date_query'     => array( 'after' => $timestring ),
				'posts_per_page' => 1
			);
			$query = new WP_Query( $args );

			if( $query->have_posts() ) {
				while($query->have_posts()) {
					$query->the_post();
					$author->last_post = get_the_date('U');
				}
				wp_reset_postdata();
			} else {
				unset($authors[$key]);
			}

		}

		// sort authors by date of last post, reverse chronological
		uasort( $authors, function ( $a, $b ) {

			if( $b->last_post > $a->last_post ) {
				return 1;
			} elseif( $b->last_post < $a->last_post ) {
				return -1;
			} else {
				return 0;
			}

		} );

		// render the HTML for the widget
		$count = 0;
		foreach( $authors as $author ) {

			$count++;
			$avatar = $name = $link = "";

			if( $showavatar ) {
				$avatar = get_avatar( $author->ID, $recent_contributors_avatar_size, null, $author->display_name );
			}
			if( $showname ) {
				$name = $author->display_name;
			}
			if( $linkdestination == 'posts_list' ) {
				$link = get_author_posts_url( $author->ID );
				$link_rel = "";
			} elseif( $linkdestination == 'website' ) {
				$link = get_the_author_meta( 'user_url', $author->ID );
				$link_rel = "external nofollow";
			}
		?>
			<li>
				<div class="recent-contributor vcard">
				<?php if( $avatar ): ?>
					<?php if( $link ): ?>
						<a class="url" href="<?php echo $link; ?>"><?php echo $avatar; ?></a>
					<?php else: ?>
						<?php echo $avatar; ?>
					<?php endif; ?>
				<?php endif; ?>
				<?php if( $name ): ?>
					<span class="fn n">
						<span class="screen-reader-text"><?php _e( 'Author ', 'recent-contributors-widget' ); ?></span>
						<?php if( $link ): ?>
							<a href="<?php echo $link; ?>" rel="<?php echo $link_rel; ?>" class="url"><?php echo $name; ?></a>
						<?php else: ?>
							<?php echo $name; ?>
						<?php endif; ?>
					</span>
				<?php endif; ?>
				<?php if( $postcount ): ?>
					<span class="post-count">
						<span class="screen-reader-text"><?php _e( 'Post count ', 'recent-contributors-widget' ); ?></span>
						(<?php echo $query->post_count; ?>)
					</span>
				<?php endif; ?>
				</div>
			</li>
		<?php } ?>
		<?php if( $count === 0): ?>
			<li>
				<p>
					<?php _e( 'No recent contributors.', 'recent-contributors-widget' ); ?>
				</p>
			</li>
		<?php endif; ?>
		</ul>
		<?php

	// After widget //

		echo $after_widget;
	}

	// Update Settings //

	function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['timestring'] = strip_tags($new_instance['timestring']);
			$instance['linkdestination'] = strip_tags($new_instance['linkdestination']);
			$instance['postcount'] = strip_tags($new_instance['postcount']);
			$instance['showavatar'] = strip_tags($new_instance['showavatar']);
			$instance['showname'] = strip_tags($new_instance['showname']);
			$instance['blacklist'] = strip_tags($new_instance['blacklist']);
		return $instance;
	}

	// Widget Control Panel //

	function form($instance) {

		$defaults = array( 'title' => 'Recent Contributors', 'timestring' => '30 days ago', 'linkdestination' => 'none', 'showavatar' => 0, 'showname' => 1, 'postcount' => 1, 'blacklist' => '');
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'recent-contributors-widget'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('timestring'); ?>"><?php _e('Since when', 'recent-contributors-widget'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('timestring'); ?>" name="<?php echo $this->get_field_name('timestring'); ?>'" type="text" value="<?php echo $instance['timestring']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('linkdestination'); ?>"><?php _e('Link destination', 'recent-contributors-widget'); ?>:</label>
			<select id="<?php echo $this->get_field_id('linkdestination'); ?>" name="<?php echo $this->get_field_name('linkdestination'); ?>">
				<option value="none" <?php selected( 'none', $instance['linkdestination'] ); ?>><?php _e( 'No link', 'recent-contributors-widget' ); ?></option>
				<option value="posts_list" <?php selected( 'posts_list', $instance['linkdestination'] ); ?>><?php _e( 'Posts list', 'recent-contributors-widget' ); ?></option>
				<option value="website" <?php selected( 'website', $instance['linkdestination'] ); ?>><?php _e( 'Author\'s website' , 'recent-contributors-widget' ); ?></option>
			</select>
		</p>
		<p>
			<input id="<?php echo $this->get_field_id('showavatar'); ?>" name="<?php echo $this->get_field_name('showavatar'); ?>" type="checkbox" value="1" <?php checked( '1', $instance['showavatar'] ); ?>/>
			<label for="<?php echo $this->get_field_id('showavatar'); ?>"><?php _e('Display Avatar?'); ?></label>
		</p>
		<p>
			<input id="<?php echo $this->get_field_id('showname'); ?>" name="<?php echo $this->get_field_name('showname'); ?>" type="checkbox" value="1" <?php checked( '1', $instance['showname'] ); ?>/>
			<label for="<?php echo $this->get_field_id('showname'); ?>"><?php _e('Display Name?'); ?></label>
		</p>
		<p>
			<input id="<?php echo $this->get_field_id('postcount'); ?>" name="<?php echo $this->get_field_name('postcount'); ?>" type="checkbox" value="1" <?php checked( '1', $instance['postcount'] ); ?>/>
			<label for="<?php echo $this->get_field_id('postcount'); ?>"><?php _e('Display Post Count?'); ?></label>
        </p>
		<p>
			<label for="<?php echo $this->get_field_id('blacklist'); ?>"><?php _e('Blacklist User IDs', 'recent-contributors-widget'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('blacklist'); ?>" name="<?php echo $this->get_field_name('blacklist'); ?>'" type="text" value="<?php echo $instance['blacklist']; ?>" />
		</p>

	<?php }

}

// End class recent_contributors_widget

add_action('widgets_init', create_function('', 'return register_widget("recent_contributors_widget");'));

if( !is_admin() ) {

	function recent_contributors_widget_scripts() {
		wp_register_style( 'recent-contributors', plugins_url( '/recent-contributors.css' , __FILE__ ) );
		wp_enqueue_style( 'recent-contributors' );
	}
	add_action( 'wp_enqueue_scripts', 'recent_contributors_widget_scripts' );

}
