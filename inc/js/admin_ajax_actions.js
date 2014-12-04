/**
* admin_ajax_actions.js
* 
* All back end ajax request should be made here
* 
* @access 	public
* @author	Ben Moody
*/
jQuery.noConflict();
(function($) {
	
	//Init vars
	var themeLocalVars = PrsoPluginFrameworkVars;
	var ajaxActionName = null;
	var actionButton   = null;
	
	//Handle ajax reqeust 'Pull Content' button
	$('.pcst-pull-content').click(function( event ){
		
		event.preventDefault();
		
		actionButton = $(this);
		
		//Cache requested ajax action
		ajaxActionName = actionButton.attr('rel');
		ajaxAction = 'pcst-' + ajaxActionName;
		
		//Set user interface feedback
		actionButton.attr( 'disabled', '' );
		$('.spinner.' + ajaxActionName).show();
		
		//Make ajax post request
		jQuery.post(
			themeLocalVars.ajaxUrl,
			{
				action: 		ajaxAction,
				ajaxNonce: 		themeLocalVars.ajaxNonce,
			},
			function( response ) {
			
				console.log(response);
				//Check for errors
				if( response.success == true ) {
				
					actionButton.html( response.data );
					$('.spinner.' + ajaxActionName).hide();
					
				} else {
				
					actionButton.hide();
					$('.spinner.' + ajaxActionName).hide();
					
					$('.pcst-pull-error').html(response.data).show();
					
				}
				
			}
		);
		
	});
	
	//Handle ajax reqeust 'Reset Index' button
	$('.pcst-reset-index').click(function( event ){
		
		event.preventDefault();
		
		actionButton = $(this);
		
		//Cache requested ajax action
		ajaxActionName = actionButton.attr('rel');
		ajaxAction = 'pcst-' + ajaxActionName;
		
		console.log(ajaxActionName);
		
		//Set user interface feedback
		actionButton.attr( 'disabled', '' );
		$('.spinner.' + ajaxActionName).show();
		
		//Make ajax post request
		jQuery.post(
			themeLocalVars.ajaxUrl,
			{
				action: 		ajaxAction,
				ajaxNonce: 		themeLocalVars.ajaxNonce,
			},
			function( response ) {
			
				console.log(response);
				//Check for errors
				if( response.success == true ) {
				
					actionButton.html( response.data );
					$('.spinner.' + ajaxActionName).hide();
					
				} else {
				
					actionButton.hide();
					$('.spinner.' + ajaxActionName).hide();
					
					$('.pcst-reset-index-error').html(response.data).show();
					
				}
				
			}
		);
		
	});
	
})(jQuery);