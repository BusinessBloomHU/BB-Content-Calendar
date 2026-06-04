<?php

class CC_AI_Connector {

	const PLATFORM_LABELS = array(
		'linkedin'  => 'LinkedIn',
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
	);

	const PLATFORM_PROMPTS = array(
		'linkedin'  => 'Írj egy professzionális LinkedIn bejegyzést. Maximum 3000 karakter. Legyen üzleti hangvételű, tartalmazzon egy konkrét tanulságot és call-to-action-t. Tagolja jól soremelésekkel az olvashatóság kedvéért. Ne használj hashtageket.',
		'instagram' => 'Írj egy Instagram feliratot. Ideálisan 150-300 karakter között, de maximum 2200 karakter. Legyen érzelmekre ható, vizuális hangulatú. A szöveg végén adj hozzá 5-10 releváns magyar és angol hashtaget.',
		'facebook'  => 'Írj egy Facebook bejegyzést. Legyen közérthető, baráti hangvételű, maximum 3-4 bekezdés. Ösztönözze az interakciót egy kérdéssel vagy hozzászólásra felkéréssel a végén.',
	);

	public function __construct() {
		add_action( 'wp_ajax_cc_generate_content',  array( $this, 'generate_content' ) );
		add_action( 'wp_ajax_cc_continue_content',  array( $this, 'continue_content' ) );
	}

	public function generate_content() {
		check_ajax_referer( 'cc_ajax', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Nincs jogosultságod ehhez a művelethez.' );
		}

		$platform     = sanitize_text_field( $_POST['platform'] ?? '' );
		$post_content = sanitize_textarea_field( $_POST['post_content'] ?? '' );
		$post_title   = sanitize_text_field( $_POST['post_title'] ?? '' );

		if ( ! array_key_exists( $platform, self::PLATFORM_PROMPTS ) ) {
			wp_send_json_error( 'Érvénytelen platform.' );
		}

		$provider = get_option( 'cc_ai_provider', 'openai' );
		$api_key  = get_option( 'cc_ai_api_key', '' );

		if ( empty( $api_key ) ) {
			wp_send_json_error( 'Nincs beállítva API kulcs. Kérlek add meg a Tartalomnaptár → Beállítások oldalon.' );
		}

		$platform_instruction = self::PLATFORM_PROMPTS[ $platform ];
		$prompt = sprintf(
			"%s\n\nA cikk címe: %s\n\nA cikk tartalma:\n%s\n\nFontos: a bejegyzés magyar nyelvű legyen.",
			$platform_instruction,
			$post_title,
			mb_substr( $post_content, 0, 3000 )
		);

		if ( $provider === 'anthropic' ) {
			$result = $this->call_anthropic( $api_key, $prompt );
		} elseif ( $provider === 'gemini' ) {
			$result = $this->call_gemini( $api_key, $prompt );
		} else {
			$result = $this->call_openai( $api_key, $prompt );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'content' => $result ) );
	}

	public function continue_content() {
		check_ajax_referer( 'cc_ajax', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Nincs jogosultságod ehhez a művelethez.' );
		}

		$platform         = sanitize_text_field( $_POST['platform'] ?? '' );
		$existing_content = sanitize_textarea_field( $_POST['existing_content'] ?? '' );

		if ( ! array_key_exists( $platform, self::PLATFORM_PROMPTS ) ) {
			wp_send_json_error( 'Érvénytelen platform.' );
		}

		if ( empty( $existing_content ) ) {
			wp_send_json_error( 'Nincs szöveg amit folytatni lehetne.' );
		}

		$provider = get_option( 'cc_ai_provider', 'openai' );
		$api_key  = get_option( 'cc_ai_api_key', '' );

		if ( empty( $api_key ) ) {
			wp_send_json_error( 'Nincs beállítva API kulcs.' );
		}

		$prompt = sprintf(
			"Az alábbi %s bejegyzés félbeszakadt. Folytasd természetesen pontosan ott ahol abbahagyta – ne ismételd az elején lévő szöveget, csak írd a folytatást. Magyar nyelvű legyen.\n\nFélbehagyott szöveg:\n%s",
			self::PLATFORM_LABELS[ $platform ] ?? $platform,
			mb_substr( $existing_content, -800 )
		);

		if ( $provider === 'anthropic' ) {
			$result = $this->call_anthropic( $api_key, $prompt );
		} elseif ( $provider === 'gemini' ) {
			$result = $this->call_gemini( $api_key, $prompt );
		} else {
			$result = $this->call_openai( $api_key, $prompt );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'content' => $result ) );
	}

	private function call_openai( $api_key, $prompt ) {
		$model = get_option( 'cc_ai_model', 'gpt-4o-mini' );

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'messages'    => array(
							array(
								'role'    => 'system',
								'content' => 'Te egy tapasztalt magyar social media szövegíró vagy, aki blogcikkek tartalmát alakítja át platform-specifikus bejegyzéssé.',
							),
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'max_tokens'  => 1200,
						'temperature' => 0.72,
					)
				),
				'timeout' => 45,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'openai_error', $body['error']['message'] ?? 'Ismeretlen OpenAI hiba.' );
		}

		return $body['choices'][0]['message']['content'] ?? '';
	}

	private function call_gemini( $api_key, $prompt ) {
		$model = get_option( 'cc_ai_model', 'gemini-3.5-flash' );

		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'system_instruction' => array(
							'parts' => array(
								array( 'text' => 'Te egy tapasztalt magyar social media szövegíró vagy, aki blogcikkek tartalmát alakítja át platform-specifikus bejegyzéssé.' ),
							),
						),
						'contents'           => array(
							array(
								'parts' => array(
									array( 'text' => $prompt ),
								),
							),
						),
						'generationConfig'   => array(
							'maxOutputTokens' => 2048,
							'temperature'     => 0.72,
						),
					)
				),
				'timeout' => 45,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'gemini_error', $body['error']['message'] ?? 'Ismeretlen Gemini hiba.' );
		}

		$parts = $body['candidates'][0]['content']['parts'] ?? array();
		return implode( '', array_column( $parts, 'text' ) );
	}

	private function call_anthropic( $api_key, $prompt ) {
		$model = get_option( 'cc_ai_model', 'claude-haiku-4-5-20251001' );

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => $model,
						'max_tokens' => 1200,
						'system'     => 'Te egy tapasztalt magyar social media szövegíró vagy, aki blogcikkek tartalmát alakítja át platform-specifikus bejegyzéssé.',
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
					)
				),
				'timeout' => 45,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'anthropic_error', $body['error']['message'] ?? 'Ismeretlen Anthropic hiba.' );
		}

		return $body['content'][0]['text'] ?? '';
	}
}
