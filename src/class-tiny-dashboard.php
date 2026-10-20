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

	/**
	 * @var Tiny_Settings settings
	 */
	private $settings;

	/**
	 * @var Tiny_Background_Optimize
	 */
	private $background_optimizer;

	/**
	 * @param Tiny_Settings            $settings
	 * @param Tiny_Background_Optimize $background_optimizer
	 */
	public function __construct( $settings, $background_optimizer ) {
		parent::__construct();
		$this->settings             = $settings;
		$this->background_optimizer = $background_optimizer;
	}

	public function admin_init() {
		if ( current_user_can( 'upload_files' ) ) {
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

		wp_enqueue_script(
			self::NAME . '_dashboard_widget',
			plugins_url( '/js/dashboard-widget.js', __FILE__ ),
			array(),
			Tiny_Plugin::version(),
			true
		);

		wp_add_dashboard_widget(
			$this->get_prefixed_name( 'dashboard_widget' ),
			esc_html__( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' ),
			array( $this, 'add_widget_view' )
		);
	}

	public function add_widget_view() {
		$stats  = Tiny_Bulk_Optimization::get_optimization_statistics( $this->settings );
		$widget = $this->get_widget_data( $stats );
		include __DIR__ . '/views/dashboard-widget.php';
	}

	/**
	 * Logic for the view
	 *
	 * @param array $optimization_stats See Tiny_Bulk_Optimization::get_optimization_statistics().
	 * @return array
	 */
	public function get_widget_data( $optimization_stats ) {
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

		$estimated_credit_use = intval( $optimization_stats['estimated_credit_use'] );

		// Only free accounts have a limited number of credits, including
		// free accounts that bought a credit bundle.
		$remaining_credits   = $this->settings->get_remaining_credits();
		$has_limited_credits = $this->settings->is_on_free_plan() &&
			is_numeric( $remaining_credits );
		$remaining_credits   = $has_limited_credits ? intval( $remaining_credits ) : null;

		// How many of the remaining images the credits still cover.
		$images_affordable = 0;
		if ( $has_limited_credits && $images_remaining > 0 && $estimated_credit_use > 0 ) {
			$images_affordable = intval(
				floor( $remaining_credits / ( $estimated_credit_use / $images_remaining ) )
			);
		}

		$time_left = null;
		if ( ! $this->settings->has_api_key() ) {
			$state = 'not_connected';
		} elseif ( $this->settings->refresh_api_key_pending() ) {
			$state = 'pending';
		} elseif ( 0 === $images_total ) {
			$state = 'empty';
		} elseif ( $this->background_optimizer->is_running() ) {
			$state = 'compressing';

			$time_left = intval(
				round( $estimated_credit_use / Tiny_Background_Optimize::WORKERS )
			);
		} elseif ( 0 === $images_remaining ) {
			$state = 'done';
		} elseif ( 0 === $remaining_credits ) {
			$state = 'out_of_credits';
		} elseif ( $has_limited_credits && $estimated_credit_use > $remaining_credits ) {
			$state = 'credits_short';
		} else {
			$state = 'ready';
		}

		return array(
			'state'                => $state,
			'images_optimized'     => $images_optimized,
			'images_total'         => $images_total,
			'images_remaining'     => $images_remaining,
			'images_affordable'    => $images_affordable,
			'bytes_saved'          => max( 0, intval( $bytes_saved ) ),
			'percentage'           => $percentage,
			'estimated_credit_use' => $estimated_credit_use,
			'remaining_credits'    => $remaining_credits,
			'time_left'            => $time_left,
			'email_address'        => $this->settings->get_email_address(),
		);
	}
}
