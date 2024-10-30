<?php
/*
Plugin Name: Jekuntmeer.nl
Plugin URI:  https://www.jekuntmeer.nl/
Description: Jekuntmeer.nl Plugin for wordpress site.
Version:     1.2.1
Author:      Piipol Excellente Whizzkids
Author URI:  https://www.piipol.nl/
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages/
Text Domain: jekuntmeer
 */

defined('ABSPATH') or die('Access Denied!!');

if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

/**
 * Current Version of the Plugin
 */
define('JEKUNTMEER__VERSION', '1.1.0');

/**
 * Minimum Wordpress version needed/tested at
 */
define('JEKUNTMEER__MINIMUM_WP_VERSION', '4.4');

/**
 * Plugins Absolute Url
 */
define('JEKUNTMEER__PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugins Absolute Path
 */
define('JEKUNTMEER__PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * From Email to replace outgoing emails
 * @see Jekuntmeer::EmailFilter()
 */
//define('JEKUNTMEER__FROM_URL', 'info@piipol.nl');

/**
 * Jekuntmeer.nl Soap Url
 */
define('JEKUNTMEER__WSDL_URL', 'https://amsterdam.jekuntmeer.nl/mod_soap2/soap/wsdl');

global $table_prefix, $jkm_table_prefix;
$jkm_table_prefix = $table_prefix . 'jkm_';

register_activation_hook(__FILE__, array('Jekuntmeer', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('Jekuntmeer', 'plugin_deactivation'));
register_uninstall_hook(__FILE__, array('Jekutmeer', 'plugin_uninstallation'));

require_once JEKUNTMEER__PLUGIN_DIR . 'class.jekuntmeer.php';

add_action('init', array('Jekuntmeer', 'init'));

if (isset($_GET['runjob']) && $_GET['runjob'] == 'jkm' && defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true) {
    header('Content-Type: application/json');
    try {
        if (!isset($_GET['maxtime']) || !isset($_GET['offset'])) {
            throw new Exception(__('Missing Arguments', 'jekuntmeer'), 1);
        }

        $maxtime = intval($_GET['maxtime']);
        if (empty($maxtime)) {
            $maxtime = null;
        } else {
            if ($maxtime <= 9) {
                throw new Exception(__('Maxtime Needs to be bigger than 9 seconds', 'jekuntmeer'), 1);
            }
            $maxtime -= 1;
        }

        $offset = sanitize_key($_GET['offset']);

        $arr = explode('_', $offset);

        $startat = false;

        if (count($arr) == 1) {
            $offset = intval($offset);
            $startat = false;
        } elseif (count($arr) == 2) {
            $offset = intval($arr[1]);
            $startat = $arr[0];
        }

        if ($offset < 0) {
            $offset = 0;
        }

        $ret = Jekuntmeer::runJob(true, true, $offset, $maxtime, $startat);
        $json = json_encode($ret);
        echo $json;
    } catch (Exception $e) {
        if (WP_DEBUG) {
            $error = array('error' => $e->__toString());
            echo json_encode($error);
        } else {
            $error = array('error' => $e->getMessage());
            echo json_encode($error);
        }
    }
    exit();
}

if (is_admin()) {
    require_once JEKUNTMEER__PLUGIN_DIR . 'class.jekuntmeer-admin.php';
    add_action('init', array('Jekuntmeer_Admin', 'init'));
}

require_once JEKUNTMEER__PLUGIN_DIR . 'widget.jekuntmeer.php';
add_action('widgets_init', function () {
    register_widget('Jekuntmeer_Widget');
});

require_once JEKUNTMEER__PLUGIN_DIR . 'shortcode.jekuntmeer.php';
add_action('init', array('Jekuntmeer_Shortcode', 'init'));
?>
