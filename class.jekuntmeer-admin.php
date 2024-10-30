<?php
/**
 * Class Jekuntmeer_Admin
 *
 * All Functions needed for the Admin Page are here
 * @see Jekuntmeer
 */
class Jekuntmeer_Admin {
    /**
     * @var bool $initiated Check that hooks get set only one time
     */
    private static $initiated = false;

    /**
     * Setup Admin Hooks
     * @see Jekuntmeer_Admin::init_hooks()
     */
    public static function init() {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    /**
     * Setups all needed hooks
     * @see Jekuntmeer_Admin::admin_init()
     * @see Jekuntmeer_Admin::admin_menu()
     */
    private static function init_hooks() {
        self::$initiated = true;

        add_action('admin_init', array('Jekuntmeer_Admin', 'admin_init'));
        add_action('admin_menu', array('Jekuntmeer_Admin', 'admin_menu'));
    }

    /**
     * Setups all Admin Pages and Options
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_login_section_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_username_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_password_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_more_section_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_get_only_own_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_allow_user_search_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_login_section_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_css_section_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_css_editor_section_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_filter_section_callback()
     * @see Jekuntmeer_Admin::jekuntmeer_config_soap_filter_callback()
     */
    public static function admin_init() {
        add_settings_section('jekuntmeer_config_soap_login_section', __('Jekuntmeer (SOAP) inlog gegevens', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_login_section_callback'), 'jekuntmeerconfigmain');
        add_settings_field('jekuntmeer_config_soap_username', __('Gebruikersnaam', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_username_callback'), 'jekuntmeerconfigmain', 'jekuntmeer_config_soap_login_section', array());
        add_settings_field('jekuntmeer_config_soap_password', __('Wachtwoord', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_password_callback'), 'jekuntmeerconfigmain', 'jekuntmeer_config_soap_login_section', array());

        add_settings_section('jekuntmeer_config_soap_more_section', __('Instellingen', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_more_section_callback'), 'jekuntmeerconfig');
        add_settings_field('jekuntmeer_config_get_only_own', __('Alleen projecten van eigen organisatie', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_get_only_own_callback'), 'jekuntmeerconfig', 'jekuntmeer_config_soap_more_section', array());
        add_settings_field('jekuntmeer_config_soap_allow_user_search', __('Sta zoeken op pagina toe', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_allow_user_search_callback'), 'jekuntmeerconfig', 'jekuntmeer_config_soap_more_section', array());

        add_settings_section('jekuntmeer_config_soap_login_section', __('Jekuntmeer inlog gegevens', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_login_section_callback'), 'jekuntmeerconfigmain');

        add_settings_section('jekuntmeer_css_section', __('Stijl hier de pagina met eigen CSS', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_css_section_callback'), 'jekuntmeercss');
        add_settings_field('jekuntmeer_css_editor', __('CSS invoer:', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_css_editor_section_callback'), 'jekuntmeercss', 'jekuntmeer_css_section', array());

        register_setting('jekuntmeerconfigmain', 'jkm_soap_username', array('Jekuntmeer_Admin', 'sanitizeUser'));
        register_setting('jekuntmeerconfigmain', 'jkm_soap_password', array('Jekuntmeer_Admin', 'sanitizePassword'));
        register_setting('jekuntmeerconfigmain', 'jkm_soap_test', array('Jekuntmeer_Admin', 'sanitizeTest'));
        register_setting('jekuntmeerconfigmain', 'jkm_code_book', array('Jekuntmeer_Admin', 'sanitizeCodeBook'));
        register_setting('jekuntmeerconfigmain', 'jkm_job', array('Jekuntmeer_Admin', 'sanitizeJob'));
        register_setting('jekuntmeerconfigmain', 'jkm_update', array('Jekuntmeer_Admin', 'sanitizeJob'));
        register_setting('jekuntmeerconfigmain', 'jkm_sync', array('Jekuntmeer_Admin', 'sanitizeSync'));
        register_setting('jekuntmeerconfigmain', 'jkm_sync_stage', array('Jekuntmeer_Admin', 'sanitizeSync'));
        register_setting('jekuntmeerconfigmain', 'jkm_message', array('Jekuntmeer_Admin', 'sanitizeJob'));
        register_setting('jekuntmeerconfigmain', 'jkm_accept_tac', array('Jekuntmeer_Admin', 'sanitizeAccept'));
        register_setting('jekuntmeerconfig', 'jkm_soap_allow_user_search', array('Jekuntmeer_Admin', 'sanitizeOption'));
        register_setting('jekuntmeerconfig', 'jkm_get_only_own', array('Jekuntmeer_Admin', 'sanitizeOption'));
        register_setting('jekuntmeerconfig', 'jkm_soap_user_filter', array('Jekuntmeer_Admin', 'sanitizeUserFilter'));
        register_setting('jekuntmeerconfig', 'jkm_filter_flags', array('Jekuntmeer_Admin', 'sanitizeJob'));
        register_setting('jekuntmeerconfig', 'jkm_filter_properties', array('Jekuntmeer_Admin', 'sanitizeJob'));
        register_setting('jekuntmeerconfig', 'jkm_filter_properties_text', array('Jekuntmeer_Admin', 'sanitizeJob'));
        register_setting('jekuntmeerconfig', 'jkm_search_label', array('Jekuntmeer_Admin', 'sanitizeUser'));
        register_setting('jekuntmeerconfig', 'jkm_search_button', array('Jekuntmeer_Admin', 'sanitizeUser'));

        add_settings_section('jekuntmeer_config_soap_filter_section', __('Filter instellingen:', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_filter_section_callback'), 'jekuntmeerconfigfilter');
        add_settings_field('jekuntmeer_config_soap_filter_options', __('Filter instellingen:', 'jekuntmeer'), array('Jekuntmeer_Admin', 'jekuntmeer_config_soap_filter_callback'), 'jekuntmeerconfigfilter', 'jekuntmeer_config_soap_filter_section', array());
        register_setting('jekuntmeerconfigfilter', 'jkm_soap_filter', array('Jekuntmeer_Admin', 'sanitizeFilter'));
        register_setting('jekuntmeerconfigfilter', 'jkm_soap_filter_check', array('Jekuntmeer_Admin', 'sanitizeTest'));

        register_setting('jekuntmeercss', 'jkm_custom_css', array('Jekuntmeer_Admin', 'sanitizeCss'));

        if (false == get_option('jkm_soap_username')) {
            add_option('jkm_soap_username', '');
        }

        if (false == get_option('jkm_soap_password')) {
            add_option('jkm_soap_password', '');
        }

        if (false == get_option('jkm_soap_test')) {
            add_option('jkm_soap_test', 0);
        }

        if (false == get_option('jkm_soap_filter')) {
            add_option('jkm_soap_filter', array());
        }

        if (false == get_option('jkm_soap_filter_check')) {
            add_option('jkm_soap_filter_check', 0);
        }

        if (false == get_option('jkm_soap_allow_user_search')) {
            add_option('jkm_soap_allow_user_search', 0);
        }

        if (false == get_option('jkm_soap_user_filter')) {
            add_option('jkm_soap_user_filter', array());
        }

        if (false == get_option('jkm_filter_flags')) {
            add_option('jkm_filter_flags', array());
        }

        if (false == get_option('jkm_filter_properties')) {
            add_option('jkm_filter_properties', array());
        }

        if (false == get_option('jkm_filter_properties_text')) {
            add_option('jkm_filter_properties_text', array());
        }

        if (false == get_option('jkm_job')) {
            add_option('jkm_job', array());
        }

        if (false == get_option('jkm_update')) {
            add_option('jkm_update', array());
        }

        if (false == get_option('jkm_sync')) {
            add_option('jkm_sync', 0);
        }

        if (false == get_option('jkm_sync_stage')) {
            add_option('jkm_sync_stage', 0);
        }

        if (false == get_option('jkm_message')) {
            add_option('jkm_message', array());
        }

        if (false == get_option('jkm_get_only_own')) {
            add_option('jkm_get_only_own', 1);
        }

        if (false == get_option('jkm_code_book')) {
            add_option('jkm_code_book', new stdClass());
        }

        if (false == get_option('jkm_accept_tac')) {
            add_option('jkm_accept_tac', 0);
        }

        if (false == get_option('jkm_search_label')) {
            add_option('jkm_search_label', __('Zoek:', 'jekuntmeer'));
        }

        if (false == get_option('jkm_search_button')) {
            add_option('jkm_search_button', __('ZOEK', 'jekuntmeer'));
        }

        if (false == get_option('jkm_custom_css')) {
            add_option('jkm_custom_css', '/* ' . __('Hier kun je CSS toevoegen om het uiterlijk van de plugin te wijzigen.', 'jekuntmeer')  . ' */' . PHP_EOL);
        }
    }

    /**
     * Calls load_menu
     * @see Jekuntmeer_Admin::load_menu()
     */
    public static function admin_menu() {
        self::load_menu();
    }

    /**
     * Add Jkm Admin Pages to Menu
     * @see Jekuntmeer_Admin::display_main_page()
     * @see Jekuntmeer_Admin::display_page()
     * @see Jekuntmeer_Admin::display_filter_page()
     * @see Jekuntmeer_Admin::display_css_page()
     */
    public static function load_menu() {
        $mainhook = add_menu_page(__('Jekuntmeer', 'jekuntmeer'), __('Jekuntmeer', 'jekuntmeer'), 'manage_options', 'jekuntmeerconfigmain', array('Jekuntmeer_Admin', 'display_main_page'));
        $hook = add_submenu_page('jekuntmeerconfigmain', __('Config', 'jekuntmeer'), __('Config', 'jekuntmeer'), 'manage_options', 'jekuntmeerconfig', array('Jekuntmeer_Admin', 'display_page'));
        $filterhook = add_submenu_page('jekuntmeerconfigmain', __('Filter', 'jekuntmeer'), __('Filter', 'jekuntmeer'), 'manage_options', 'jekuntmeerfilter', array('Jekuntmeer_Admin', 'display_filter_page'));
        $customCSSHook = add_submenu_page('jekuntmeerconfigmain', __('Custom CSS', 'jekuntmeer'), __('Custom CSS', 'jekuntmeer'), 'manage_options', 'jekuntmeercss', array('Jekuntmeer_Admin', 'display_css_page'));
    }

    /**
     * Displays Info Page
     * @see Jekuntmeer_Admin::doatest()
     * @see Jekuntmeer::view()
     */
    public static function display_main_page() {
        if (class_exists('SoapClient')) {
            if (get_option('jkm_accept_tac')) {
                $arr = array();
                if (get_option('jkm_soap_test')) {
                    $res = self::doatest();
                    $arr['testres'] = $res;
                }
                if (isset($_POST['askforauth'])) {
                    $arr['askforauth'] = 1;
                    $arr['email'] = sanitize_email($_POST['email']);
                    $arr['message'] = sanitize_text_field($_POST['message']);
                }
                Jekuntmeer::view('info', $arr);
            } else {
                echo '<div class="wrap"><h1>' . __('Ga s.v.p. akkoord met de voorwaarden.', 'jekuntmeer') . '</h1></div>';
            }
        } else {
            echo '<div class="wrap"><h1>' . __('Soap Staat niet aan!', 'jekuntmeer') . '</h1></div>';
        }
    }

    /**
     * Displays Configuration Page
     * @see Jekuntmeer::isConnected()
     * @see Jekuntmeer_Admin::display_configuration_page()
     */
    public static function display_page() {
        if (class_exists('SoapClient')) {
            if (get_option('jkm_accept_tac')) {
                if (Jekuntmeer::isConnected()) {
                    self::display_configuration_page();
                } else {
                    echo '<div class="wrap"><h1>' . __('Verbinding met Jekuntmeer.nl niet aanwezig/gelukt.', 'jekuntmeer') . '</h1></div>';
                }
            } else {
                echo '<div class="wrap"><h1>' . __('Ga s.v.p. akkoord met de voorwaarden.', 'jekuntmeer') . '</h1></div>';
            }
        } else {
            echo '<div class="wrap"><h1>' . __('Soap Staat niet aan!', 'jekuntmeer') . '</h1></div>';
        }
    }

    /**
     * Displays Filters Page
     * @see Jekuntmeer::isConnected()
     * @see Jekuntmeer::runJob()
     * @see Jekuntmeer::view()
     */
    public static function display_filter_page() {
        if (class_exists('SoapClient')) {
            if (get_option('jkm_accept_tac')) {
                if (Jekuntmeer::isConnected()) {
                    $arr = array();

                    if (get_option('jkm_soap_filter_check')) {
                        update_option('jkm_soap_filter_check', 0);
                        update_option('jkm_sync', 0);
                        update_option('jkm_sync_stage', 0);
                        $res = Jekuntmeer::runJob(false, false, null, null, null);

                        $arr['testres'] = $res;
                    }

                    Jekuntmeer::view('filter', $arr);
                } else {
                    echo '<div class="wrap"><h1>' . __('Verbinding met Jekuntmeer.nl niet aanwezig/gelukt.', 'jekuntmeer') . '</h1></div>';
                }
            } else {
                echo '<div class="wrap"><h1>' . __('Ga s.v.p. akkoord met de voorwaarden.', 'jekuntmeer') . '</h1></div>';
            }
        } else {
            echo '<div class="wrap"><h1>' . __('Soap Staat niet aan!', 'jekuntmeer') . '</h1></div>';
        }
    }

    /**
     * Displays Config Page
     * @see Jekuntmeer::view()
     */
    public static function display_configuration_page() {
        if (class_exists('SoapClient')) {
            $arr = array();
            Jekuntmeer::view('config', $arr);
        } else {
            echo '<div class="wrap"><h1>' . __('Soap Staat niet aan!', 'jekuntmeer') . '</h1></div>';
        }
    }

    /**
     * Displays Css Page
     * @see Jekuntmeer::view()
     */
    public static function display_css_page() {
        if (class_exists('SoapClient')) {
            if (get_option('jkm_accept_tac')) {
                $arr = array();
                Jekuntmeer::view('css', $arr);
            } else {
                echo '<div class="wrap"><h1>' . __('Ga s.v.p. akkoord met de voorwaarden.', 'jekuntmeer') . '</h1></div>';
            }
        } else {
            echo '<div class="wrap"><h1>' . __('Soap Staat niet aan!', 'jekuntmeer') . '</h1></div>';
        }
    }

    /**
     * Sanitizes Input
     * @param string $input Unsanitized text
     * @return string Sanitized Text
     */
    public static function sanitizeUser($input) {
        $soap_username = $input;
        return sanitize_text_field($soap_username);
    }

    /**
     * Sanitizes Input
     * @param string $input Unsanitized text
     * @return string Sanitized Text
     */
    public static function sanitizePassword($input) {
        $soap_password = $input;
        return sanitize_text_field($soap_password);
    }

    /**
     * Sanitizes integer Input
     * @param int $input Unsanitized integer
     * @return int Sanitized integer in bool form
     */
    public static function sanitizeTest($input) {
        if ($input !== 0) {
            $input = 1;
        }
        return $input;
    }

    /**
     * Sanitizes integer Input
     * @param int $input Unsanitized integer
     * @return int Sanitized integer in bool form
     */
    public static function sanitizeOption($input) {
        if (intval($input)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Sanitizes Array
     * @param array $input Unsanitized Array
     * @return array Sanitized Array
     */
    public static function sanitizeJob($input) {
        if (is_array($input)) {
            return $input;
        } else {
            return array();
        }
    }

    /**
     * Sanitizes integer Input
     * @param int $input Unsanitized integer
     * @return int Sanitized integer
     */
    public static function sanitizeSync($input) {
        return intval($input);
    }

    /**
     * Sanitizes Css Input
     * @param string $input Unsanitized Css
     * @return mixed Sanitized Css
     */
    public static function sanitizeCss($input) {
        return esc_textarea($input);
    }

    /**
     * Sanitizes bool Input as integer
     * @param int $input Unsanitized integer
     * @return int Sanitized integer in bool form
     */
    public static function sanitizeAccept($input) {
        if ($input === null) {
            return 1;
        } elseif ($input) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * SOAP Login HTML
     * @see Jekuntmeer_Admin::admin_init()
     */
    public static function jekuntmeer_config_soap_login_section_callback() {
        echo '<p>' . __('Stel uw gebruikernsaam en wachtwoord in:', 'jekuntmeer') . '</p>';
    }

    /**
     * SOAP More Settings HTML
     * @see Jekuntmeer_Admin::admin_init()
     */
    public static function jekuntmeer_config_soap_more_section_callback() {
    }

    /**
     * SOAP Username HTML
     * @see Jekuntmeer_Admin::admin_init()
     * @param mixed $args Arguments
     */
    public static function jekuntmeer_config_soap_username_callback($args) {
        $username = get_option('jkm_soap_username');
        $html = '<input type="text" id="jkm_soap_username" name="jkm_soap_username" value="' . $username . '"/>' . PHP_EOL;
        echo $html;
    }

    /**
     * SOAP Password HTML
     * @see Jekuntmeer_Admin::admin_init()
     * @param mixed $args Arguments
     */
    public static function jekuntmeer_config_soap_password_callback($args) {
        $password = get_option('jkm_soap_password');
        $html = '<input type="password" id="jkm_soap_password" name="jkm_soap_password" value="' . $password . '"' . (empty($password) ? ' readonly onfocus="this.removeAttribute(\'readonly\');"' : '') . '/>' . PHP_EOL;
        $html .= '<button id="showpassword" class="btn btn-warning" type="button" onclick="
        var docsoap = document.getElementById(\'jkm_soap_password\');
        if (docsoap.type  == \'password\') {
            docsoap.type = \'text\';
            this.innerHTML = \'' . __('Verberg wachtwoord', 'jekuntmeer') . '\';
        } else {
            docsoap.type = \'password\';
            this.innerHTML = \'' . __('Toon wachtwoord', 'jekuntmeer') . '\';
        }
        ">
        ' . __('Toon wachtwoord', 'jekuntmeer') . '</button>' . PHP_EOL;
        echo $html;
    }

    /**
     * User Search HTML
     * @see Jekuntmeer_Admin::admin_init()
     * @see Jekuntmeer::getCodeBook()
     * @see Jekuntmeer_Admin::checkDisabled()
     * @param mixed $args Arguments
     */
    public static function jekuntmeer_config_soap_allow_user_search_callback($args) {
        $checked = get_option('jkm_soap_allow_user_search');
        $filter = get_option('jkm_soap_user_filter');
        $filterflags = get_option('jkm_filter_flags');
        $searchlabel = get_option('jkm_search_label');
        $searchbutton = get_option('jkm_search_button');
        $filterproperties = get_option('jkm_filter_properties');
        $filterpropertiestext = get_option('jkm_filter_properties_text');

        $html = '<input type="checkbox" id="jkm_soap_allow_user_search" name="jkm_soap_allow_user_search" value="1" ' . ($checked ? 'checked="" ' : '') . 'onclick="
var main = document.getElementById(\'jkm_soap_allow_user_search\');
var inputtoggle = document.getElementsByClassName(\'tohideinput\');
var labeltoggle = document.getElementsByClassName(\'tohidelabel\');

for (var i = 0; i < inputtoggle.length; i++) {
    if(main.checked) {
        inputtoggle[i].readOnly = false;
    } else {
        inputtoggle[i].readOnly = true;
    }
}

for (var i = 0; i < labeltoggle.length; i++) {
    if(main.checked) {
        var style = labeltoggle[i].attributes.getNamedItem(\'style\');
        style.value = \'\';
    } else {
        var style = labeltoggle[i].attributes.getNamedItem(\'style\');
        style.value = \'pointer-events: none; cursor: default;\';
    }
}
" ' . PHP_EOL;
        $html .= '/>' . PHP_EOL;
        $html .= '<br/><br/><p><strong>'. __('Op welke vragen?', 'jekuntmeer') .'</strong></p>' . PHP_EOL;

        $html .= '<div>' . PHP_EOL;

        $codebook = Jekuntmeer::getCodeBook();
        if (!empty($codebook)) {
            foreach ($codebook->properties as $property) {
                $html .= '    <label class="tohidelabel" style="'. (self::checkDisabled($property->ID) ? 'color: #008000; ' : '') . ($checked ? '' : 'pointer-events: none; cursor: default;') . '" >' . PHP_EOL;
                $html .= '    <input class="tohideinput" type="checkbox" value="1" name="jkm_soap_user_filter[' . $property->ID . ']"' . (isset($filter[$property->ID]) ? ' checked=""' : '') . ' ' . ($checked ? '' : 'readOnly=""') . ' />' . PHP_EOL;
                $html .= esc_html($property->beschrijving) . PHP_EOL;
                $html .= '    </label>' . PHP_EOL;
                $html .= '<br/>' . PHP_EOL;
            }
        }

        $html .= '<br/></div>' . PHP_EOL;

        $html .= '<h2>' . __('Meer instellingen', 'jekuntmeer') . ':</h2>' . PHP_EOL;

        $flags = array(0 => __('Toon organisatie naam', 'jekuntmeer'), 1 => __('Toon wijk/regio', 'jekuntmeer'), 2 => __('Toon locaties', 'jekuntmeer'), 3 => __('Toon foto', 'jekuntmeer'), 4 => __('Toon eigenschappen', 'jekuntmeer'));
        $flag4js = 'onclick="
        var propelmslb = document.getElementsByClassName(\'tohidepropertieslabel\');
        var propelmsip = document.getElementsByClassName(\'tohidepropertiesinput\');

        for (var i = 0; i < propelmslb.length; i++) {
            propelmsip[i].readOnly = !this.checked;
        }

        for (var i = 0; i < propelmsip.length; i++) {
            propelmslb[i].readOnly = !this.checked;
            if(this.checked) {
                var style = propelmslb[i].attributes.getNamedItem(\'style\');
                style.value = \'\';
            } else {
                var style = propelmslb[i].attributes.getNamedItem(\'style\');
                style.value = \'pointer-events: none; cursor: default;\';
            }
        }
        " ';
        foreach ($flags as $flag => $text) {
            $html .= '    <label class="tohidelabel" style="' . ($checked  ? '' : 'pointer-events: none; cursor: default;') . '">' . PHP_EOL;
            $html .= '    <input class="tohideinput" type="checkbox" value="' . $flag . '" name="jkm_filter_flags[' . $flag . ']"' . (isset($filterflags[$flag]) ? ' checked=""' : '') . ' ' . ($checked ? '' : 'readOnly=""') . ' '. ($flag == 4 ? $flag4js : '') .'/>' . PHP_EOL;
            $html .= $text . PHP_EOL;
            $html .= '    </label>' . PHP_EOL;
            $html .= '<br/>' . PHP_EOL;
        }

        $html .= '<div>' . PHP_EOL;
        $html .= '    <h2 style="margin-bottom:0.2em">' . __('Eigenschappen om te tonen', 'jekuntmeer') . ':</h2>' . PHP_EOL;
        $html .= '    <p>' . __('Alleen aan te passen als vinkje bij "Toon eigenschappen" hierboven aan staat', 'jekuntmeer') . '</p>' . PHP_EOL;

        if (!empty($codebook)) {
            foreach ($codebook->properties as $property) {
                $html .= '    <label class="tohidelabel tohidepropertieslabel" style="'. (self::checkDisabled($property->ID) ? 'color: #008000; ' : '') . ($checked && isset($filterflags[4]) ? '' : 'pointer-events: none; cursor: default;') . '" >' . PHP_EOL;
                $html .= '    <input class="tohideinput tohidepropertiesinput" type="checkbox" value="1" name="jkm_filter_properties[' . $property->ID . ']"' . (isset($filterproperties[$property->ID]) ? ' checked=""' : '') . ' ' . ($checked && isset($filterflags[4]) ? '' : 'readOnly=""') . ' />' . PHP_EOL;
                $html .= html_entity_decode($property->beschrijving) . '<br />'. PHP_EOL;
                $html .= '    <input class="tohideinput tohidepropertiestext altertekst " style="margin-left:2em;margin-bottom:1em;width:75%;"  type="text" value="'. (isset($filterpropertiestext[$property->ID]) ? $filterpropertiestext[$property->ID] : '') .'" placeholder="'. __('Alternatieve tekst/vraag om te tonen') .'" name="jkm_filter_properties_text[' . $property->ID . ']" ' . ($checked && isset($filterflags[4]) ? '' : 'readOnly=""') . ' />' . PHP_EOL;
                $html .= '    </label>' . PHP_EOL;
                $html .= '<br/>' . PHP_EOL;
            }
        }

        $html .= '</div>' . PHP_EOL;


        $html .= '<br/>' . PHP_EOL;
        $html .= '    <label class="tohidelabel" style="' . ($checked ? '' : 'pointer-events: none; cursor: default;') . '">' . __('Tekst boven zoekformulier:', 'jekuntmeer') . '</label>' . PHP_EOL;
        $html .= '<br/>' . PHP_EOL;
        $html .= '    <input class="tohideinput" type="text" value="' . $searchlabel . '" name="jkm_search_label"' . ($checked ? '' : 'readOnly=""') . ' />' . PHP_EOL;
        $html .= '<br/>' . PHP_EOL;

        $html .= '    <label class="tohidelabel" style="' . ($checked ? '' : 'pointer-events: none; cursor: default;') . '">' . __('Tekst voor zoek knop:', 'jekuntmeer') . '</label>' . PHP_EOL;
        $html .= '<br/>' . PHP_EOL;
        $html .= '    <input class="tohideinput" type="text" value="' . $searchbutton . '" name="jkm_search_button"' . ($checked ? '' : 'readOnly=""') . ' />' . PHP_EOL;
        $html .= '<br/>' . PHP_EOL;

        $html .= '</div>' . PHP_EOL;


        echo $html;
    }

    /**
     * SOAP Only Own HTML
     * @see Jekuntmeer_Admin::admin_init()
     * @param mixed $args Arguments
     */
    public static function jekuntmeer_config_get_only_own_callback($args) {
        $checked = get_option('jkm_get_only_own');

        $html = '<input type="checkbox" name="jkm_get_only_own" value="1" ' . ($checked ? 'checked="" ' : '') . '/>';
        echo $html;
    }

    /**
     * Runs a test to see if the connection works
     * @see Jekuntmeer::isConnected()
     * @see Jekuntmeer::getConnection()
     * @see Jekuntmeer::getSoapUrl()
     * @return string Test Result
     */
    public static function doatest() {
        update_option('jkm_soap_test', 0);

        $username = get_option('jkm_soap_username');

        if (empty($username)) {
            return __('SOAPTEST: gebruikernsaam is leeg', 'jekuntmeer');
        }

        $password = get_option('jkm_soap_password');

        if (empty($password)) {
            return __('SOAPTEST: Wachtwoord is leeg', 'jekuntmeer');
        }

        try {
            if (!class_exists('SoapClient')) {
                throw new Exception(__('Soap staat niet aan!', 'jekuntmeer'));
            } else {
                $client = new SoapClient(Jekuntmeer::getSoapUrl(), array('login' => $username, 'password' => $password, 'cache_wsdl' => WSDL_CACHE_NONE));
                $test = $client->Codeboek();
            }
        } catch (SoapFault $e) {
            return __('SOAPTEST: Er gaat iets mis', 'jekuntmeer') . ' ' . $e->getMessage();
        } catch (Exception $e) {
            return __('SOAPTEST: Er gaat iets mis', 'jekuntmeer') . ' ' . $e->getMessage();
        }

        return __('SOAPTEST: Verbinding met jekuntmeer.nl gelukt.', 'jekuntmeer');
    }

    /**
     * Filter Section HTML
     * @see Jekuntmeer_Admin::admin_init()
     */
    public static function jekuntmeer_config_soap_filter_section_callback() {

    }

    /**
     * Css Section HTML
     * @see Jekuntmeer_Admin::admin_init()
     */
    public static function jekuntmeer_css_section_callback() {

    }

    /**
     * Css Editor HTML
     * @see Jekuntmeer_Admin::admin_init()
     */
    public static function jekuntmeer_css_editor_section_callback() {
        echo '<textarea id="jkm_custom_css" name="jkm_custom_css" cols="150" rows="40" name="cssedit" id="cssedit" aria-describedby="cssedit-description">' . Jekuntmeer::echo_safe(get_option('jkm_custom_css')) .'</textarea>';
    }

    /**
     * SOAP Filters HTML
     * @see Jekuntmeer_Admin::admin_init()
     * @see Jekuntmeer::getCodeBook()
     * @param $args
     */
    public static function jekuntmeer_config_soap_filter_callback($args) {
        $filter = get_option('jkm_soap_filter');
        $html = '';
        echo $html;

        $codebook = Jekuntmeer::getCodeBook();
        if (!empty($codebook)) {
            foreach ($codebook->properties as $property) {
                echo '    <span><h3><strong>' . (empty($property->beschrijving) ? esc_html($property->korte_beschrijving) : esc_html($property->beschrijving)) . '</strong></h3>' . PHP_EOL;

                if (!empty($property->property)) {
                    foreach ($property->property as $singleproperty) {
                        echo '    <label>' . PHP_EOL;
                        echo '    <input type="checkbox" value="1" name="jkm_soap_filter[' . intval($property->ID) . '][' . intval($singleproperty->ID) . ']"' . (isset($filter[$property->ID][$singleproperty->ID]) ? ' checked=""' : '') . '/>' . PHP_EOL;
                        echo $singleproperty->waarde . PHP_EOL;
                        echo '    </label>' . PHP_EOL;

                        echo '<br/>';
                    }
                }

                echo '    </span>' . PHP_EOL;

                echo '<br/>';
            }
        }
    }

    /**
     * Sanitizes Filter Input
     * @param array $input Unsanitized Filter
     * @see Jekuntmeer_Admin::checkDepleted()
     * @return array Sanitized Filter
     */
    public static function sanitizeFilter($input) {
        $input = self::checkDepleted($input);
        return $input;
    }

    /**
     * Sanitizes User Filter Input
     * @deprecated 1.0.0 Using sanitizeJob instead.
     * @param array $input Unsanitized Filter
     * @see Jekuntmeer_Admin::checkDepleted()
     * @see Jekuntmeer_Admin::checkDisabled()
     * @see Jekuntmeer_Admin::sanitizeJob()
     * @return array Sanitized User Filter
     */
    public static function sanitizeUserFilter($input) {
        return self::sanitizeJob($input);
    }

    /**
     * Sanitizes Codebook Input
     * @param stdClass $input Codebook
     * @see Jekuntmeer::saveCodeBook()
     * @return stdClass New Codebook
     */
    public static function sanitizeCodeBook($input) {
        if (!isset($input->properties)) {
            $input = Jekuntmeer::saveCodeBook();
        }

        return $input;
    }

    /**
     * Check if Properties are disabled
     * @param array $input Array of Checked Properties
     * @param bool $level2 Level 1 array or Level 2
     * @see Jekuntmeer::getCodeBook()
     * @return array Sanitized Array of Properties
     */
    public static function checkDepleted($input = null, $level2 = true) {
        $codebook = Jekuntmeer::getCodeBook();
        $checkedCodebook = $input;
        if (empty($checkedCodebook) || empty($codebook)) {
            return array();
        }

        $retarr = array();

        if (!empty($codebook->properties)) {
            foreach ($codebook->properties as $property) {
                if ($level2) {
                    if (!empty($property->property)) {
                        foreach ($property->property as $singleproperty) {
                            if (isset($checkedCodebook[$property->ID][$singleproperty->ID])) {
                                $retarr[intval($property->ID)][intval($singleproperty->ID)] = 1;
                            }
                        }
                    }
                } else {
                    if (isset($checkedCodebook[$property->ID])) {
                        $retarr[intval($property->ID)] = 1;
                    }
                }
            }
        }

        return $retarr;
    }

    /**
     * Check if Property is Disabled
     * @param int|array $ID Property ID or Array of Properties
     * @see Jekuntmeer::getCodeBook()
     * @see Jekuntmeer::checkPropertyExists()
     * @return array|bool True if Disabled or New Array of Enabled Properties
     */
    public static function checkDisabled($ID) {
        $codebook = Jekuntmeer::getCodeBook();

        if (is_numeric($ID)) {
            if (!empty($codebook->properties)) {
                foreach ($codebook->properties as $property) {
                    if ($property->ID == $ID) {
                        if (!empty($property->property)) {
                            foreach ($property->property as $subprop) {
                                if (Jekuntmeer::checkPropertyExists($ID, $subprop->ID)) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        } elseif (is_array($ID)) {
            $ret = array();

            foreach ($ID as $main => $val) {
                if (self::checkDisabled($main)) {
                    $ret[$main] = 1;
                }
            }

            return $ret;
        }

        return false;
    }
}
?>
