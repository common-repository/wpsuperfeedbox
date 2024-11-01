<?php
/*
Plugin Name: WPSuperFeedBox
Plugin URI: http://www.superlevel.de/wordpress/wpsuperfeedbox
Description: Adds a fancy hideable Feed Box to your Post Page. Boost your feed subscriber count! Just place <code>&lt;?php addWPSuperFeedBox(); ?&gt;</code> anywhere in your <code>single.php</code> to start boosting.
Author: Matthias Kunze
Version: 0.9
Author URI: http://www.superlevel.de
*/

if (!class_exists('WPSuperFeedBox')) {

        class WPSuperFeedBox {

                var $version = '0.9';

                function WPSuperFeedBox() {
                        $this->__construct();
                }

                /**
                 * init
                 */
                function __construct() {
                        $basename = basename(dirname(__FILE__));
                        $this->pluginDir = get_option('siteurl') . '/wp-content/plugins/' . $basename;

                        register_activation_hook(__FILE__, array(&$this, 'activateWPSuperFeedBox'));
                        register_deactivation_hook(__FILE__, array(&$this, 'deactivateWPSuperFeedBox'));

                        add_action('admin_menu', array(&$this, 'addWPSuperFeedBoxToAdmin'));
                        add_action('wp_head', array(&$this, 'addWPSuperFeedBoxCSS'), 1000);
                        add_action('wp_print_scripts', array(&$this,'addWPSuperFeedBoxScript'), 1000);

                        load_plugin_textdomain('wpsuperfeedbox', 'wp-content/plugins/' . $basename, $basename);
                }

                /**
                 * plugin activated
                 */
                function activateWPSuperFeedBox() {
                        add_option('WPSuperFeedBox', array(
                                'show_message' => __('Wenn dir dieser Artikel gefallen hat, abonniere doch den Feed (<a href="[RSS_URL]">RSS</a> oder <a href="[ATOM_URL]">Atom</a>) von &bdquo;[BLOG_NAME]&ldquo;, um keinen der zuk&uuml;nftigen Beitr&auml;ge zu verpassen.', 'wpsuperfeedbox'),
                                'close_message' => __('(Hinweis schlie&szlig;en)', 'wpsuperfeedbox')
                        ));
                }

                /**
                 * plugin deactivated
                 */
                function deactivateWPSuperFeedBox() {
                        delete_option('WPSuperFeedBox');
                }

                /**
                 * add admin panel
                 */
                function addWPSuperFeedBoxToAdmin() {
                        add_submenu_page('plugins.php', __('WPSuperFeedBox Konfiguration', 'wpsuperfeedbox'), 'WPSuperFeedBox', 8, basename(__FILE__), array(&$this, 'configWPSuperFeedBox'));
                }

                /**
                 * admin panel view
                 */
                function configWPSuperFeedBox() {
                        $cssFile = dirname(__FILE__) . '/wpsuperfeedbox-styles.css';

                        $wpsfbOptions = get_option('WPSuperFeedBox');
                        $saved = false;

                        if (isset($_POST['submit'])) {
                                $validNonce = wp_verify_nonce($_REQUEST['_wpnonce'], 'wp-wpsuperfeedbox-settings');
                                if ($validNonce === false) {
                                        die("Security problem!");
                                }

                                $showMessage = stripslashes($_POST['show_message']);
                                $closeMessage = stripslashes($_POST['close_message']);

                                // new options
                                $wpsfbOptions = array(
                                        'show_message' => $showMessage,
                                        'close_message' => $closeMessage
                                );

                                update_option('WPSuperFeedBox', $wpsfbOptions);

                                // css
                                $newCss = stripslashes(trim($_POST['style_sheet']));
                				if (is_writeable($cssFile)) {
                    					$f = fopen($cssFile, 'w+');
                    					fwrite($f, $newCss);
                    					fclose($f);
                				}
                				$saved = true;
                        }

                        $disabled = ' disabled="disabled"';
                        if (!is_file($cssFile)) {
                                $cssMsg = sprintf(__('<em>%s</em> wurde nicht gefunden!', 'wpsuperfeedbox'), 'wpsuperfeedbox-styles.css');
                        }
                        else if (!is_readable($cssFile)) {
                                $cssMsg = sprintf(__('<em>%s</em> konnte nicht gelesen werden!', 'wpsuperfeedbox'), 'wpsuperfeedbox-styles.css');
                        }
                        else if (!is_writeable($cssFile)) {
                                $cssMsg = sprintf(__('<em>%s</em> ist nicht beschreibbar!', 'wpsuperfeedbox'), 'wpsuperfeedbox-styles.css');
                        }
                        else {
                                $cssMsg = sprintf(__('<em>%s</em> kann direkt bearbeitet werden.</small>', 'wpsuperfeedbox'), 'wpsuperfeedbox-styles.css');
                                $disabled = '';
                        }

                        $content = '';
                        if (filesize($cssFile) > 0) {
                				$f = fopen($cssFile, 'r');
                				$content = fread($f, filesize($cssFile));
                				$content = wp_specialchars($content);
            			}

                        $wpsfbOptions['show_message'] = wp_specialchars($wpsfbOptions['show_message']);
                        $wpsfbOptions['close_message'] = wp_specialchars($wpsfbOptions['close_message']);
                ?>

                        <div class="wrap">
				            <h2><?php _e('WPSuperFeedBox Konfiguration', 'wpsuperfeedbox'); ?></h2>

                            <?php if ($saved === true) : ?>
				            <div class="updated fade below-h2"><p><?php _e('&Auml;nderungen gespeichert', 'wpsuperfeedbox'); ?></p></div>
				            <?php endif; ?>

				            <p><?php _e('Platziere <code>&lt;?php addWPSuperFeedBox(); ?&gt;</code> irgendwo in deiner <code>single.php</code>, um die Feedbox zu aktivieren.', 'wpsuperfeedbox'); ?></p>

				            <form action="" method="post" id="quiz-conf">
				            <?php wp_nonce_field('wp-wpsuperfeedbox-settings'); ?>
				            <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php _e('Text der Feedbox', 'wpsuperfeedbox'); ?><br/><br/>
                                <br/><small>
                                [RSS_URL] = <?php _e('RSS-Feed', 'wpsuperfeedbox'); ?><br/>
                                [ATOM_URL] = <?php _e('Atom-Feed', 'wpsuperfeedbox'); ?><br/>
                                [BLOG_NAME] = <?php _e('Weblogtitel', 'wpsuperfeedbox'); ?></small></th>
                                <td><textarea cols="63" rows="10" name="show_message"><?php echo $wpsfbOptions['show_message']; ?></textarea></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('Hinweis ausblenden', 'wpsuperfeedbox'); ?></th>
                                <td><input type="text" name="close_message" value="<?php echo $wpsfbOptions['close_message']; ?>" size="65" /></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('CSS bearbeiten', 'wpsuperfeedbox'); ?><br/>
                                <br/>
                                <?php echo $cssMsg; ?></th>
                                <td><textarea<?php echo $disabled; ?> cols="63" rows="20" name="style_sheet"><?php echo $content; ?></textarea></td>
                            </tr>
                            </table>
                            <p class="submit">
                                <input name="submit" type="submit" class="button-primary" value="<?php _e('&Auml;nderungen &uuml;bernehmen', 'wpsuperfeedbox'); ?>" />
                            </p>
				            </form>
				        </div>

				        <?php
                }

                /**
                 * add feedbox css to single.php
                 */
                function addWPSuperFeedBoxCSS() {
                        if (is_single() && !isset($_COOKIE['feed-note'])) {
                                echo '<link rel="stylesheet" href="' . $this->pluginDir . '/wpsuperfeedbox-styles.css" type="text/css" media="screen"  />';
                                echo "
<script type=\"text/javascript\">
/* <![CDATA[ */
var wpsfbHome = '" . SITECOOKIEPATH . "';
/* ]]> */
</script>";
                        }
                }

                /**
                 * add feedbox script to single.php
                 */
                function addWPSuperFeedBoxScript() {
                        if (is_single() && !isset($_COOKIE['feed-note'])) {
                                wp_enqueue_script('wpsuperfeedbox_script', $this->pluginDir . '/wpsuperfeedbox.js', array('jquery'), $this->version);
                        }
                }

                /**
                 * print the feed box
                 */
                function printWPSuperFeedBox() {
                        $html = '';
                        if (!isset($_COOKIE['feed-note'])) {
                                $wpsfbOptions = get_option('WPSuperFeedBox');
                                $feedboxMessage = str_replace(
                                        array('[ATOM_URL]', '[RSS_URL]', '[BLOG_NAME]'),
                                        array(get_bloginfo('atom_url'), get_bloginfo('rss2_url'), get_bloginfo('name')),
                                        $wpsfbOptions['show_message']
                                );
                                $feedboxClose = $wpsfbOptions['close_message'];

                                $html  = '<div class="wpsfb-feedbox-note">';
                                    $html .= $feedboxMessage;
                                    $html .= '<a href="#" class="wpsfb-close-feedbox">' . $feedboxClose . '</a>';
                                $html .= '</div>';
                        }
                        return $html;
                }

        }

}

$WPSuperFeedBox = new WPSuperFeedBox();

function addWPSuperFeedBox() {
        global $WPSuperFeedBox;
        echo $WPSuperFeedBox->printWPSuperFeedBox();
}