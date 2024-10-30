<?php
/**
 * Class Jekuntmeer_Widget
 *
 * Widget for Displaying Projects on Site
 * @see Jekuntmeer
 * @see WP_Widget
 */
class Jekuntmeer_Widget extends WP_Widget {

    /**
     * Jekuntmeer_Widget constructor.
     *
     * Adds the widget to the list
     * @see WP_Widget::WP_Widget()
     */
    function __construct() {
        parent::__construct(
            'jekuntmeer_widget',
            'Jekuntmeer Widget',
            array('description' => __('Widget voor Jekuntmeer.nl', 'jekuntmeer'))
        );
    }

    /**
     * Echos HTML of List of projects
     * @inheritdoc WP_Widget
     * @see Jekuntmeer::displayFilter()
     * @see Jekuntmeer::display()
     * @see Jekuntmeer::displayPaging()
     * @see WP_Widget::widget()
     */
    public function widget($args, $instance) {
        if (get_option('jkm_accept_tac')) {
            extract($args);
            extract($instance);

            $keyword = null;
            $page = null;
            $filter = array();

            if (isset($_GET['jkm_id']) && isset($_GET['jkm_page'])) {
                $pid = sanitize_key($_GET['jkm_id']);

                if ($id == $pid) {
                    $page = intval($_GET['jkm_page']);

                    if (isset($_GET['next'])) {
                        $page++;
                    } elseif (isset($_GET['back'])) {
                        $page--;
                        if ($page <= 1) {
                            $page = 1;
                        }
                    }
                }
            }

            if (isset($_GET['jkm_search_sub'])) {
                $page = 1;
            }

            if (isset($_GET['jkm_id']) && isset($_GET['jkm_search'])) {
                $pid = sanitize_key($_GET['jkm_id']);

                if ($id == $pid) {
                    $keyword = sanitize_text_field($_GET['jkm_search']);
                }
            }

            if (isset($_GET['jkm_id']) && isset($_GET['jkm_search_filter'])) {
                $pid = sanitize_key($_GET['jkm_id']);

                if ($id == $pid) {
                    $filter = array_map('sanitize_text_field', $_GET['jkm_search_filter']);
                }
            }

            $allow = intval(get_option('jkm_soap_allow_user_search'));

            if ($allow) {
                Jekuntmeer::displayFilter($id, $keyword, (!empty($itemsperpage)) ? false : true, '', $filter);
            }

            $res = Jekuntmeer::display($keyword, $filter, $itemsperpage, $page);

            if (!empty($itemsperpage)) {
                $endtag = false;
                if (!$allow) {
                    $endtag = true;
                }
                Jekuntmeer::displayPaging($id, $itemsperpage, $page, $res, $endtag);
            }
        }
    }

    /**
     * Echos Form to change settings
     * @inheritdoc WP_Widget
     * @see WP_Widget::form()
     */
    public function form($instance) {
        $defaults = array('itemsperpage' => 0);
        $instance = wp_parse_args((array) $instance, $defaults);

        $itemsperpage = intval($instance['itemsperpage']);
        $html = '<p>' . PHP_EOL;
        $html .= '<label for="' . $this->get_field_id('itemsperpage') . '">' . __('Hoeveel activiteiten wilt u tonen per pagina?', 'jekuntmeer') . '</label>' . PHP_EOL;
        $html .= '<input id="' . $this->get_field_id('itemsperpage') . '" name="' . $this->get_field_name('itemsperpage') . '" type="text" value="' . $itemsperpage . '">' . PHP_EOL;
        $html .= '</p>' . PHP_EOL;

        echo $html;
    }

    /**
     * Updates current Widget
     * @inheritdoc WP_Widget
     * @see WP_Widget::update()
     * @return array New Instance Settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['itemsperpage'] = intval($new_instance['itemsperpage']);

        return $instance;
    }

}
?>