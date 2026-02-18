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
     * uninstall_rules removes htaccess rules from upload directory.
     * - creates a htaccess file in uploads directory
     * - run uninstall_rules
     * - validate if rules are removed
     */
    function test_uninstall_rules_removes_upload_dir_htaccess()
    {
        $upload_dir = $this->vfs->url() . '/wp-content/uploads';
        $htaccess_file = $upload_dir . '/.htaccess';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $htaccess_content = "# BEGIN tiny-compress-images\nRewriteEngine On\n# END tiny-compress-images";
        file_put_contents($htaccess_file, $htaccess_content);

        $this->assertTrue(file_exists($htaccess_file), 'htaccess should exist before uninstall');

        Tiny_Apache_Rewrite::uninstall_rules();

        $contents = file_get_contents($htaccess_file);
        $this->assertStringNotContainsString('tiny-compress-images', $contents, 'htaccess should not contain tinify anymore');
    }

    /**
     * uninstall_rules removes htaccess rules from upload directory.
     * - creates a htaccess file in uploads directory
     * - run uninstall_rules
     * - validate if rules are removed
     */
    function test_uninstall_rules_removes_home_dir_htaccess()
    {
        $home_path = $this->vfs->url() . '/';
        $htaccess_file = $home_path . '.htaccess';

        $this->wp->stub('get_home_path', function () use ($home_path) {
            return $home_path;
        });

        file_put_contents($htaccess_file, "# BEGIN tiny-compress-images\nRewriteEngine On\n# END tiny-compress-images");

        $this->assertTrue(file_exists($htaccess_file), 'htaccess should exist before uninstall');

        Tiny_Apache_Rewrite::uninstall_rules();

        $contents = file_get_contents($htaccess_file);
        $this->assertStringNotContainsString('tiny-compress-images', $contents, 'htaccess should not contain plugin markers after uninstall');
    }

    /**
     * Test that uninstall_rules handles non-existent upload directory htaccess gracefully.
     */
    function test_uninstall_rules_handles_missing_upload_htaccess()
    {
        $upload_dir = $this->vfs->url() . '/wp-content/uploads';
        $htaccess_file = $upload_dir . '/.htaccess';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Ensure file doesn't exist
        if (file_exists($htaccess_file)) {
            unlink($htaccess_file);
        }

        $this->assertFalse(file_exists($htaccess_file), 'htaccess should not exist');

        // Should not throw error
        $result = Tiny_Apache_Rewrite::uninstall_rules();

        $this->assertTrue($result, 'uninstall_rules should return true even when file does not exist');
    }

    /**
     * Test that uninstall_rules handles non-existent home directory htaccess gracefully.
     */
    function test_uninstall_rules_handles_missing_home_htaccess()
    {
        $home_path = $this->vfs->url() . '/';
        $htaccess_file = $home_path . '.htaccess';

        // Mock get_home_path to return our virtual filesystem path
        $this->wp->stub('get_home_path', function () use ($home_path) {
            return $home_path;
        });

        // Ensure file doesn't exist
        if (file_exists($htaccess_file)) {
            unlink($htaccess_file);
        }

        $this->assertFalse(file_exists($htaccess_file), 'htaccess should not exist');

        // Should not throw error
        $result = Tiny_Apache_Rewrite::uninstall_rules();

        $this->assertTrue($result, 'uninstall_rules should return true even when file does not exist');
    }
}
