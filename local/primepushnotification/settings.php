<?php 
if ( $hassiteconfig ){
 
	// Create the new settings page
	// - in a local plugin this is not defined as standard, so normal $settings->methods will throw an error as
	// $settings will be NULL
	$settings = new admin_settingpage( 'local_primepushnotification', 'Notification setting' );
 
	// Create 
	$ADMIN->add( 'localplugins', $settings );
 
	// Add a setting field to the settings for this page
	$settings->add( new admin_setting_configcheckbox(
 
		// This is the reference you will use to your configuration
		'pushnotification',
 
		// This is the friendly title for the config, which will be displayed
		'Push Notification' ,
    
    'Check if you want to enable Push Notification',
    
    'admin'
	) );

		// Add a setting field to the settings for this page
	$settings->add( new admin_setting_configcheckbox(
 
		// This is the reference you will use to your configuration
		'emailnotification',
 
		// This is the friendly title for the config, which will be displayed
		'Email Notification',
    
    'Check if you want to enable Email Notification',
    
    'admin'
	) );


		// Add a setting field to the settings for this page
	$settings->add( new admin_setting_configcheckbox(
 
		// This is the reference you will use to your configuration
		'smsnotification',
 
		// This is the friendly title for the config, which will be displayed
		'Sms Notification' ,
    
    'Check if you want to enable Sms Notification',
    
    'admin'
	) );
	

 
}

?>