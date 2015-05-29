<?php
/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

if ( ! class_exists( 'Timber' ) ) {
	echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
	return;
}

$context = Timber::get_context();
$context['posts'] = Timber::get_posts();

// If the user is logged in we should send that object to our templates
if ( is_user_logged_in() ) {
  $user = new TimberUser();
  

  $avatar = get_avatar( $current_user->user_email, 64 );
  $avatar_url = preg_match("/src='(.*?)'/i", $avatar, $matches_url);
  $avatar_width = preg_match("/width='(.*?)'/i", $avatar, $matches_width);
  $avatar_height = preg_match("/height='(.*?)'/i", $avatar, $matches_height);

  $avatar = array(
    'img_url' => $matches_url[1],
    'width' => $matches_width[1],
    'height' => $matches_height[1]
  );

  $logout_link = wp_logout_url( home_url() );

  //var_dump($avatar);  
  //$user['avatar_url'] = $matches[1];
  $context['avatar'] = $avatar;
  $context['logout_url'] = $logout_link;
  $context['user'] = $user;
}

$context['foo'] = 'bar';
$templates = array( 'index.twig' );
if ( is_home() ) {
	array_unshift( $templates, 'home.twig' );
}

Timber::render( $templates, $context );
