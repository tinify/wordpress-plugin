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
    const PREFIX = 'tinypng_';
    const DUMMY_SIZE = '_tiny_dummy';

    protected static function get_prefixed_name($name) {
        return self::PREFIX . $name;
    }

    private $sizes;
    private $tinify_sizes;

    public function admin_init() {
        $section = self::get_prefixed_name('settings');
        add_settings_section($section, self::translate('PNG and JPEG compression'), $this->get_method('render_section'), 'media');

        $field = self::get_prefixed_name('api_key');
        register_setting('media', $field);
        add_settings_field($field, self::translate('TinyPNG API key'), $this->get_method('render_api_key'), 'media', $section, array('label_for' => $field));

        $field = self::get_prefixed_name('sizes');
        register_setting('media', $field);
        add_settings_field($field, self::translate('File compression'), $this->get_method('render_sizes'), 'media', $section);
    }

    public function get_api_key() {
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
        $link = '<a href="https://tinypng.com/developers">' . self::translate_escape('TinyPNG Developer section') . '</a>';
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
}
