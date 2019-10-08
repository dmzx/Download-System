(function($) {	// Avoid conflicts with other libraries

"use strict";

	phpbb.addAjaxCallback('no_access', function(e) {
	 	Swal.fire({
			title: downloadSystem.lang.no_permission,
			text: downloadSystem.lang.not_download,
			type: 'warning',
			showConfirmButton: false,
			timer: 2500,
		})
		e.preventDefault();
	});

	phpbb.addAjaxCallback('access', function(e) {
		if (downloadSystem.showDonation) {
			swal.fire({
				title: downloadSystem.lang.file_download,
				html: downloadSystem.lang.donate_message,
				showCancelButton: true,
				imageUrl: downloadSystem.downloadImage,
				imageAlt: downloadSystem.lang.download,
				imageHeight: 150,
				confirmButtonText: downloadSystem.lang.donate,
				confirmButtonColor: '#ed2861',
				cancelButtonText: downloadSystem.lang.download,
				cancelButtonColor: '#82c545',
			}).then((result) => {
				if (result.value) {
					window.open(downloadSystem.donationUrl, '_blank');

					swal.fire({
						title: downloadSystem.lang.donate_thanks,
						text: downloadSystem.lang.download_start,
						type: 'success',
						showConfirmButton: false,
						timer: 4000,
					});

					window.location.href = downloadSystem.file_link;
				} else if (result.dismiss === Swal.DismissReason.cancel) {

					swal.fire({
						title: downloadSystem.lang.download,
						text: downloadSystem.lang.download_start,
						type: 'success',
						showConfirmButton: false,
						timer: 1500,
					});

					window.location.href = downloadSystem.file_link;
				}
			});
		}
		else
		{
			swal.fire({
				title: downloadSystem.lang.download,
				text: downloadSystem.lang.download_start,
				type: 'success',
				showConfirmButton: false,
				timer: 1500,
			});

			window.location.href = downloadSystem.file_link;
		}

		e.preventDefault();
	});

	phpbb.addAjaxCallback('forumtitlex', function(e) {
	 	Swal.fire({
			title: downloadSystem.lang.redirect,
			type: 'success',
			showConfirmButton: false,
			timer: 1000,
		}).then((result) => {
		if (result.dismiss === Swal.DismissReason.timer) {
			window.location.href = $(this).attr('href');
		}
	});

		e.preventDefault();
	});

	window.onload = function () {
		showUserAlerts();
	}

	function showUserAlerts()
	{
		var $alertnocat = $("div#alertnocat");
		var $alertnofile = $("div#alertnofile");

		if ($alertnocat.length > 0) {
			swal.fire({
				title: downloadSystem.lang.noCategory,
				type: 'info'
			})
		}

		if ($alertnofile.length > 0) {
			swal.fire({
				title: downloadSystem.lang.noFile,
				type: 'info'
			})
		}
	}

})(jQuery); // Avoid conflicts with other libraries