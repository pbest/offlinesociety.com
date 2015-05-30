<?php
/*
Template Name: Join
*/

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;

$title = get_field('join_title');
$context['join_title'] = $title;

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


Timber::render( array( 'page-join.twig', 'page.twig' ), $context );

?>
