<?php
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_script('timepicker_min_js', plugins_url('assets/js/jquery-ui-timepicker-addon-1.6.3.min.js', dirname(__FILE__)));

add_action('admin_print_footer_scripts', 'aomailer_script_email', 99);
function aomailer_script_email() {
	?>
	<script type="text/javascript">
		function getBalance(token) {
			return false;
		}

		function loadDateTimePickerPlugin() {
			jQuery.datepicker.setDefaults({
				closeText: 'Закрыть',
				prevText: '<Пред',
				nextText: 'След>',
				currentText: 'Сегодня',
				monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
				monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'],
				dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
				dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
				dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
				weekHeader: 'Нед',
				dateFormat : 'yy-mm-dd',
				firstDay: 1,
				showAnim: 'slideDown',
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '',
			});

			jQuery('#AomailerSmsMailing_date_send').datetimepicker({
				dateFormat : 'yy-mm-dd',
				currentText: 'Сегодня',
				closeText: 'Закрыть',
				timeFormat: 'HH:mm:ss',
				timeOnlyTitle: 'Выберите время',
				timeText: 'Время',
				hourText: 'Часы',
				minuteText: 'Минуты',
				secondText: 'Секунды',
				timeSuffix: ''
			});	
		}

		jQuery(document).ready(function($) {
			var token = '<?=wp_get_session_token()?>';
			setTimeout(function(){jQuery('.message_output').fadeOut('slow')}, 10000);
		});
	</script>
<?php } ?>

<div id="aomp-page-sms" class="container-fluid">

	<div id="aomp-overlay"></div>
	<i id="aomp-order-loader-admin" class="fa fa-cog fa-spin fa-fw"></i>
	
	<div class="masthead">
		
		<h3 class="text-muted">
			<img src="<?=$this->settings['logo']?>" alt="logo" class="" width="200"><br><br>
			<?=__('Email', 'aomailer')?>
		</h3>
		
    </div>
	
	<div class="row">
		<div class="col-sm-12">
			
			<ul class="nav nav-tabs" id="settings-tab">
				<li class="active">
					<a href="#aomp-emailsettings" data-toggle="tab">
						<i class="fa fa-cogs" aria-hidden="true"></i> <?=__('Settings', 'aomailer')?>
					</a>
				</li>
			</ul>
			
		</div>
	</div>
	
	<p class="clearfix"></p>

	<div class="tab-content">
		<div class="tab-pane fade in active" id="aomp-emailsettings">
		
			<?php require_once __DIR__ . '/email_page/__email_settings.php'; ?>
			
		</div>

	</div>
	
</div>
