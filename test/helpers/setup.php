<?php

require 'vendor/autoload.php';

function wordpress($url = null) {
    return getenv('WORDPRESS_URL') . $url;
}

function wordpress_version() {
    return intval(getenv('WORDPRESS_VERSION'));
}

function configure_wordpress_for_testing($driver) {
    if (is_wordpress_setup()) {
        restore_wordpress_site();
        set_siteurl(wordpress());
        login($driver);
        clear_uploads($driver);
    } else {
        if (wordpress_version() > 30) {
            setup_wordpress_language($driver);
        }
        setup_wordpress_site($driver);
        set_siteurl(wordpress());
        login($driver);
        activate_plugin($driver);
        backup_wordpress_site();
    }
    set_test_webservice_url();
}

function restore_wordpress() {
    if (is_wordpress_setup()) {
        set_siteurl('http://' . getenv('HOST_IP') . ':' . getenv('HOST_PORT'));
    }
    restore_webservice_url();
}

function mysql_dump_file() {
    return dirname(__FILE__) . '/../../tmp/mysqldump_' . getenv('WORDPRESS_DATABASE') . '.sql.gz';
}

function restore_wordpress_site() {
    shell_exec('gunzip -c < ' . mysql_dump_file() . ' | mysql -h ' . getenv('HOST_IP') . ' -u root -p' . getenv('MYSQL_ROOT_PASSWORD') . ' ' . getenv('WORDPRESS_DATABASE'));
}

function backup_wordpress_site() {
    shell_exec('mysqldump -h ' . getenv('HOST_IP') . ' -u root -p' . getenv('MYSQL_ROOT_PASSWORD') . ' ' . getenv('WORDPRESS_DATABASE') . ' | gzip -c > ' . mysql_dump_file());
}

function set_test_webservice_url() {
    $config_dir = dirname(__FILE__) . '/../../src/config';
    shell_exec('mv ' . $config_dir . '/tiny-config.php ' . $config_dir . '/tiny-config.php.bak');
    shell_exec('cp ' . dirname(__FILE__) . '/../fixtures/tiny-config.php ' . $config_dir . '/tiny-config.php');
}

function restore_webservice_url() {
    $config_dir = dirname(__FILE__) . '/../../src/config';
    shell_exec('test -f ' . $config_dir . '/tiny-config.php.bak && mv ' . $config_dir . '/tiny-config.php.bak ' . $config_dir . '/tiny-config.php');
}

function set_siteurl($site_url) {
    $db = new mysqli(getenv('HOST_IP'), 'root', getenv('MYSQL_ROOT_PASSWORD'), getenv('WORDPRESS_DATABASE'));
    $statement = $db->prepare("UPDATE wp_options SET option_value = ? WHERE option_name = 'home' OR option_name = 'siteurl'");
    $statement->bind_param('s', $site_url);
    $statement->execute();
}

function is_wordpress_setup() {
    $db = new mysqli(getenv('HOST_IP'), 'root', getenv('MYSQL_ROOT_PASSWORD'));
    if ($result = $db->query("SELECT * FROM information_schema.tables WHERE table_schema = '" . getenv('WORDPRESS_DATABASE') . "'")) {
        return $result->num_rows > 0;
    } else {
        return false;
    }
}

function setup_wordpress_language($driver) {
    $driver->get(wordpress('/wp-admin/install.php'));
    $driver->findElement(WebDriverBy::tagName('form'))->submit();
}

function setup_wordpress_site($driver) {
    if ($driver->getCurrentURL() != wordpress('/wp-admin/install.php?step=1')) {
        $driver->get(wordpress('/wp-admin/install.php'));
    }
    $driver->findElement(WebDriverBy::name('weblog_title'))->sendKeys('Wordpress test');
    $driver->findElement(WebDriverBy::name('user_name'))->clear()->sendKeys('admin');
    $driver->findElement(WebDriverBy::name('admin_password'))->sendKeys('admin');
    $driver->findElement(WebDriverBy::name('admin_password2'))->sendKeys('admin');
    $driver->findElement(WebDriverBy::name('admin_email'))->sendKeys('developers@voormedia.com');
    $driver->findElement(WebDriverBy::tagName('form'))->submit();
    $h1s = $driver->findElements(WebDriverBy::tagName('h1'));
    $texts = array_map("innerText", $h1s);
    if (array_search('Success', $texts) >= 0) {
        print "Setting up WordPress is successful.\n";
    } else {
        var_dump($driver->getPageSource());
        throw new UnexpectedValueException('Setting up WordPress failed.');
    }
}

function clear_uploads($driver) {
    media_bulk_action($driver, 'delete');
}

function media_bulk_action($driver, $action) {
    $driver->get(wordpress('/wp-admin/upload.php?mode=list'));
    $checkboxes = $driver->findElements(WebDriverBy::cssSelector('th input[type="checkbox"]'));
    if (count($checkboxes) > 0) {
        $checkboxes[0]->click();
        $driver->findElement(WebDriverBy::cssSelector('select[name="action"] option[value="' . $action . '"]'))->click();
        $driver->findElement(WebDriverBy::cssSelector('div.actions input[value="Apply"]'))->click();
    }
}

function login($driver) {
    $driver->get(wordpress('/wp-login.php'));
    $driver->findElement(WebDriverBy::tagName('body'))->click();
    $driver->findElement(WebDriverBy::name('log'))->clear()->click()->sendKeys('admin');
    $driver->findElement(WebDriverBy::name('pwd'))->clear()->click()->sendKeys('admin');
    $driver->findElement(WebDriverBy::tagName('form'))->submit();
    if ($driver->findElement(WebDriverBy::tagName('h2'))->getText() == 'Dashboard') {
        print "Successfully logged into WordPress.\n";
    } else {
        var_dump($driver->getPageSource());
        throw new UnexpectedValueException('Login failed.');
    }
}

function activate_plugin($driver) {
    $driver->get(wordpress('/wp-admin/plugins.php'));
    $activate_links = $driver->findElements(WebDriverBy::xpath('//a[starts-with(@href, "plugins.php?action=activate&plugin=tinypng-image-compression")]'));
    $deactivate_links = $driver->findElements(WebDriverBy::xpath('//a[starts-with(@href, "plugins.php?action=deactivate&plugin=tinypng-image-compression")]'));
    if (count($activate_links) > 0) {
        $activate_links[0]->click();
    } elseif (count($deactivate_links) > 0) {
        print "Plugin already activated.\n";
    } else {
        var_dump($driver->getPageSource());
        throw new UnexpectedValueException('Activating plugin failed.');
    }
}

register_shutdown_function('restore_wordpress');

$global_phantom_host = 'http://127.0.0.1:4444/wd/hub';
$global_driver = RemoteWebDriver::create($global_phantom_host, DesiredCapabilities::firefox());
$global_session_id = $global_driver->getSessionID();
configure_wordpress_for_testing($global_driver);
