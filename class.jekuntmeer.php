<?php
/**
 * Class Jekuntmeer
 *
 * Main Functions needed to run the Plugin
 * @see Jekuntmeer_Admin
 */
class Jekuntmeer {
    /**
     * @var bool $initiated Check that hooks get set only one time
     */
    private static $initiated = false;

    /**
     * @see Jekuntmeer::displayProjectsFilter()
     * @var array $stop_words Stopwords for keywords in displayFilter
     */
    private static $stop_words = array('mijn', 'mijne', 'jouw', 'je', 'uw', 'jouwe', 'uwe', 'zijn', 'haar', 'zijne', 'hare', 'ons', 'onze', 'jullie', 'hun', 'hunne', 'de', 'het', 'een',
        'dit', 'dat', 'deze', 'die', 'ik', 'jij', 'u', 'je', 'mij', 'me', 'jou', 'hij', 'zij', 'die', 'ze', 'wij', 'we', 'ons', 'jullie', 'u', 'zij', 'hen', 'hun');

    /**
     * Setup Hooks and Jkm Database Tables
     *
     * init gets run at Wp Action init
     * @see Jekuntmeer::init_hooks()
     * @see Jekuntmeer::acceptTac()
     * @see Jekuntmeer::checkDatabaseDate();
     */
    public static function init() {
        if (!self::$initiated) {
            self::init_hooks();
            if (!get_option('jkm_accept_tac')) {
                self::acceptTac();
            }

            if (!class_exists('SoapClient')) {
                $messages = get_option('jkm_message');
                $message = '<strong>' . __('Deze plugin heeft Soap Nodig!<br/>Activeer Soap op je server.', 'jekuntmeer') . '</strong>';
                $messages['nosoap'] = $message;
                update_option('jkm_message', $messages);
            }

            self::checkDatabaseDate();
        }
    }

    /**
     * Setups all needed hooks and filters and job
     * @see Jekuntmeer::language_init()
     * @see Jekuntmeer::init_scripts()
     * @see Jekuntmeer::jekuntmeer_messages()
     */
    private static function init_hooks() {
        self::$initiated = true;

        add_action('plugins_loaded', array('Jekuntmeer', 'language_init'));
        add_action('wp_enqueue_scripts', array('Jekuntmeer', 'init_scripts'));
        add_action('admin_notices', array('Jekuntmeer', 'jekuntmeer_messages'));

        if (!defined('DISABLE_WP_CRON') | (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == false)) {
            if (!wp_next_scheduled('jekuntmeer_job')) {
                wp_schedule_event(strtotime('+1 day', current_time('timestamp')), 'daily', 'jekuntmeer_job');
            }
        }
        
        add_action('jekuntmeer_job', array('Jekuntmeer', 'jekuntmeer_job'));
        
        add_filter('wp_mail_from', array('Jekuntmeer', 'EmailFilter'));
        add_filter('wp_mail_from_name', array('Jekuntmeer', 'EmailNameFilter'));
        
        global $wp;
        $wp->add_query_var('runjob');
        $wp->add_query_var('maxtime');
        $wp->add_query_var('offset');
    }

    /**
     * Setups all JavaScript and Css Needed for the widget
     */
    public static function init_scripts() {
        wp_register_script('jekuntmeer_multiselect', JEKUNTMEER__PLUGIN_URL . 'js/jquery.multiselect.js', array('jquery'), '2.1.3', false);
        wp_register_style('jekuntmeer_multiselect', JEKUNTMEER__PLUGIN_URL . 'css/jquery.multiselect.css', array(), '2.1.3', false);
        wp_register_style('jekuntmeer_widget', JEKUNTMEER__PLUGIN_URL . 'css/widget.jekuntmeer.css', array(), '0.0.1', false);

        wp_enqueue_script('jekuntmeer_multiselect', JEKUNTMEER__PLUGIN_URL . 'js/jquery.multiselect.js', array('jquery'), '2.1.3', false);
        wp_enqueue_style('jekuntmeer_multiselect', JEKUNTMEER__PLUGIN_URL . 'css/jquery.multiselect.css', array(), '2.1.3', false);
        wp_enqueue_style('jekuntmeer_widget', JEKUNTMEER__PLUGIN_URL . 'css/widget.jekuntmeer.css', array(), '0.0.1', false);

        wp_add_inline_style('jekuntmeer_widget', self::echo_safe(get_option('jkm_custom_css')));
    }

    /**
     * Setups translations
     */
    public static function language_init() {
        load_plugin_textdomain('jekuntmeer', false, JEKUNTMEER__PLUGIN_DIR . '/languages');
    }

    /**
     * Echos the messages in option jkm_message
     */
    public static function jekuntmeer_messages() {
        $messages = get_option('jkm_message');
        if (!empty($messages) && count($messages)) {
            $html = '';
            foreach ($messages as $id => $message) {
                $html .= '<div class="updated notice ' . ($id == 'acceptTac' ? '' : 'is-dismissible') . '">' . PHP_EOL;
                $html .= '<p>' . PHP_EOL;
                $html .= $message . PHP_EOL;
                $html .= '</p>' . PHP_EOL;
                $html .= '</div>' . PHP_EOL;
            }
            echo $html;
            update_option('jkm_message', array());
        }
    }

    /**
     * Setups after plugin activation:
     *
     * Checks Jkm Database Tables Date
     *
     * Updates Jkm Database Tables if needed
     * @see Jekuntmeer::checkDatabaseDate()
     * @see Jekuntmeer::setUpDatabase()
     * @see Jekuntmeer::runJob()
     * @return bool|array Returns false if update not needed else array of the job results
     */
    public static function plugin_activation() {
        if (self::isConnected()) {
            self::checkDatabaseDate();

            $lastsync = get_option('jkm_sync');
            $lastsyncdate = strtotime('+1 day', $lastsync);

            if ($lastsyncdate < current_time('timestamp')) {
                $ret['starttime'] = current_time('timestamp');

                $ret['setup'] = self::setUpDatabase();
                $ret['job'] = self::runJob(false, false);

                $ret['endtime'] = current_time('timestamp');
                $ret['took'] = $ret['endtime'] - $ret['starttime'];

                if ($ret['job']['renewproject'] === false) {
                    $messages = get_option('jkm_message');
                    $messages[] = __('Helaas niets gevonden. Is de koppeling met Jekuntmeer.nl correct ingesteld?', 'jekuntmeer');
                    update_option('jkm_message', $messages);
                } else {
                    $messages = get_option('jkm_message');
                    $messages[] = __('Synchronisatie met Jekuntmeer.nl compleet over 24 uur', 'jekuntmeer');
                    update_option('jkm_message', $messages);
                }

                update_option('jkm_update', $ret);

                return $ret;
            } else {
                $messages = get_option('jkm_message');
                $messages[] = __('Synchronisatie is niet nodig', 'jekuntmeer');
                update_option('jkm_message', $messages);
            }
        } else {
            self::setUpDatabase();
        }

        return false;
    }

    /**
     * Cleanup before plugin deactivation:
     *
     * Emptys Jkm Database Tables
     *
     * Removes Cron Job
     * @see Jekuntmeer::emptyDatabase()
     * @return array
     */
    public static function plugin_deactivation() {
        if (wp_next_scheduled('jekuntmeer_job')) {
            wp_clear_scheduled_hook('jekuntmeer_job');
        }

        return self::emptyDatabase();
    }

    /**
     * Cleanup before plugin uninstallation:
     *
     * Delete Jkm Database Tables
     * @see Jekuntmeer::deleteDatabase()
     * @return array|null|object Database query results
     */
    public static function plugin_uninstallation() {
        return self::deleteDatabase();
    }

    /**
     * Creates Jkm Database Tables if not exists
     * @return array Database query results
     */
    public static function setUpDatabase() {
        global $wpdb, $jkm_table_prefix;

        $tablename = $jkm_table_prefix . 'projects';

        $query = "
        CREATE TABLE IF NOT EXISTS {$tablename} (
          `ID` bigint(20) NOT NULL AUTO_INCREMENT,
          `naam` text NOT NULL,
          `beschrijving` text NULL,
          `deleted` int(20) NOT NULL,
          `jkm_url` text NOT NULL,
          `foto_url` text NULL,
          `straat` text NULL,
          `huisnummer` text NULL,
          `postcode` text NULL,
          `plaats` text NULL,
          `telefoon` text NULL,
          `website` text NULL,
          `contactpersoon` text NULL,
          `locatie_IDS` text NULL,
          `organisatie_ID` bigint(20) NOT NULL,
          `wijzigingsdatum` date DEFAULT '0000-00-00',
          PRIMARY KEY (`ID`)
        ) AUTO_INCREMENT=1 ;";
        $res[] = $wpdb->get_results($query, OBJECT);

        $wpdb->get_col("SELECT * FROM {$tablename} WHERE 1=1 LIMIT 0,0");
        $tables = $wpdb->get_col_info();

        if (!in_array('wijzigingsdatum', $tables)) {
            $query = "ALTER TABLE {$tablename} ADD `wijzigingsdatum` date DEFAULT '0000-00-00'";
            $res[] = $wpdb->get_results($query, OBJECT);
        }

        if (in_array('huisnummer_toevoeging', $tables)) {
            $query = "ALTER TABLE {$tablename} DROP `huisnummer_toevoeging`";
            $res[] = $wpdb->get_results($query, OBJECT);
        }

        $tablename = $jkm_table_prefix . 'organisaties';
        $query = "
        CREATE TABLE IF NOT EXISTS {$tablename} (
          `ID` bigint(20) NOT NULL AUTO_INCREMENT,
          `naam` text NOT NULL,
          `straat` text NULL,
          `huisnummer` text NULL,
          `huisnummer_toevoeging` text NULL,
          `postcode` text NULL,
          `plaats` text NULL,
          `telefoon` text NULL,
          `website` text NULL,
          `contactpersoon` text NULL,
          `beschrijving` text NULL,
          `jkm_url` text NOT NULL,
          `locatie_IDS` text NULL,
          PRIMARY KEY (`ID`)
        ) AUTO_INCREMENT=1 ;";
        $res[] = $wpdb->get_results($query, OBJECT);

        $tablename = $jkm_table_prefix . 'locaties';
        $query = "
        CREATE TABLE IF NOT EXISTS {$tablename} (
          `ID` bigint(20) NOT NULL AUTO_INCREMENT,
          `organisatie_ID` bigint(20) NOT NULL,
          `naam` text NOT NULL,
          `straat` text NULL,
          `huisnummer` text NULL,
          `postcode` text NULL,
          `plaats` text NULL,
          `telefoon` text NULL,
          `website` text NULL,
          `contactpersoon` text NULL,
          `lat` float(10,6) DEFAULT NULL,
          `long` float(10,6) DEFAULT NULL,
          PRIMARY KEY (`ID`)
        ) AUTO_INCREMENT=1 ;";
        $res[] = $wpdb->get_results($query, OBJECT);

        $wpdb->get_col("SELECT * FROM {$tablename} WHERE 1=1 LIMIT 0,0");
        $tables = $wpdb->get_col_info();

        if (!in_array('lat', $tables) && !in_array('long', $tables)) {
            $query = "ALTER TABLE {$tablename} ADD `lat` float(10,6) DEFAULT NULL, ADD `long` float(10,6) DEFAULT NULL;";
            $res[] = $wpdb->get_results($query, OBJECT);
        }

        if (in_array('huisnummer_toevoeging', $tables)) {
            $query = "ALTER TABLE {$tablename} DROP `huisnummer_toevoeging`";
            $res[] = $wpdb->get_results($query, OBJECT);
        }

        $tablename = $jkm_table_prefix . 'properties';
        $query = "
        CREATE TABLE IF NOT EXISTS {$tablename} (
          `project_ID` bigint(20) NOT NULL,
          `parent_ID` bigint(20) NULL,
          `ID` bigint(20) NOT NULL,
          `value` longtext NULL DEFAULT NULL
        ) ;";
        $res[] = $wpdb->get_results($query, OBJECT);

        $wpdb->get_col("SELECT * FROM {$tablename} WHERE 1=1 LIMIT 0,0");
        $tables = $wpdb->get_col_info();

        if (!in_array('value', $tables)) {
            $query = "ALTER TABLE {$tablename} ADD `value` LONGTEXT NULL DEFAULT NULL;";
            $res[] = $wpdb->get_results($query, OBJECT);
        }

        return $res;
    }

    /**
     * Delete Jkm Database Tables
     * @return array|null|object Database query results
     */
    public static function deleteDatabase() {
        global $wpdb, $jkm_table_prefix;

        $tablename = $jkm_table_prefix . 'projects';
        $tablenameo = $jkm_table_prefix . 'organisaties';
        $tablenamel = $jkm_table_prefix . 'locaties';
        $tablenamep = $jkm_table_prefix . 'properties';

        $query = "DROP TABLE {$tablename}, {$tablenameo}, {$tablenamel}, {$tablenamep};";

        $res = $wpdb->get_results($query, OBJECT);

        return $res;
    }

    /**
     * Emptys Jkm Database Tables
     *
     * Reset jkm_sync and jkm_sync_stage options
     * @return array Database query results
     */
    public static function emptyDatabase() {
        global $wpdb, $jkm_table_prefix;

        $tablename = $jkm_table_prefix . 'projects';
        $query = "TRUNCATE TABLE {$tablename};";
        $res[] = $wpdb->get_results($query, OBJECT);

        $tablename = $jkm_table_prefix . 'organisaties';
        $query = "TRUNCATE TABLE {$tablename};";
        $res[] = $wpdb->get_results($query, OBJECT);

        $tablename = $jkm_table_prefix . 'locaties';
        $query = "TRUNCATE TABLE {$tablename};";
        $res[] = $wpdb->get_results($query, OBJECT);

        $tablename = $jkm_table_prefix . 'properties';
        $query = "TRUNCATE TABLE {$tablename};";
        $res[] = $wpdb->get_results($query, OBJECT);

        update_option('jkm_sync', 0);
        update_option('jkm_sync_stage', 0);

        return $res;
    }

    /**
     * Includes php from view folder and set arguments as variables
     * @param string $name Name of the View
     * @param array $args Arguments to set
     */
    public static function view($name, $args = array()) {
        foreach ($args as $key => $val) {
            $$key = $val;
        }

        $file = JEKUNTMEER__PLUGIN_DIR . 'views/' . $name . '.php';
        if (file_exists($file) && validate_file($file) === 0) {
            include $file;
        }
    }

    /**
     * Checks that SOAP is working and connected
     * @see Jekuntmeer::getConnection()
     * @return bool If there is a connection
     */
    public static function isConnected() {
        $client = self::getConnection();
        if ($client === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Gets a SOAP Connection with Jekuntmeer
     *
     * Using options jkm_soap_username and jkm_soap_password as login and password
     * @see Jekuntmeer::getSoapUrl()
     * @return bool|SoapClient Returns false if failed else Connected SoapClient
     */
    public static function getConnection() {
        $username = get_option('jkm_soap_username');
        $password = get_option('jkm_soap_password');

        try {
            if (!class_exists('SoapClient')) {
                throw new Exception(__('Soap staat niet aan!', 'jekuntmeer'));
            } else {
                $client = new SoapClient(self::getSoapUrl(), array('login' => $username, 'password' => $password, 'cache_wsdl' => WSDL_CACHE_NONE));
                $client->Codeboek();
            }
        } catch (SoapFault $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return $client;
    }

    /**
     * Gets Current Codeboek
     * @see Jekuntmeer::getConnection()
     * @return stdClass CodeBook or empty stdClass
     */
    public static function saveCodeBook() {
        try {
            $client = self::getConnection();
            if ($client === false) {
                return new stdClass;
            } else {
                $ret = $client->Codeboek();
            }
        } catch (SoapFault $e) {
            return new stdClass;
        } catch (Exception $e) {
            return new stdClass;
        }

        return $ret;
    }

    /**
     * Gets Codeboek saved in option jkm_code_book
     * @return bool|stdClass Codebook or false if empty
     */
    public static function getCodeBook() {
        $codebook = get_option('jkm_code_book');
        if (isset($codebook->properties)) {
            return $codebook;
        } else {
            return false;
        }
    }

    /**
     * Returns description of the errorcode passed
     * @see Jekuntmeer::getCodeBook()
     * @param string $errorcode Errorcode to look up
     * @return string Description of errorcode passed
     */
    public static function getSOAPError($errorcode) {
        $codebook = self::getCodeBook();

        foreach ($codebook->return_codes as $error) {
            if ($errorcode == $error->ID) {
                return esc_html($error->description);
            }
        }

        return __('Onbekende status code', 'jekuntmeer');
    }

    /**
     * Returns the url used in SOAP
     * @return string WSDL Url
     */
    public static function getSoapUrl() {
        return JEKUNTMEER__WSDL_URL;
    }

    /**
     * SOAP Call to get Projects from Jekuntmeer.nl
     * @param int $orgID Organization ID if option jkm_get_only_own is set is becomes getOwnID
     * @param string $keyword Keyword for filter
     * @param int $total Total to get, 0 means all
     * @param int $offset Offset
     * @param int $detailed Show more informaition
     * @param int $deleted Show Deleted Projects
     * @param null|SoapClient $client
     * @see Jekuntmeer::getConnection()
     * @see Jekuntmeer::getOwnID()
     * @see Jekuntmeer::getProperties()
     * @return stdClass|bool Projects if failed to do so returns false
     */
    public static function getProject($orgID = 0, $keyword = '', $total = 0, $offset = 0, $detailed = 0, $deleted = 0, $client = null) {
        if (!is_object($client)) {
            $client = self::getConnection();
        }

        if (is_object($client)) {
            $own = get_option('jkm_get_only_own');
            if ($own && empty($orgID)) {
                $orgID = self::getOwnID();
            }

            $res = $client->projecten2(self::getProperties(), $orgID, $keyword, $total, $offset, $detailed, $deleted);

            return $res;
        }

        return false;
    }

    /**
     * Get SOAP properties using option jkm_soap_filter
     * @see Jekuntmeer::getCodeBook()
     * @see SoapProperties
     * @see SoapProperty
     * @return array List of selected properties
     */
    public static function getProperties() {
        $filter = get_option('jkm_soap_filter');
        $codebook = self::getCodeBook();
        $ret = array();

        if (!empty($codebook) && !empty($filter)) {
            if (!empty($codebook->properties)) {
                foreach ($codebook->properties as $property) {
                    if (isset($filter[$property->ID])) {
                        if (!empty($property->property)) {
                            foreach ($property->property as $singleproperty) {
                                if (isset($filter[$property->ID][$singleproperty->ID])) {
                                    if (!isset($first)) {
                                        $first = true;
                                        $addobj = new SoapProperties();
                                        $addobj->ID = $property->ID;
                                        $addobj->beschrijving = $property->beschrijving;
                                        $addobj->korte_beschrijving = $property->korte_beschrijving;
                                    }

                                    $addprop = new SoapProperty($singleproperty->ID, $singleproperty->waarde);
                                    $addobj->property[] = $addprop;
                                }
                            }

                            if (isset($first)) {
                                $ret[] = $addobj;
                                unset($first);
                            }
                        }
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Echos Project HTML
     *
     * Using option jkm_filter_flags
     * @param object $object Project
     * @see Jekuntmeer::getOrganisatieByID()
     * @see Jekuntmeer::getLocationsByID()
     * @see Jekuntmeer::getLocationsIDS()
     * @return bool False if $object is not a object
     */
    public static function displayProject($object) {
        if (!is_object($object) || empty($object)) {
            return false;
        }

        $org = self::getOrganisatieByID($object->organisatie_ID);
        $filterflags = get_option('jkm_filter_flags');
        $html = '';
        $html .= '<div class="jkm_widget_results">' . PHP_EOL;
        $html .= '    <h2 class="jkm_widget_results_name">' . esc_html($object->naam) . '</h2>' . PHP_EOL;
        if (isset($filterflags[3])) {
            if (!empty($object->foto_url)) {
                $html .= '    <img class="jkm_widget_results_img" src="' . esc_url($object->foto_url) . '" alt="' . esc_attr($object->naam) . '-' . __(' afbeelding', 'jekuntmeer') . '" onerror=\''. esc_js('this.style.display = "none"') .'\' />' . PHP_EOL;
            }
        }

        if (isset($filterflags[0])) {
            $html .= '    <p class="jkm_widget_results_organization">' . PHP_EOL;
            $html .= '        <strong>' . __('Organisatie', 'jekuntmeer') . ': </strong><a href="' . (isset($org->ERROR) ? '' : esc_url($org->jkm_url)) . '" rel="nofollow" target="_blank" title="' . __('Lees verder op Jekuntmeer.nl', 'jekuntmeer') . '">' . (isset($org->ERROR) ? esc_html($org->ERRORNAME) : esc_html($org->naam)) . '</a><br>' . PHP_EOL;
            $html .= '    </p>' . PHP_EOL;
        }

        if (isset($filterflags[1])) {
            $locations = self::getLocationsByID($object->ID);
            if (!empty($locations)) {
                $html .= '    <p class="jkm_widget_results_locations">' . PHP_EOL;
                $html .= __('Wijk:', 'jekuntmeer') . ' ' . esc_html(implode(__(' en ', 'jekuntmeer'), $locations));
                $html .= '    </p>' . PHP_EOL;
            }
        }

        if (isset($filterflags[2])) {
            $locations = self::getLocationsIDS($object->locatie_IDS);
            if (!empty($locations)) {
                $html .= '    <p class="jkm_widget_results_locations_IDS">' . PHP_EOL;
                if (sizeof($locations) > 1) {
                    $html .= __('Locaties:', 'jekuntmeer') . ' ' . esc_html(implode(__(' en ', 'jekuntmeer'), $locations));
                } else {
                    $html .= __('Locatie:', 'jekuntmeer') . ' ' . esc_html(implode(__(' en ', 'jekuntmeer'), $locations));
                }
                $html .= '    </p>' . PHP_EOL;
            }
        }

        if (isset($filterflags[4])) {
            $properties = self::getPropertiesByFilter($object->ID);
            if (!empty($properties)) {
                $html .= '    <div class="jkm_widget_results_properties">' . PHP_EOL;
                foreach ($properties as $perrent => $propertyarr) {
                    $perrentname = self::getPropertyNameByID($perrent);
                    if (!empty($perrentname)) {
                        $html .= '    <p class="jkm_properties_header jkm_ph_'.intval($perrent).'"><strong>'.esc_html($perrentname).'</strong></p>' . PHP_EOL;
                    }
                    $html .= '    <ul class="jkm_properties_list jkm_ls_'.intval($perrent).'">' . PHP_EOL;
                    foreach ($propertyarr as $property => $value) {
                        if (empty($value)) {
                            $value = self::getPropertyNameByID($property);
                        }
                        $html .= '        <li class="jkm_property jkm_p_'.intval($property).'">'.esc_html($value).'</li>' . PHP_EOL;
                    }
                    $html .= '    </ul>' . PHP_EOL;
                }
                $html .= '</div>' . PHP_EOL;
                $html .= '<br/>' . PHP_EOL;
            }
        }

        $html .= '    <div class="jkm_widget_results_description">' . PHP_EOL;
        $beschr = (empty($object->korte_beschrijving) ? esc_html($object->beschrijving) : esc_html($object->korte_beschrijving));
        $html .= '        ' . self::echo_safe($beschr) . PHP_EOL;
        $html .= '    </div>' . PHP_EOL;
        $html .= '    <div class="jkm_widget_results_jkm_url">' . PHP_EOL;
        $html .= '        <a href="' . esc_url($object->jkm_url) . '" rel="nofollow" target="_blank" title="' . __('Ga naar Jekuntmeer.nl', 'jekuntmeer') . '">' . __('Lees verder op Jekuntmeer.nl', 'jekuntmeer') . '</a>' . PHP_EOL;
        $html .= '    </div>' . PHP_EOL;
        $html .= '<br/>' . PHP_EOL;
        $html .= '<hr/>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;
        echo $html;
        return true;
    }

    /**
     * Get Locations from Project
     * @param object $object Project
     * @return array List of location IDs
     */
    public static function displayLocatieIDS($object) {
        if (isset($object->locatie_IDS)) {
            if (is_array($object->locatie_IDS)) {
                return $object->locatie_IDS;
            } elseif (is_null($object->locatie_IDS)) {
                return array();
            } else {
                return array(intval($object->locatie_IDS));
            }
        } else {
            return array();
        }
    }

    /**
     * Displays List of Projects
     * @param null|string $keyword Keyword to use
     * @param null|array $filter Filters to use
     * @param null|int $itemsPerPage Items to show
     * @param int $page Current Page Number
     * @see Jekuntmeer::displayAllProjects()
     * @see Jekuntmeer::displayProjectsFilter()
     * @return int Number of Projects Found
     */
    public static function display($keyword = null, $filter = null, $itemsPerPage = null, $page = 1) {
        if (empty($keyword) && empty($filter)) {
            return self::displayAllProjects($itemsPerPage, $page);
        } else {
            return self::displayProjectsFilter($keyword, $filter, $itemsPerPage, $page);
        }
    }

    /**
     * Echos Filter Options
     *
     * Using jkm_search_label and jkm_search_button for Button Text
     * @param string $ID Id of Widget
     * @param string|null|string $keyword Keyword to use
     * @param bool|int $page Current Page
     * @param bool $starttag If form needs to be closed
     * @param array $filter Filters to use
     * @see Jekuntmeer::filterSelectHTML()
     */
    public static function displayFilter($ID, $keyword = '', $page = false, $starttag = true, $filter = array()) {
        $searchlabel = get_option('jkm_search_label');
        $searchbutton = get_option('jkm_search_button');
        $html = '<form method="get" action="" class="jkm_widget">' . PHP_EOL;
        $html .= '    <div class="jkm_widget_filter_group">' . PHP_EOL;
        if (!empty($searchlabel)) {
            $html .= '        <label class="jkm_widget_control_label" for="jkm_search">' . esc_html($searchlabel) . '</label>' . PHP_EOL;
        }
        $html .= '        <div class="jkm_widget_block">' . PHP_EOL;
        $html .= '            <input name="jkm_id" class="hidden" type="hidden" value="' . sanitize_key($ID) . '"/>' . PHP_EOL;
        $html .= '            <input name="jkm_search" placeholder="" class="jkm_widget_search" type="search" value="' . esc_html($keyword) . '"/>' . PHP_EOL;
        $html .= '        <div class="jkm_widget_block">' . PHP_EOL;
        $html .= '          <div class="jkm_widget_search_group">' . PHP_EOL;
        $html .= self::filterSelectHTML($filter);
        $html .= '          </div>' . PHP_EOL;
        $html .= '          <div class="jkm_widget_results_submit">' . PHP_EOL;
        $html .= '              <input name="jkm_search_sub" class="jkm_widget_results_submit_button" type="submit" value="' . (empty($searchbutton) ? __('Zoek', 'jekuntmeer') : esc_html($searchbutton)) . '"/>' . PHP_EOL;
        $html .= '          </div>' . PHP_EOL;
        $html .= '       </div>' . PHP_EOL;
        $html .= '       </div>' . PHP_EOL;
        $html .= '    </div>' . PHP_EOL;
        $html .= ($starttag ? '</form>' . PHP_EOL : PHP_EOL);

        echo $html;
    }

    /**
     * Synchronize the database with Jekuntmeer.nl
     *
     * Using Options jkm_sync, jkm_sync_stage, jkm_code_book, jkm_get_only_own, jkm_job, jkm_update
     * @param bool $long Get everything or only 30
     * @param bool $check Check if projects are deleted
     * @param null|int $offset Offset to start at
     * @param null|int $maxtime Max execution time in seconds
     * @param null|string $startat Section to start at (U or D)
     * @see Jekuntmeer::emptyDatabase()
     * @see Jekuntmeer::saveCodeBook()
     * @see Jekuntmeer::renewProject()
     * @see Jekuntmeer::renewProjectChanges()
     * @return array Job results
     */
    public static function runJob($long = true, $check = true, $offset = null, $maxtime = null, $startat = null) {
        if (defined('DOING_CRON') && DOING_CRON == true) {
            @ini_set('max_execution_time', 120);
        } else {
            if ($maxtime) {
                @ini_set('max_execution_time', intval($maxtime)+5);
            }
        }

        $ret = array();

        $lastsync = get_option('jkm_sync');

        if (empty($offset) && empty($startat)) {
            self::emptyDatabase();
        }

        if ($lastsync == 0) {
            $jobrule = 1;
        } else {
            $jobrule = 2;
        }
        $long = boolval($long);
        $ret['long'] = $long;
        $ret['jobrule'] = $jobrule;
        $ret['codebook'] = update_option('jkm_code_book', self::saveCodeBook());
        $ret['onlyown'] = intval(get_option('jkm_get_only_own'));

        $ret['starttime'] = current_time('timestamp');

        if (!empty($maxtime)) {
            $maxtime = intval($maxtime);
            global $jkm_job_end_time;
            $jkm_job_end_time = $ret['starttime'] + $maxtime;
        }

        if ($jobrule == 1) {
            $updated = self::renewProject($long, $offset);
            if (is_array($updated)) {
                $ret['updated'] = intval($updated['count']);
                $ret['offset'] = 'u_' . intval($updated['offset']);
                $ret['total'] = intval($updated['total']);
                $ret['end'] = false;
            } else {
                $ret['updated'] = ($updated === false ? false : intval($updated));
                $ret['end'] = true;
            }
        } elseif ($jobrule == 2) {
            if ($startat != 'u') {
                $updated = self::renewProjectChanges($offset);
                if (is_array($updated)) {
                    $ret['updated'] = intval($updated['count']);
                    $ret['offset'] = 'r_' . intval($updated['offset']);
                    $ret['total'] = intval($updated['total']);
                    $ret['end'] = false;
                } else {
                    $ret['updated'] = ($updated === false ? false : intval($updated));
                    $ret['end'] = true;
                    $offset = 0;
                }
            } else {
                $ret['end'] = true;
            }

            if ($check && $ret['end'] == true) {
                unset($ret['end']);
                $deleted = self::checkProject($offset);
                if (is_array($deleted)) {
                    $ret['deleted'] = intval($deleted['count']);
                    $ret['offset'] = 'd_' . intval($deleted['offset']);
                    $ret['total'] = intval($deleted['total']);
                    $ret['end'] = false;
                } else {
                    $ret['deleted'] = ($deleted === false ? false : intval($deleted));
                    $ret['end'] = true;
                }
            }
        }

        $ret['endtime'] = current_time('timestamp');
        $ret['took'] = $ret['endtime'] - $ret['starttime'];

        if ($long) {
            update_option('jkm_job', $ret);
        } else {
            update_option('jkm_update', $ret);
        }

        if ($ret['updated'] !== false && $long == true && $ret['end'] == true) {
            update_option('jkm_sync', current_time('timestamp'));
            update_option('jkm_sync_stage', 0);
        }

        if (isset($ret['offset'])) {
            $ret['continueurl'] = esc_url($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/?runjob=jkm&offset=' .  sanitize_key($ret['offset']) . '&maxtime=' . ($maxtime + 1), array('http', 'https'));
        }

        return $ret;
    }

    /**
     * Renew Projects in Jkm Database Table
     * @param bool $long Get everything or 30
     * @param int $offset Offset to start at
     * @see Jekuntmeer::getProject()
     * @see Jekuntmeer::getConnection()
     * @see Jekuntmeer::displayLocatieIDS()
     * @see Jekuntmeer::renewOrganisatie()
     * @see Jekuntmeer::renewLocatie()
     * @see Jekuntmeer::renewProperties()
     * @return bool|array|int Count of projects updated or false if failed
     */
    public static function renewProject($long = false, $offset = 0) {
        $project = self::getProject();
        if (!is_object($project) || empty($project) || empty($project->projecten)) {
            return false;
        }

        global $wpdb, $jkm_table_prefix, $jkm_job_end_time;
        $tablename = $jkm_table_prefix . 'projects';

        $count = 0;

        if ($long) {
            $end = $project->aantal_beschikbaar;
            $con = self::getConnection();

            for ($localoffset = $offset; $localoffset < $end; $localoffset += 30) {
                $project = self::getProject(0, '', 30, $localoffset, 0, 0, $con);
                if (isset($jkm_job_end_time)) {
                    if (current_time('timestamp') >= $jkm_job_end_time) {
                        $offset += $count;
                        return array('offset' => $offset, 'count' => $count, 'total' => $end);
                        break;
                    }
                }
                if (!is_object($project) || empty($project) || empty($project->projecten)) {
                    $localoffset = $end;
                    break;
                }

                if (is_object($project->projecten)) {
                    $pjt = $project->projecten;
                    if (intval($pjt->deleted) != 1) {
                        $save = array();
                        $save['ID'] = intval($pjt->ID);
                        $save['naam'] = esc_html($pjt->naam);
                        $save['beschrijving'] = esc_html($pjt->beschrijving);
                        $save['deleted'] = intval($pjt->deleted);
                        $save['jkm_url'] = esc_url($pjt->jkm_url);
                        $save['foto_url'] = esc_url($pjt->foto_url);
                        $save['straat'] = esc_html($pjt->contact_adres->straat);
                        $save['huisnummer'] = esc_html($pjt->contact_adres->huisnummer);
                        $save['postcode'] = esc_html($pjt->contact_adres->postcode);
                        $save['plaats'] = esc_html($pjt->contact_adres->plaats);
                        $save['telefoon'] = esc_html($pjt->contact_adres->telefoon);
                        $save['website'] = esc_url($pjt->contact_adres->website);
                        $save['contactpersoon'] = esc_html($pjt->contact_adres->contactpersoon);
                        $save['locatie_IDS'] = serialize(self::displayLocatieIDS($pjt->locatie_IDS));
                        $save['organisatie_ID'] = intval($pjt->organisatie_ID);
                        $save['wijzigingsdatum'] = esc_html($pjt->wijzigingsdatum);

                        $res = $wpdb->replace($tablename, $save);
                        if ($res === false) {
                        } else {
                            self::renewOrganisatie(intval($pjt->organisatie_ID));
                            self::renewLocatie(self::displayLocatieIDS($pjt), intval($pjt->organisatie_ID));
                            self::renewProperties(intval($pjt->ID));
                            $count += $res;
                        }
                    }
                } else {
                    foreach ($project->projecten as $pjt) {
                        if (isset($jkm_job_end_time)) {
                            if (current_time('timestamp') >= $jkm_job_end_time) {
                                $offset += $count;
                                return array('offset' => $offset, 'count' => $count, 'total' => $end);
                            }
                        }
                        if (intval($pjt->deleted) != 1) {
                            $save = array();
                            $save['ID'] = intval($pjt->ID);
                            $save['naam'] = esc_html($pjt->naam);
                            $save['beschrijving'] = esc_html($pjt->beschrijving);
                            $save['deleted'] = intval($pjt->deleted);
                            $save['jkm_url'] = esc_url($pjt->jkm_url);
                            $save['foto_url'] = esc_url($pjt->foto_url);
                            $save['straat'] = esc_html($pjt->contact_adres->straat);
                            $save['huisnummer'] = esc_html($pjt->contact_adres->huisnummer);
                            $save['postcode'] = esc_html($pjt->contact_adres->postcode);
                            $save['plaats'] = esc_html($pjt->contact_adres->plaats);
                            $save['telefoon'] = esc_html($pjt->contact_adres->telefoon);
                            $save['website'] = esc_url($pjt->contact_adres->website);
                            $save['contactpersoon'] = esc_html($pjt->contact_adres->contactpersoon);
                            $save['locatie_IDS'] = serialize(self::displayLocatieIDS($pjt));
                            $save['organisatie_ID'] = intval($pjt->organisatie_ID);
                            $save['wijzigingsdatum'] = esc_html($pjt->wijzigingsdatum);

                            $res = $wpdb->replace($tablename, $save);
                            if ($res === false) {
                            } else {
                                self::renewOrganisatie(intval($pjt->organisatie_ID));
                                self::renewLocatie(self::displayLocatieIDS($pjt), intval($pjt->organisatie_ID));
                                self::renewProperties(intval($pjt->ID));
                                $count += $res;
                            }
                        }
                    }
                }
            }
        } else {
            $end = $project->aantal_beschikbaar;
            foreach ($project->projecten as $pjt) {
                if (isset($jkm_job_end_time)) {
                    if (current_time('timestamp') >= $jkm_job_end_time) {
                        $offset += $count;
                        return array('offset' => $offset, 'count' => $count, 'total' => $end);
                        break;
                    }
                }
                if (intval($pjt->deleted) != 1) {
                    $save = array();
                    $save['ID'] = intval($pjt->ID);
                    $save['naam'] = esc_html($pjt->naam);
                    $save['beschrijving'] = esc_html($pjt->beschrijving);
                    $save['deleted'] = intval($pjt->deleted);
                    $save['jkm_url'] = esc_url($pjt->jkm_url);
                    $save['foto_url'] = esc_url($pjt->foto_url);
                    $save['straat'] = esc_html($pjt->contact_adres->straat);
                    $save['huisnummer'] = esc_html($pjt->contact_adres->huisnummer);
                    $save['postcode'] = esc_html($pjt->contact_adres->postcode);
                    $save['plaats'] = esc_html($pjt->contact_adres->plaats);
                    $save['telefoon'] = esc_html($pjt->contact_adres->telefoon);
                    $save['website'] = esc_url($pjt->contact_adres->website);
                    $save['contactpersoon'] = esc_html($pjt->contact_adres->contactpersoon);
                    $save['locatie_IDS'] = serialize(self::displayLocatieIDS($pjt));
                    $save['organisatie_ID'] = intval($pjt->organisatie_ID);
                    $save['wijzigingsdatum'] = esc_html($pjt->wijzigingsdatum);

                    $res = $wpdb->replace($tablename, $save);
                    if ($res === false) {
                    } else {
                        self::renewOrganisatie(intval($pjt->organisatie_ID));
                        self::renewLocatie(self::displayLocatieIDS($pjt), intval($pjt->organisatie_ID));
                        self::renewProperties(intval($pjt->ID));
                        $count += $res;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Update Changed Projects to Jkm Database Table
     *
     * Using option jkm_sync
     * @param int $offset Offset to start at
     * @see Jekuntmeer::getConnection()
     * @see Jekuntmeer::getOwnID()
     * @see Jekuntmeer::getProperties()
     * @see Jekuntmeer::displayLocatieIDS()
     * @see Jekuntmeer::renewOrganisatie()
     * @see Jekuntmeer::renewLocatie()
     * @see Jekuntmeer::renewProperties()
     * @see Jekuntmeer::checkOrganisatie()
     * @see Jekuntmeer::checkLocatie()
     * @see Jekuntmeer::checkProperties()
     * @return bool|array|int Count of projects updated or false if failed
     */
    public static function renewProjectChanges($offset = 0) {
        $con = self::getConnection();
        if (empty($con)) {
            return false;
        }

        global $wpdb, $jkm_table_prefix, $jkm_job_end_time;
        $tablename = $jkm_table_prefix . 'projects';

        $lastsync = get_option('jkm_sync');

        try {
            $res = $con->projecten_mutaties2(Jekuntmeer::getProperties(), $lastsync, Jekuntmeer::getOwnID(), 30, $offset);
        } catch (Exception $e) {
            return false;
        }

        $count = 0;

        if (!empty($res) && !empty($res->projecten)) {
            if ($res->aantal_beschikbaar > 30) {
                $end = $res->aantal_beschikbaar;
                for ($localoffset = $offset; $localoffset < $end; $localoffset += 30) {
                    try {
                        $res = $con->projecten_mutaties2(Jekuntmeer::getProperties(), $lastsync, Jekuntmeer::getOwnID(), 30, $offset);
                    } catch (Exception $e) {
                        //FALLBACK
                    }

                    if (isset($jkm_job_end_time)) {
                        if (current_time('timestamp') >= $jkm_job_end_time) {
                            $offset += $count;
                            return array('offset' => $offset, 'count' => $count, 'total' => $end);
                            break;
                        }
                    }
                    if (!is_object($res) || empty($res) || empty($res->projecten)) {
                        $localoffset = $end;
                        break;
                    }

                    if (is_object($res->projecten)) {
                        $pjt = $res->projecten;
                        if (intval($pjt->deleted) != 1) {
                            $save = array();
                            $save['ID'] = intval($pjt->ID);
                            $save['naam'] = esc_html($pjt->naam);
                            $save['beschrijving'] = esc_html($pjt->beschrijving);
                            $save['deleted'] = intval($pjt->deleted);
                            $save['jkm_url'] = esc_url($pjt->jkm_url);
                            $save['foto_url'] = esc_url($pjt->foto_url);
                            $save['straat'] = esc_html($pjt->contact_adres->straat);
                            $save['huisnummer'] = esc_html($pjt->contact_adres->huisnummer);
                            $save['postcode'] = esc_html($pjt->contact_adres->postcode);
                            $save['plaats'] = esc_html($pjt->contact_adres->plaats);
                            $save['telefoon'] = esc_html($pjt->contact_adres->telefoon);
                            $save['website'] = esc_url($pjt->contact_adres->website);
                            $save['contactpersoon'] = esc_html($pjt->contact_adres->contactpersoon);
                            $save['locatie_IDS'] = serialize(self::displayLocatieIDS($pjt));
                            $save['organisatie_ID'] = intval($pjt->organisatie_ID);
                            $save['wijzigingsdatum'] = esc_html($pjt->wijzigingsdatum);

                            $res = $wpdb->replace($tablename, $save);
                            if ($res === false) {
                            } else {
                                self::renewOrganisatie(intval($pjt->organisatie_ID));
                                self::renewLocatie(self::displayLocatieIDS($pjt), intval($pjt->organisatie_ID));
                                self::renewProperties(intval($pjt->ID));
                                $count += $res;
                            }
                        } else {
                            $del = $wpdb->delete($tablename, array('ID' => intval($pjt->ID)));
                            $delorg = self::checkOrganisatie(intval($pjt->organisatie_ID));
                            self::checkLocatie($delorg);
                            self::checkProperties(intval($pjt->ID));
                            $count += $del;
                        }
                    } else {
                        if (count($res->projecten) && is_array($res->projecten)) {
                            foreach ($res->projecten as $current => $pjt) {
                                if (isset($jkm_job_end_time)) {
                                    if (current_time('timestamp') >= $jkm_job_end_time) {
                                        $offset .= $current;
                                        return array('offset' => $offset, 'count' => $count, 'total' => $end);
                                    }
                                }
                                if (intval($pjt->deleted) != 1) {
                                    $save = array();
                                    $save['ID'] = intval($pjt->ID);
                                    $save['naam'] = esc_html($pjt->naam);
                                    $save['beschrijving'] = esc_html($pjt->beschrijving);
                                    $save['deleted'] = intval($pjt->deleted);
                                    $save['jkm_url'] = esc_url($pjt->jkm_url);
                                    $save['foto_url'] = esc_url($pjt->foto_url);
                                    $save['straat'] = esc_html($pjt->contact_adres->straat);
                                    $save['huisnummer'] = esc_html($pjt->contact_adres->huisnummer);
                                    $save['postcode'] = esc_html($pjt->contact_adres->postcode);
                                    $save['plaats'] = esc_html($pjt->contact_adres->plaats);
                                    $save['telefoon'] = esc_html($pjt->contact_adres->telefoon);
                                    $save['website'] = esc_url($pjt->contact_adres->website);
                                    $save['contactpersoon'] = esc_html($pjt->contact_adres->contactpersoon);
                                    $save['locatie_IDS'] = serialize(self::displayLocatieIDS($pjt));
                                    $save['organisatie_ID'] = intval($pjt->organisatie_ID);
                                    $save['wijzigingsdatum'] = esc_html($pjt->wijzigingsdatum);

                                    $res = $wpdb->replace($tablename, $save);
                                    if ($res === false) {
                                    } else {
                                        self::renewOrganisatie(intval($pjt->organisatie_ID));
                                        self::renewLocatie(self::displayLocatieIDS($pjt), intval($pjt->organisatie_ID));
                                        self::renewProperties(intval($pjt->ID));
                                        $count += $res;
                                    }
                                } else {
                                    $del = $wpdb->delete($tablename, array('ID' => intval($pjt->ID)));
                                    $delorg = self::checkOrganisatie(intval($pjt->organisatie_ID));
                                    self::checkLocatie($delorg);
                                    self::checkProperties(intval($pjt->ID));
                                    $count += $del;
                                }
                            }
                        }
                    }
                }
            } else {
                if (is_object($res->projecten)) {
                    $pjt = $res->projecten;
                    if (intval($pjt->deleted) != 1) {
                        $save = array();
                        $save['ID'] = intval($pjt->ID);
                        $save['naam'] = esc_html($pjt->naam);
                        $save['beschrijving'] = esc_html($pjt->beschrijving);
                        $save['deleted'] = intval($pjt->deleted);
                        $save['jkm_url'] = esc_url($pjt->jkm_url);
                        $save['foto_url'] = esc_url($pjt->foto_url);
                        $save['straat'] = esc_html($pjt->contact_adres->straat);
                        $save['huisnummer'] = esc_html($pjt->contact_adres->huisnummer);
                        $save['postcode'] = esc_html($pjt->contact_adres->postcode);
                        $save['plaats'] = esc_html($pjt->contact_adres->plaats);
                        $save['telefoon'] = esc_html($pjt->contact_adres->telefoon);
                        $save['website'] = esc_url($pjt->contact_adres->website);
                        $save['contactpersoon'] = esc_html($pjt->contact_adres->contactpersoon);
                        $save['locatie_IDS'] = serialize(self::displayLocatieIDS($pjt));
                        $save['organisatie_ID'] = intval($pjt->organisatie_ID);
                        $save['wijzigingsdatum'] = esc_html($pjt->wijzigingsdatum);

                        $res = $wpdb->replace($tablename, $save);
                        if ($res === false) {
                        } else {
                            self::renewOrganisatie(intval($pjt->organisatie_ID));
                            self::renewLocatie(self::displayLocatieIDS($pjt), intval($pjt->organisatie_ID));
                            self::renewProperties(intval($pjt->ID));
                            $count += $res;
                        }
                    } else {
                        $del = $wpdb->delete($tablename, array('ID' => intval($pjt->ID)));
                        $delorg = self::checkOrganisatie(intval($pjt->organisatie_ID));
                        self::checkLocatie($delorg);
                        self::checkProperties(intval($pjt->ID));
                        $count += $del;
                    }
                } else {
                    if (count($res->projecten) && is_array($res->projecten)) {
                        foreach ($res->projecten as $current => $pjt) {
                            if (isset($jkm_job_end_time)) {
                                if (current_time('timestamp') >= $jkm_job_end_time) {
                                    $offset .= $current;
                                    return array('offset' => $offset, 'count' => $current, 'total' => $res->aantal_beschikbaar);
                                    break;
                                }
                            }
                            if (intval($pjt->deleted) != 1) {
                                $save = array();
                                $save['ID'] = intval($pjt->ID);
                                $save['naam'] = esc_html($pjt->naam);
                                $save['beschrijving'] = esc_html($pjt->beschrijving);
                                $save['deleted'] = intval($pjt->deleted);
                                $save['jkm_url'] = esc_url($pjt->jkm_url);
                                $save['foto_url'] = esc_url($pjt->foto_url);
                                $save['straat'] = esc_html($pjt->contact_adres->straat);
                                $save['huisnummer'] = esc_html($pjt->contact_adres->huisnummer);
                                $save['postcode'] = esc_html($pjt->contact_adres->postcode);
                                $save['plaats'] = esc_html($pjt->contact_adres->plaats);
                                $save['telefoon'] = esc_html($pjt->contact_adres->telefoon);
                                $save['website'] = esc_url($pjt->contact_adres->website);
                                $save['contactpersoon'] = esc_html($pjt->contact_adres->contactpersoon);
                                $save['locatie_IDS'] = serialize(self::displayLocatieIDS($pjt));
                                $save['organisatie_ID'] = intval($pjt->organisatie_ID);
                                $save['wijzigingsdatum'] = esc_html($pjt->wijzigingsdatum);

                                $res = $wpdb->replace($tablename, $save);
                                if ($res === false) {
                                } else {
                                    self::renewOrganisatie(intval($pjt->organisatie_ID));
                                    self::renewLocatie(self::displayLocatieIDS($pjt), intval($pjt->organisatie_ID));
                                    self::renewProperties(intval($pjt->ID));
                                    $count += $res;
                                }
                            } else {
                                $del = $wpdb->delete($tablename, array('ID' => intval($pjt->ID)));
                                $delorg = self::checkOrganisatie(intval($pjt->organisatie_ID));
                                self::checkLocatie($delorg);
                                self::checkProperties(intval($pjt->ID));
                                $count += $del;
                            }
                        }
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Check if Project is deleted
     * @param int $offset Offset to start at
     * @see Jekuntmeer::checkProjectDeleted()
     * @see Jekuntmeer::checkOrganisatie()
     * @see Jekuntmeer::checkLocatie()
     * @see Jekuntmeer::checkProperties()
     * @return int|array Count of projects deleted
     */
    public static function checkProject($offset = 0) {
        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'projects';

        $count = 0;
        $offset = (empty($offset) ? 0 : intval($offset));

        $query = "
        SELECT SQL_CALC_FOUND_ROWS `ID` FROM {$tablename} WHERE 1 LIMIT %d, 30
        ";
        $res = $wpdb->get_results($wpdb->prepare($query, $offset), OBJECT);

        $query = '
        SELECT FOUND_ROWS();
        ';
        $totalres = $wpdb->get_results($query, OBJECT);
        $total = intval($totalres[0]->{'FOUND_ROWS()'});

        for ($localoffset = $offset; $localoffset <= $total; $localoffset += 30) {
            $query = "
            SELECT `ID` FROM {$tablename} WHERE 1 LIMIT %d, 30
            ";
            $res = $wpdb->get_results($wpdb->prepare($query, $localoffset), OBJECT);
            if (!empty($res) && count($res)) {
                foreach ($res as $pro) {
                    if (isset($jkm_job_end_time)) {
                        if (current_time('timestamp') >= $jkm_job_end_time) {
                            $offset += $count;
                            return array('offset' => $offset, 'count' => $count, 'total' => $total);
                            break;
                        }
                    }

                    $pid = intval($pro->ID);
                    if (self::checkProjectDeleted($pid)) {
                        $del = $wpdb->delete($tablename, array('ID' => $pid));
                        $delorg = self::checkOrganisatie(intval($pro->organisatie_ID));
                        self::checkLocatie($delorg);
                        self::checkProperties($pid);
                        $count += $del;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Checks if Project is deleted
     * @param int $pid ID of the Project
     * @see Jekuntmeer::getConnection()
     * @return bool Returns true if Deleted
     */
    public static function checkProjectDeleted($pid) {
        $con = self::getConnection();
        if (empty($con)) {
            return false;
        }

        $res = $con->projectdetail(intval($pid));

        if (intval($res->deleted) == 1 || is_null($res->deleted)) {
            return true;
        }

        return false;
    }

    /**
     * Echo All Projects in Jkm Database Table
     * @param int|null $itemsPerPage Number of Items to show
     * @param int $page Current Page
     * @see Jekuntmeer::displayProject()
     * @return int Number of Projects found
     */
    public static function displayAllProjects($itemsPerPage = null, $page = 1) {
        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'projects';

        $itemsPerPage = intval($itemsPerPage);
        $page = intval($page);

        if (!empty($itemsPerPage)) {
            $offset = ($page - 1) * $itemsPerPage;
            if ($offset < 0) {
                $offset = 0;
            }
        } else {
            $offset = 0;
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$tablename} WHERE 1 LIMIT %d,%d";
        $res = $wpdb->get_results($wpdb->prepare($query, $offset, $itemsPerPage), OBJECT);

        if (!empty($itemsPerPage)) {
            $query = 'SELECT FOUND_ROWS();';
            $totalres = $wpdb->get_results($query, OBJECT);
            $total = intval($totalres[0]->{'FOUND_ROWS()'});
        } else {
            $total = count($res);
        }

        if (!empty($res) && count($res)) {
            foreach ($res as $pro) {
                self::displayProject($pro);
            }
        }

        return $total;
    }

    /**
     * Echo Projects using keyword and filter in Jkm Database Table
     * @param null|string $keyword Keyword to use
     * @param null|string $filter Filter to use
     * @param null|int $itemsPerPage Items to show
     * @param int $page Current Page
     * @see Jekuntmeer::sanitize_single_array()
     * @see Jekuntmeer::getKeywords()
     * @see Jekuntmeer::displayProject()
     * @return int Number of Projects found
     */
    public static function displayProjectsFilter($keyword = null, $filter = null, $itemsPerPage = null, $page = 1) {
        global $wpdb, $jkm_table_prefix;

        $tablenamep = $jkm_table_prefix . 'projects';
        $tablename = $jkm_table_prefix . 'properties';

        $keyword = sanitize_text_field($keyword);
        $filter = self::sanitize_single_array($filter);

        if (!empty($filter)) {
            $query = "SELECT {$tablenamep}.ID FROM `{$tablenamep}`";
            $where = '';
            $first = true;
            foreach ($filter as $main => $pros) {
                if (!empty($pros)) {
                    $query .= $wpdb->prepare(" LEFT JOIN `{$tablename}` AS `prop_%d` ON (prop_%d.project_ID = {$tablenamep}.ID AND prop_%d.parent_ID = %d)", array_fill(0, 4, intval($main)));
                    $where .= ($first ? '' : ' AND ');
                    $where .= $wpdb->prepare("prop_%d.ID IN (".implode(', ', array_fill(0, count($pros), '%d')).")", array_merge(array(intval($main)), $pros));
                    $first = false;
                }
            }
            $query .= " WHERE {$where} GROUP BY {$tablenamep}.ID";

            $res = $wpdb->get_results($query, OBJECT);

            $IDList = array();
            if (!empty($res) && count($res)) {
                foreach ($res as $prop) {
                    $IDList[] = intval($prop->ID);
                }
            }
        }

        if (!empty($itemsPerPage)) {
            $offset = ($page - 1) * $itemsPerPage;
            if ($offset < 0) {
                $offset = 0;
            }
        } else {
            $offset = 0;
        }

        $addKeywords = '';

        if (!empty($keyword)) {
            $addKeywords = '';
            $keywords = self::getKeywords($keyword, self::$stop_words);
            if (count($keywords)) {
                if (count($keywords) == 1) {
                    if (!empty($IDList)) {
                        $addKeywords .= $wpdb->prepare("(`naam` LIKE %s OR `beschrijving` LIKE %s)", "%" . esc_html(end($keywords)) . "%", "%" . esc_html(end($keywords)) . "%");
                    } else {
                        $addKeywords .= $wpdb->prepare("`naam` LIKE %s OR `beschrijving` LIKE %s", "%" . esc_html(end($keywords)) . "%", "%" . esc_html(end($keywords)) . "%");
                    }
                } else {
                    $first = true;
                    foreach ($keywords as $value) {
                        if (!$first) {
                            $addKeywords .= " AND ";
                        }
                        $addKeywords .= $wpdb->prepare("(`naam` LIKE %s OR `beschrijving` LIKE %s)", "%" . esc_html($value) . "%", "%" . esc_html($value) . "%");
                        $first = false;
                    }
                }
            }
        }

        $where = '1=1';
        if ($filter) {
            $where .= ' AND ';
            $where .= $wpdb->prepare("`ID` IN (".implode(', ', array_fill(0, count($IDList), '%d')).") ", $IDList);
        }

        if ($addKeywords) {
            $where .= ' AND ';
            $where .= $addKeywords;
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM `{$tablenamep}` WHERE {$where} " . $wpdb->prepare("LIMIT %d,%d", array(intval($offset), intval($itemsPerPage)));

        $res = $wpdb->get_results($query, OBJECT);

        if (!empty($itemsPerPage)) {
            $query = '
            SELECT FOUND_ROWS();
            ';

            $totalres = $wpdb->get_results($query, OBJECT);
            $total = intval($totalres[0]->{'FOUND_ROWS()'});
        } else {
            $total = count($res);
        }

        if (!empty($res) && count($res)) {
            foreach ($res as $pro) {
                self::displayProject($pro);
            }
        } else {
            echo '<h2 class="jkm_widget_no_result">' . __('Helaas, niets gevonden.', 'jekuntmeer') . '</h2>' . PHP_EOL;
        }

        return $total;
    }

    /**
     * Get Organisation
     * @param array $properties Properties to use
     * @param string $keyword Keyword to use
     * @param int $total Total amount to get
     * @param int $offset Offset to start at
     * @see Jekuntmeer::getConnection()
     * @return stdClass SOAP result
     */
    public static function getOrganisatie($properties = array(), $keyword = '', $total = 0, $offset = 0) {
        $client = self::getConnection();
        if (is_object($client)) {
            $res = $client->organisaties($properties, $keyword, $total, $offset);

            return $res;
        }
    }

    /**
     * Get Organisation by ID
     * @param int $organisatie_ID Organisation ID
     * @return stdClass SOAP result
     */
    public static function getOrganisatieByID($organisatie_ID) {
        $organisatie_ID = intval($organisatie_ID);

        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'organisaties';

        $query = $wpdb->prepare("
        SELECT * FROM `{$tablename}` WHERE `ID` = %d
        ", $organisatie_ID);

        $res = $wpdb->get_results($query, OBJECT);

        if (!empty($res) && count($res)) {
            $pro = $res[0];

            return $pro;
        } else {
            $ret = new stdClass();
            $ret->ERROR = true;
            $ret->ERRORNAME = __('Niet gevonden.', 'jekuntmeer');

            return $ret;
        }
    }

    /**
     * Updates Organisation in Jkm Database Table
     * @param int $orgID Organisation ID
     * @see Jekuntmeer::getOrganisatie()
     * @return bool|int Database result
     */
    public static function renewOrganisatie($orgID) {
        if (!self::isConnected()) {
            return false;
        }

        global $wpdb, $jkm_table_prefix;

        $tablename = $jkm_table_prefix . 'organisaties';

        $orgres = self::getOrganisatie(array(), $orgID, 0, 0);

        if (is_object($orgres->organisaties)) {
            $org = $orgres->organisaties;
        } else {
            return false;
        }

        $save = array();
        $save['ID'] = intval($org->ID);
        $save['naam'] = esc_html($org->naam);
        $save['straat'] = esc_html($org->adres->straat);
        $save['huisnummer'] = esc_html($org->adres->huisnummer);
        $save['postcode'] = esc_html($org->adres->postcode);
        $save['plaats'] = esc_html($org->adres->plaats);
        $save['telefoon'] = esc_html($org->adres->telefoon);
        $save['website'] = esc_html($org->adres->website);
        $save['contactpersoon'] = esc_html($org->adres->contactpersoon);
        $save['beschrijving'] = esc_html($org->beschrijving);
        $save['jkm_url'] = esc_url($org->jkm_url);
        $save['locatie_IDS'] = serialize(self::displayLocatieIDS($org));

        $res = $wpdb->replace($tablename, $save);

        return $res;
    }

    /**
     * Deletes Organisation from Jkm Database Table
     * @param int $orgID Organisation ID
     * @return int Database result
     */
    public static function checkOrganisatie($orgID) {
        global $wpdb, $jkm_table_prefix;

        $tablename = $jkm_table_prefix . 'projects';

        $query = $wpdb->prepare("
        SELECT DISTINCT `organisatie_ID` FROM `{$tablename}` WHERE `organisatie_ID` = %d;
        ", intval($orgID));
        $res = $wpdb->get_results($query, OBJECT);

        if (!empty($res) && count($res)) {
            return 0;
        } else {
            $tablename = $jkm_table_prefix . 'organisaties';
            $wpdb->delete($tablename, array('ID' => intval($orgID)));
            return $orgID;
        }
    }

    /**
     * Updates Locations
     * @param array $locationIDS List of Location IDS
     * @param int $organisatie_ID Organisation ID
     * @see Jekuntmeer::isConnected()
     * @see Jekuntmeer::getConnection()
     * @return bool|int Number of Locations updated
     */
    public static function renewLocatie($locationIDS, $organisatie_ID) {
        if (!self::isConnected()) {
            return false;
        }

        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'locaties';

        $con = self::getConnection();
        $res = false;
        foreach ($locationIDS as $lid) {
            $lidres = $con->locaties(intval($lid), intval($organisatie_ID), 0, 0);
            if (is_object($lidres->locaties)) {
                $loc = $lidres->locaties;
                $save = array();
                $save['ID'] = intval($loc->ID);
                $save['organisatie_ID'] = intval($loc->organisatie_ID);
                $save['naam'] = esc_html($loc->naam);
                $save['straat'] = esc_html($loc->adres->straat);
                $save['huisnummer'] = esc_html($loc->adres->huisnummer);
                $save['postcode'] = esc_html($loc->adres->postcode);
                $save['plaats'] = esc_html($loc->adres->plaats);
                $save['telefoon'] = esc_html($loc->adres->telefoon);
                $save['website'] = esc_url($loc->adres->website);
                $save['contactpersoon'] = esc_html($loc->adres->contactpersoon);
                $save['lat'] = floatval(esc_html($loc->lat));
                $save['long'] = floatval(esc_html($loc->long));

                $res = $wpdb->replace($tablename, $save);
            }
        }

        return $res;
    }

    /**
     * Deletes Locations
     * @param int $orgID Organisation ID
     * @return bool|int Database result
     */
    public static function checkLocatie($orgID = 0) {
	    global $wpdb, $jkm_table_prefix;
        if (!empty(intval($orgID))) {
            $tablename = $jkm_table_prefix . 'locaties';
            $del = $wpdb->delete($tablename, array('organisatie_ID' => intval($orgID)));
            return $del;
        } else {
            return false;
        }
    }

    /**
     * Gets All Organisation Old way
     * @deprecated 1.0.0
     * @return array Organisations
     */
    public static function getAllOrganisaties() {//Old Unused
        $offset = 0;
        $end = false;

        $ret = array();

        while (!$end) {
            $organisatie = self::getOrganisatie(array(), '', 100, $offset);
            if (!is_object($organisatie) || empty($organisatie) || empty($organisatie->organisaties)) {
                $end = true;
                break;
            }

            foreach ($organisatie->organisaties as $org) {
                $ret[$org->ID] = $org;
            }

            $offset += 100;
        }

        return $ret;
    }

    /**
     * Echos Paging
     * @param int $ID Widget ID
     * @param null|int $itemsPerPage Items to Show
     * @param int $page Current Page
     * @param int $total Total to get
     * @param bool $endtag To show Form
     */
    public static function displayPaging($ID, $itemsPerPage = null, $page = 1, $total = 0, $endtag = true) {
        if (!empty($itemsPerPage)) {
            $ID = sanitize_key($ID);
            $itemsPerPage = intval($itemsPerPage);
            $page = intval($page);
            $total = intval($total);
            $endtag = ($endtag ? true : false);

            if ($page <= 0) {
                $page = 1;
            }

            $end = false;
            $lastPage = ceil($total/$itemsPerPage);
            if ($page >= $lastPage || empty($lastPage)) {
                $end = true;
            }

            $html = ($endtag ? '<form method="get" action="?" class="jkm_widget">' . PHP_EOL : PHP_EOL);
            $html .= '    <div class="jkm_widget_page_group">' . PHP_EOL;

            if (!empty($total) && !empty($itemsPerPage)) {
                $html .= '<h2 class="jkm_widget_paging">' . __('Pagina', 'jekuntmeer') . ' ' . $page . '/' . $lastPage . '</h2>' . PHP_EOL;
            }

            $html .= '<input name="jkm_id" class="hidden" type="hidden" value="' . $ID . '"/>' . PHP_EOL;

            if ($page > 1) {
                $html .= '<input type="submit" name="back" class="jkm_widget_back" value="' . __('Vorige', 'jekuntmeer') . '"/>' . PHP_EOL;
            }

            if (!$end) {
                $html .= '<input type="submit" name="next" class="jkm_widget_next" value="' . __('Volgende', 'jekuntmeer') . '"/>' . PHP_EOL;
            }

            $html .= '<input type="hidden" name="jkm_page" value="' . $page . '"/>' . PHP_EOL;

            $html .= '<br/>' . PHP_EOL;
            $html .= '<br/>' . PHP_EOL;
            $html .= '<br/>' . PHP_EOL;
            $html .= '    </div>' . PHP_EOL;
            $html .= '</form>' . PHP_EOL;

            echo $html;
        }
    }

    /**
     * Echos Filter Selection
     *
     * Using Option jkm_soap_user_filter
     * @param array $selected Selected Filters
     * @see Jekuntmeer::getCodeBook()
     * @see Jekuntmeer::sanitize_multiple_array()
     * @return string Final HTML
     */
    public static function filterSelectHTML($selected = array()) {
        $codebook = self::getCodeBook();
        $filter = get_option('jkm_soap_user_filter');
        $selected = self::sanitize_multiple_array($selected);

        $html = PHP_EOL;
        if (!empty($codebook)) {
            foreach ($codebook->properties as $property) {
                $count = 0;
                $temphtml = '';
                if (isset($filter[$property->ID])) {
                    $temphtml .= '            <select name="jkm_search_filter[]" class="jkm_search_filter" multiple="multiple">' . PHP_EOL;

                    foreach ($property->property as $subproperty) {
                        $disable = !self::checkPropertyExists($property->ID, $subproperty->ID);

                        $temphtml .= '                <option '. ($disable ? ' disabled="disabled" ' : ' ') .'value="' . intval($property->ID) . '|' . intval($subproperty->ID) . '" ' . (isset($selected[$property->ID][$subproperty->ID]) ? 'selected=""' : '') . ' id="' . intval($property->ID) . '-' . intval($subproperty->ID) . '" >' . esc_html($subproperty->waarde) . '</option>' . PHP_EOL;
                        ++$count;
                    }

                    $temphtml .= '            </select>' . PHP_EOL;

                    $beschr = empty($property->korte_beschrijving) ? esc_html($property->beschrijving) : esc_html($property->korte_beschrijving);
                    $temphtml .= '<script type="text/javascript">
                    jQuery(\'.jkm_search_filter\').multiselect({ texts: { placeholder: \'' . trim(esc_js(self::echo_safe($beschr)), '&nbsp; i') . '\' } });
                    </script>
                    ';
                }
                if (!empty($count)) {
                    $html .= $temphtml;
                }
            }
        }

        return $html;
    }

    /**
     * Sanitize Filter Array
     * @param array $array Filter Array
     * @see Jekuntmeer::sanitize_single_array()
     * @return array Sanitized Array
     */
    public static function sanitize_multiple_array($array) {
        $ret = array();

        if (!empty($array)) {
            foreach ($array as $value) {
                $arr = explode('|', $value);
                if (count($arr) == 2) {
                    $perrent = intval($arr[0]);
                    $child = intval($arr[1]);
                    if (!($perrent == 0 && $child == 0)) {
                        $ret[$perrent][$child] = $child;
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Updates Properties
     * @see Jekuntmeer::getConnection()
     * @param int $pid Project ID
     * @return bool|int Number of Properties updated
     */
    public static function renewProperties($pid) {
        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'properties';

        $con = self::getConnection();
        if (empty($con)) {
            return false;
        }
        $pid = intval($pid);

        $prop = $con->projectdetail($pid);

        $array = array();

        foreach ($prop->properties as $propertie) {
            $ID = intval($propertie->ID);
            if (is_array($propertie->property)) {
                foreach ($propertie->property as $value) {
                    $array[$ID][intval($value->ID)] = esc_html($value->waarde);
                }
            } elseif (is_object($propertie->property)) {
                $array[$ID][intval($propertie->property->ID)] = esc_html($propertie->property->waarde);
            }
        }
        $res = false;
        if (!empty($array)) {
            $wpdb->delete($tablename, array('project_ID' => $pid));
            foreach ($array as $mainid => $subarray) {
                $save = array();
                $save['project_ID'] = $pid;
                $save['parent_ID'] = null;
                $save['ID'] = $mainid;
                $save['value'] = null;

                $res = $wpdb->replace($tablename, $save);

                foreach ($subarray as $subid => $value) {
                    $save = array();
                    $save['project_ID'] = $pid;
                    $save['parent_ID'] = $mainid;
                    $save['ID'] = $subid;
                    $save['value'] = $value;

                    $res = $wpdb->replace($tablename, $save);
                }
            }
        }

        return $res;
    }

    /**
     * Deletes Properties
     * @param int $pID Project ID
     * @return bool|int Database result
     */
    public static function checkProperties($pID = 0) {
    	global $jkm_table_prefix, $wpdb;
        if (!empty($pID)) {
            $tablename = $jkm_table_prefix . 'properties';
            $del = $wpdb->delete($tablename, array('project_ID' => intval($pID)));
            return $del;
        } else {
            return false;
        }
    }

    /**
     * Get List of Properties
     * @param int $ID Project ID
     * @return array Properties
     */
    public static function getPropertiesByID($ID) {
        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'properties';

        $query = "
        SELECT * FROM `{$tablename}` WHERE `project_ID` = %d AND `parent_ID` IS NOT NULL;
        ";
        $res = $wpdb->get_results($wpdb->prepare($query, intval($ID)), OBJECT);

        $ret = array();

        if (!empty($res) && count($res)) {
            foreach ($res as $prop) {
                if (!is_null($prop->parent_ID)) {
                    $ret[intval($prop->parent_ID)][intval($prop->ID)] = esc_html($prop->value);
                }
            }
        }

        return $ret;
    }

    /**
     * Get List of Properties Using Option jkm_filter_properties
     * @param int $ID Project ID
     * @see Jekuntmeer::getPropertiesByID()
     * @return array Filterd Properties
     */
    public static function getPropertiesByFilter($ID) {
        $filterproperties = get_option('jkm_filter_properties');
        $properties = self::getPropertiesByID(intval($ID));

        $ret = array();

        foreach ($properties as $prop => $proparr) {
            foreach ($proparr as $subprop => $value) {
                if (isset($filterproperties[$prop])) {
                    $ret[intval($prop)][intval($subprop)] = esc_html($value);
                }
            }
        }

        return $ret;
    }

    /**
     * Gets the Name of the Property
     * @param int $ID Property ID
     * @see Jekuntmeer::getCodeBook()
     * @return string Property Name
     */
    public static function getPropertyNameByID($ID) {
        $propertiestext = get_option('jkm_filter_properties_text');
        $ID = intval($ID);
        if (isset($propertiestext[$ID])) {
            return $propertiestext[$ID];
        }

        $codebook = self::getCodeBook();

        foreach ($codebook->properties as $property) {
            if ($property->ID == $ID) {
                return esc_html($property->beschrijving);
            } else {
                foreach ($property->property as $subproperty) {
                    if ($subproperty->ID == $ID) {
                        return esc_html($subproperty->waarde);
                    }
                }
            }
        }

        return '';
    }

    /**
     * Get List of Locations
     * @param int $ID Project ID
     * @see Jekuntmeer::getPropertiesByID()
     * @see Jekuntmeer::getCodeBook()
     * @return array|bool Locations
     */
    public static function getLocationsByID($ID) {
        $properties = self::getPropertiesByID($ID);
        $codebook = self::getCodeBook();

        if (empty($codebook->properties[9]->property) || !is_array($codebook->properties[9]->property) || empty($properties)) {
            return false;
        }

        $prop = array();
        foreach ($codebook->properties[9]->property as $stdClass) {
            $prop[intval($stdClass->ID)] = esc_html($stdClass->waarde);
        }

        $ret = array();

        if (key_exists(7, $properties)) {
            foreach ($properties[7] as $id => $value) {
                if (isset($prop[intval($id)])) {
                    $ret[intval($id)] = esc_html($prop[intval($id)]);
                }
            }
        }

        return $ret;
    }

    /**
     * Get List of Locations IDS
     * @param array|string $array Array of Locations
     * @return array|bool Locations IDS
     */
    public static function getLocationsIDS($array) {
        if (!is_array($array)) {
            $array = unserialize($array);
        }
        if (!count($array)) {
            return false;
        }

        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'locaties';

        $query = $wpdb->prepare("
        SELECT * FROM `{$tablename}` WHERE `ID` IN (".implode(', ', array_fill(0, count($array), '%d')).");
        ", $array);

        $res = $wpdb->get_results($query, OBJECT);

        $ret = array();

        if (!empty($res) && count($res)) {
            foreach ($res as $loc) {
                $ret[] = esc_html($loc->naam);
            }
        }

        return $ret;
    }

    /**
     * Sanitize Filter Array
     * @param array $array Filter Array
     * @see Jekuntmeer::sanitize_multiple_array()
     * @return array Sanitized Array
     */
    public static function sanitize_single_array($array) {
        $ret = array();

        if (!empty($array)) {
            foreach ($array as $value) {
                $arr = explode('|', $value);
                if (count($arr) == 2) {
                    $parrent = intval($arr[0]);
                    $child = intval($arr[1]);
                    if (!($parrent == 0 && $child == 0)) {
                        $ret[$parrent][$child] = $child;
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Check if property exists in the database
     * @param int $parent_ID Parent Property ID
     * @param int $ID Property ID
     * @return bool True if exists
     */
    public static function checkPropertyExists($parent_ID, $ID) {
        global $wpdb, $jkm_table_prefix;
        $tablename = $jkm_table_prefix . 'properties';

        $query = $wpdb->prepare("
            SELECT `project_ID` FROM `{$tablename}` WHERE `parent_ID` = %d AND `ID` = %d
        ", intval($parent_ID), intval($ID));
        $res = $wpdb->get_results($query, OBJECT);

        if (!empty($res) && count($res)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks how old the database is and if a ReSync is needed
     *
     * Uses Option jkm_message, jkm_sync, jkm_sync_stage
     * @see Jekuntmeer::sendEmail()
     * @see Jekuntmeer::emptyDatabase()
     * @return bool Returns true if a update is needed
     */
    public static function checkDatabaseDate() {
        $lastsync = get_option('jkm_sync');
        $stage = get_option('jkm_sync_stage');

        $lastvaliddate = strtotime('+4 week', $lastsync);
        if ($lastvaliddate < current_time('timestamp') && !empty($lastsync)) {
            if ($stage < 4) {
                self::sendEmail('4weeks');
                self::emptyDatabase();
                update_option('jkm_sync_stage', 5);
            }

            $messages = get_option('jkm_message');
            $messages['4weeks'] = __('De gegevens van Jekuntmeer.nl zijn verouderd en kunnen niet getoond worden.', 'jekuntmeer');
            update_option('jkm_message', $messages);
            return true;
        }

        $lastvaliddate = strtotime('+3 week', $lastsync);
        if ($lastvaliddate < current_time('timestamp') && !empty($lastsync)) {
            if ($stage < 3) {
                self::sendEmail('3weeks');
                update_option('jkm_sync_stage', 4);
            }

            $messages = get_option('jkm_message');
            $messages['3weeks'] = __('Let op: de gegevens van jekuntmeer zijn niet meer actueel.', 'jekuntmeer');
            update_option('jkm_message', $messages);
            return true;
        }

        $lastvaliddate = strtotime('+2 week', $lastsync);
        if ($lastvaliddate < current_time('timestamp') && !empty($lastsync)) {
            if ($stage < 2) {
                self::sendEmail('2weeks');
                update_option('jkm_sync_stage', 3);
            }

            $messages = get_option('jkm_message');
            $messages['2weeks'] = __('Let op: de gegevens van jekuntmeer zijn niet meer actueel..', 'jekuntmeer');
            update_option('jkm_message', $messages);
            return true;
        }

        return false;
    }

    /**
     * Sends email using wp_mail to admin_email
     * @param string $type Type of Email to send
     * @return bool
     */
    public static function sendEmail($type) {
        $email = get_option('admin_email');
        $url = get_option('siteurl');
        if ($type == '1week') {
            return wp_mail($email, __('Your database is 1 week old', 'jekuntmeer'), __('Your Jekuntmeer.nl Plugin database is a week out of date at website ', 'jekuntmeer') . $url . '.', '', array());
        } elseif ($type == '2weeks') {
            return wp_mail($email, __('Your database is 2 week old', 'jekuntmeer'), __('Your Jekuntmeer.nl Plugin database is 2 weeks out of date at website ', 'jekuntmeer') . $url . '.', '', array());
        } elseif ($type == '3weeks') {
            return wp_mail($email, __('Your database is 3 week old', 'jekuntmeer'), __('Your Jekuntmeer.nl Plugin database is 3 weeks out of date at website ', 'jekuntmeer') . $url . '.', '', array());
        } elseif ($type == '4weeks') {
            return wp_mail($email, __('Your database is Deleted', 'jekuntmeer'), __('Your Jekuntmeer.nl Plugin database is 4 weeks out of date and was deleted at website ', 'jekuntmeer') . $url . '.', '', array());
        } else {
            return false;
        }
    }

    /**
     * Email Filter to change the From Email
     * @param null|string $email Old Email
     * @return string info @ SERVER_NAME or JEKUNTMEER__FROM_URL
     */
    public static function EmailFilter($email = null) {
        if (defined('JEKUNTMEER__FROM_URL')) {
            $from_email = JEKUNTMEER__FROM_URL;
        } else {
            $sitename = strtolower($_SERVER['SERVER_NAME']);
            if (substr($sitename, 0, 4) == 'www.') {
                $sitename = substr($sitename, 4);
            }
            $from_email = 'info@' . $sitename;
        }

        if (is_email($from_email)) {
            return $from_email;
        } else {
            return $email;
        }
    }

    /**
     * Email Filter to change the From Name
     * @param null|string $name From Name
     * @return string Retruns always the name "info"
     */
    public static function EmailNameFilter($name = null) {
        return 'info';
    }

    /**
     * Get the Current Account's Organisation ID
     * @see Jekuntmeer::getConnection()
     * @return int Organisation ID or 0 if not found
     */
    public static function getOwnID() {
        $own = get_option('jkm_get_only_own');
        if (!$own) {
            return 0;
        }

        $con = self::getConnection();
        if (empty($con) || !is_object($con)) {
            return 0;
        }

        $res = $con->mijn_organisatie();
        if (empty($res) || !is_object($res) || empty($res->organisaties)) {
            return 0;
        }

        return intval($res->organisaties->ID);
    }

    /**
     * Displays a One Time message to the user that they need to allow connection with Jekuntmeer.nl
     *
     * This Plugin uses SOAP and Needs to connect with Jekuntmeer.nl
     *
     * Uses Option jkm_accept_tac, jkm_message
     */
    public static function acceptTac() {
        if (isset($_POST['accept_tac'])) {
            $messages = get_option('jkm_message');
            unset($messages['acceptTac']);
            update_option('jkm_accept_tac', 1);
            $messages['tacaccepted'] = __('U kunt nu de Jekuntmeer koppeling gebruiken.', 'jekuntmeer');
            update_option('jkm_message', $messages);
        } else {
            $messages = get_option('jkm_message');

            $message = __('Deze plugin gebruikt een koppeling met jekuntmeer.nl<br/>Accepteer de voorwaarden om de koppeling te kunnen gebruiken.', 'jekuntmeer');
            $message .= '<br/><form action="" method="post"><button id="accept_tac" type="submit" name="accept_tac" class="button button-primary">' . __('Ik sta koppeling met jekuntmeer.nl toe', 'jekuntmeer') . '</button></form>';
            $messages['acceptTac'] = $message;

            if (version_compare($GLOBALS['wp_version'], JEKUNTMEER__MINIMUM_WP_VERSION, '<')) {
                $message = '<strong>' . __('Deze plugin is niet getest op uw WordPress versie!', 'jekuntmeer') . '</strong>';
                $messages['nottested'] = $message;
            }

            update_option('jkm_message', $messages);
        }
    }

    /**
     * Get Keywords from search input
     * @param string $string String of texts
     * @param array $stopwords List of keywords
     * @see Jekuntmeer::$stop_words
     * @return array List of Keywords from the string passed
     */
    public static function getKeywords($string, $stopwords = array()) {
        mb_internal_encoding('UTF-8');
        $string = preg_replace('/[\pP]/u', '', trim(preg_replace('/\s\s+/iu', '', mb_strtolower($string))));
        $matchWords = array_filter(explode(' ', $string), function ($item) use ($stopwords) {return !($item == '' || in_array($item, $stopwords) || mb_strlen($item) <= 2 || is_numeric($item));});
        $wordCountArr = array_count_values($matchWords);
        arsort($wordCountArr);
        return array_keys(array_slice($wordCountArr, 0, 10));
    }

    /**
     * Decode HTML and Only Allow br
     * @param string $html Encoded HTML
     * @param bool $return Return or ECHO
     * @return string Decoded HTML
     */
    public static function echo_safe($html, $return = true) {
        $html = htmlspecialchars_decode($html, ENT_NOQUOTES);
        $html = strip_tags($html, '<br>');
        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }
}

/**
 * Class SoapProperties
 *
 * Used to pass properties in SOAP Call
 * @see Jekuntmeer::getProperties()
 * @see SoapProperty
 */
class SoapProperties {
    /**
     * @var array $property List of Properties
     */
    public $property;
    /**
     * @var int $ID ID of the Property
     */
    public $ID;
    /**
     * @var string $beschrijving Description of the Property
     */
    public $beschrijving;
    /**
     * @var string $korte_beschrijving Short Description of the Property
     */
    public $korte_beschrijving;

    /**
     * SoapProperties constructor.
     * @see SoapProperty
     */
    public function __construct() {
        $this->property = array();
    }
}

/**
 * Class SoapProperty
 *
 * Property to pass in SOAP Call
 * @see SoapProperties
 */
class SoapProperty {
    /**
     * @var int $ID ID of the Property
     */
    public $ID;
    /**
     * @var int $waarde Value of the Property
     */
    public $waarde;

    /**
     * SoapProperty constructor.
     * @param int $ID ID of the Property
     * @param int $value Value of the Property
     * @see SoapProperties
     */
    public function __construct($ID, $value) {
        $this->ID = $ID;
        $this->waarde = $value;
    }
}