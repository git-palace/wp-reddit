<?php
/*
	Plugin Name: WP Reddit Scraper Addon
*/


// Only access to admin
add_action( 'wp_enqueue_scripts', function() {
	if ( !post_type_exists( 'insta_poster' ) )
		return;

	wp_redirect( admin_url( 'edit.php?post_type=insta_poster' ) );
	exit;
} );

add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_style( 'reddit-css', plugin_dir_url( __FILE__ ) . 'styles.css' );
});


// add images box in ins poster editor
add_action( 'add_meta_boxes_insta_poster', function( $post ) {
	add_meta_box( 
		'scraped-images-box',
		__( 'Scrapped Images from Reddit' ),
		function() {
			require_once 'tpl-main.php';
		}
	);
} );


// hook when insta_poster is published
add_action( 'publish_insta_poster', function( $id, $post ) {
	$sub_reddit = get_field( 'sub_reddit', $id );

	if ( !isset( $sub_reddit ) || empty( $sub_reddit ) )
		return;

	$sub_reddit_list = get_option( 'sub_reddit_list', array() );

	if ( !in_array( $sub_reddit, $sub_reddit_list ) ) {
		array_push( $sub_reddit_list, $sub_reddit );

		update_option( 'sub_reddit_list', $sub_reddit_list );
	}

	scrape_reddit( $sub_reddit );
}, 10, 2 );


function reddit_image_upload( $sub_reddit, $org_img_url ) {
	if ( !isset( $org_img_url ) || empty( $org_img_url ) )
		return;

	$upload_dir = wp_upload_dir();

	$img_path = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . 'reddit-images/' . $sub_reddit );

	if ( !file_exists( $img_path ) )
		wp_mkdir_p( $img_path );

	try {

		$strs = explode( '/', $org_img_url );

		$strs = explode( '?', array_pop( $strs ) );

		$filename = array_shift( $strs );

		file_put_contents( $img_path . $filename, file_get_contents( $org_img_url ) );

	} catch (Exception $e) {

	}

	return trailingslashit( trailingslashit( $upload_dir['baseurl'] ) . 'reddit-images/' . $sub_reddit ) . $filename;
}

// get data from reddit
function scrape_reddit( $sub_reddit ) {

	$url = sprintf( 'https://www.reddit.com/r/%s/.json?limit=10', $sub_reddit );

	$response = wp_remote_get( $url );

	$article_list = get_option( 'sub_reddit_' . $sub_reddit, array() );

	$response = json_decode( $response['body'], true );

	foreach ( $response['data']['children'] as $article ) {
		$timestamp = (string)intval($article['data']['created_utc']);

		if ( !array_key_exists( $timestamp, $article_list) ) {

			$temp_article = array(
				'title' => '',
				'images' => array()
			);

			$temp_article['title'] = $article['data']['title'];

			if ( array_key_exists( 'preview', $article['data'] ) ) {
				foreach ( $article['data']['preview']['images'] as $image )
					$temp_article['images'][] = reddit_image_upload( $sub_reddit, $image['source']['url'] );
			}

			$article_list[$timestamp] = $temp_article;
		}
	}

	update_option( 'sub_reddit_' . $sub_reddit, $article_list );
}

add_action( 'scrap_all_sub_reddits', function() {
	$sub_reddit_list = get_option( 'sub_reddit_list', array() );

	foreach ( $sub_reddit_list as $sub_reddit ) {
		scrape_reddit( $sub_reddit );
	}
} );