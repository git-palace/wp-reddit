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
        function() { }
    );
} );


// hook when insta_poster is published
add_action( 'publish_insta_poster', function( $id, $post) {
	scrape_reddit( $id );
}, 10, 2 );


add_action( 'admin_init', function() {
	scrape_reddit( 12 );
} );


function scrape_reddit( $post_id ) {
	$sub_reddit = get_field( 'sub_reddit', $post_id );

	$response = file_get_contents( sprintf( 'https://www.reddit.com/r/%s/.json?limit=%s', $sub_reddit, 10 ) );

	error_log( print_r( $response, true ) );
}