<?php
/**
 * The template for displaying 404 pages (Not Found)
 *
 * Methods for TimberHelper can be found in the /functions sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::get_context();

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

Timber::render( '404.twig', $context );
