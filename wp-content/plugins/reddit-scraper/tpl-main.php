<?php
if ( !isset( $_GET['post'] ) || empty( $_GET['post'] ) )
	return;

$sub_reddit = get_field( 'sub_reddit', $_GET['post'] );
$article_list = get_option( 'sub_reddit_' . $sub_reddit, array() );

?>
<ul class="card-columns">
	<?php
	foreach ( $article_list as $key => $article ) {
		if ( !empty( $article['images'] ) )
			include 'tpl-image.php';
	}
	?>
</ul>