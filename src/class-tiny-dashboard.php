<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2023 Tinify B.V.
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the Free
* Software Foundation; either version 2 of the License, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
* FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
* more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc., 51
* Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
class Tiny_Dashboard extends Tiny_WP_Base {

	const LOW_CREDITS_THRESHOLD = 100;

	/**
	 * @var Tiny_Settings settings
	 */
	private $settings;

	/**
	 * @param Tiny_Settings $settings
	 */
	public function __construct( $settings ) {
		parent::__construct();
		$this->settings = $settings;
	}

	public function admin_init() {
		if ( current_user_can('upload_files') ) {
			add_action(
				'wp_dashboard_setup',
				array( $this, 'add_dashboard_widget' )
			);
		}
	}

	public function add_dashboard_widget() {
		wp_enqueue_style(
			self::NAME . '_dashboard_widget',
			plugins_url( '/css/dashboard-widget.css', __FILE__ ),
			array(),
			Tiny_Plugin::version()
		);

		wp_add_dashboard_widget(
			$this->get_prefixed_name( 'dashboard_widget' ),
			esc_html__( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' ),
			$this->get_method( 'add_widget_view' )
		);
	}

	public function add_widget_view() {
		$optimization_statistics = Tiny_Bulk_Optimization::get_optimization_statistics( $this->settings );
		$widget                  = self::get_widget_data(
			$optimization_statistics,
			$this->settings->get_remaining_credits(),
			$this->settings->get_paying_state(),
			$this->settings->has_api_key()
		);
		$email_address           = $this->settings->get_email_address();
		include __DIR__ . '/views/dashboard-widget.php';
	}

	/**
	 * Logic for the view
	 *
	 * @param array        $optimization_stats See Tiny_Bulk_Optimization::get_optimization_statistics().
	 * @param int|false    $remaining_credits  Stored account credits, false when unknown.
	 * @param string|false $paying_state       Stored account paying state, false when unknown.
	 * @param bool         $has_api_key
	 * @return array
	 */
	public static function get_widget_data( $optimization_stats, $remaining_credits = false, $paying_state = false, $has_api_key = true ) {
		$images_total     = max( 0, intval( $optimization_stats['uploaded-images'] ) );
		$images_remaining = min(
			$images_total,
			count( $optimization_stats['available-for-optimization'] )
		);
		$images_optimized = $images_total - $images_remaining;
		$bytes_saved      = $optimization_stats['unoptimized-library-size']
			- $optimization_stats['optimized-library-size'];

		$percentage = $images_total > 0
			? intval( floor( $images_optimized / $images_total * 100 ) )
			: 0;

		$label = self::get_label_text( $percentage );

		if ( 0 === $images_total ) {
			$status = 'empty';
			$panda  = 'panda-waiting.png';
		} elseif ( 0 === $images_remaining ) {
			$status = 'done';
			$panda  = 'panda-laying.png';
		} else {
			$status = 'in_progress';
			$panda  = 'panda-waiting.png';
		}

		// Only free accounts have a limited number of credits, including
		// free accounts that bought a credit bundle.
		$has_limited_credits = 'free' === $paying_state && is_numeric( $remaining_credits );
		$remaining_credits   = $has_limited_credits ? intval( $remaining_credits ) : null;

		if ( ! $has_api_key ) {
			$notice = 'no_api_key';
		} elseif ( null !== $remaining_credits && $remaining_credits < self::LOW_CREDITS_THRESHOLD ) {
			$notice = 'low_credits';
		} else {
			$notice = null;
		}

		return array(
			'status'            => $status,
			'images_optimized'  => $images_optimized,
			'images_total'      => $images_total,
			'images_remaining'  => $images_remaining,
			'bytes_saved'       => max( 0, intval( $bytes_saved ) ),
			'percentage'        => $percentage,
			'label'             => $label,
			'panda'             => $panda,
			'remaining_credits' => $remaining_credits,
			'notice'            => $notice,
		);
	}

	private static function get_label_text( $percentage ) {
		if ( $percentage > 99 ) {
			return __( 'optimized', 'tiny-compress-images' );
		}
		if ( $percentage > 75 ) {
			return __( 'almost there', 'tiny-compress-images' );
		}

		if ( $percentage > 50 ) {
			return __( 'halfway there', 'tiny-compress-images' );
		}

		if ( $percentage === 0 ) {
			return '';
		}

		return __( 'getting started', 'tiny-compress-images' );
	}
}
