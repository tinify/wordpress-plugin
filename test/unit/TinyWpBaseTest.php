<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Test_Base extends Tiny_WP_Base {}

class Tiny_WP_Base_Test extends Tiny_TestCase
{
	protected $subject;

	public function set_up()
	{
		parent::set_up();
		$this->subject = new Tiny_Test_Base();
	}

	public function test_should_add_init_hooks()
	{
		$this->assertEquals(
			array(
				array('init', array($this->subject, 'init')),
				array('rest_api_init', array($this->subject, 'rest_init')),
				array('admin_init', array($this->subject, 'admin_init')),
				array('admin_menu', array($this->subject, 'admin_menu')),
			),
			$this->wp->getCalls('add_action')
		);
	}
	public function will_hook_into_cli_init_when_cli_is_available()
	{
		if (!defined('WP_CLI')) {
			define('WP_CLI', WP_CLI);
		}
		$this->assertEquals(
			array(
				array('cli_init', array($this->subject, 'cli_init')),
			),
			$this->wp->getCalls('add_action')
		);
		
		define('WP_CLI', false);
	}
}
