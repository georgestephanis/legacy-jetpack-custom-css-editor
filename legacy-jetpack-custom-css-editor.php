<?php

/*
 * Plugin Name: Legacy Jetpack Custom CSS Editor
 * Plugin URI: http://github.com/georgestephanis/legacy-jetpack-custom-css-editor
 * Description: This plugin re-adds the full page admin Custom CSS editor to Jetpack.
 * Author: George Stephanis
 * Version: 0.1-dev
 * Author URI: https://stephanis.info
 */

/*
 * This plugin is meant to also work sans-Jetpack, to provide an admin-side editor,
 * but there's a core bug at the moment that prevents it from functioning as such.
 *
 * Hopefully it will work that way in the future.
 */

class Legacy_Jetpack_Custom_CSS_Editor {
	/**
	 * Set up the class by adding all necessary hooks.
	 */
	public static function add_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_post_legacy_jetpack_update_custom_css', array( __CLASS__, 'legacy_jetpack_update_custom_css' ) );
	}

	/**
	 * Register scripts and the like.
	 */
	public static function admin_init() {
		// For some reason, permissions don't work on some parts without Jetpack's stuff --
		// current_user_can( 'edit_post', wp_get_custom_css_post()->ID )
		// ^^ winds up returning false -- when it should be true.
		// Until this is resolved, don't let this plugin work without Jetpack.
		if ( ! class_exists( 'Jetpack_Custom_CSS_Enhancements' ) ) {
			return;
		}
		wp_register_script( 'legacy-jetpack-custom-css-editor', plugins_url( 'use-codemirror.js', __FILE__ ), array( 'underscore', 'jetpack-codemirror' ), '0.1-dev', true );
		wp_register_script( 'legacy-no-jetpack-custom-css-editor', plugins_url( 'no-jetpack', __FILE__ ) );
	}

	/**
	 * Handle legacy page declaration.
	 */
	public static function admin_menu() {
		// For some reason, permissions don't work on some parts without Jetpack's stuff --
		// current_user_can( 'edit_post', wp_get_custom_css_post()->ID )
		// ^^ winds up returning false -- when it should be true.
		// Until this is resolved, don't let this plugin work without Jetpack.
		if ( ! class_exists( 'Jetpack_Custom_CSS_Enhancements' ) ) {
			return;
		}
		add_theme_page( __( 'Legacy CSS Editor', 'jetpack' ), __( 'Legacy CSS Editor', 'jetpack' ), 'edit_css', 'legacy-editcss', array( __CLASS__, 'admin_page' ) );
	}

	/**
	 * Handle form submissions.
	 */
	public static function legacy_jetpack_update_custom_css() {
		$stylesheet = get_stylesheet();
		check_admin_referer( "legacy_jetpack_update_custom_css-{$stylesheet}" );

		if ( ! current_user_can( 'edit_css' ) ) {
			wp_die( __( "Cheatin', uh?", 'jetpack' ) );
		}

		if ( ! class_exists( 'Jetpack_Custom_CSS_Enhancements' ) ) {
			wp_die( __( 'Error: Missing Jetpack.', 'jetpack' ) );
		}

		if ( isset( $_POST['jetpack_custom_css'] ) ) {
			$jetpack_custom_css = array();

			$jetpack_custom_css['preprocessor'] = null;
			if ( ! empty( $_POST['jetpack_custom_css']['preprocessor'] ) ) {
				$jetpack_custom_css['preprocessor'] = Jetpack_Custom_CSS_Enhancements::sanitize_preprocessor( $_POST['jetpack_custom_css']['preprocessor'] );
			}

			$jetpack_custom_css['replace'] = ! empty( $_POST['jetpack_custom_css']['replace'] );

			$jetpack_custom_css['content_width'] = null;
			if ( ! empty( $_POST['jetpack_custom_css']['content_width'] ) ) {
				$jetpack_custom_css['content_width'] = intval( $_POST['jetpack_custom_css']['content_width'], 10 );
			}

			set_theme_mod( 'jetpack_custom_css', $jetpack_custom_css );
		}

		if ( $jetpack_custom_css['preprocessor'] ) {
			/** This filter is documented in jetpack/modules/custom-css/custom-css.php */
			$preprocessors = apply_filters( 'jetpack_custom_css_preprocessors', array() );

			if ( isset( $preprocessors[ $jetpack_custom_css['preprocessor'] ] ) ) {
				$pre = Jetpack_Custom_CSS_Enhancements::sanitize_css( $_POST['css'] );
				$css = call_user_func( $preprocessors[  $jetpack_custom_css['preprocessor'] ]['callback'], $pre );

				wp_update_custom_css_post( $css, array( 'preprocessed' => $pre ) );
				wp_safe_redirect( $_POST['_wp_http_referer'] . '#saved' );
				return;
			}
		}

		$css = Jetpack_Custom_CSS_Enhancements::sanitize_css( $_POST['css'] );
		wp_update_custom_css_post( $css );
		wp_safe_redirect( $_POST['_wp_http_referer'] . '#saved' );
	}

	/**
	 * Handle output of the admin page.
	 */
	public static function admin_page() {
		if ( wp_script_is( 'jetpack-codemirror', 'registered' ) ) {
			wp_enqueue_script( 'legacy-jetpack-custom-css-editor' );
			wp_enqueue_style( 'jetpack-codemirror' );
		} else {
			wp_enqueue_script( 'legacy-no-jetpack-custom-css-editor' );
		}
		$post = wp_get_custom_css_post();
		if ( empty( $post ) ) {
			$post = get_default_post_to_edit( 'custom-css' );
		}
		$stylesheet = get_stylesheet();
		$theme = wp_get_theme();
		$jetpack_custom_css = array();
		if ( class_exists( 'Jetpack_Custom_CSS_Enhancements' ) ) {
			$jetpack_custom_css = get_theme_mod( 'jetpack_custom_css', array() );
		}
		?>
		<div class="wrap">
			<h1>
				<?php
				printf( __( 'Custom CSS for &#8220;%1$s&#8221;', 'jetpack' ), esc_html( $theme->Name ) );
				if ( current_user_can( 'customize' ) && class_exists( 'Jetpack_Custom_CSS_Enhancements' ) ) {
					printf(
						' <a class="page-title-action hide-if-no-customize" href="%1$s">%2$s</a>',
						esc_url( Jetpack_Custom_CSS_Enhancements::customizer_link() ),
						esc_html__( 'Manage with Live Preview', 'jetpack' )
					);
				}
				?>
			</h1>
			<form action="admin-post.php" method="POST">
				<input type="hidden" name="action" value="legacy_jetpack_update_custom_css" />
				<?php wp_nonce_field( "legacy_jetpack_update_custom_css-{$stylesheet}" ); ?>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<?php
							$content = $post->post_content;
							if ( ! empty( $post->post_content_filtered ) && ! empty( $jetpack_custom_css['preprocessor'] ) ) {
								$content = $post->post_content_filtered;
							}
							?>
							<textarea class="widefat" id="safecss" name="css"><?php echo esc_textarea( $content ); ?></textarea>
						</div>
						<div id="postbox-container-1" class="postbox-container">
							<div id="side-sortables" class="meta-box-sortables">
								<div id="updatediv" class="postbox">
									<h2 style="border-bottom: 1px solid #eee;"><?php esc_html_e( 'Publish', 'jetpack' ); ?></h2>
									<div class="inside">
										<div id="minor-publishing">
											<p><?php esc_html_e( "When you're done editing your CSS, don't forget to hit 'Update'!", 'jetpack' ); ?></p>

											<?php if ( class_exists( 'Jetpack_Custom_CSS_Enhancements' ) ) : ?>
												<div class="misc-pub-section">
													<label><?php esc_html_e( 'Preprocessor:', 'jetpack' ); ?></label>
													<select name="jetpack_custom_css[preprocessor]" id="preprocessor_choices">
														<option value="" <?php selected( '', $jetpack_custom_css['preprocessor'] ); ?>><?php esc_html_e( 'None', 'jetpack' ); ?></option>
														<?php
														/** This filter is documented in jetpack/modules/custom-css/custom-css.php */
														$preprocessors = apply_filters( 'jetpack_custom_css_preprocessors', array() );
														foreach ( $preprocessors as $key => $data ) : ?>
															<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $jetpack_custom_css['preprocessor'] ); ?>><?php echo esc_html( $data['name'] ); ?></option>
														<?php endforeach; ?>
													</select>
												</div>
												<div class="misc-pub-section">
													<label>
														<input type="checkbox" name="jetpack_custom_css[replace]" value="on" <?php checked( ! empty( $jetpack_custom_css['replace'] ) ); ?> />
														<?php esc_html_e( 'Don\'t use the theme\'s original CSS.', 'jetpack' ); ?></label>
													</label>
												</div>
												<div class="misc-pub-section">
													<label><?php esc_html_e( 'Media Width:', 'jetpack' ); ?>
														<input type="number" name="jetpack_custom_css[content_width]" placeholder="<?php echo esc_attr_e( 'Default', 'jetpack' ); ?>" value="<?php echo esc_attr( $jetpack_custom_css['content_width'] ); ?>">
													</label>
												</div>
											<?php endif; ?>

										</div>
									</div>
									<div id="major-publishing-actions">
										<?php submit_button( __( 'Preview', 'jetpack' ), 'secondary', 'preview', false ); ?>
										<div id="publishing-action">
											<?php submit_button( __( 'Update', 'jetpack' ), 'primary large', 'update_css', false ); ?>
										</div>
									</div>
								</div>

								<?php
								// Get eleven, so that if we fetch more than ten, we can display the "More..." link.
								$revisions = wp_get_post_revisions( $post->ID, array( 'posts_per_page' => 11 ) );
								if ( $revisions ) : $counter = 0; ?>
									<div id="revisionsdiv" class="postbox">
										<h2 style="border-bottom: 1px solid #eee;"><?php esc_html_e( 'CSS Revisions', 'jetpack' ); ?></h2>
										<div class="inside">
											<p><?php echo esc_html( sprintf( __( 'You can view prior versions of your Custom CSS for %s here:', 'jetpack' ), $theme->Name ) ); ?></p>
											<ul>
												<?php foreach ( $revisions as $revision_id => $revision ) :?>
													<li><a href="<?php echo esc_url( get_edit_post_link( $revision_id ) ); ?>" target="_blank">
															<?php
															if ( ++$counter > 10 ) {
																esc_html_e( 'Older&hellip;', 'jetpack' );
															} else {
																echo wp_post_revision_title( $revision, false );
															}
															?>
														</a></li>
												<?php endforeach; ?>
											</ul>
										</div>
									</div>
								<?php endif; ?>

								<?php if ( class_exists( 'Jetpack_Custom_CSS_Enhancements' ) ) : ?>
									<div id="otherthemesdiv" class="postbox">
										<h2 style="border-bottom: 1px solid #eee;"><?php esc_html_e( 'Inactive Themes', 'jetpack' ); ?></h2>
										<div class="inside">
											<?php Jetpack_Custom_CSS_Enhancements::revisions_switcher_box( $stylesheet ); ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
		<style>
			#safecss,
			body .CodeMirror {
				min-height: 100%;
				height: 500px;
				height: calc( 100vh - 183px );
			}
			.other-themes-wrap:after {
				content: '';
				display: block;
				clear: both;
			}
			.other-themes-wrap select {
				margin-top: 1em;
			}
			.other-themes-wrap .button {
				float: right;
				margin-top: 1em;
			}
		</style>
		<?php
	}
}

Legacy_Jetpack_Custom_CSS_Editor::add_hooks();
