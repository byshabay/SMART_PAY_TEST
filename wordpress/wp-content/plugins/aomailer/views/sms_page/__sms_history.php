<?php
add_action('admin_print_footer_scripts', 'aomailer_sms_page_history', 99);
function aomailer_sms_page_history() {
	?>
	<script type="text/javascript">
		var token = '<?=wp_get_session_token()?>';
		jQuery(document).ready(function($) {
			jQuery(document).delegate('#sms-history-form', 'submit', function(event){
				var form_data = {};
				form_data.token = token;
				form_data.page_type = 'sms';
				form_data.AomailerSmsHistory = {};
				if ((typeof event.currentTarget === "object") && (event.currentTarget !== null)) {
					jQuery.each(event.currentTarget, function(index, value){
						if (value.type=='submit') {
							return false;
						}
							
						var name = value.name.replace(/.*\[|\]/gi,'');
						if (value.type=='checkbox' || value.type=='radio') {
							form_data.AomailerSmsHistory[name] = value.checked;
						} else {
							form_data.AomailerSmsHistory[name] = value.value;
						}
					});
				}
					
				reloadForm('smshistory', token, form_data, '<?=admin_url()?>');
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
						
		<div class="row">
			<div class="col-sm-12">
				<div class="bs-callout bs-callout-danger">
					<h4><?=__('HistoryAdministrator', 'aomailer')?></h4>
					<p><?=__('HistoryAdministratorWarning', 'aomailer')?></p>
				</div>
			</div>
		</div>
		
		<div class="row" id="aomp-history-load-content">
			<div class="col-sm-12">
		
		
		
			<div class="table-responsive">
				<table class="table table-striped table-hover table-condensed">
					<thead>
						<tr>
							<th><?=__('Number', 'aomailer')?></th>
							<th><?=__('Operator', 'aomailer')?></th>
							<th><?=__('Status', 'aomailer')?></th>
							<th><?=__('Send time', 'aomailer')?></th>
							<th><?=__('Price', 'aomailer')?></th>
						</tr>
					</thead>
					<tbody>
						
						<?php if (empty($this->history['stat'])) : ?>
							<tr>
								<td>0</td>
								<td></td>
								<td></td>
								<td>0000-00-00 00:00:00</td>
								<td>0.0</td>
							</tr>
						<?php else : ?>
						
							<?php foreach ($this->history['stat'] as $value) : ?>
								
								<tr>
									<td>+<?=$value['phone']?></td>
									<td><?=$value['operator']?></td>
									<td><?=$value['status_title']?></td>
									<td><?=$value['time']?></td>
									<td>
										
										<?=$value['price']?> 
										<?php if (!empty($this->settings['currency'])) : ?>
				
											<?php if ($this->settings['currency']=='RUR') : ?>
											
												&nbsp;<i class="fa fa-rub" aria-hidden="true"></i>
												
											<?php endif; ?>
											
										<?php endif; ?>
									
									</td>
								</tr>
		
							<?php endforeach; ?>

						<?php endif; ?>

					</tbody>
				</table>
			</div>
		
		
			</div>
		</div>
		
		<p>&nbsp;</p>
		
		<form id="sms-history-form" action="" method="post" class="form-horizontal" role="form">

			<div class="form-group">
				<label for="AomailerSmsHistory_phone" class="col-sm-2 control-label"><?=__('Phone', 'aomailer')?></label>
				<div class="col-sm-10">
					<input type="text" class="form-control" name="AomailerSmsHistory[phone]" id="AomailerSmsHistory_phone" value="<?=!empty($_POST['data']['AomailerSmsHistory']['phone']) ? $_POST['data']['AomailerSmsHistory']['phone'] : ''?>">
				</div>
			</div>
			
			<div class="form-group">
				<label class="col-sm-2 control-label"><?=__('Period', 'aomailer')?></label>
				
				 <div class="col-sm-10">
					 <div class="input-group">
						<span class="input-group-addon">
							<?=__('from', 'aomailer')?>
						</span>

						<input type="datetime" class="form-control" name="AomailerSmsHistory[date_start]" id="AomailerSmsHistory_date_start" class="datepicker" value="<?=!empty($_POST['data']['AomailerSmsHistory']['date_start']) ? $_POST['data']['AomailerSmsHistory']['date_start'] : ''?>" autocomplete="off">
						
						<span class="input-group-addon">
							<?=__('to', 'aomailer')?>
						</span>
						
						<input type="datetime" class="form-control" name="AomailerSmsHistory[date_stop]" id="AomailerSmsHistory_date_stop" class="datepicker" value="<?=!empty($_POST['data']['AomailerSmsHistory']['date_stop']) ? $_POST['data']['AomailerSmsHistory']['date_stop'] : ''?>" autocomplete="off">
						
					</div>
				</div>
			</div>
			
			<?php if (!empty($this->settings['array_from_name'])) : ?>
			
				<div class="form-group">
					<label for="AomailerSmsHistory_from_name" class="col-sm-2 control-label"><?=__('FromName', 'aomailer')?></label>
					<div class="col-sm-10">
						
						<select name="AomailerSmsHistory[from_name]" class="form-control" id="AomailerSmsHistory_from_name">
							
							<option value=""><?=__('Select', 'aomailer')?></option>
							
							<?php if (!empty($this->settings['array_from_name'])) : ?>
							
								<?php foreach ($this->settings['array_from_name'] as $value) : ?>
								
									<option <?=(!empty($_POST['data']['AomailerSmsHistory']['from_name']) && $value['value']==$_POST['data']['AomailerSmsHistory']['from_name']) ? 'selected' : ''?> value="<?=$value['value']?>"><?=$value['value']?></option>

								<?php endforeach; ?>
							
							<?php endif ?>
							
							
						</select>
						
					</div>
				</div>
				
			<?php endif; ?>

			<div class="form-group">
				<div class="col-sm-12">
					<input type="submit" class="btn btn-primary pull-right btn-xs-block floating_button" value="<?=__('btnSearch', 'aomailer')?>">
				</div>
			</div>
			
		</form>
						
	</div>

</div>