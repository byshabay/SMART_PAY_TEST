<?php
add_action('admin_print_footer_scripts', 'aomailer_sms_page_template', 99);
function aomailer_sms_page_template() {
	?>
	<script type="text/javascript">
		var token = '<?=wp_get_session_token()?>';
		jQuery(document).ready(function($) {
			jQuery(document).delegate('#sms-templates-form', 'submit', function(event){
				var form_data = {};
				form_data.token = token;
				form_data.page_type = 'sms';
				form_data.AomailerSmsTemplates = {};
				form_data.AomailerSmsTemplates.admin_number = {};
				if ((typeof event.currentTarget === "object") && (event.currentTarget !== null)) {
					var i=0;
					jQuery.each(event.currentTarget, function(index, value){
						if (value.type=='submit') {
							return false;
						}
	
						var arr = value.name.split('[');
						if (arr[1] && typeof arr[1] !== "undefined") {
							var name = arr[1].replace(/\]/gi,'');
							if (arr[2] && typeof arr[2] !== "undefined") {
								if (name == 'admin_number')	{
									if (value.type=='checkbox' || value.type=='radio') {
										form_data.AomailerSmsTemplates.admin_number[i] = value.checked;
									} else {
										form_data.AomailerSmsTemplates.admin_number[i] = value.value;
									}
									i++;
								}	
							} else {
								if (value.type=='checkbox' || value.type=='radio') {
									form_data.AomailerSmsTemplates[name] = value.checked;
								} else {
									form_data.AomailerSmsTemplates[name] = value.value;
								}
								
								if (name=='admin_events_type') {
									form_data.id_admin = value.value;
								}
								
								if (name=='client_events_type') {
									form_data.id_client = value.value;
								}
							}
						}						
					});
				}

				reloadForm('smstemplates', token, form_data, '<?=admin_url()?>');
				event.preventDefault();
			});	
			
			jQuery(document).delegate('#AomailerSmsTemplates_admin_events_type, #AomailerSmsTemplates_client_events_type', 'change', function(e){
				var id_admin = jQuery('#AomailerSmsTemplates_admin_events_type').val();
				var id_client = jQuery('#AomailerSmsTemplates_client_events_type').val();
				if(id_admin && id_client) {
					jQuery('#aomp-overlay, #aomp-order-loader-admin').show();
					jQuery('#aomp-order-form').load('<?=admin_url()?>admin-ajax.php?action=aomailer_select_template&id_admin=' + id_admin + '&id_client=' + id_client, function() {
						loadPage();
					});
				}
			});	
			
			jQuery(document).delegate('#AomailerSmsMailing_text_sms', 'keyup', function(e){
				var count = 0;
				var size = 0;	
				var value = this.value;

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
					
				jQuery(this).parent('div').find('.aomp-count-sms-letters').text(count);
				jQuery(this).parent('div').find('.aomp-size-sms-letters').text(size);
				jQuery(this).parent('div').find('.aomp-length-sms-letters').text(divider);				
			});
		});
	</script>
<?php } ?>

<div class="row message_output">
	<div class="col-sm-12">
	
		<?php if (!empty($this->settings['error'])) : ?>
				
			<p class="alert alert-danger">
				<?=$this->settings['error']?>
			</p>
				
		<?php elseif (!empty($this->settings['success'])) : ?>
			
			<p class="alert alert-success">
				<?=$this->settings['success']?>
			</p>
			
		<?php endif; ?>

	</div>
</div>

<div class="row" id="aomp-order-form">
	
	<div class="col-sm-12">
						
		<form id="sms-templates-form" action="" method="post" class="form-horizontal" role="form">
			
			<div class="form-group">
				<div class="col-sm-12">
					<div class="bs-callout bs-callout-warning">
						<h4><?=__('TemplatesAdministrator', 'aomailer')?></h4>
						<p><i><?=__('TemplatesAdministratorWarning', 'aomailer')?></i></p>
					</div>
				</div>
			</div>

			<div class="form-group">
				<label for="AomailerSmsTemplates_admin_events_type" class="col-sm-2 control-label"><?=__('EventsType', 'aomailer')?></label>
				<div class="col-sm-10">
					<select name="AomailerSmsTemplates[admin_events_type]" class="form-control" id="AomailerSmsTemplates_admin_events_type">
						<option <?=($this->settings['id_admin']==1) ? 'selected' : ''?> value="1"><?=__('NewOrder', 'aomailer')?></option>
						<option <?=($this->settings['id_admin']==2) ? 'selected' : ''?> value="2"><?=__('PaymentOrder', 'aomailer')?></option>
						<option <?=($this->settings['id_admin']==3) ? 'selected' : ''?> value="3"><?=__('WaitingForPayment', 'aomailer')?></option>
						<option <?=($this->settings['id_admin']==4) ? 'selected' : ''?> value="4"><?=__('Treatment', 'aomailer')?></option>
						<option <?=($this->settings['id_admin']==5) ? 'selected' : ''?> value="5"><?=__('OnHold', 'aomailer')?></option>
						<option <?=($this->settings['id_admin']==6) ? 'selected' : ''?> value="6"><?=__('Canceled', 'aomailer')?></option>
						<option <?=($this->settings['id_admin']==7) ? 'selected' : ''?> value="7"><?=__('Returned', 'aomailer')?></option>
						<option <?=($this->settings['id_admin']==8) ? 'selected' : ''?> value="8"><?=__('Failed', 'aomailer')?></option>
					</select>
				</div>
			</div>
				
			<?php if (!empty($this->settings['array_from_name'])) : ?>
				
				<div class="form-group">
					<label for="AomailerSmsTemplates_admin_from_name" class="col-sm-2 control-label"><?=__('FromName', 'aomailer')?></label>
					<div class="col-sm-10">
							
						<select name="AomailerSmsTemplates[admin_from_name]" class="form-control" id="AomailerSmsTemplates_admin_from_name">
								
							<option value=""><?=__('Select', 'aomailer')?></option>
								
							<?php if (!empty($this->settings['array_from_name'])) : ?>
								
								<?php foreach ($this->settings['array_from_name'] as $value) : ?>
									
									<option <?=($value['value']==$this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_from_name']) ? 'selected' : ''?> value="<?=$value['value']?>"><?=$value['value']?></option>

								<?php endforeach; ?>
								
							<?php endif ?>
								
								
						</select>
							
					</div>
				</div>
					
			<?php endif; ?>
			
			<div class="form-group">
				<label for="AomailerSmsTemplates_admin_text_sms" class="col-sm-2 control-label">
					<?=__('TextSMS', 'aomailer')?>
				</label>
				<div class="col-sm-5">
					<textarea align="left" class="form-control text-left" id="AomailerSmsTemplates_admin_text_sms" name="AomailerSmsTemplates[admin_text_sms]" rows="10"><?=!empty($this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_text_sms']) ? htmlspecialchars(trim($this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_text_sms'])) : ''?></textarea>
					<small><i><?=__('CountLetters', 'aomailer')?>: <span class="aomp-count-sms-letters"></span></i></small>	
					<div><small><i><?=__('LengthOneSms', 'aomailer')?> <span class="aomp-length-sms-letters">
					
					<?php if (!empty($this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_text_sms'])) : ?>
					
						<?php if (preg_match('/[а-я]/i', $this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_text_sms'])) : ?>
							70
						<?php else : ?>
							160
						<?php endif; ?>
						
					<?php endif; ?>
					
					</span> <?=__('LengthOneSmsEng', 'aomailer')?></i></small></div>	
					<div><small><i><?=__('SizeMessage', 'aomailer')?>: <span class="aomp-size-sms-letters"></span> SMS</i></small></div>
					<p class="clearfix"></p>
				</div>
				<div class="col-sm-5 overflow">

					<?php foreach ($this->settings['tag'] as $tag_name=>$tag) : ?>
						
						<div class="personification">#<?=$tag?>#</div>
						
					<?php endforeach; ?>
		
				</div>
			</div>
			
			<div id="aomp-templates-admin">
				<div class="form-group">
					<label for="AomailerSmsTemplates_admin_used_translit" class="col-sm-2 control-label"><?=__('Use translit', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="checkbox" name="AomailerSmsTemplates[admin_used_translit]" id="AomailerSmsTemplates_admin_used_translit" <?=!empty($this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_used_translit']) ? 'checked' : ''?>>
					</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="AomailerSmsTemplates_admin_number" class="col-sm-2 control-label"><?=__('Phone', 'aomailer')?></label>
				<div class="col-sm-10 clone_block">

					<?php if (!empty($this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_number']) && is_array($this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_number'])) : ?>
						
						<?php foreach ($this->settings['admin_template_settings'][$this->settings['id_admin']]['admin_number'] as $key=>$value) : ?>
							
							<?php if (empty($key)) : ?>
								
								<div class="has-feedback">
									<input type="tel" class="form-control PhoneMask" style="margin-top:2px" name="AomailerSmsTemplates[admin_number][]" id="AomailerSmsTemplates_admin_number" value="<?=$value?>">
								</div>
								
							<?php else : ?>
								
								<div class="has-feedback">
									<input type="tel" class="form-control PhoneMask" style="margin-top:2px" name="AomailerSmsTemplates[admin_number][]" id="AomailerSmsTemplates_admin_number_<?=$key?>" value="<?=$value?>">
									<span class="glyphicon glyphicon-remove form-control-feedback glyphicon-remove-red"></span>
								</div>
								
							<?php endif; ?>
							
						<?php endforeach; ?>
						
					<?php else : ?>

						<div class="has-feedback">
							<input type="tel" class="form-control PhoneMask" style="margin-top:2px" name="AomailerSmsTemplates[admin_number][]" id="AomailerSmsTemplates_admin_number" value="">
						</div>
							
					<?php endif; ?>
						
				</div>
				<p class="clearfix"></p>
				<div class="clone_button">
					<i class="fa fa-plus pull-right" aria-hidden="true"></i>
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-12">
					<div class="bs-callout bs-callout-info">
						<h4><?=__('TemplatesClient', 'aomailer')?></h4>
						<p><i><?=__('TemplatesClientWarning', 'aomailer')?></i></p>
					</div>
				</div>
			</div>
		
			<div class="form-group">
				<label for="AomailerSmsTemplates_client_events_type" class="col-sm-2 control-label"><?=__('EventsType', 'aomailer')?></label>
				<div class="col-sm-10">
					<select name="AomailerSmsTemplates[client_events_type]" class="form-control" id="AomailerSmsTemplates_client_events_type">
						<option <?=($this->settings['id_client']==1) ? 'selected' : ''?> value="1"><?=__('NewOrder', 'aomailer')?></option>
						<option <?=($this->settings['id_client']==2) ? 'selected' : ''?> value="2"><?=__('PaymentOrder', 'aomailer')?></option>
						<option <?=($this->settings['id_client']==3) ? 'selected' : ''?> value="3"><?=__('WaitingForPayment', 'aomailer')?></option>
						<option <?=($this->settings['id_client']==4) ? 'selected' : ''?> value="4"><?=__('Treatment', 'aomailer')?></option>
						<option <?=($this->settings['id_client']==5) ? 'selected' : ''?> value="5"><?=__('OnHold', 'aomailer')?></option>
						<option <?=($this->settings['id_client']==6) ? 'selected' : ''?> value="6"><?=__('Canceled', 'aomailer')?></option>
						<option <?=($this->settings['id_client']==7) ? 'selected' : ''?> value="7"><?=__('Returned', 'aomailer')?></option>
						<option <?=($this->settings['id_client']==8) ? 'selected' : ''?> value="8"><?=__('Failed', 'aomailer')?></option>
					</select>
				</div>
			</div>
			
			<?php if (!empty($this->settings['array_from_name'])) : ?>
				
				<div class="form-group">
					<label for="AomailerSmsTemplates_client_from_name" class="col-sm-2 control-label"><?=__('FromName', 'aomailer')?></label>
					<div class="col-sm-10">
							
						<select name="AomailerSmsTemplates[client_from_name]" class="form-control" id="AomailerSmsTemplates_client_from_name">
								
							<option value=""><?=__('Select', 'aomailer')?></option>
								
							<?php if (!empty($this->settings['array_from_name'])) : ?>
								
								<?php foreach ($this->settings['array_from_name'] as $value) : ?>
									
									<option <?=($value['value']==$this->settings['client_template_settings'][$this->settings['id_client']]['client_from_name']) ? 'selected' : ''?> value="<?=$value['value']?>"><?=$value['value']?></option>

								<?php endforeach; ?>
								
							<?php endif ?>
								
								
						</select>
							
					</div>
				</div>
					
			<?php endif; ?>
			
			<div class="form-group">
				<label for="AomailerSmsTemplates_client_text_sms" class="col-sm-2 control-label">
					<?=__('TextSMS', 'aomailer')?>
				</label>
				<div class="col-sm-5">
					<textarea class="form-control" id="AomailerSmsTemplates_client_text_sms" name="AomailerSmsTemplates[client_text_sms]" rows="10"><?=!empty($this->settings['client_template_settings'][$this->settings['id_client']]['client_text_sms']) ? htmlspecialchars(trim($this->settings['client_template_settings'][$this->settings['id_client']]['client_text_sms'])) : ''?></textarea>
					<small><i><?=__('CountLetters', 'aomailer')?> <span class="aomp-count-sms-letters"></span></i></small>	
					<div><small><i><?=__('LengthOneSms', 'aomailer')?> <span class="aomp-length-sms-letters">
					
					<?php if (!empty($this->settings['client_template_settings'][$this->settings['id_client']]['client_text_sms'])) : ?>
					
						<?php if (preg_match('/[а-я]/i', $this->settings['client_template_settings'][$this->settings['id_client']]['client_text_sms'])) : ?>
							70
						<?php else : ?>
							160
						<?php endif; ?>
						
					<?php endif; ?>
					
					</span> <?=__('LengthOneSmsEng', 'aomailer')?></i></small></div>	
					<div><small><i><?=__('SizeMessage', 'aomailer')?>: <span class="aomp-size-sms-letters"></span> SMS</i></small></div>
					<p class="clearfix"></p>
				</div>
				<div class="col-sm-5 overflow">
					<?php foreach ($this->settings['tag'] as $tag_name=>$tag) : ?>
						
						<div class="personification">#<?=$tag?>#</div>
						
					<?php endforeach; ?>
				</div>
			</div>
				
			<div id="aomp-templates-client">	
				<div class="form-group">
					<label for="AomailerSmsTemplates_client_used_translit" class="col-sm-2 control-label"><?=__('Use translit', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="checkbox" name="AomailerSmsTemplates[client_used_translit]" id="AomailerSmsTemplates_client_used_translit" <?=!empty($this->settings['client_template_settings'][$this->settings['id_client']]['client_used_translit']) ? 'checked' : ''?>>
					</div>
				</div>
			</div>	
				
			<div class="form-group">
				<div class="col-sm-12">
					<input type="submit" id="aomp-form-sms-button" class="btn btn-primary pull-right btn-xs-block floating_button" value="<?=__('btnSave', 'aomailer')?>">
				</div>
			</div>
			
		</form>
						
	</div>

</div>