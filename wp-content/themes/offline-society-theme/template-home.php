<?php
/*
Template Name: Home
*/


/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;

/*
CHECK FOR USER STATUS
-------------------------------------- */
if ( is_user_logged_in() ) {
$user = new TimberUser();  
$user_roles = $current_user->roles;
$user_role = array_shift($user_roles);
//echo '<strong>Current User Role</strong>: ' . $user_role;
// Current roles:
// role = user 
// role = member
// rol = administrator

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
  $context['user_role'] = $user_role;
  $context['user'] = $user;
}
//--------------------------------------

$tagline = get_field('landing_tagline');
$context['landing_tagline'] = $tagline;

$landing_button_label = get_field('landing_button_label');
$context['landing_button_label'] = $landing_button_label;

Timber::render( array( 'page-home.twig', 'page.twig' ), $context );



?> 