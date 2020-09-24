function countLetters() {
	var elem = [
		'AomailerSmsTemplates_admin_text_sms', 
		'AomailerSmsTemplates_client_text_sms', 
		'AomailerSmsMailing_text_sms'
	];

	elem.forEach(function(id, i, elem) {
		var count = 0;
		var size = 0;
		var value = jQuery('#' + id).val();
		
		if (value && typeof value !== 'undefined') {
			count = value.length;
			if (value.match(/[а-я]/gi)) {
				if (count<=70) {
					var divider = 70;
				} else {
					var divider = 67;
				}
			} else {
				if (count<=160) {
					var divider = 160;
				} else {
					var divider = 153;
				}
			}
			
			size = Math.ceil(count/divider);
		}
		
		jQuery('#' + id).parent('div').find('.aomp-count-sms-letters').text(count);
		jQuery('#' + id).parent('div').find('.aomp-size-sms-letters').text(size);
		jQuery('#' + id).parent('div').find('.aomp-length-sms-letters').text(divider);
	});
};

function switcher() {
	jQuery('#aomp-order-type-admin, #aomp-templates-admin').find('.form-group input[type="checkbox"]').bootstrapSwitch({
		'size': 'mini',
		'onColor': 'warning',
		'offColor': 'default',
		'onText': '<i class="fa fa-check" aria-hidden="true"></i>',
		'offText': '<i class="fa fa-times" aria-hidden="true"></i>'
	});
		
	jQuery('#aomp-order-type-client, #aomp-templates-client').find('.form-group input[type="checkbox"]').bootstrapSwitch({
		'size': 'mini',
		'onColor': 'info',
		'offColor': 'default',
		'onText': '<i class="fa fa-check" aria-hidden="true"></i>',
		'offText': '<i class="fa fa-times" aria-hidden="true"></i>'
	});
		
	jQuery('#aomp-order-type-settings').find('.form-group input[type="checkbox"]').bootstrapSwitch({
		'size': 'mini',
		'onColor': 'danger',
		'offColor': 'default',
		'onText': '<i class="fa fa-check" aria-hidden="true"></i>',
		'offText': '<i class="fa fa-times" aria-hidden="true"></i>',
	});
};

function loadPage() {
	setTimeout(function(){jQuery('.message_output').fadeOut('slow')}, 5000);
	jQuery(".PhoneMask").mask("+7 (999) 999-99-99");
	countLetters();
	switcher();
	loadDateTimePickerPlugin();
	jQuery('#aomp-overlay, #aomp-order-loader-admin').hide();
};

function reloadForm(id, token, data, action) {			
	jQuery('#aomp-overlay, #aomp-order-loader-admin').show();
	jQuery('#aomp-' + id).load(action + 'admin-ajax.php?action=aomailer_load_form_' + id, {data: data}, function(){
		loadPage();
		getBalance(token);	
	});
};

jQuery(document).ready(function() {

	loadPage();
		
	jQuery(document).delegate('#aomp-settings-tab a', 'click', function(e){
		jQuery('.message_output').hide();
		e.preventDefault();
		jQuery(this).tab('show');
	});
		
	jQuery(document).delegate('.fa-plus', 'click', function(e){
		var clone_block = jQuery(this).parents('.form-group').find('.clone_block');
		var name = clone_block.find('input:first-child').attr('name');
		var id = clone_block.find('input:first-child').attr('id');

		var new_block = jQuery('<div>').attr({
			class: 'has-feedback'
		}).appendTo(clone_block);	

		var new_input = jQuery('<input>').attr({
			type: 'tel',
			name: name,
			class: 'form-control PhoneMask',
			style: 'margin-top:2px'
		}).appendTo(new_block);

		var new_span = jQuery('<span>').attr({
			class: 'glyphicon glyphicon-remove form-control-feedback glyphicon-remove-red'
		}).appendTo(new_block);

		new_input.attr('id', id + '_' + new_block.index());
		jQuery(".PhoneMask").mask("+7 (999) 999-99-99");
	});
		
	jQuery(document).delegate('span.glyphicon-remove', 'click', function(e){
		jQuery(this).parent('.has-feedback').remove();
	});
	
	jQuery(document).delegate('.tips', 'mouseover', function(e){
		jQuery(this).tooltip('show');
	});
	
	jQuery(document).delegate('.personification', 'click', function(e){
		var e = this;
		if (window.getSelection) { 
			var s=window.getSelection(); 
			if (s.setBaseAndExtent[0]) { 
				s.setBaseAndExtent(e,0,e,e.innerText.length-1); 
			}else{ 
				var r=document.createRange(); 
				r.selectNodeContents(e); 
				s.removeAllRanges(); 
				s.addRange(r);
			} 
		} else if (document.getSelection) { 
			var s=document.getSelection(); 
			var r=document.createRange(); 
			r.selectNodeContents(e); 
			s.removeAllRanges(); 
			s.addRange(r); 
		} else if (document.selection) { 
			var r=document.body.createTextRange(); 
			r.moveToElementText(e); 
			r.select();
		}

		document.execCommand('copy');
	});
});