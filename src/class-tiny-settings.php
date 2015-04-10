<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015 Voormedia B.V.
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

class Tiny_Settings extends Tiny_WP_Base {
    const DUMMY_SIZE = '_tiny_dummy';

    private $sizes;
    private $tinify_sizes;
    private $compressor;

    public function admin_init() {
        parent::admin_init();

        try {
            $this->compressor = Tiny_Compress::get_compressor($this->get_api_key(), $this->get_method('after_compress_callback'));
        } catch (Tiny_Exception $e) {
            $this->add_admin_notice('compressor_exception', self::translate_escape($e->getMessage()), true);
        }

        $section = self::get_prefixed_name('settings');
        add_settings_section($section, self::translate('PNG and JPEG compression'), $this->get_method('render_section'), 'media');

        $field = self::get_prefixed_name('api_key');
        register_setting('media', $field);
        add_settings_field($field, self::translate('TinyPNG API key'), $this->get_method('render_api_key'), 'media', $section, array('label_for' => $field));

        $field = self::get_prefixed_name('sizes');
        register_setting('media', $field);
        add_settings_field($field, self::translate('File compression'), $this->get_method('render_sizes'), 'media', $section);

        $field = self::get_prefixed_name('status');
        register_setting('media', $field);
        add_settings_field($field, self::translate('Connection status'), $this->get_method('render_pending_status'), 'media', $section);

        add_action('wp_ajax_tiny_compress_status', $this->get_method('get_status'));
    }

    public function get_status() {
        $this->render_status();
        exit();
    }

    public function get_compressor() {
        return $this->compressor;
    }

    public function set_compressor($compressor) {
        $this->compressor = $compressor;
    }

    protected function get_api_key() {
        if (defined('TINY_API_KEY')) {
            return TINY_API_KEY;
        } else {
            return get_option(self::get_prefixed_name('api_key'));
        }
    }

    protected static function get_intermediate_size($size) {
        # Inspired by http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
        global $_wp_additional_image_sizes;

        $width  = get_option($size . '_size_w');
        $height = get_option($size . '_size_h');
        if ($width && $height) {
            return array($width, $height);
        }

        if (isset($_wp_additional_image_sizes[$size])) {
            $sizes = $_wp_additional_image_sizes[$size];
            return array(
                isset($sizes['width']) ? $sizes['width'] : null,
                isset($sizes['height']) ? $sizes['height'] : null,
            );
        }
        return array(null, null);
    }

    public function get_sizes() {
        if (is_array($this->sizes)) {
            return $this->sizes;
        }

        $setting = get_option(self::get_prefixed_name('sizes'));

        $size = Tiny_Metadata::ORIGINAL;
        $this->sizes = array($size => array(
            'width' => null, 'height' => null,
            'tinify' => !is_array($setting) || (isset($setting[$size]) && $setting[$size] === 'on'),
        ));

        foreach (get_intermediate_image_sizes() as $size) {
            if ($size === self::DUMMY_SIZE) {
                continue;
            }
            list($width, $height) = self::get_intermediate_size($size);
            if ($width && $height) {
                $this->sizes[$size] = array(
                    'width' => $width, 'height' => $height,
                    'tinify' => !is_array($setting) || (isset($setting[$size]) && $setting[$size] === 'on'),
                );
            }
        }
        return $this->sizes;
    }

    public function get_tinify_sizes() {
        if (is_array($this->tinify_sizes)) {
            return $this->tinify_sizes;
        }

        $this->tinify_sizes = array();
        foreach ($this->get_sizes() as $size => $values) {
            if ($values['tinify']) {
                $this->tinify_sizes[] = $size;
            }
        }
        return $this->tinify_sizes;
    }

    public function render_section() {
    }

    public function render_api_key() {
        $field = self::get_prefixed_name('api_key');
        $key = $this->get_api_key();

        if (defined('TINY_API_KEY')) {
            echo '<p>' . sprintf(self::translate('The API key has been configured in %s'), 'wp-config.php') . '.</p>';
        } else {
            echo '<input type="text" id="' . $field . '" name="' . $field . '" value="' . htmlspecialchars($key) . '" size="40" />';
        }
        echo '<p>';
        $link = '<a href="https://tinypng.com/developers" target="_blank">' . self::translate_escape('TinyPNG Developer section') . '</a>';
        if (empty($key)) {
            printf(self::translate_escape('Visit %s to get an API key') . '.', $link);
        } else {
            printf(self::translate_escape('Visit %s to view your usage or upgrade your account') . '.', $link);
        }
        echo '</p>';
    }

    public function render_sizes() {
        echo '<p>' . self::translate_escape('You can choose to compress different image sizes created by WordPress here') . '.<br/>';
        echo self::translate_escape('Remember each additional image size will affect your TinyPNG monthly usage') . "!";?>
<input type="hidden" name="<?php echo self::get_prefixed_name('sizes[' . self::DUMMY_SIZE .']'); ?>" value="on"/></p>
<?php
        foreach ($this->get_sizes() as $size => $option) {
            $this->render_size_checkbox($size, $option);
        }
    }

    private function render_size_checkbox($size, $option) {
        $id = self::get_prefixed_name("sizes_$size");
        $field = self::get_prefixed_name("sizes[$size]");
        if ($size === Tiny_Metadata::ORIGINAL) {
            $label = self::translate_escape("original");
        } else {
            $label = $size . " - ${option['width']}x${option['height']}";
        }?>
<p><input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $field ?>" value="on" <?php if ($option['tinify']) { echo ' checked="checked"'; } ?>/>
<label for="<?php echo $id; ?>"><?php echo $label; ?></label></p>
<?php
    }

    public function get_compression_count() {
        $field = self::get_prefixed_name('status');
        return get_option($field);
    }

    public function after_compress_callback($details, $headers) {
        if(isset($headers["Compression-Count"])) {
            $field = self::get_prefixed_name('status');
            update_option($field, $headers["Compression-Count"]);

            if (isset($details['error']) && $details['error'] == 'TooManyRequests') {
                $link = '<a href="https://tinypng.com/developers" target="_blank">' . self::translate_escape('subscription') . '</a>';
                $this->add_admin_notice('limit_reached', sprintf(self::translate_escape('you have reached your limit of %s compressions this month. Upgrade your %s if you like to compress more images') . '.', $headers["Compression-Count"], $link));
            } else {
                $this->remove_admin_notice('limit_reached');
            }
        }
    }

    public function render_status() {
        switch ($this->compressor->get_status()) {
            case Tiny_Compressor_Status::Green:
                echo '<p><img src="images/yes.png"> ' . self::translate_escape('API connection successful') . '</p>';
                break;
            case Tiny_Compressor_Status::Yellow:
                echo '<p>' . self::translate_escape('API status could not be checked, enable cURL for more information') . '.</p>';
                return;
            case Tiny_Compressor_Status::Red:
                echo '<p><img src="images/no.png"> ' . self::translate_escape('API connection unsuccessful') . '</p>';
                return;
        }

        $compressions = self::get_compression_count();
        echo '<p>';
        // We currently have no way to check if a user is free or flexible.
        if ($compressions == 500) {
            $link = '<a href="https://tinypng.com/developers" target="_blank">' . self::translate_escape('TinyPNG API subscription') . '</a>';
            printf(self::translate_escape('You have reached your limit of %s compressions this month') . '.', $compressions);
            echo '<br>';
            printf(self::translate_escape('If you need to compress more images you can change your %s') . '.', $link);
        } else {
           printf(self::translate_escape('You have made %s compressions this month') . '.', self::get_compression_count());
        }
        echo '</p>';
    }

    public function render_pending_status() {
        echo '<div id="tiny-compress-status"><div class="spinner"></div></div>';
    }
}
