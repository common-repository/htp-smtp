<?php

namespace HTP_SMTP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Network_Admin_Menus {
	const HTP_SMTP_PAGE = Main::PLUGIN_SLUG . '-network';
	const OPTION_NAME = Main::OPTION_NAME . '-network';
	var $menu_title;
	var $page_title;

	public function __construct() {
		$this->page_title = Main::PLUGIN_NAME;
		$this->menu_title = Main::PLUGIN_NAME;
		add_action( 'network_admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_options' ] );
		add_action( 'network_admin_edit_' . self::HTP_SMTP_PAGE . '-update', [ $this, 'update' ] );
	}

	public function add_page() {
		add_menu_page(
			$this->page_title,
			$this->menu_title,
			'manage_network_options',
			self::HTP_SMTP_PAGE,
			[ $this, 'display_page' ]
		);
	}

	public function display_page() {
		echo '<div class="wrap">';
		printf( '<h2>%s</h2>', $this->page_title );
		if ( isset( $_GET['updated'] ) ) {
			printf( '<div class="updated notice notice-inline is-dismissible"><p>%s</p></div>', __( 'Options Saved', 'htp-smtp' ) );
		}
		echo '<form method="post" action="' . network_admin_url( 'edit.php?action=' . self::HTP_SMTP_PAGE . '-update' ) . '">';
		settings_fields( self::OPTION_NAME );
		do_settings_sections( self::OPTION_NAME );
		submit_button();
		echo '</form>';
		Main::test_form();
	}

	public function register_options() {
		$section_id = self::HTP_SMTP_PAGE . '_general_section';
		add_settings_section(
			$section_id,
			sprintf( '%s', __( 'General', 'htp-smtp' ) ),
			[ $this, 'display_section' ],
			self::HTP_SMTP_PAGE
		);
		add_settings_field(
			'from',
			__( 'From', 'htp-smtp' ),
			[ $this, 'render_fields' ],
			self::HTP_SMTP_PAGE,
			$section_id,
			[
				'label_for' => 'from',
				'type'      => 'email',
				'req'       => true
			]
		);
		add_settings_field(
			'from_name',
			__( 'From Name', 'htp-smtp' ),
			[ $this, 'render_fields' ],
			self::HTP_SMTP_PAGE,
			$section_id,
			[
				'label_for' => 'from_name',
				'type'      => 'text',
				'req'       => true
			]
		);
		if ( ! defined( 'HTP_SMTP_HOST' ) ) {
			add_settings_field(
				'smtp_host',
				__( 'SMTP Host', 'htp-smtp' ),
				[ $this, 'render_fields' ],
				self::HTP_SMTP_PAGE,
				$section_id,
				[
					'label_for' => 'smtp_host',
					'type'      => 'text',
					'req'       => true
				]
			);
		}
		if ( ! defined( 'HTP_SMTP_SECURE' ) ) {
			add_settings_field(
				'smtp_secure',
				__( 'SMTP Secure', 'htp-smtp' ),
				[ $this, 'render_fields' ],
				self::HTP_SMTP_PAGE,
				$section_id,
				[
					'label_for' => 'smtp_secure',
					'type'      => 'radio',
					'data'      => [
						'none' => __( 'None', 'htp-smtp' ),
						'ssl'  => __( 'SSL', 'htp-smtp' ),
						'tls'  => __( 'TLS', 'htp-smtp' ),
					]
				]
			);
		}
		if ( ! defined( 'HTP_SMTP_PORT' ) ) {
			add_settings_field(
				'smtp_port',
				__( 'SMTP Port', 'htp-smtp' ),
				[ $this, 'render_fields' ],
				self::HTP_SMTP_PAGE,
				$section_id,
				[
					'label_for' => 'smtp_port',
					'type'      => 'number',
					'min'       => 0
				]
			);
		}
		if ( ! defined( 'HTP_SMTP_AUTH' ) ) {
			add_settings_field(
				'smtp_auth',
				__( 'SMTP Authentication', 'htp-smtp' ),
				[ $this, 'render_fields' ],
				self::HTP_SMTP_PAGE,
				$section_id,
				[
					'label_for' => 'smtp_auth',
					'type'      => 'radio',
					'data'      => [
						'no'  => __( 'No', 'htp-smtp' ),
						'yes' => __( 'Yes', 'htp-smtp' ),
					]
				]

			);
		}
		if ( ! defined( 'HTP_SMTP_USER' ) ) {
			add_settings_field(
				'username',
				__( 'Username', 'htp-smtp' ),
				[ $this, 'render_fields' ],
				self::HTP_SMTP_PAGE,
				$section_id,
				[
					'label_for' => 'username',
					'type'      => 'text',
				]
			);
		}
		if ( ! defined( 'HTP_SMTP_PASS' ) ) {
			add_settings_field(
				'password',
				__( 'Password', 'htp-smtp' ),
				[ $this, 'render_fields' ],
				self::HTP_SMTP_PAGE,
				$section_id,
				[
					'label_for' => 'password',
					'type'      => 'password',
				]
			);
		}
		if ( ! defined( 'HTP_SMTP_HTML' ) ) {
			add_settings_field(
				'html',
				__( 'HTML', 'htp-smtp' ),
				[ $this, 'render_fields' ],
				self::HTP_SMTP_PAGE,
				$section_id,
				[
					'label_for' => 'html',
					'type'      => 'checkbox',
					'desc'      => __( 'Send this email in HTML or in plain text format.', 'htp-smtp' )
				]
			);
		}
		add_settings_field(
			'delete_options',
			__( 'Delete Options', 'htp-smtp' ),
			[ $this, 'render_fields' ],
			self::HTP_SMTP_PAGE,
			$section_id,
			[
				'label_for' => 'delete_options',
				'type'      => 'checkbox',
				'desc'      => __( 'Delete options while deactivate this plugin.', 'htp-smtp' )
			]
		);
		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME
		);
	}

	public function display_section( $args ) {
	}

	public function render_fields( array $args ) {
		Main::render_fields( $args, self::OPTION_NAME, true );
	}

	public function update() {
		if ( empty( $_POST['_wpnonce'] )
		     || ! wp_verify_nonce( $_POST['_wpnonce'], self::OPTION_NAME . '-options' )
		     || ! check_admin_referer( self::OPTION_NAME . '-options' )
		     || empty( $_POST['action'] )
		     || $_POST['action'] !== 'update'
		     || empty( $_POST[ self::OPTION_NAME ] )
		) {
			$updated = false;
		} else {
			$options                   = [];
			$options['from']           = sanitize_email( trim( $_POST['from'] ) );
			$options['from_name']      = sanitize_text_field( trim( $_POST['from_name'] ) );
			$options['smtp_host']      = sanitize_text_field( trim( $_POST['smtp_host'] ) );
			$options['smtp_secure']    = sanitize_text_field( trim( $_POST['smtp_secure'] ) );
			$options['smtp_port']      = is_numeric( trim( $_POST['smtp_port'] ) ) ? absint( trim( $_POST['smtp_port'] ) ) : null;
			$options['smtp_auth']      = sanitize_text_field( trim( $_POST['smtp_auth'] ) );
			$options['username']       = sanitize_text_field( trim( $_POST['username'] ) );
			$options['password']       = sanitize_text_field( trim( $_POST['password'] ) );
			$options['html']           = sanitize_text_field( trim( $_POST['html'] ) );
			$options['delete_options'] = sanitize_text_field( trim( $_POST['delete_options'] ) );

			update_site_option( self::OPTION_NAME, $options );
			$updated = true;
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::HTP_SMTP_PAGE,
					'updated' => $updated,
				),
				network_admin_url( 'admin.php' )
			)
		);
		exit;
	}
}