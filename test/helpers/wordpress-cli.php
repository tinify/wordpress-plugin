<?php

namespace {
    /**
     * Mock WP_CLI to be used in php unit tests
     */
    class WP_CLI
    {
        public static function log($message) {}

        public static function success($message) {}

        public static function warning($message) {}

        public static function add_command($name, $command) {}
    }
}

namespace Utils {
    class MockProgressBar
    {
        public function tick() {}

        public function finish() {}
    }
    function make_progress_bar($label, $count)
    {
        return new MockProgressBar();
    }
}
