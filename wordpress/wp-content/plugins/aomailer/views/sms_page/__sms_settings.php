<?php
add_action('admin_print_footer_scripts', 'aomailer_sms_page_settings', 99);
function aomailer_sms_page_settings() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			var token = '<?=wp_get_session_token()?>';
			jQuery(document).delegate('#sms-settings-form', 'submit', function(event){
				var form_data = {};
				form_data.token = token;
				form_data.page_type = 'sms';
				form_data.AomailerSmsSettings = {};
				if ((typeof event.currentTarget === "object") && (event.currentTarget !== null)) {
					jQuery.each(event.currentTarget, function(index, value){
						if (value.type=='submit') {
							return false;
						}
							
						var name = value.name.replace(/.*\[|\]/gi,'');
						if (value.type=='checkbox' || value.type=='radio') {
							form_data.AomailerSmsSettings[name] = value.checked;
						} else {
							form_data.AomailerSmsSettings[name] = value.value;
						}
					});
				}

				reloadForm('smssettings', token, form_data, '<?=admin_url()?>');
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
						
		<form id="sms-settings-form" action="" method="post" class="form-horizontal" role="form">
			
			<div class="form-group">
				<div class="col-sm-12">
					<div class="bs-callout bs-callout-danger">
						<h4><?=__('SettingsAdministrator', 'aomailer')?></h4>
						<p><?=__('SettingsAdministratorWarning', 'aomailer')?></p>
					</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="AomailerSmsSettings_login" class="col-sm-2 control-label"><?=__('Login', 'aomailer')?></label>
				<div class="col-sm-10">
					<input type="text" class="form-control" name="AomailerSmsSettings[login]" id="AomailerSmsSettings_login" value="<?=!empty($this->settings['login']) ? $this->settings['login'] : ''?>">
				</div>
			</div>
			
			<div class="form-group">
				<label for="AomailerSmsSettings_passwd" class="col-sm-2 control-label"><?=__('Password', 'aomailer')?></label>
				<div class="col-sm-10">
					<input type="password" class="form-control" name="AomailerSmsSettings[passwd]" id="AomailerSmsSettings_passwd" placeholder="********" value="<?=!empty($this->settings['passwd']) ? $this->settings['passwd'] : ''?>">
				</div>
			</div>
			
			<?php if (!empty($this->settings['array_from_name'])) : ?>
			
				<div class="form-group">
					<label for="AomailerSmsSettings_from_name" class="col-sm-2 control-label"><?=__('FromName', 'aomailer')?></label>
					<div class="col-sm-10">
						
						<select name="AomailerSmsSettings[from_name]" class="form-control" id="AomailerSmsSettings_from_name">
							
							<option value=""><?=__('Select', 'aomailer')?></option>
							
							<?php if (!empty($this->settings['array_from_name'])) : ?>
							
								<?php foreach ($this->settings['array_from_name'] as $value) : ?>
								
									<option <?=(!empty($this->settings['from_name']) && $value['value']==$this->settings['from_name']) ? 'selected' : ''?> value="<?=$value['value']?>"><?=$value['value']?></option>

								<?php endforeach; ?>
							
							<?php endif ?>
							
							
						</select>
						
					</div>
				</div>
				
			<?php endif; ?>
			
			<div class="form-group">
				<div class="col-sm-12">
					<input type="submit" class="btn btn-primary pull-right btn-xs-block floating_button" value="<?=__('btnSave', 'aomailer')?>">
				</div>
			</div>
			
		</form>
						
	</div>

</div>