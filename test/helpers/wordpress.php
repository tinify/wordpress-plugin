<?php

define('ABSPATH', dirname(dirname(__FILE__)) . '/');
define('WPINC', 'wp-includes-for-tests');

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;
use PHPUnit\Framework\Assert;

class WordPressOptions
{
	private $values;

	public function __construct()
	{
		$this->values = array(
			'thumbnail_size_w' => 150,
			'thumbnail_size_h' => 150,
			'medium_size_w' => 300,
			'medium_size_h' => 300,
			'medium_large_size_w' => 768,
			'medium_large_size_h' => 0,
			'large_size_w' => 1024,
			'large_size_h' => 1024,
		);
	}

	public function set($key, $value)
	{
		if (preg_match('#^(.+)\[(.+)\]$#', $key, $match)) {
			if (! isset($this->values[$match[1]])) {
				$this->values[$match[1]] = array();
			}
			$this->values[$match[1]][$match[2]] = $value;
		} else {
			$this->values[$key] = $value;
		}
	}

	public function get($key, $default = null)
	{
		return isset($this->values[$key]) ? $this->values[$key] : $default;
	}
}

class WordPressStubs
{
	const UPLOAD_DIR = 'wp-content/uploads';

	private $vfs;
	private $initFunctions;
	private $admin_initFunctions;
	private $options;
	private $metadata;
	private $calls;
	private $stubs;
	private $filters;

	public function __construct($vfs)
	{
		$GLOBALS['wp'] = $this;
		$this->vfs = $vfs;
		$this->addMethod('add_action');
		$this->addMethod('do_action');
		$this->addMethod('add_filter');
		$this->addMethod('apply_filters');
		$this->addMethod('register_setting');
		$this->addMethod('add_settings_section');
		$this->addMethod('add_settings_field');
		$this->addMethod('get_option');
		$this->addMethod('get_site_option');
		$this->addMethod('update_site_option');
		$this->addMethod('get_post_meta');
		$this->addMethod('update_post_meta');
		$this->addMethod('get_intermediate_image_sizes');
		$this->addMethod('add_image_size');
		$this->addMethod('translate');
		$this->addMethod('load_plugin_textdomain');
		$this->addMethod('get_post_mime_type');
		$this->addMethod('get_plugin_data');
		$this->addMethod('wp_upload_dir');
		$this->addMethod('get_site_url');
		$this->addMethod('plugin_basename');
		$this->addMethod('is_multisite');
		$this->addMethod('current_user_can');
		$this->addMethod('wp_get_attachment_metadata');
		$this->addMethod('is_admin');
		$this->addMethod('is_customize_preview');
		$this->addMethod('is_plugin_active');
		$this->addMethod('trailingslashit');
		$this->addMethod('current_time');
		$this->addMethod('wp_mkdir_p');
		$this->defaults();
		$this->create_filesystem();
	}

	public function create_filesystem()
	{
		vfsStream::newDirectory(self::UPLOAD_DIR)
			->at($this->vfs);
	}

	public function defaults()
	{
		$this->initFunctions = array();
		$this->admin_initFunctions = array();
		$this->options = new WordPressOptions();
		$this->metadata = array();
		$this->filters = array();
		$GLOBALS['_wp_additional_image_sizes'] = array();
	}

	public function call($method, $args)
	{
		$mocks = new WordPressMocks();
		$this->calls[$method][] = $args;
		if ('add_action' === $method) {
			if ('init' === $args[0]) {
				$this->initFunctions[] = $args[1];
			} elseif ('admin_init' === $args[0]) {
				$this->admin_initFunctions[] = $args[1];
			}
		}
		// Allow explicit stubs to override defaults/behaviors
		if (isset($this->stubs[$method]) && $this->stubs[$method]) {
			return call_user_func_array($this->stubs[$method], $args);
		}
		if ('add_filter' === $method) {
			$tag = isset($args[0]) ? $args[0] : '';
			$function_to_add = isset($args[1]) ? $args[1] : '';
			$priority = isset($args[2]) ? intval($args[2]) : 10;
			$accepted_args = isset($args[3]) ? intval($args[3]) : 1;
			if (! isset($this->filters[$tag])) {
				$this->filters[$tag] = array();
			}
			if (! isset($this->filters[$tag][$priority])) {
				$this->filters[$tag][$priority] = array();
			}
			$this->filters[$tag][$priority][] = array(
				'function' => $function_to_add,
				'accepted_args' => $accepted_args,
			);
			return true;
		}
		if ('apply_filters' === $method) {
			$tag = isset($args[0]) ? $args[0] : '';
			// $value is the first value passed to filters
			$value = isset($args[1]) ? $args[1] : null;
			$call_args = array_slice($args, 1);
			if (isset($this->filters[$tag])) {
				$priorities = array_keys($this->filters[$tag]);
				sort($priorities, SORT_NUMERIC);
				foreach ($priorities as $priority) {
					foreach ($this->filters[$tag][$priority] as $callback) {
						$accepted = max(1, intval($callback['accepted_args']));
						$args_to_pass = array_slice($call_args, 0, $accepted);
						$returned = call_user_func_array($callback['function'], $args_to_pass);
						// Filters should return the (possibly modified) value as first argument.
						$call_args[0] = $returned;
					}
				}
			}
			return $call_args[0];
		}
		if ('translate' === $method) {
			return $args[0];
		} elseif ('get_option' === $method) {
			return call_user_func_array(array($this->options, 'get'), $args);
		} elseif ('get_post_meta' === $method) {
			return call_user_func_array(array($this, 'getMetadata'), $args);
		} elseif ('add_image_size' === $method) {
			return call_user_func_array(array($this, 'addImageSize'), $args);
		} elseif ('update_post_meta' === $method) {
			return call_user_func_array(array($this, 'updateMetadata'), $args);
		} elseif ('get_intermediate_image_sizes' === $method) {
			return array_merge(array('thumbnail', 'medium', 'medium_large', 'large'), array_keys($GLOBALS['_wp_additional_image_sizes']));
		} elseif ('get_plugin_data' === $method) {
			return array('Version' => '1.7.2');
		} elseif ('plugin_basename' === $method) {
			return 'tiny-compress-images';
		} elseif ('wp_upload_dir' === $method) {
			return array('basedir' => $this->vfs->url() . '/' . self::UPLOAD_DIR, 'baseurl' => '/' . self::UPLOAD_DIR);
		} elseif ('is_admin' === $method) {
			return true;
		} elseif (method_exists($mocks, $method)) {
			return $mocks->$method($args[0]);
		}
	}

	public function addMethod($method)
	{
		$this->calls[$method] = array();
		$this->stubs[$method] = array();
		if (! function_exists($method)) {
			eval("function $method() { return \$GLOBALS['wp']->call('$method', func_get_args()); }");
		}
	}

	public function addOption($key, $value)
	{
		$this->options->set($key, $value);
	}

	public function addImageSize($size, $values)
	{
		$GLOBALS['_wp_additional_image_sizes'][$size] = $values;
	}

	public function getMetadata($id, $key, $single = false)
	{
		$values = isset($this->metadata[$id]) ? $this->metadata[$id] : array();
		$value = isset($values[$key]) ? $values[$key] : '';
		return $single ? $value : array($value);
	}

	public function updateMetadata($id, $key, $values)
	{
		$this->metadata[$id][$key] = $values;
	}

	public function setTinyMetadata($id, $values)
	{
		$this->metadata[$id] = array(Tiny_Config::META_KEY => $values);
	}

	public function getCalls($method)
	{
		return $this->calls[$method];
	}

	public function init()
	{
		foreach ($this->initFunctions as $func) {
			call_user_func($func);
		}
	}

	public function admin_init()
	{
		foreach ($this->admin_initFunctions as $func) {
			call_user_func($func);
		}
	}

	public function stub($method, $func)
	{
		$this->stubs[$method] = $func;
	}

	public function createImage($file_size, $path, $name)
	{
		if (! $this->vfs->hasChild(self::UPLOAD_DIR . "/$path")) {
			vfsStream::newDirectory(self::UPLOAD_DIR . "/$path")->at($this->vfs);
		}
		$dir = $this->vfs->getChild(self::UPLOAD_DIR . "/$path");

		vfsStream::newFile($name)
			->withContent(new LargeFileContent($file_size))
			->at($dir);
	}

	public function createImages($sizes = null, $original_size = 12345, $path = '14/01', $name = 'test')
	{
		vfsStream::newDirectory(self::UPLOAD_DIR . "/$path")->at($this->vfs);
		$dir = $this->vfs->getChild(self::UPLOAD_DIR . '/' . $path);

		vfsStream::newFile("$name.png")
			->withContent(new LargeFileContent($original_size))
			->at($dir);

		if (is_null($sizes)) {
			$sizes = array('thumbnail' => 100, 'medium' => 1000, 'large' => 10000, 'post-thumbnail' => 1234);
		}

		foreach ($sizes as $key => $size) {
			vfsStream::newFile("$name-$key.png")
				->withContent(new LargeFileContent($size))
				->at($dir);
		}
	}

	public function createImagesFromJSON($virtual_images)
	{
		foreach ($virtual_images['images'] as $image) {
			self::createImage($image['size'], $virtual_images['path'], $image['file']);
		}
	}

	public function getTestMetadata($path = '14/01', $name = 'test')
	{
		$metadata = array(
			'file' => "$path/$name.png",
			'width' => 4000,
			'height' => 3000,
			'sizes' => array(),
		);

		$regex = '#^' . preg_quote($name) . '-([^.]+)[.](png|jpe?g)$#';
		$dir = $this->vfs->getChild(self::UPLOAD_DIR . "/$path");
		foreach ($dir->getChildren() as $child) {
			$file = $child->getName();
			if (preg_match($regex, $file, $match)) {
				$metadata['sizes'][$match[1]] = array('file' => $file);
			}
		}

		return $metadata;
	}

	/**
	 * Testhelper to easily assert if a hook has been invoked
	 * 
	 * @param string $hookname name of the filter or action
	 * @param mixed $expected_args arguments to the hook
	 */
	public static function assertHook($hookname, $expected_args = null)
	{
		$hooks = array('add_action', 'add_filter');
		$found = false;

		foreach ($hooks as $method) {
			if (! isset($GLOBALS['wp'])) {
				break;
			}

			foreach ($GLOBALS['wp']->getCalls($method) as $call) {
				if (! isset($call[0]) || $call[0] !== $hookname) {
					continue;
				}

				if (is_null($expected_args)) {
					$found = true;
					break 2;
				}

				if ($expected_args === array_slice($call, 1)[0]) {
					$found = true;
					break 2;
				}
			}
		}

		$message = is_null($expected_args)
			? sprintf('Expected hook "%s" to be called.', $hookname)
			: sprintf('Expected hook "%s" to be called with the given arguments.', $hookname);

		Assert::assertTrue($found, $message);
	}
}

class WordPressMocks {
	/**
	 * Mocked function for https://developer.wordpress.org/reference/functions/trailingslashit/
	 *
	 * @return void
	 */
	public function trailingslashit($value)
	{
		return $value . '/';
	}
	
	/**
	 * Mocked function for https://developer.wordpress.org/reference/functions/current_time/
	 *
	 * @return int|string
	 */
	public function current_time() {
		$dt = new DateTime( 'now' );
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Mocked function for https://developer.wordpress.org/reference/functions/wp_mkdir_p/
	 *
	 * @return bool
	 */
	public function wp_mkdir_p( $dir ) {
		mkdir( $dir, 0755, true );
	}
}

class WP_HTTP_Proxy
{
	public function is_enabled()
	{
		return false;
	}
}

function __($text, $domain = 'default')
{
	return translate($text, $domain);
}

function esc_html__($text, $domain = 'default')
{
	return translate($text, $domain);
}
