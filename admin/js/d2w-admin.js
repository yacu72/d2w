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
	 * This enables you to define handlers, for when  the DOM is ready :
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

	 	// Open and close postbox divs in admin  area.
    $('.handlediv').click(function () {
        $(this).parent().toggleClass("closed").addClass('postbox');
    });	 

    /**
     * Migrates batch group of images
     */
     $('input[data-action=migrate-image-group]').click( function() {

     	$(this).attr('disabled', 'disabled').next('img').removeClass('waiting').addClass('processing');

    	var drupal_type = $(this).attr('data-drupal_type');
    	var group_id = $(this).attr('data-images-group'); 
    	var data_action = $(this).attr('data-action');    	

     	var dataJSON = {
     		'action': 'd2w_migrate_images_action',
     		'data_action': data_action,
     		'group_id': group_id,
     		'drupal_type': drupal_type,
     	}

    	$.ajax({
    		method: "POST",
    		url: wp_ajax.ajax_url,
    		data: dataJSON,
    	})
    	.done(function( response ){

    		console.log('Successful AJAX Call! /// Return Data: ' + response);
    		var parsed_data = JSON.parse(response);
    		$('.processing').addClass('waiting').removeClass('processing');

    	});


     	return false;

     });


    /**
     * Size limit is changed, this triggers batch recalculation.
     */
    $('select[name=data-size-limit]').change( function(){

    	var drupal_type = $(this).attr('data-drupal-type');
    	var group_id = $(this).attr('data-images-group');
    	var size_limit = $(this).val();

    	var dataJSON = {
    		'action': 'd2w_migrate_images_action',
    		'drupal_type': drupal_type,
    		'size_limit': size_limit,
    		'group_id': group_id,

    	};

    	$.ajax({
    		method: "POST",
    		url: wp_ajax.ajax_url,
    		data: dataJSON,
    	})
    	.done(function( response ){

    		console.log('Successful AJAX Call! /// Return Data: ' + response);
    		var parsed_data = JSON.parse(response);
    		$('.d2w-batch-list-'+ parsed_data.drupal_type ).html(parsed_data.html);

    	});

    	return false;

    });

    /**
     *  Creates Par relationship between drupal node  type => WP node type
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
	 			var parsed_data = JSON.parse(response);

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

    // handles migration settings
    $('.button-migrate-settings').click( function() {

    	$(this).val('Saving...');

    	var d2wSettings = [];

 			$('input.settings-input').each( function( index ) {
 				d2wSettings[index] = [ $(this).attr('data-name'), $(this).val()];
 				
 			});

 			var dataJSON = {
 				'action': 'd2w_migrate_settings_action',
 				'd2wSettings': d2wSettings,
 			};

 			$.ajax({
 				method: "POST",
 				url: wp_ajax.ajax_url,
 				data: dataJSON,
 			})
 			.done( function( response ) {
	 			console.log('Successful AJAX Call! /// Return Data: ' + response);
	 			var parsed_data = JSON.parse(response);
	 			$('.button-migrate-settings').val('Save Settings');
 			});

    	return false;
    });  

    //handles ajax calls for data migration
	 	$('.button-migrate').click(function(){

	 		var drupal_type = $(this).attr('data-drupal-type') ? $(this).attr('data-drupal-type') : 'none';
	 		var action = $(this).attr('data-action');

	 		// Loader gif
	 		$(this).val('Migrating...').after('<div style="float:none" data-spinner="'+ drupal_type +'" class="spinner is-active"></div>');

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
	 			var parsed_data = JSON.parse(response);
	 			//$('div[data-spinner="spinner-'+ parsed_data.drupal_node_type +'"]').removeClass('is-active');
	 			$('div[data-spinner="'+ parsed_data.drupal_node_type +'"]').hide();
	 			$('input[data-drupal-type='+ parsed_data.drupal_node_type  +']').replaceWith( parsed_data.response +' <i class="dashicons dashicons-yes"></i>');
	 		});

	 		return false;
	 	});

	 	// handles relation between drupal nodes ans wp tax.
	 	$('select[data-action=select-tax-rel]').change(function(){

	 		var wp_tax = $(this).val();
	 		var drupal_node_type = $(this).attr('data-post-type');

	 		var dataJSON = {
	 			'action': 'd2w_migrate_tax_action',
	 			//'id': $('.migrate-form').serialize(),
	 			'drupal_type': drupal_node_type,
	 			'wp_tax': wp_tax,
	 			'action_type': 'migrate-tax-rel',
	 		};

	 		$.ajax({
	 			method: "POST",
	 			url: wp_ajax.ajax_url,
	 			data: dataJSON,
	 		})
	 		.done(function( response ) {
	 			console.log('Successful AJAX Call! /// Return Data: ' + response);
	 			var parsed_data = JSON.parse(response);
	 		});

	 	});

	 	$('.button-migrate-tax').click( function(){

	 		var drupal_node_type = $(this).attr('data-post-type');
	 		var wp_tax = $(this).prev('select').val();

	 		var dataJSON = {
	 			'action': 'd2w_migrate_tax_action',
	 			//'id': $('.migrate-form').serialize(),
	 			'drupal_type': drupal_node_type,
	 			'wp_tax': wp_tax,
	 			'action_type': 'migrate-tax-terms',
	 		};

	 		$.ajax({
	 			method: "POST",
	 			url: wp_ajax.ajax_url,
	 			data: dataJSON,
	 		})
	 		.done(function( response ) {
	 			console.log('Successful AJAX Call! /// Return Data: ' + response);
	 			var parsed_data = JSON.parse(response);
				$('input[data-post-type='+ parsed_data.drupal_node_type  +']').replaceWith('<i class="dashicons dashicons-yes"></i>');	
	 		});	 		

	 		return false;
	 	});

	 });

})( jQuery );
