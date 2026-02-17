<?php
require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Apache_Rewrite_Test extends Tiny_TestCase
{
    /**
     * assert that on plugin initialization the hook on 'update_option_tinypng_convert_format'
     * has been added when conversion is enabled and delivyer method is 'htaccess'. 
     * This ensures that whenever the option is updated, the htaccess rules are inserted or removed.
     */
    function test_plugin_init_will_add_hook()
    {
        $mock_capabilities = Mockery::mock('alias:Tiny_Server_Capabilities');
        $mock_capabilities->shouldReceive('is_apache')->andReturn(true);
        
        $mock_settings = $this->createMock(Tiny_Settings::class);
        $mock_settings->method('get_conversion_enabled')->willReturn(true);
        $mock_settings->method('get_conversion_delivery_method')->willReturn('htaccess');

        new Tiny_Conversion($mock_settings);

        $this->wp->init();

        WordPressStubs::assertHook('update_option_tinypng_convert_format', 'Tiny_Apache_Rewrite::toggle_rules');
    }

    /**
     * assert that when the plugin is uninstalled, the htaccess rules are removed
     */
    function test_plugin_uninstalled_removed_rules() {
        
    }

    /**
     * assert that when the plugin is installed, convert is enabled and delivery is apache, then
     * htaccess rules will be installed.
     */
    function test_plugin_install_add_rules() {}
}
