<?php

class CC_Calendar {

	const PLATFORM_COLORS = array(
		'linkedin'  => '#0077b5',
		'instagram' => '#e1306c',
		'facebook'  => '#1877f2',
	);

	const PLATFORM_ICONS = array(
		'linkedin'  => '💼',
		'instagram' => '📷',
		'facebook'  => '👥',
	);

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_cc_get_events', array( $this, 'get_events' ) );
	}

	public function add_menu() {
		add_menu_page(
			'Tartalomnaptár',
			'Tartalomnaptár',
			'edit_posts',
			'content-calendar',
			array( $this, 'render_page' ),
			'dashicons-calendar-alt',
			25
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( $hook !== 'toplevel_page_content-calendar' ) {
			return;
		}

		wp_enqueue_style(
			'fullcalendar',
			'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css',
			array(),
			'6.1.15'
		);
		wp_enqueue_script(
			'fullcalendar',
			'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
			array(),
			'6.1.15',
			true
		);
		wp_enqueue_style( 'cc-calendar-admin', CC_URL . 'admin/css/admin.css', array(), CC_VERSION );
		wp_enqueue_script(
			'cc-calendar',
			CC_URL . 'admin/js/calendar.js',
			array( 'fullcalendar' ),
			CC_VERSION,
			true
		);
		wp_localize_script(
			'cc-calendar',
			'CC',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cc_ajax' ),
			)
		);
	}

	public function render_page() {
		?>
		<div class="wrap">
			<h1 class="cc-page-title">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-5px;margin-right:6px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
				Tartalomnaptár
			</h1>

			<div class="cc-calendar-wrap">
				<div class="cc-legend">
					<span class="cc-legend-item">
						<span class="cc-legend-dot" style="background:#2563eb"></span> WordPress
					</span>
					<span class="cc-legend-item">
						<span class="cc-legend-dot" style="background:#0077b5"></span> LinkedIn
					</span>
					<span class="cc-legend-item">
						<span class="cc-legend-dot" style="background:#e1306c"></span> Instagram
					</span>
					<span class="cc-legend-item">
						<span class="cc-legend-dot" style="background:#1877f2"></span> Facebook
					</span>
					<span class="cc-legend-sep"></span>
					<span class="cc-legend-item cc-legend-opacity">
						<span class="cc-legend-dot cc-opacity-full" style="background:#64748b"></span> Publikált
					</span>
					<span class="cc-legend-item cc-legend-opacity">
						<span class="cc-legend-dot cc-opacity-half" style="background:#64748b"></span> Kész
					</span>
					<span class="cc-legend-item cc-legend-opacity">
						<span class="cc-legend-dot cc-opacity-low" style="background:#64748b"></span> Piszkozat
					</span>
				</div>
				<div id="cc-calendar"></div>
			</div>
		</div>
		<?php
	}

	public function get_events() {
		check_ajax_referer( 'cc_ajax', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Nincs jogosultságod.' );
		}

		$events = array();

		$args = array(
			'post_status' => array( 'publish', 'future', 'draft' ),
			'numberposts' => 500,
			'post_type'   => get_option( 'cc_post_types', array( 'post' ) ),
		);

		$posts = get_posts( $args );

		foreach ( $posts as $wp_post ) {
			$status_color_map = array(
				'publish' => '#2563eb',
				'future'  => '#7c3aed',
				'draft'   => '#94a3b8',
			);

			$bg_color = $status_color_map[ $wp_post->post_status ] ?? '#2563eb';
			$opacity  = $wp_post->post_status === 'draft' ? 0.55 : 1;

			$status_labels = array(
				'publish' => 'Publikált',
				'future'  => 'Ütemezett',
				'draft'   => 'Piszkozat',
			);

			$events[] = array(
				'id'              => 'wp-' . $wp_post->ID,
				'title'           => '📝 ' . $wp_post->post_title,
				'start'           => $wp_post->post_date,
				'url'             => get_edit_post_link( $wp_post->ID, 'raw' ),
				'backgroundColor' => $bg_color,
				'borderColor'     => $bg_color,
				'extendedProps'   => array(
					'type'         => 'wordpress',
					'status'       => $wp_post->post_status,
					'statusLabel'  => $status_labels[ $wp_post->post_status ] ?? $wp_post->post_status,
					'opacity'      => $opacity,
				),
			);

			foreach ( self::PLATFORM_COLORS as $platform => $color ) {
				$content   = get_post_meta( $wp_post->ID, "_cc_{$platform}_content", true );
				$sm_date   = get_post_meta( $wp_post->ID, "_cc_{$platform}_date", true );
				$sm_status = get_post_meta( $wp_post->ID, "_cc_{$platform}_status", true );

				if ( empty( $content ) ) {
					continue;
				}

				$opacity_map = array(
					'published' => 1.0,
					'ready'     => 0.75,
					'draft'     => 0.45,
				);
				$opacity = $opacity_map[ $sm_status ] ?? 0.45;

				$status_label_map = array(
					'published' => 'Publikált',
					'ready'     => 'Kész',
					'draft'     => 'Piszkozat',
				);

				$event_date = $sm_date ?: $wp_post->post_date;
				$icon       = self::PLATFORM_ICONS[ $platform ];

				$events[] = array(
					'id'              => "{$platform}-{$wp_post->ID}",
					'title'           => $icon . ' ' . $wp_post->post_title,
					'start'           => $event_date,
					'url'             => get_edit_post_link( $wp_post->ID, 'raw' ),
					'backgroundColor' => $color,
					'borderColor'     => $color,
					'extendedProps'   => array(
						'type'        => $platform,
						'status'      => $sm_status ?: 'draft',
						'statusLabel' => $status_label_map[ $sm_status ] ?? 'Piszkozat',
						'opacity'     => $opacity,
					),
				);
			}
		}

		wp_send_json( $events );
	}
}
