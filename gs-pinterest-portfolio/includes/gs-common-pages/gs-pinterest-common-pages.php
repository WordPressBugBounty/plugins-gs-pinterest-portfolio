<?php

require_once plugin_dir_path( __FILE__ ) . 'gs-plugins-common-pages.php';

new GS_Plugins_Common_Pages([
	
	'parent_slug' 	=> 'gsp-pinterest-main',
	
	'lite_page_title' 	=> __('Lite Plugins by GS Plugins'),
	'pro_page_title' 	=> __('Premium Plugins by GS Plugins'),
	'help_page_title' 	=> __('Support & Documentation by GS Plugins'),
	'lite_page_slug' 	=> 'gs-pinterest-plugins-lite',
	'pro_page_slug' 	=> 'gs-pinterest-plugins-premium',
	'help_page_slug' 	=> 'gs-pinterest-plugins-help',

	'links' => [
		'docs_link' 	=> 'https://docs.gsplugins.com/gs-pinterest-portfolio/',
		'rating_link' 	=> 'https://wordpress.org/support/plugin/gs-pinterest-portfolio/reviews/',
	]

]);