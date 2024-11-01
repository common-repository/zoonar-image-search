<?php
defined( 'ABSPATH' ) or die( 'All your base are belong to us!' );

$current_user = wp_get_current_user();
$first_name = get_user_meta( $current_user->ID, 'first_name', true );
$last_name = get_user_meta( $current_user->ID, 'last_name', true );

$locale = get_user_locale( $current_user->ID );
$locale = strtolower( substr( $locale, 0, 2 ) );
if( ! in_array( $locale, array('de', 'en', 'fr') ) )
  $locale = 'en';
?>
<script>
  window.zoonar_config = 
  {
    firstname: '<?php echo str_replace( "'", "", $first_name );?>',
    lastname: '<?php echo str_replace( "'", "", $last_name );?>',
    email: '<?php echo $current_user->user_email;?>',
    language: '<?php echo $locale;?>',
    override_security: 0,
    wp_host: '<?php echo get_site_url();?>',
  };
</script>

<div class="wrap">
  <noscript><strong>We're sorry but zoonar_plugin doesn't work properly without JavaScript enabled. Please enable it to continue.</strong></noscript>
  <div id="zoonar-image-plugin"></div>
</div>