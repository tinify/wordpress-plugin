<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2018 Tinify B.V.
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

class Tiny_WPML {

	public function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {
		// When WPML duplicates an attachment in other languages.
		add_action( 'wpml_after_duplicate_attachment',
			array( $this, 'copy_tiny_postmeta' ), 10, 2
		);

		// When you add a missing translation text or restore an image
		// on the WPML media tranlation popup.
		add_action( 'wpml_after_copy_attached_file_postmeta',
			array( $this, 'after_copy_attached_file' ), 10, 2
		);

		// When adding an alternative image on the WPML media translation popup.
		add_action( 'wpml_updated_attached_file', array( $this, 'updated_attached_file' ) );
	}

	public function copy_tiny_postmeta( $post_id, $duplicate_post_id ) {
		$original_tiny_postmeta = get_post_meta( $post_id, Tiny_Config::META_KEY, true );
		update_post_meta( $duplicate_post_id, Tiny_Config::META_KEY, $original_tiny_postmeta );
	}

	public function after_copy_attached_file( $original_post_id, $post_id ) {
		$original_tiny_postmeta = get_post_meta( $original_post_id, Tiny_Config::META_KEY, true );
		update_post_meta( $post_id, Tiny_Config::META_KEY, $original_tiny_postmeta );
	}

	public function updated_attached_file( $post_id ) {
		delete_post_meta( $post_id, Tiny_Config::META_KEY );
	}
}
