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


// add images box in ins poster editor
add_action( 'add_meta_boxes_insta_poster', function( $post ) {
	add_meta_box( 
		'scraped-images-box',
		__( 'Scrapped Images from Reddit' ),
		function() {
			$sub_reddit_list = get_option( 'sub_reddit_list', array() );

			echo '<pre>';
			print_r( $sub_reddit_list );
			echo '</pre>';

			$sub_reddit = get_field( 'sub_reddit', $_GET['post'] );

			$article_list = get_option( 'sub_reddit_' . $sub_reddit, array() );

			echo '<pre>';
			print_r( $article_list );
			echo '</pre>';
		}
	);
} );


// hook when insta_poster is published
add_action( 'publish_insta_poster', function( $id, $post ) {
	scrape_reddit( $id );
}, 10, 2 );


// get data from reddit
function scrape_reddit( $post_id ) {
	$sub_reddit = get_field( 'sub_reddit', $post_id );

	if ( !isset( $sub_reddit ) || empty( $sub_reddit ) )
		return;

	$sub_reddit_list = get_option( 'sub_reddit_list', array() );

	if ( !in_array( $sub_reddit, $sub_reddit_list ) ) {
		array_push( $sub_reddit_list, $sub_reddit );

		update_option( 'sub_reddit_list', $sub_reddit_list );
	}

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
					$temp_article['images'][] = $image['source']['url'];
			}

			$article_list[$timestamp] = $temp_article;
		}
	}

	update_option( 'sub_reddit_' . $sub_reddit, $article_list );
}