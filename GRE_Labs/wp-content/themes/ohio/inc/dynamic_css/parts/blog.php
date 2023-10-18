<?php
/*
	Blog custom style
	
	Table of contents: (use search)
	# 1. Variables
	# 2. Contained layout background
*/

# 1. Variables
$posts_card_background = OhioOptions::get( 'posts_card_background_color', null, false, true );
$posts_card_divider_background = OhioOptions::get( 'posts_card_divider_color', null, false, true );

# 2. Contained layout background
if ( $posts_card_background ) {
	$_selector = [
		'.blog-item:not(.-layout2):not(.-layout7).-contained .card-details',
		'.blog-item.-layout7.-contained'
	];
	$_css = 'background-color:' . $posts_card_background . ';';
	OhioBuffer::pack_dynamic_css_to_buffer( $_selector, $_css );
}

# 3. Divider color
if ( $posts_card_divider_background ) {
	$_selector = [
		'.blog-item.-layout6:not(.-contained)',
		'.blog-item.-layout7:not(.-contained)'
	];
	$_css = 'border-color:' . $posts_card_divider_background . ';';
	OhioBuffer::pack_dynamic_css_to_buffer( $_selector, $_css );
}