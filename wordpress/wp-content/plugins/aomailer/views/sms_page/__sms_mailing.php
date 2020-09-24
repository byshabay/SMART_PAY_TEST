<?php
add_action('admin_print_footer_scripts', 'aomailer_sms_page_mailing', 99);
function aomailer_sms_page_mailing() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			loadDateTimePickerPlugin();
			var token = '<?=wp_get_session_token()?>';
			jQuery(document).delegate('#sms-mailing-form', 'submit', function(event){
				var form_data = {};
				form_data.token = token;
				form_data.page_type = 'sms';
				form_data.query_type = 'send';
				form_data.AomailerSmsMailing = {};
				if ((typeof event.currentTarget === "object") && (event.currentTarget !== null)) {
					jQuery.each(event.currentTarget, function(index, value){
						if (value.type=='submit') {
							return false;
						}
							
						var name = value.name.replace(/.*\[|\]/gi,'');
						if (value.type=='checkbox' || value.type=='radio') {
							form_data.AomailerSmsMailing[name] = value.checked;
						} else {
							form_data.AomailerSmsMailing[name] = value.value;
						}
					});
				}
					
				reloadForm('smsmailing', token, form_data, '<?=admin_url()?>');
				event.preventDefault();
				return false;
			});	

			jQuery(document).delegate('.aomp-checkbox', 'switchChange.bootstrapSwitch', function (e, state) {
				if (this.id=='AomailerSmsMailing_all_recipients') {
					if (state === true) {
						jQuery('.radio_2').bootstrapSwitch('state', false);
					}	
				} else {
					if (state === true) {
						jQuery('.radio_1').bootstrapSwitch('state', false);
					}
				}
				
				var form_data = {};
				form_data.token = token;
				form_data.page_type = 'sms';
				form_data.query_type = 'selected';
				form_data.AomailerSmsMailing = {};
				jQuery('#sms-mailing-form').find('input, textarea').each(function(i, elem) {
					if (elem.type=='submit') {
						return false;
					}
					
					var name = elem.name.replace(/.*\[|\]/gi,'');
					if (elem.type=='checkbox' || elem.type=='radio') {
						form_data.AomailerSmsMailing[name] = elem.checked;
					} else {
						form_data.AomailerSmsMailing[name] = elem.value;
					}
				});	

				reloadForm('smsmailing', token, form_data, '<?=admin_url()?>');
				return false;
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


<div class="row">

	<div class="col-sm-12">
						
		<form id="sms-mailing-form" action="" method="post" class="form-horizontal" role="form">
			
			<div class="form-group">
				<div class="col-sm-12">
					<div class="bs-callout bs-callout-danger">
						<h4><?=__('MailingTitle', 'aomailer')?></h4>
						<p><?=__('MailingWarning', 'aomailer')?></p>
					</div>
				</div>
			</div>
			
			<div id="aomp-order-type-settings">
			
				<div class="form-group">
					<label for="AomailerSmsMailing_text_sms" class="col-sm-2 control-label">
						<?=__('TextSMS', 'aomailer')?>
					</label>
					<div class="col-sm-5">
						<textarea align="left" class="form-control text-left" id="AomailerSmsMailing_text_sms" name="AomailerSmsMailing[text_sms]" rows="10"><?=!empty($this->settings['mailing_settings']['text_sms']) ? htmlspecialchars(trim($this->settings['mailing_settings']['text_sms'])) : ''?></textarea>
						<small><i><?=__('CountLetters', 'aomailer')?>: <span class="aomp-count-sms-letters"></span></i></small>
						<div><small><i><?=__('LengthOneSms', 'aomailer')?> <span class="aomp-length-sms-letters">
					
						<?php if (!empty($this->settings['mailing_settings']['text_sms'])) : ?>
						
							<?php if (preg_match('/[а-я]/i', $this->settings['mailing_settings']['text_sms'])) : ?>
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
				
				<div class="form-group">
					<label for="AomailerSmsMailing_used_translit" class="col-sm-2 control-label"><?=__('Use translit', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="checkbox" name="AomailerSmsMailing[used_translit]" id="AomailerSmsMailing_used_translit" <?=!empty($this->settings['mailing_settings']['used_translit']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<hr>

				<div class="form-group">
					<label for="AomailerSmsMailing_all_recipients" class="col-sm-2 control-label"><?=__('All customers', 'aomailer')?></label>
					<div class="col-sm-2">
						<input class="aomp-checkbox radio_1" type="checkbox" name="AomailerSmsMailing[all_recipients]" id="AomailerSmsMailing_all_recipients" <?=!empty($this->settings['mailing_settings']['all_recipients']) ? 'checked' : ''?>>
						<p class="clearfix"></p>
					</div>
					<label for="AomailerSmsMailing_pending_recipients" class="col-sm-4 control-label"><?=__('WaitingForPayment', 'aomailer')?></label>
					<div class="col-sm-4">
						<input class="aomp-checkbox radio_2" type="checkbox" name="AomailerSmsMailing[pending_recipients]" id="AomailerSmsMailing_pending_recipients" <?=!empty($this->settings['mailing_settings']['pending_recipients']) ? 'checked' : ''?>>
					</div>
				</div>
			
				<div class="form-group">
					<div class="col-sm-4"></div>
					<label for="AomailerSmsMailing_processing_recipients" class="col-sm-4 control-label"><?=__('Treatment', 'aomailer')?></label>
					<div class="col-sm-4">
						<input class="aomp-checkbox radio_2" type="checkbox" name="AomailerSmsMailing[processing_recipients]" id="AomailerSmsMailing_processing_recipients" <?=!empty($this->settings['mailing_settings']['processing_recipients']) ? 'checked' : ''?>>
					</div>
				</div>
			
				<div class="form-group">
					<div class="col-sm-4"></div>
					<label for="AomailerSmsMailing_onhold_recipients" class="col-sm-4 control-label"><?=__('OnHold', 'aomailer')?></label>
					<div class="col-sm-4">
						<input class="aomp-checkbox radio_2" type="checkbox" name="AomailerSmsMailing[onhold_recipients]" id="AomailerSmsMailing_onhold_recipients" <?=!empty($this->settings['mailing_settings']['onhold_recipients']) ? 'checked' : ''?>>
					</div>
				</div>
			
				<div class="form-group">
					<div class="col-sm-4"></div>
					<label for="AomailerSmsMailing_completed_recipients" class="col-sm-4 control-label"><?=__('Done', 'aomailer')?></label>
					<div class="col-sm-4">
						<input class="aomp-checkbox radio_2" type="checkbox" name="AomailerSmsMailing[completed_recipients]" id="AomailerSmsMailing_completed_recipients" <?=!empty($this->settings['mailing_settings']['completed_recipients']) ? 'checked' : ''?>>
					</div>
				</div>
			
				<div class="form-group">
					<div class="col-sm-4"></div>
					<label for="AomailerSmsMailing_canceled_recipients" class="col-sm-4 control-label"><?=__('Canceled', 'aomailer')?></label>
					<div class="col-sm-4">
						<input class="aomp-checkbox radio_2" type="checkbox" name="AomailerSmsMailing[canceled_recipients]" id="AomailerSmsMailing_canceled_recipients" <?=!empty($this->settings['mailing_settings']['canceled_recipients']) ? 'checked' : ''?>>
					</div>
				</div>
			
				<div class="form-group">
					<div class="col-sm-4"></div>
					<label for="AomailerSmsMailing_refunded_recipients" class="col-sm-4 control-label"><?=__('Returned', 'aomailer')?></label>
					<div class="col-sm-4">
						<input class="aomp-checkbox radio_2" type="checkbox" name="AomailerSmsMailing[refunded_recipients]" id="AomailerSmsMailing_refunded_recipients" <?=!empty($this->settings['mailing_settings']['refunded_recipients']) ? 'checked' : ''?>>
					</div>
				</div>
			
				<div class="form-group">
					<div class="col-sm-4"></div>
					<label for="AomailerSmsMailing_failed_recipients" class="col-sm-4 control-label"><?=__('Failed', 'aomailer')?></label>
					<div class="col-sm-4">
						<input class="aomp-checkbox radio_2" type="checkbox" name="AomailerSmsMailing[failed_recipients]" id="AomailerSmsMailing_failed_recipients" <?=!empty($this->settings['mailing_settings']['failed_recipients']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<hr>
			
				<div class="form-group">
					<label for="AomailerSmsMailing_text_sms" class="col-sm-2 control-label">
						<?=__('Recipients', 'aomailer')?>
					</label>
					<div class="col-sm-10">
						<textarea align="left" class="form-control text-left" id="AomailerSmsMailing_selection_recipients" name="AomailerSmsMailing[selection_recipients]" rows="10"><?=!empty($this->settings['mailing_settings']['selection_recipients']) ? htmlspecialchars(trim($this->settings['mailing_settings']['selection_recipients'])) : ''?></textarea>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsMailing_date_send" class="col-sm-2 control-label"><?=__('departure date', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="datetime" class="form-control" name="AomailerSmsMailing[date_send]" id="AomailerSmsMailing_date_send" class="datepicker" value="<?=!empty($this->settings['mailing_settings']['date_send']) ? $this->settings['mailing_settings']['date_send'] : ''?>" autocomplete="off">
					</div>
				</div>

			</div>
			
			<div class="form-group">
				<div class="col-sm-12">
					<input type="submit" class="btn btn-primary pull-right btn-xs-block floating_button" value="<?=__('btnSend', 'aomailer')?>">
				</div>
			</div>
			
		</form>
						
	</div>

</div>