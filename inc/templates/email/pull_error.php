<?php
/**
* Template: email -> pull_error
*
* Contains plain text content for email sent when an error is detecting making a mater server pull request/ content sync
* 
* @author	Ben Moody
*/
?>
<?php _ex( 'This is a message from your Content Syndication Toolkit wordpress plugin.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ); echo "\r\n"; ?>
		
<?php _ex( 'There was an error when trying to get new syndication posts from the content creator. All posts in this import will need to be reimported.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ); echo "\r\n"; ?>

<?php _ex( 'Please login to wordpress and make a manual content pull request using the plugin tools under "Settings -> Content Syndication" in the wordpress admin area.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ); echo "\r\n"; ?>

<?php _ex( 'Here is the error message:', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ); echo "\r\n"; ?>

"<?php esc_attr_e( $error_msg ); ?>"