/* global CC, FullCalendar */
document.addEventListener('DOMContentLoaded', function () {
	const calendarEl = document.getElementById('cc-calendar');
	if (!calendarEl || typeof FullCalendar === 'undefined') return;

	const calendar = new FullCalendar.Calendar(calendarEl, {
		initialView:  'dayGridMonth',
		locale:       'hu',
		height:       'auto',
		firstDay:     1, // Monday

		headerToolbar: {
			left:   'prev,next today',
			center: 'title',
			right:  'dayGridMonth,timeGridWeek,listMonth',
		},

		buttonText: {
			today:     'Ma',
			month:     'Hónap',
			week:      'Hét',
			list:      'Lista',
		},

		// Fetch events via AJAX
		events: function (info, successCallback, failureCallback) {
			fetch(
				`${CC.ajaxUrl}?action=cc_get_events&nonce=${CC.nonce}&start=${info.startStr}&end=${info.endStr}`,
				{ method: 'GET' }
			)
				.then(function (res) { return res.json(); })
				.then(successCallback)
				.catch(failureCallback);
		},

		// Open post editor on click
		eventClick: function (info) {
			if (info.event.url) {
				info.jsEvent.preventDefault();
				window.open(info.event.url, '_blank', 'noopener');
			}
		},

		// Apply opacity based on status
		eventDidMount: function (info) {
			const props  = info.event.extendedProps;
			const opacity = typeof props.opacity === 'number' ? props.opacity : 1;
			info.el.style.opacity = opacity;

			// Tooltip
			const statusLabel = props.statusLabel || props.status || '';
			const typeLabel   = {
				wordpress: 'WordPress',
				linkedin:  'LinkedIn',
				instagram: 'Instagram',
				facebook:  'Facebook',
			}[props.type] || props.type || '';

			info.el.title = [
				info.event.title.replace(/^[^\s]+ /, ''), // strip icon
				typeLabel,
				statusLabel,
			].filter(Boolean).join(' · ');
		},

		// Responsive
		windowResize: function () {
			if (window.innerWidth < 768) {
				calendar.changeView('listMonth');
			}
		},
	});

	calendar.render();

	// Auto switch to list view on small screens
	if (window.innerWidth < 768) {
		calendar.changeView('listMonth');
	}
});
