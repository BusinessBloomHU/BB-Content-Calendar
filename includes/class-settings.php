<?php

class CC_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_submenu() {
		add_submenu_page(
			'content-calendar',
			'Tartalomnaptár Beállítások',
			'Beállítások',
			'manage_options',
			'content-calendar-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( 'cc_settings', 'cc_ai_provider', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'openai' ) );
		register_setting( 'cc_settings', 'cc_ai_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'cc_settings', 'cc_ai_model', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'gpt-4o-mini' ) );
		register_setting(
			'cc_settings',
			'cc_post_types',
			array(
				'sanitize_callback' => function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array( 'post' );
					}
					return array_map( 'sanitize_text_field', $value );
				},
				'default' => array( 'post' ),
			)
		);
	}

	public function render_page() {
		$provider    = get_option( 'cc_ai_provider', 'openai' );
		$saved_model = get_option( 'cc_ai_model', 'gpt-4o-mini' );
		$api_key     = get_option( 'cc_ai_api_key', '' );
		$saved_types = get_option( 'cc_post_types', array( 'post' ) );

		$openai_models = array(
			'gpt-4o'       => 'GPT-4o (legjobb minőség)',
			'gpt-4o-mini'  => 'GPT-4o Mini (gyors & olcsó)',
		);

		$anthropic_models = array(
			'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (legjobb minőség)',
			'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (gyors & olcsó)',
		);

		$gemini_models = array(
			'gemini-3.5-flash'    => 'Gemini 3.5 Flash (ingyenes)',
			'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite (ingyenes, kisebb)',
		);
		?>
		<div class="wrap">
			<h1>Tartalomnaptár – Beállítások</h1>

			<?php settings_errors( 'cc_settings' ); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'cc_settings' ); ?>

				<h2>AI beállítások</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cc_ai_provider">AI szolgáltató</label></th>
						<td>
							<select name="cc_ai_provider" id="cc_ai_provider">
								<option value="openai" <?= selected( $provider, 'openai', false ) ?>>OpenAI (ChatGPT)</option>
								<option value="anthropic" <?= selected( $provider, 'anthropic', false ) ?>>Anthropic (Claude)</option>
								<option value="gemini" <?= selected( $provider, 'gemini', false ) ?>>Google (Gemini) – ingyenes tier</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cc_ai_api_key">API kulcs</label></th>
						<td>
							<input
								type="password"
								id="cc_ai_api_key"
								name="cc_ai_api_key"
								value="<?= esc_attr( $api_key ) ?>"
								class="regular-text"
								autocomplete="new-password"
							>
							<p class="description">
								<strong>OpenAI:</strong>
								<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com/api-keys</a>
								<br>
								<strong>Anthropic:</strong>
								<a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener">console.anthropic.com/settings/keys</a>
								<br>
								<strong>Google Gemini:</strong>
								<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">aistudio.google.com/app/apikey</a>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cc_ai_model">AI modell</label></th>
						<td>
							<select name="cc_ai_model" id="cc_ai_model">
								<optgroup label="OpenAI modellek" id="cc-openai-models">
									<?php foreach ( $openai_models as $val => $label ) : ?>
										<option value="<?= esc_attr( $val ) ?>" <?= selected( $saved_model, $val, false ) ?>>
											<?= esc_html( $label ) ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="Anthropic modellek" id="cc-anthropic-models">
									<?php foreach ( $anthropic_models as $val => $label ) : ?>
										<option value="<?= esc_attr( $val ) ?>" <?= selected( $saved_model, $val, false ) ?>>
											<?= esc_html( $label ) ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="Google Gemini modellek" id="cc-gemini-models">
									<?php foreach ( $gemini_models as $val => $label ) : ?>
										<option value="<?= esc_attr( $val ) ?>" <?= selected( $saved_model, $val, false ) ?>>
											<?= esc_html( $label ) ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
							</select>
						</td>
					</tr>
				</table>

				<h2>Tartalom beállítások</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Post típusok</th>
						<td>
							<?php
							$all_post_types = get_post_types( array( 'public' => true ), 'objects' );
							foreach ( $all_post_types as $pt ) :
							?>
								<label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
									<input
										type="checkbox"
										name="cc_post_types[]"
										value="<?= esc_attr( $pt->name ) ?>"
										<?= in_array( $pt->name, (array) $saved_types, true ) ? 'checked' : '' ?>
									>
									<span>
										<?= esc_html( $pt->label ) ?>
										<code style="font-size:11px;color:#64748b;"><?= esc_html( $pt->name ) ?></code>
									</span>
								</label>
							<?php endforeach; ?>
							<p class="description">Ezeken a post típusokon jelenik meg a social media metabox.</p>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Beállítások mentése' ); ?>
			</form>

			<script>
			(function() {
				const providerSelect = document.getElementById('cc_ai_provider');
				const openaiGroup = document.getElementById('cc-openai-models');
				const anthropicGroup = document.getElementById('cc-anthropic-models');
				const geminiGroup = document.getElementById('cc-gemini-models');

				const groups = { openai: openaiGroup, anthropic: anthropicGroup, gemini: geminiGroup };

				function updateModels() {
					const val = providerSelect.value;
					Object.entries(groups).forEach(([key, group]) => {
						group.style.display = key === val ? '' : 'none';
					});
					const visible = groups[val];
					if (visible && !visible.querySelector('option[selected]')) {
						visible.querySelector('option').selected = true;
					}
				}

				providerSelect.addEventListener('change', updateModels);
				updateModels();
			})();
			</script>
		</div>
		<?php
	}
}
