/* global CC, wp, jQuery */
(function ($) {
	'use strict';

	const CHAR_LIMITS = {
		linkedin: 3000,
		instagram: 2200,
		facebook: 63206,
	};

	// ---- Tab switching ----
	$(document).on('click', '.cc-tab-btn', function () {
		const platform = $(this).data('platform');
		$('.cc-tab-btn').removeClass('active').attr('aria-selected', 'false');
		$('.cc-tab-panel').removeClass('active');
		$(this).addClass('active').attr('aria-selected', 'true');
		$(`.cc-tab-panel[data-platform="${platform}"]`).addClass('active');
	});

	// ---- Character counting ----
	function updateCharCount($textarea) {
		const platform = $textarea.data('platform');
		const len      = $textarea.val().length;
		const limit    = CHAR_LIMITS[platform] || 0;
		const $count   = $(`.cc-char-count[data-platform="${platform}"]`);

		if (!limit) return;
		$count.text(`${len.toLocaleString('hu')} / ${limit.toLocaleString('hu')} karakter`);
		$count.toggleClass('cc-over-limit', len > limit);

		// Show date/status row and continue button once something is typed
		if (len > 0) {
			$(`.cc-date-status-row[data-platform="${platform}"]`).removeClass('cc-hidden');
			$(`.cc-continue-btn[data-platform="${platform}"]`).removeClass('cc-hidden');
		} else {
			$(`.cc-continue-btn[data-platform="${platform}"]`).addClass('cc-hidden');
		}
	}

	$(document).on('input', '.cc-textarea', function () {
		updateCharCount($(this));
	});

	// Init counts on page load
	$('.cc-textarea').each(function () {
		updateCharCount($(this));
	});

	// ---- Status badge update ----
	$(document).on('change', '.cc-status-select', function () {
		const platform = $(this).data('platform');
		const status   = $(this).val();
		const labels   = { draft: 'Piszkozat', ready: 'Kész', published: 'Publikált' };
		const $btn     = $(`.cc-tab-btn[data-platform="${platform}"]`);

		$btn.find('.cc-status-badge').remove();
		if (status && labels[status]) {
			$btn.append(
				`<span class="cc-status-badge cc-status-${status}">${labels[status]}</span>`
			);
		}
	});

	// ---- Continue generation ----
	$(document).on('click', '.cc-continue-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();

		const platform  = $(this).data('platform');
		const $btn      = $(this);
		const $overlay  = $('#cc-ai-overlay');
		const $textarea = $('#cc_' + platform + '_content');
		const existing  = $textarea.val();

		if ( !existing || !existing.trim() ) {
			alert('Nincs szöveg amit folytatni lehetne.');
			return;
		}

		$btn.prop('disabled', true).text('…');
		$overlay.addClass('cc-visible');

		$.post(
			CC.ajaxUrl,
			{
				action:           'cc_continue_content',
				nonce:            CC.nonce,
				platform:         platform,
				existing_content: existing,
			},
			function (response) {
				if (response && response.success) {
					$textarea.val( existing + response.data.content );
					$textarea.trigger('input');
				} else {
					alert('Folytatás hiba: ' + ( response && response.data ? response.data : 'Ismeretlen hiba' ));
				}
			}
		).fail(function (xhr) {
			alert('Kapcsolódási hiba (' + xhr.status + '). Ellenőrizd az AI beállításokat.');
		}).always(function () {
			$btn.prop('disabled', false).text('↩ Folytatás');
			$overlay.removeClass('cc-visible');
		});
	});

	// ---- Emoji picker ----
	const EMOJIS = {
		smileys:  ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','😉','😍','🥰','😘','😎','🤩','🥳','😏','😌','😔','😢','😭','😤','😠','😳','🤔','🤗','😬','🤐','😴','🤯','🤠','🥸','😷','🤒'],
		gestures: ['👍','👎','👏','🙌','🤝','🤜','🤛','✊','👊','🤚','✋','🖐','👋','🤙','💪','🦾','🙏','🫶','❤️','🧡','💛','💚','💙','💜','🖤','🤍','💔','❣️','💕','💞','💓','💗','💖','💘','💝'],
		business: ['📈','📉','📊','💼','🤝','💡','🎯','📢','📣','🔑','💰','💵','🏆','🥇','📝','✍️','🗓️','📅','📆','🔔','💬','💭','📌','📍','🔗','✅','☑️','❌','⚠️','🚨','📋','📂','🗂️','📁','🔍','🔎'],
		objects:  ['🚀','⭐','🌟','💫','✨','🔥','⚡','🌈','🎉','🎊','🎁','🎈','🎶','🎵','🎯','🏅','🎖️','🏋️','🎭','🎨','🖼️','📸','🎬','🎤','📱','💻','🖥️','⌨️','🖨️','🖱️','📡','🔭','🔬','💊','🧬','🧪'],
		nature:   ['🌍','🌎','🌏','🌱','🌿','🍀','🌺','🌸','🌻','🌼','🌷','🌹','🌴','🌵','🎋','🍁','🍂','🍃','☀️','🌤️','⛅','🌦️','🌧️','⛈️','🌩️','❄️','🌨️','💨','🌊','🔆','🌙','⭐','🌠','☄️','🌌'],
	};

	let activeEmojiPlatform = null;
	let lastSelectionStart  = 0;
	let lastSelectionEnd    = 0;

	function populateEmojiGrid( $picker, cat ) {
		const $grid = $picker.find('.cc-emoji-grid');
		$grid.empty();
		( EMOJIS[cat] || [] ).forEach( function( emoji ) {
			$grid.append( $('<button>').attr('type','button').addClass('cc-emoji-item').text(emoji) );
		});
	}

	$(document).on('click', '.cc-emoji-btn', function(e) {
		e.stopPropagation();
		const platform = $(this).data('platform');
		const $picker  = $(`.cc-emoji-picker[data-platform="${platform}"]`);

		// Close others
		$('.cc-emoji-picker').not($picker).prop('hidden', true);

		if ( $picker.prop('hidden') ) {
			if ( !$picker.find('.cc-emoji-grid').children().length ) {
				populateEmojiGrid( $picker, 'smileys' );
			}
			// Save cursor position before picker opens
			const $ta = $(`#cc_${platform}_content`);
			lastSelectionStart = $ta[0]?.selectionStart ?? $ta.val().length;
			lastSelectionEnd   = $ta[0]?.selectionEnd   ?? lastSelectionStart;
			activeEmojiPlatform = platform;
			$picker.prop('hidden', false);
		} else {
			$picker.prop('hidden', true);
		}
	});

	$(document).on('click', '.cc-emoji-cat', function() {
		$(this).closest('.cc-emoji-picker').find('.cc-emoji-cat').removeClass('active');
		$(this).addClass('active');
		populateEmojiGrid( $(this).closest('.cc-emoji-picker'), $(this).data('cat') );
	});

	$(document).on('click', '.cc-emoji-item', function(e) {
		e.stopPropagation();
		const emoji    = $(this).text();
		const platform = $(this).closest('.cc-emoji-picker').data('platform');
		const $ta      = $(`#cc_${platform}_content`);
		const val      = $ta.val();
		const start    = lastSelectionStart;
		const end      = lastSelectionEnd;

		$ta.val( val.substring(0, start) + emoji + val.substring(end) );
		lastSelectionStart = lastSelectionEnd = start + emoji.length;
		$ta[0]?.setSelectionRange( lastSelectionStart, lastSelectionEnd );
		$ta.trigger('input');
		$ta.focus();
	});

	// Track cursor in textarea
	$(document).on('mouseup keyup', '.cc-textarea', function() {
		lastSelectionStart = this.selectionStart;
		lastSelectionEnd   = this.selectionEnd;
	});

	// Close picker on outside click
	$(document).on('click', function() {
		$('.cc-emoji-picker').prop('hidden', true);
	});

	$(document).on('click', '.cc-emoji-picker', function(e) {
		e.stopPropagation();
	});

	// ---- AI generation ----
	$(document).on('click', '.cc-ai-btn', function () {
		const platform = $(this).data('platform');
		const $btn     = $(this);
		const $overlay = $('#cc-ai-overlay');

		// Get post content (Gutenberg or Classic editor)
		let postContent = '';
		if (typeof wp !== 'undefined' && wp.data) {
			try {
				const editor = wp.data.select('core/editor');
				if (editor) {
					postContent = editor.getEditedPostContent() || '';
					// Strip block markup / HTML tags
					postContent = postContent.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
				}
			} catch (e) { /* not in block editor */ }
		}

		if (!postContent && $('#content').length) {
			postContent = $('#content').val();
		}

		if (!postContent.trim()) {
			// Try to use the already-saved post (it's there on edit screens)
			postContent = '';
		}

		$btn.prop('disabled', true).html(`
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:cc-spin .7s linear infinite"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
			Generálás…
		`);
		$overlay.addClass('cc-visible');

		$.ajax({
			url:    CC.ajaxUrl,
			method: 'POST',
			data: {
				action:       'cc_generate_content',
				nonce:        CC.nonce,
				post_id:      CC.postId,
				platform:     platform,
				post_content: postContent.substring(0, 3000),
				post_title:   CC.postTitle,
			},
			success: function (response) {
				if (response.success) {
					const $textarea = $(`#cc_${platform}_content`);
					$textarea.val(response.data.content);
					$textarea.trigger('input');
					// Mark tab as having content
					$(`.cc-tab-btn[data-platform="${platform}"]`).addClass('cc-has-content');
				} else {
					alert('Hiba: ' + (response.data || 'Ismeretlen hiba'));
				}
			},
			error: function () {
				alert(
					'Kapcsolódási hiba. Kérlek ellenőrizd az AI beállításokat a Tartalomnaptár → Beállítások oldalon.'
				);
			},
			complete: function () {
				$btn.prop('disabled', false).html(`
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
					AI generálás
				`);
				$overlay.removeClass('cc-visible');
			},
		});
	});

})(jQuery);
