<?php
add_action('admin_print_footer_scripts', 'aomailer_sms_page_events', 99);
function aomailer_sms_page_events() {
	?>
	<script type="text/javascript">
		var token = '<?=wp_get_session_token()?>';
		jQuery(document).ready(function($) {
			jQuery(document).delegate('#sms-events-form', 'submit', function(event){
				var form_data = {};
				form_data.token = token;
				form_data.page_type = 'sms';
				form_data.AomailerSmsEvents = {};
				if ((typeof event.currentTarget === "object") && (event.currentTarget !== null)) {
					jQuery.each(event.currentTarget, function(index, value){
						if (value.type=='submit') {
							return false;
						}
							
						var name = value.name.replace(/.*\[|\]/gi,'');
						if (value.type=='checkbox' || value.type=='radio') {
							form_data.AomailerSmsEvents[name] = value.checked;
						} else {
							form_data.AomailerSmsEvents[name] = value.value;
						}
					});
				}
					
				reloadForm('smsevents', token, form_data, '<?=admin_url()?>');
				event.preventDefault();
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
						
		<form id="sms-events-form" action="" method="post" class="form-horizontal" role="form">
			
			<div id="aomp-order-type-admin" class="col-sm-6">
			
				<div class="form-group">
					<div class="col-sm-12">
						<div class="bs-callout bs-callout-warning">
							<h4><?=__('EventsAdministrator', 'aomailer')?></h4>
							<p><?=__('EventsAdministratorWarning', 'aomailer')?></p>
						</div>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_used" class="col-sm-5 control-label"><?=__('EventUsed', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_used]" id="AomailerSmsEvents_admin_event_used" <?=!empty($this->settings['admin_event_used']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<hr>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_new_order" class="col-sm-5 control-label"><?=__('NewOrder', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_new_order]" id="AomailerSmsEvents_admin_event_new_order" <?=!empty($this->settings['admin_event_new_order']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_payment_order" class="col-sm-5 control-label"><?=__('PaymentOrder', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_payment_order]" id="AomailerSmsEvents_admin_event_payment_order" <?=!empty($this->settings['admin_event_payment_order']) ? 'checked' : ''?>>
					</div>
				</div>

				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_change_order_3" class="col-sm-5 control-label"><?=__('WaitingForPayment', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_change_order_3]" id="AomailerSmsEvents_admin_event_change_order_3" <?=!empty($this->settings['admin_event_change_order_3']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_change_order_4" class="col-sm-5 control-label"><?=__('Treatment', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_change_order_4]" id="AomailerSmsEvents_admin_event_change_order_4" <?=!empty($this->settings['admin_event_change_order_4']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_change_order_5" class="col-sm-5 control-label"><?=__('OnHold', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_change_order_5]" id="AomailerSmsEvents_admin_event_change_order_5" <?=!empty($this->settings['admin_event_change_order_5']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_change_order_6" class="col-sm-5 control-label"><?=__('Canceled', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_change_order_6]" id="AomailerSmsEvents_admin_event_change_order_6" <?=!empty($this->settings['admin_event_change_order_6']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_change_order_7" class="col-sm-5 control-label"><?=__('Returned', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_change_order_7]" id="AomailerSmsEvents_admin_event_change_order_7" <?=!empty($this->settings['admin_event_change_order_7']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_admin_event_change_order_8" class="col-sm-5 control-label"><?=__('Failed', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[admin_event_change_order_8]" id="AomailerSmsEvents_admin_event_change_order_8" <?=!empty($this->settings['admin_event_change_order_8']) ? 'checked' : ''?>>
					</div>
				</div>
			
			</div>
			<div id="aomp-order-type-client" class="col-sm-6">
			
				<div class="form-group">
					<div class="col-sm-12">
						<div class="bs-callout bs-callout-info">
							<h4><?=__('EventsClient', 'aomailer')?></h4>
							<p><?=__('EventsClientWarning', 'aomailer')?></p>
						</div>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_used" class="col-sm-5 control-label"><?=__('EventUsed', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_used]" id="AomailerSmsEvents_client_event_used" <?=!empty($this->settings['client_event_used']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<hr>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_new_order" class="col-sm-5 control-label"><?=__('NewOrder', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_new_order]" id="AomailerSmsEvents_client_event_new_order" <?=!empty($this->settings['client_event_new_order']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_payment_order" class="col-sm-5 control-label"><?=__('PaymentOrder', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_payment_order]" id="AomailerSmsEvents_client_event_payment_order" <?=!empty($this->settings['client_event_payment_order']) ? 'checked' : ''?>>
					</div>
				</div>

				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_change_order_3" class="col-sm-5 control-label"><?=__('WaitingForPayment', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_change_order_3]" id="AomailerSmsEvents_client_event_change_order_3" <?=!empty($this->settings['client_event_change_order_3']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_change_order_4" class="col-sm-5 control-label"><?=__('Treatment', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_change_order_4]" id="AomailerSmsEvents_client_event_change_order_4" <?=!empty($this->settings['client_event_change_order_4']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_change_order_5" class="col-sm-5 control-label"><?=__('OnHold', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_change_order_5]" id="AomailerSmsEvents_client_event_change_order_5" <?=!empty($this->settings['client_event_change_order_5']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_change_order_6" class="col-sm-5 control-label"><?=__('Canceled', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_change_order_6]" id="AomailerSmsEvents_client_event_change_order_6" <?=!empty($this->settings['client_event_change_order_6']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_change_order_7" class="col-sm-5 control-label"><?=__('Returned', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_change_order_7]" id="AomailerSmsEvents_client_event_change_order_7" <?=!empty($this->settings['client_event_change_order_7']) ? 'checked' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerSmsEvents_client_event_change_order_8" class="col-sm-5 control-label"><?=__('Failed', 'aomailer')?></label>
					<div class="col-sm-5">
						<input type="checkbox" name="AomailerSmsEvents[client_event_change_order_8]" id="AomailerSmsEvents_client_event_change_order_8" <?=!empty($this->settings['client_event_change_order_8']) ? 'checked' : ''?>>
					</div>
				</div>

			</div>
			
			<div class="form-group">
				<div class="col-sm-12">
					<input type="submit" class="btn btn-primary pull-right btn-xs-block floating_button" name="AomailerEvents[btn]" value="<?=__('btnSave', 'aomailer')?>">
				</div>
			</div>
			
		</form>
						
	</div>

</div>
