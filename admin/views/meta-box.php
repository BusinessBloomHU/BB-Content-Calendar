<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$platforms = CC_Meta_Box::PLATFORMS;
$statuses  = CC_Meta_Box::STATUSES;
$labels    = CC_Meta_Box::PLATFORM_LABELS;
$limits    = CC_Meta_Box::CHAR_LIMITS;

$icons = array(
	'linkedin'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
	'instagram' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
	'facebook'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
);
?>
<div class="cc-wrap">
	<div class="cc-tabs" role="tablist">
		<?php foreach ( $platforms as $i => $platform ) :
			$content = get_post_meta( $post->ID, "_cc_{$platform}_content", true );
			$status  = get_post_meta( $post->ID, "_cc_{$platform}_status", true );
			$date    = get_post_meta( $post->ID, "_cc_{$platform}_date", true );
			$has_content = ! empty( $content );
		?>
			<button
				type="button"
				class="cc-tab-btn <?= $i === 0 ? 'active' : '' ?> <?= $has_content ? 'cc-has-content' : '' ?>"
				data-platform="<?= esc_attr( $platform ) ?>"
				role="tab"
				aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
			>
				<span class="cc-tab-icon"><?= $icons[ $platform ] ?></span>
				<?= esc_html( $labels[ $platform ] ) ?>
				<?php if ( $status && isset( $statuses[ $status ] ) ) : ?>
					<span class="cc-status-badge cc-status-<?= esc_attr( $status ) ?>"><?= esc_html( $statuses[ $status ] ) ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $platforms as $i => $platform ) :
		$content = get_post_meta( $post->ID, "_cc_{$platform}_content", true );
		$date    = get_post_meta( $post->ID, "_cc_{$platform}_date", true );
		$status  = get_post_meta( $post->ID, "_cc_{$platform}_status", true );
		$len     = mb_strlen( $content );
		$limit   = $limits[ $platform ];
		$show_meta = ! empty( $content ) || ! empty( $date ) || ! empty( $status );
	?>
	<div
		class="cc-tab-panel <?= $i === 0 ? 'active' : '' ?>"
		data-platform="<?= esc_attr( $platform ) ?>"
		role="tabpanel"
	>
		<div class="cc-panel-header">
			<h4>
				<span class="cc-tab-icon"><?= $icons[ $platform ] ?></span>
				<?= esc_html( $labels[ $platform ] ) ?> tartalom
			</h4>
			<div class="cc-panel-actions">
				<div class="cc-emoji-wrap">
					<button type="button" class="cc-emoji-btn" data-platform="<?= esc_attr( $platform ) ?>" title="Emoji beszúrása">😊</button>
					<div class="cc-emoji-picker" data-platform="<?= esc_attr( $platform ) ?>" hidden>
						<div class="cc-emoji-categories">
							<button type="button" class="cc-emoji-cat active" data-cat="smileys">😀</button>
							<button type="button" class="cc-emoji-cat" data-cat="gestures">👍</button>
							<button type="button" class="cc-emoji-cat" data-cat="business">📈</button>
							<button type="button" class="cc-emoji-cat" data-cat="objects">🎯</button>
							<button type="button" class="cc-emoji-cat" data-cat="nature">🌟</button>
						</div>
						<div class="cc-emoji-grid"></div>
					</div>
				</div>
				<button type="button" class="cc-ai-btn button" data-platform="<?= esc_attr( $platform ) ?>">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
					AI generálás
				</button>
			</div>
		</div>

		<div class="cc-field">
			<textarea
				id="cc_<?= esc_attr( $platform ) ?>_content"
				name="cc_<?= esc_attr( $platform ) ?>_content"
				class="cc-textarea"
				placeholder="<?= esc_attr( $labels[ $platform ] ) ?> poszt szövege..."
				data-platform="<?= esc_attr( $platform ) ?>"
				data-limit="<?= esc_attr( $limit ) ?>"
			><?= esc_textarea( $content ) ?></textarea>
			<div class="cc-textarea-footer">
				<span class="cc-char-count <?= $len > $limit ? 'cc-over-limit' : '' ?>" data-platform="<?= esc_attr( $platform ) ?>">
					<?= esc_html( number_format_i18n( $len ) ) ?> / <?= esc_html( number_format_i18n( $limit ) ) ?> karakter
				</span>
			</div>
		</div>

		<div class="cc-date-status-row <?= $show_meta ? '' : 'cc-hidden' ?>" data-platform="<?= esc_attr( $platform ) ?>">
			<div class="cc-field">
				<label for="cc_<?= esc_attr( $platform ) ?>_date">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
					Tervezett publikálás
				</label>
				<input
					type="date"
					id="cc_<?= esc_attr( $platform ) ?>_date"
					name="cc_<?= esc_attr( $platform ) ?>_date"
					value="<?= esc_attr( $date ) ?>"
					class="cc-date-input"
				>
			</div>
			<div class="cc-field">
				<label for="cc_<?= esc_attr( $platform ) ?>_status">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					Státusz
				</label>
				<select
					id="cc_<?= esc_attr( $platform ) ?>_status"
					name="cc_<?= esc_attr( $platform ) ?>_status"
					class="cc-status-select"
					data-platform="<?= esc_attr( $platform ) ?>"
				>
					<option value="">– Nincs beállítva –</option>
					<?php foreach ( $statuses as $value => $label ) : ?>
						<option value="<?= esc_attr( $value ) ?>" <?= selected( $status, $value, false ) ?>>
							<?= esc_html( $label ) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</div>

<div class="cc-ai-overlay" id="cc-ai-overlay" aria-hidden="true">
	<span class="cc-ai-spinner"></span>
	<span>AI tartalom generálása…</span>
</div>
