<?php

class WordPressOptions {
    private $values;

    public function __construct() {
         $this->values = array(
            'thumbnail_size_w' => 150,
            'thumbnail_size_h' => 150,
            'medium_size_w' => 300,
            'medium_size_h' => 300,
            'large_size_w' => 1024,
            'large_size_h' => 1024,
        );
    }

    public function set($key, $value) {
        if (preg_match('#^(.+)\[(.+)\]$#', $key, $match)) {
            if (!isset($this->values[$match[1]])) {
                $this->values[$match[1]] = array();
            }
            $this->values[$match[1]][$match[2]] = $value;
        } else {
            $this->values[$key] = $value;
        }
    }

    public function get($key, $default=null) {
        return isset($this->values[$key]) ? $this->values[$key] : $default;
    }
}

class WordPressStubs {
    private $initFunctions;
    private $admin_initFunctions;
    private $options;
    private $metadata;
    private $calls;
    private $stubs;

    public function __construct() {
        $this->addMethod('add_action');
        $this->addMethod('add_filter');
        $this->addMethod('register_setting');
        $this->addMethod('add_settings_section');
        $this->addMethod('add_settings_field');
        $this->addMethod('get_option');
        $this->addMethod('get_site_option');
        $this->addMethod('update_site_option');
        $this->addMethod('get_post_meta');
        $this->addMethod('update_post_meta');
        $this->addMethod('get_intermediate_image_sizes');
        $this->addMethod('translate');
        $this->addMethod('load_plugin_textdomain');
        $this->addMethod('get_post_mime_type');
        $this->addMethod('wp_upload_dir');
        $this->addMethod('plugin_basename');
        $this->addMethod('is_multisite');
        $this->addMethod('current_user_can');
        $this->defaults();
    }

    public function defaults() {
        $this->initFunctions = array();
        $this->admin_initFunctions = array();
        $this->options = new WordPressOptions();
        $this->metadata = array();
        $GLOBALS['_wp_additional_image_sizes'] = array();
    }

    public function clear() {
        $this->defaults();
        foreach (array_keys($this->calls) as $method) {
            $this->calls[$method] = array();
            $this->stubs[$method] = array();
        }
    }

    public function call($method, $args) {
        $this->calls[$method][] = $args;
        if ('add_action' === $method) {
            if ('init' === $args[0]) {
                $this->initFunctions[] = $args[1];
            } elseif ('admin_init' === $args[0]) {
                $this->admin_initFunctions[] = $args[1];
            }
        }
        if ('translate' === $method) {
            return $args[0];
        } elseif ('get_option' === $method) {
            return call_user_func_array(array($this->options, 'get'), $args);
        } elseif ('get_post_meta' === $method) {
            return call_user_func_array(array($this, 'getMetadata'), $args);
        } elseif ('update_post_meta' === $method) {
            return call_user_func_array(array($this, 'updateMetadata'), $args);
        } elseif ('get_intermediate_image_sizes' === $method) {
            return array_merge(array('thumbnail', 'medium', 'large'), array_keys($GLOBALS['_wp_additional_image_sizes']));
        } elseif ($this->stubs[$method]) {
            return call_user_func_array($this->stubs[$method], $args);
        }
    }

    public function addMethod($method) {
        $this->calls[$method] = array();
        $this->stubs[$method] = array();
        eval("function $method() { return \$GLOBALS['wp']->call('$method', func_get_args()); }");
    }

    public function addOption($key, $value) {
        $this->options->set($key, $value);
    }

    public function addImageSize($size, $values) {
        $GLOBALS['_wp_additional_image_sizes'][$size] = $values;
    }

    public function getMetadata($id, $key, $single=false) {
        $values = isset($this->metadata[$id]) ? $this->metadata[$id] : array();
        $value = isset($values[$key]) ? $values[$key] : '';
        return $single ? $value : array($value);
    }

    public function updateMetadata($id, $key, $values) {
        $this->metadata[$id][$key] = $values;
    }

    public function getCalls($method) {
        return $this->calls[$method];
    }

    public function init() {
        foreach ($this->initFunctions as $func) {
            call_user_func($func);
        }
    }

    public function admin_init() {
        foreach ($this->admin_initFunctions as $func) {
            call_user_func($func);
        }
    }

    public function stub($method, $func) {
        $this->stubs[$method] = $func;
    }
}


$GLOBALS['wp'] = new WordPressStubs();
