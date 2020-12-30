<?php

add_action( 'wp_ajax_my_action', 'my_action' );

function my_action() {
    echo 'FUCKKKKK';
	wp_die(); // this is required to terminate immediately and return a proper response
}

?>