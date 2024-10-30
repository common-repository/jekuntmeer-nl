<?php
/**
 * Class Jekuntmeer_Shortcode
 *
 * Runs Jekuntmeer Widget as Shortcode so you can put it on any page
 * @see Jekuntmeer_Widget
 */
class Jekuntmeer_Shortcode {
    /**
     * @var bool $initiated Check that hooks get set only one time
     */
    private static $initiated = false;

    /**
     * @var int $count For making the Widget unique
     */
    private static $count = 0;

    /**
     * Calls init_hooks if not done
     * @see Jekuntmeer_Shortcode::init_hooks()
     */
    public static function init() {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    /**
     * Setups Shortcode
     * @see Jekuntmeer_Shortcode::jekuntmeer()
     */
    private static function init_hooks() {
        self::$initiated = true;

        add_shortcode('jekuntmeer', array('Jekuntmeer_Shortcode', 'jekuntmeer'));
    }

    /**
     * Jekuntmeer shortcode
     *
     * Usage: [jekuntmeer itemsperpage="int"] Where int can be any integer number also 0
     *
     * 0 means to show all projects
     * @param array $atts Atterbutes to set
     * @param null|string $content Html Content
     * @see Jekuntmeer_Widget
     * @return string Widget HTML Generated
     */
    public static function jekuntmeer($atts, $content = null) {
        self::$count++;
        $a = shortcode_atts(array('itemsperpage' => 0, 'id' => 'jkmshortcode-' . self::$count), $atts);

        ob_start();
        the_widget('Jekuntmeer_Widget', $a, array());
        return ob_get_clean();
    }
}
?>