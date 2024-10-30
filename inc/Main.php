<?php

namespace HTP_SMTP;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Main {
	const OPTION_NAME = 'htp-smtp';
	const PLUGIN_NAME = 'HTP SMTP';
	const PLUGIN_SLUG = 'htp-smtp';
	protected static $instance;
	private static $options;

	public function __construct() {
		if ( is_multisite() && is_network_admin() ) {
			self::$options = get_site_option( Network_Admin_Menus::OPTION_NAME );
		} else {
			self::$options = get_option( Admin_Menus::OPTION_NAME );
		}

		$this->init_hooks();
		add_action( 'init', [ $this, 'init' ] );
	}

	private function init_hooks() {
		register_activation_hook( HTP_SMTP_FILE, [ 'HTP_SMTP\Setup', 'install' ] );
		register_deactivation_hook( HTP_SMTP_FILE, [ 'HTP_SMTP\Setup', 'deactivate' ] );
		register_uninstall_hook( HTP_SMTP_FILE, [ 'HTP_SMTP\Setup', 'uninstall' ] );

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'phpmailer_init', [ $this, 'smtp' ] );
		add_filter( 'plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
		if ( is_multisite() ) {
			add_filter( 'network_admin_plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
		}

	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function test_form() {
		echo '<form method="post">';
		printf( '<h2>%s</h2>', __( 'Send Test Email', 'htp-smtp' ) );
		self::test_handle();
		echo '<table class="form-table">';
		echo '<tbody>';
		echo '<tr>';
		echo '<th>';
		printf( '<label for="send_to">%s</label>', __( 'Send to', 'htp-smtp' ) );
		echo '</th>';
		echo '<td>';
		echo '<input type="email" name="send_to" size="50" value="' . get_option( 'admin_email' ) . '" required>';
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '<input type="hidden" name="htp_smtp_test" value="test" />';
		echo '<input type="hidden" name="htp_smtp_test_nonce" value="' . wp_create_nonce( 'htp_smtp_test' ) . '" />';
		submit_button( __( 'Send Email', 'htp-smtp' ) );
		echo '</form>';
		echo '</div>';
	}

	private static function test_handle() {
		if (
			empty( $_POST['htp_smtp_test'] )
			|| empty( $_POST['htp_smtp_test_nonce'] )
		) {
			return;
		}
		$notice         = '<div class="notice notice-error notice-inline is-dismissible"><p>%s</p></div>';
		$notice_success = '<div class="notice notice-success notice-inline is-dismissible"><p>%s</p></div>';
		if ( ! wp_verify_nonce( $_POST['htp_smtp_test_nonce'], 'htp_smtp_test' ) || $_POST['htp_smtp_test'] !== 'test' ) {
			printf( $notice, __( 'Security check not passed!', 'htp-smtp' ) );

			return;
		}
		if ( ! is_email( trim( $_POST['send_to'] ) ) ) {
			printf( $notice, __( 'Email address is not valid', 'htp-smtp' ) );

			return;
		}

		if ( ! is_email( self::$options['from'] ) ) {
			printf( $notice, __( 'You cannot send an email. Mailer is not properly configured. Please check your settings.', 'htp-smtp' ) );

			return;
		}

		if ( wp_mail(
			sanitize_email( trim( $_POST['send_to'] ) ),
			__( 'HTP SMTP: Test email', 'htp-smtp' ),
			__( 'Congrats, test email was sent successfully!', 'htp-smtp' )
		) ) {
			printf( $notice_success, __( 'Test email was sent successfully! Please check your inbox to make sure it is delivered.', 'htp-smtp' ) );
		} else {
			printf( $notice, __( 'You cannot send an email. Mailer is not properly configured. Please check your settings.', 'htp-smtp' ) );
		}

		return;
	}

	public static function render_fields( array $args, $option_name, $is_network = false ) {
		$id = $args['label_for'];
		if ( $is_network ) {
			$options = get_site_option( $option_name );
		} else {
			$options = get_option( $option_name );
		}

		$value = isset( $options[ $id ] ) ? $options[ $id ] : null;

		$req = ( ! empty( $args['req'] ) && $args['req'] == true ) ? ' required ' : '';

		switch ( $args['type'] ) {
			case 'none':
			default:
				break;
			case 'checkbox':
				echo '<label>';
				echo '<input type="checkbox" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="yes" ' . checked( $value, 'yes', false ) . ' ' . esc_html( $req ) . '/>';
				if ( ! empty( $args['desc'] ) ) {
					printf( '%s', $args['desc'] );
				}
				echo '</label>';
				break;
			case 'radio':
				if ( $data = $args['data'] ) {
					foreach ( $data as $k => $v ) {
						echo '<label>';
						echo '<input type="radio" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="' . esc_attr( $k ) . '" ' . checked( $value, $k, false ) . '/>';
						esc_html_e( $v );
						echo '</label>';
						echo ' &nbsp; ';
					}
				}
				break;
			case 'email':
				echo '<input type="email" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="' . esc_attr( $value ) . '" size="50" ' . esc_html( $req ) . '/>';
				break;
			case 'text':
				echo '<input type="text" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="' . esc_attr( $value ) . '" size="50" ' . esc_html( $req ) . '/>';
				break;
			case 'number':
				$min  = isset( $args['min'] ) && is_numeric( trim( $args['min'] ) ) ? ' min="' . esc_attr( $args['min'] ) . '" ' : '';
				$max  = isset( $args['max'] ) && is_numeric( trim( $args['max'] ) ) ? ' max="' . esc_attr( $args['max'] ) . '" ' : '';
				$step = isset( $args['step'] ) && is_numeric( trim( $args['step'] ) ) ? ' step="' . esc_attr( $args['step'] ) . '" ' : '';
				echo '<input type="number" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="' . esc_attr( $value ) . '" size="50" ' . $min . $max . $step . esc_html( $req ) . '/>';
				break;
			case 'password':
				echo '<input type="password" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="' . esc_attr( $value ) . '" size="50" ' . esc_html( $req ) . '/>';
				break;
		}
		if ( ! empty( $args['desc'] ) && $args['type'] !== 'checkbox' ) {
			printf( '<p class="description">%s</p>', esc_html( $args['desc'] ) );
		}
	}

	public function init() {
		if ( is_multisite() ) {
			new Network_Admin_Menus();
		}
		new Admin_Menus();
	}

	public function plugin_action_links( $links, $file ) {
		if ( strpos( $file, basename( HTP_SMTP_FILE ) ) !== false ) {
			$settings_url = add_query_arg( 'page', Admin_Menus::HTP_SMTP_PAGE, admin_url( 'admin.php' ) );
			if ( is_multisite() && is_network_admin() ) {
				$settings_url = add_query_arg( 'page', Network_Admin_Menus::HTP_SMTP_PAGE, network_admin_url( 'admin.php' ) );
			}
			$newLinks = array(
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					$settings_url,
					__( 'Settings', 'htp-smtp' )
				),
			);

			$links = array_merge( $newLinks, $links );
		}

		return $links;
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'htp-smtp', false, basename( dirname( HTP_SMTP_FILE ) ) . '/languages/' );
	}

	public function smtp( $phpmailer ) {
		if ( ! is_email( self::$options['from'] )
		     || empty( self::$options['from_name'] )
		) {
			return;
		}
		$smtp_host   = isset( self::$options['smtp_host'] ) ? self::$options['smtp_host'] : null;
		$smtp_secure = isset( self::$options['smtp_secure'] ) ? self::$options['smtp_secure'] : null;
		$smtp_port   = isset( self::$options['smtp_port'] ) ? self::$options['smtp_port'] : null;
		$smtp_auth   = isset( self::$options['smtp_auth'] ) ? self::$options['smtp_auth'] : null;
		$username    = isset( self::$options['username'] ) ? self::$options['username'] : null;
		$password    = isset( self::$options['password'] ) ? self::$options['password'] : null;
		$html        = isset( self::$options['html'] ) ? self::$options['html'] : null;
		if (
			is_multisite()
			&& ! is_network_admin()
			&& isset( self::$options['use_network_smtp'] )
			&& self::$options['use_network_smtp'] === 'yes'
		) {
			$network_options = get_site_option( Network_Admin_Menus::OPTION_NAME );
			$smtp_host       = isset( $network_options['smtp_host'] ) ? $network_options['smtp_host'] : null;
			$smtp_secure     = isset( $network_options['smtp_secure'] ) ? $network_options['smtp_secure'] : null;
			$smtp_port       = isset( $network_options['smtp_port'] ) ? $network_options['smtp_port'] : null;
			$smtp_auth       = isset( $network_options['smtp_auth'] ) ? $network_options['smtp_auth'] : null;
			$username        = isset( $network_options['username'] ) ? $network_options['username'] : null;
			$password        = isset( $network_options['password'] ) ? $network_options['password'] : null;
			$html            = isset( $network_options['html'] ) ? $network_options['html'] : null;
		}
		$smtp_host   = defined( 'HTP_SMTP_HOST' ) ? HTP_SMTP_HOST : $smtp_host;
		$smtp_secure = defined( 'HTP_SMTP_SECURE' ) ? HTP_SMTP_SECURE : $smtp_secure;
		$smtp_port   = defined( 'HTP_SMTP_PORT' ) ? HTP_SMTP_PORT : $smtp_port;
		$smtp_auth   = defined( 'HTP_SMTP_AUTH' ) ? HTP_SMTP_AUTH : $smtp_auth;
		$username    = defined( 'HTP_SMTP_USER' ) ? HTP_SMTP_USER : $username;
		$password    = defined( 'HTP_SMTP_PASS' ) ? HTP_SMTP_PASS : $password;
		$html        = defined( 'HTP_SMTP_HTML' ) ? HTP_SMTP_HTML : $html;

		if ( empty( $smtp_host ) ) {
			return;
		}

		$phpmailer->Mailer   = 'smtp';
		$phpmailer->From     = self::$options['from'];
		$phpmailer->FromName = self::$options['from_name'];
		$phpmailer->Sender   = $phpmailer->From;
		$phpmailer->AddReplyTo( $phpmailer->From, $phpmailer->FromName );
		$phpmailer->Host       = $smtp_host;
		$phpmailer->SMTPSecure = $smtp_secure;
		$phpmailer->Port       = $smtp_port;
		$phpmailer->SMTPAuth   = $smtp_auth == 'yes';
		if ( $html === 'yes' ) {
			$phpmailer->ContentType = 'text/html';
		}

		if ( $phpmailer->SMTPAuth ) {
			$phpmailer->Username = $username;
			$phpmailer->Password = $password;
		}
	}
}