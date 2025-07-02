<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Cli_Test extends Tiny_TestCase {


	public function set_up() {
		parent::set_up();
	}

	public function test_will_register_command_on_cli_init_hook() {
		$this->wp->defaults();
		
		if (!defined('WP_CLI')) {
			define('WP_CLI', true);
		}
		
		$tiny_cli = new Tiny_Cli(null);
		
		$add_action_calls = $this->wp->getCalls('add_action');
		$cli_init_found = false;
		
		foreach ($add_action_calls as $call) {
			if ($call[0] === 'cli_init' && $call[1] === array($tiny_cli, 'register_command')) {
				$cli_init_found = true;
				break;
			}
		}
		
		$this->assertTrue($cli_init_found, 'register_command should be hooked to cli_init');
	}

	public function test_will_not_hook_if_cli_is_unavailable() {
		$this->wp->defaults();
		
		$add_action_calls = $this->wp->getCalls('add_action');
		$cli_init_found = false;
		
		foreach ($add_action_calls as $call) {
			if ($call[0] === 'cli_init') {
				$cli_init_found = true;
				break;
			}
		}
		
		$this->assertFalse($cli_init_found, 'No cli_init hooks should be registered when WP_CLI is not available');
	}

	public function test_will_compress_attachments_given_in_params() {
		$command = new Tiny_Command();
		$command->optimize(array(), array(
			"attachments" => array(4030),
		));

		
	}
}