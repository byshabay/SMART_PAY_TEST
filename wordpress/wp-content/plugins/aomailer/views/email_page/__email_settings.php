<?php
add_action('admin_print_footer_scripts', 'aomailer_email_page_settings', 99);
function aomailer_email_page_settings() {
	?>
	<script type="text/javascript">
		var token = '<?=wp_get_session_token()?>';
		jQuery(document).ready(function($) {
			jQuery(document).delegate('#email-settings-form', 'submit', function(event){
				var form_data = {};
				form_data.token = token;
				form_data.page_type = 'email';
				form_data.AomailerEmailSettings = {};
				if ((typeof event.currentTarget === "object") && (event.currentTarget !== null)) {
					jQuery.each(event.currentTarget, function(index, value){
						if (value.type=='submit') {
							return false;
						}
							
						var name = value.name.replace(/.*\[|\]/gi,'');
						if (value.type=='checkbox' || value.type=='radio') {
							form_data.AomailerEmailSettings[name] = value.checked;
						} else {
							form_data.AomailerEmailSettings[name] = value.value;
						}
					});
				}
					
				reloadForm('emailsettings', token, form_data, '<?=admin_url()?>');
				event.preventDefault();
				return false;
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
						
		<form id="email-settings-form" action="" method="post" class="form-horizontal" role="form">
		
			<div id="aomp-order-type-settings">

				<div class="form-group">
					<div class="col-sm-12">
						<div class="bs-callout bs-callout-danger">
							<h4><?=__('SettingsAdministratorEmail', 'aomailer')?></h4>
							<p><?=__('SettingsAdministratorWarningEmail', 'aomailer')?></p>
						</div>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerEmailSettings_admin_email_used" class="col-sm-2 control-label"><?=__('EmailUsed', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="checkbox" name="AomailerEmailSettings[admin_email_used]" id="AomailerEmailSettings_admin_email_used" <?=!empty($this->settings['admin_email_used']) ? 'checked' : ''?>>
					</div>
				</div>

				<div class="form-group">
					<label for="AomailerEmailSettings_from" class="col-sm-2 control-label"><?=__('FromEmail', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="email" class="form-control" name="AomailerEmailSettings[from]" id="AomailerEmailSettings_from" <?=!empty($this->settings['from']) ? 'value="'.$this->settings['from'].'"' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerEmailSettings_email_from_name" class="col-sm-2 control-label"><?=__('FromNameEmail', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="text" class="form-control" name="AomailerEmailSettings[email_from_name]" id="AomailerEmailSettings_email_from_name" <?=!empty($this->settings['email_from_name']) ? 'value="'.$this->settings['email_from_name'].'"' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerEmailSettings_reply_to" class="col-sm-2 control-label"><?=__('ReplyTo', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="email" class="form-control" name="AomailerEmailSettings[reply_to]" id="AomailerEmailSettings_reply_to" <?=!empty($this->settings['reply_to']) ? 'value="'.$this->settings['reply_to'].'"' : ''?>>
					</div>
				</div>
				
				<div class="form-group">
					<label for="AomailerEmailSettings_reply_to_name" class="col-sm-2 control-label"><?=__('ReplyToName', 'aomailer')?></label>
					<div class="col-sm-10">
						<input type="text" class="form-control" name="AomailerEmailSettings[reply_to_name]" id="AomailerEmailSettings_reply_to_name" <?=!empty($this->settings['reply_to_name']) ? 'value="'.$this->settings['reply_to_name'].'"' : ''?>>
					</div>
				</div>
				
			</div>

			<div class="form-group">
				<div class="col-sm-12">
					<input type="submit" class="btn btn-primary pull-right btn-xs-block floating_button" value="<?=__('btnSave', 'aomailer')?>">
				</div>
			</div>
			
		</form>
						
	</div>

</div>