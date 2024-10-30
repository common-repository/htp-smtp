<?php

namespace HTP_SMTP;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Setup {
	public static function install() {
		self::check_requirements();
		self::default_settings();
	}

	public static function check_requirements() {
		if ( version_compare( phpversion(), '5.3.29', '<=' ) ) {
			wp_die( 'PHP 5.3 or lower detected. HTP SMTP requires PHP 5.6 or greater.' );
		}
	}

	public static function default_settings() {
		$default = [
			'from'           => '',
			'from_name'      => '',
			'smtp_host'      => '',
			'smtp_secure'    => 'tls',
			'smtp_port'      => 587,
			'smtp_auth'      => 'yes',
			'username'       => '',
			'password'       => '',
			'html'           => 'yes',
			'delete_options' => 'yes',
		];

		if ( is_multisite() ) {
			$network_options = get_site_option( Network_Admin_Menus::OPTION_NAME );
			if ( ! $network_options ) {
				$network_options = [
					'from'      => get_site_option( 'admin_email' ),
					'from_name' => get_site_option( 'site_name' ),
				];
				update_site_option( Network_Admin_Menus::OPTION_NAME, wp_parse_args( $network_options, $default ) );
			}
			foreach ( get_sites() as $site ) {
				$options = get_blog_option( $site->blog_id, Admin_Menus::OPTION_NAME );
				if ( ! $options ) {
					$options = [
						'from'             => get_blog_option( $site->blog_id, 'admin_email' ),
						'from_name'        => get_blog_option( $site->blog_id, 'blogname' ),
						'use_network_smtp' => 'yes'
					];
					update_blog_option( $site->blog_id, Admin_Menus::OPTION_NAME, wp_parse_args( $options, $default ) );
				}
			}
		} else {
			$options = get_option( Admin_Menus::OPTION_NAME );
			if ( ! $options ) {
				$options = [
					'from'      => get_option( 'admin_email' ),
					'from_name' => get_bloginfo( 'name' ),
				];
				update_option( Admin_Menus::OPTION_NAME, wp_parse_args( $options, $default ) );
			}
		}

	}

	public static function uninstall() {
		self::deactivate();
	}

	public static function deactivate() {
		if ( is_multisite() ) {
			if ( is_network_admin() ) {
				$network_options       = get_site_option( Network_Admin_Menus::OPTION_NAME );
				$delete_network_option = isset( $network_options['delete_options'] ) ? $network_options['delete_options'] : null;
				if ( $delete_network_option === 'yes' ) {
					delete_site_option( Network_Admin_Menus::OPTION_NAME );
					foreach ( get_sites() as $site ) {
						delete_blog_option( $site->blog_id, Admin_Menus::OPTION_NAME );
					}
				}
			} else {
				$site_id       = get_current_blog_id();
				$options       = get_blog_option( $site_id, Admin_Menus::OPTION_NAME );
				$delete_option = isset( $options['delete_options'] ) ? $options['delete_options'] : null;
				if ( $delete_option === 'yes' ) {
					delete_blog_option( $site_id, Admin_Menus::OPTION_NAME );
				}
			}
		} else {
			$options       = get_option( Admin_Menus::OPTION_NAME );
			$delete_option = isset( $options['delete_options'] ) ? $options['delete_options'] : null;
			if ( $delete_option === 'yes' ) {
				delete_option( Admin_Menus::OPTION_NAME );
			}
		}
	}
}