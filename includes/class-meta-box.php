<?php

class CC_Meta_Box {

	const PLATFORMS = array( 'linkedin', 'instagram', 'facebook' );

	const STATUSES = array(
		'draft'     => 'Piszkozat',
		'ready'     => 'Kész',
		'published' => 'Publikált',
	);

	const PLATFORM_LABELS = array(
		'linkedin'  => 'LinkedIn',
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
	);

	const CHAR_LIMITS = array(
		'linkedin'  => 3000,
		'instagram' => 2200,
		'facebook'  => 63206,
	);

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function add_meta_box() {
		$post_types = get_option( 'cc_post_types', array( 'post' ) );
		if ( ! is_array( $post_types ) ) {
			$post_types = array( 'post' );
		}
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'content-calendar-social',
				'Tartalomnaptár – Social Media',
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'cc_save_meta', 'cc_nonce' );
		include CC_PATH . 'admin/views/meta-box.php';
	}

	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['cc_nonce'] ) || ! wp_verify_nonce( $_POST['cc_nonce'], 'cc_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::PLATFORMS as $platform ) {
			$content_key = "cc_{$platform}_content";
			$date_key    = "cc_{$platform}_date";
			$status_key  = "cc_{$platform}_status";

			if ( isset( $_POST[ $content_key ] ) ) {
				update_post_meta( $post_id, "_$content_key", wp_kses_post( $_POST[ $content_key ] ) );
			}
			if ( isset( $_POST[ $date_key ] ) ) {
				$date_val = sanitize_text_field( $_POST[ $date_key ] );
				// Normalize datetime-local format (YYYY-MM-DDTHH:MM) to date-only (YYYY-MM-DD)
				if ( strpos( $date_val, 'T' ) !== false ) {
					$date_val = substr( $date_val, 0, 10 );
				}
				update_post_meta( $post_id, "_$date_key", $date_val );
			}
			if ( isset( $_POST[ $status_key ] ) ) {
				$status = sanitize_text_field( $_POST[ $status_key ] );
				if ( $status === '' || array_key_exists( $status, self::STATUSES ) ) {
					update_post_meta( $post_id, "_$status_key", $status );
				}
			}
		}
	}

	public function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'cc-admin', CC_URL . 'admin/css/admin.css', array(), CC_VERSION );
		wp_enqueue_script( 'cc-admin', CC_URL . 'admin/js/admin.js', array( 'jquery' ), CC_VERSION, true );

		global $post;
		wp_localize_script(
			'cc-admin',
			'CC',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'cc_ajax' ),
				'postId'    => $post ? $post->ID : 0,
				'postTitle' => $post ? $post->post_title : '',
			)
		);
	}
}
