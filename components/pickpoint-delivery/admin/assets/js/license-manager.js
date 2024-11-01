/**
 * Этот файл содержит скрипт исполняелся во время процедур с формой лицензирования.
 * Его основная роль отправка ajax запросов на проверку, активацию, деактивацию лицензии
 * и вывод уведомлений об ошибка или успешно выполнении проверок.
 *
 * @author Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 05.10.2018, Webcraftic
 * @version 1.1
 * @since 1.4.0
 */
jQuery(function($) {

	var allNotices = [];

	$(document).on('click', '.wdyd-control-btn', function() {
		var wrapper = $('#wdyd-license-wrapper'),
			loader = wrapper.data('loader'),
			pluginName = wrapper.data('plugin-name'),
			wpnonce = wrapper.data('nonce'),
			licenseAction = $(this).data('action');

		for( i = 0; i < allNotices.length; i++ ) {
			$.wbcr_factory_clearfy_228.app.hideNotice(allNotices[i]);
		}

		$('.wdyd-control-btn').hide();

		$(this).after('<img class="wdyd-loader" src="' + loader + '">');

		var data = {
			action: 'wdyd_check_license',
			_wpnonce: wpnonce,
			license_action: licenseAction,
			licensekey: ''
		};

		if( $(this).data('action').trim() === 'activate' ) {
			data.licensekey = $('#license-key').val().trim();
		}

		$.ajax(ajaxurl, {
			type: 'post',
			dataType: 'json',
			data: data,
			success: function(response) {
				var noticeId;

				if( !response || !response.success ) {

					$('.wdyd-control-btn').show();
					$('.wdyd-loader').remove();

					if( response.data ) {
						console.log(response.data.error_message);
						alert('Error: [' + response.data.error_message + ']');
					} else {
						console.log(response);
					}

					return;
				}

				if( response.data && response.data.message ) {
					noticeId = $.wbcr_factory_clearfy_228.app.showNotice(response.data.message, 'success');
					allNotices.push(noticeId);

					window.location.reload();
				}

			},
			error: function(xhr, ajaxOptions, thrownError) {

				$('.wdyd-control-btn').show();
				$('.wdyd-loader').remove();

				console.log(xhr.status);
				console.log(xhr.responseText);
				console.log(thrownError);

				var noticeId = $.wbcr_factory_clearfy_228.app.showNotice('Error: [' + thrownError + '] Status: [' + xhr.status + '] Error massage: [' + xhr.responseText + ']', 'danger');

				allNotices.push(noticeId);
			}
		});

		return false;
	});

});
