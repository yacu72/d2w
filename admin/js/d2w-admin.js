(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready :
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded :
	 *
	 * $( window ).load(function() { 
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a 
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work .
	 */
	 $(document).ready(function() {

	 	// Open and close postbox divs in admin area.
    $('.handlediv').click(function () {
        $(this).parent().toggleClass("closed").addClass('postbox');
    });	 

    /**
     * Creates Par relationship between drupal node type => WP node type
     */
    $('.select-post-type').change(function() {
    	var drupal_post_type = $(this).attr('data-post-type');
    	var wp_post_type = $(this).val();
    	
	 		var dataJSON = {
	 			'action': 'd2w_node_type_relationship_action',
	 			'drupal_post_type': drupal_post_type,
	 			'wp_post_type': wp_post_type,
	 		};

	 		$.ajax({
	 			method: "POST",
	 			url: wp_ajax.ajax_url,
	 			data: dataJSON,
	 		})
	 		.done(function( response ) {
	 			console.log('Successful AJAX Call! /// Return Data: ' + response);
	 		});

	 		return false;    	
    });	

    /**
     * Creates Par relationship between drupal node field => WP node field
     */
    $('.field-option').change(function() {
    	
    	var drupal_post_type = $(this).parent().closest('.inside').children('select.select-post-type').attr('data-post-type');
    	var drupal_field = $(this).parent().closest('dl').children('dt').html();
    	var pod_field = $(this).val();

	 		var dataJSON = {
	 			'action': 'd2w_field_relationship_action',
	 			'post_type': drupal_post_type,
	 			'drupal_field': drupal_field,
	 			'pod_field': pod_field, 
	 		};

	 		$.ajax({
	 			method: "POST",
	 			url: wp_ajax.ajax_url,
	 			data: dataJSON,
	 		})
	 		.done(function( response ) {
	 			console.log('Successful AJAX Call! /// Return Data: ' + response);
	 		});

	 		return false;    	

    });	    

    //handles ajax calls for data migration
	 	$('.button-migrate').click(function(){

	 		var drupal_type = $(this).attr('data-drupal-type');
	 		var action = $(this).attr('data-action');

	 		var dataJSON = {
	 			'action': 'd2w_migrate_page_action',
	 			//'id': $('.migrate-form').serialize(),
	 			'drupal_type': drupal_type,
	 			'action_type': action,
	 		};

	 		$.ajax({
	 			method: "POST",
	 			url: wp_ajax.ajax_url,
	 			data: dataJSON,
	 		})
	 		.done(function( response ) {
	 			console.log('Successful AJAX Call! /// Return Data: ' + response);

	 		});

	 		return false;
	 	});
	 });

})( jQuery );
