<?php
/* ==============================================================================

Plugin Name: Seo-Lexikon
Plugin URI: http://www.3task.de/tools-programme/wordpress-lexikon/
Description: Dies ist ein Lexikon Plugin mit einer Zusatzfunktion. Kommen in einem Lexikon Beitrag Wörter vor, die schon als Lexikon Eintrag vorhanden sind, verlinkt es automatisch diesen Lexikon Eintrag. Das ist also eine interne Quervlinkung wie es für SEO's manchmal wichtig ist.
Author: <a href="http://www.3task.de">3task.de</a>
Version: 5.5+php8-1.1

/* ============================================================================== */

class seo_lexikon_3task
{
    private static $GLOBALS = [];

    public $items;
    public $options;
    public $post_parent;
    public $content;
    public $post_id;

    public function Enable()
    {
        if (!class_exists('WPlize')) {
            require_once 'inc/wplize.class.php';
        }

        add_action('admin_menu', [seo_lexikon_3task::class, 'RegisterAdminPage']);
        add_filter('the_content', 'seo_lexikon_contentfilter');
    }

    public function int($content)
    {
        $WPlize = new WPlize('seo_lexikon_options');
        $this->items = $WPlize->get_option('ltItems');
        $this->options = $WPlize->get_option('options');

        global $post;
        $this->post_id = $post->ID;
        $this->post_parent = $post->post_parent;
        $this->content = $content;

        if (is_admin() || !is_array($this->items)) {
            return false;
        }

        foreach ($this->items as $item) {
            if ($this->post_id === intval($item['ltAddId'])) {
                $this->content = $this->CreateSummary();

                return;
            }

            if ('selective' === $this->options['ltLinking'] && $post->post_parent === intval($item['ltAddId']) || 'all' === $this->options['ltLinking']) {
                $this->CreateLinking($item['ltAddId']);
            }

            if ($this->post_parent === intval($item['ltAddId']) && 1 === intval($item['ltAddNav'])) {
                $this->content = $this->CreateNavigation($item['ltAddId']);
            }
        }
    }

    public static function RegisterAdminPage()
    {
        add_submenu_page('options-general.php', 'Seo-Lexikon', 'Seo-Lexikon', 'manage_options', __FILE__, 'seo_lexikon_admin');
    }

    public function CreateLinking($id)
    {
        $results = seo_lexikon_3task::getResults($id);

        if (count($results) > 0) {
            static::$GLOBALS['seo_lexikon_replace_num'] = 0;
            static::$GLOBALS['seo_lexikon_replace_array'] = [];

            foreach (array_slice($results, 0, 20) as $result) {
                // $s = trim(ent2ncr(htmlentities(utf8_decode($result['post_title']))));
                $s = trim($result['post_title']);
                $link = get_the_permalink($result['ID']);

                static::$GLOBALS['r'] = $result['ID'];
                static::$GLOBALS['s'] = $result['post_title'];

                $this->content = preg_replace(
                    "~(?![^<]*>)(?!<h[1-6][^>])(?!<a.*>)(\b$s\b)(?!.*<\/h[1-6]>)(?!.*<\/a>)~",
                    "<a href='$link'>$s</a>",
                    $this->content
                );

                // (\S+)=["']?((?:.(?!["']?\s+(?:\S+)=|[>"']))+.)["']?

                // $this->content = preg_replace_callback	(
                // 										'~(?![^<]*>)([^a-zA-ZüöäÜÖÄ\-])(?!<h[1-6][^>]*)(?!<a[^>]*)(?!<img[^\/>]*)('.$s.')(?!.*\/>)(?!.*<\/a>)(?!.*<\/h[1-6]>)([^a-zA-ZüöäÜÖÄ\-])~i',
                // 										array(seo_lexikon_3task::class, 'replace_callback'),
                // 										$this->content,
                // 										1
                // 										);
            }

            if (static::$GLOBALS['seo_lexikon_replace_num'] > 0) {
                foreach (static::$GLOBALS['seo_lexikon_replace_array'] as $key => $value) {
                    $this->content = str_replace('[lexikonflag]'.$key.'[/lexikonflag]', $value, $this->content);
                }
            }
        }
    }

    public function CreateNavigation($parent_id)
    {
        $alphabeticList = seo_lexikon_3task::GetAlphabeticList($parent_id);

        if (count($alphabeticList) > 0) {
            $output = null;

            $output = "<div class='AlphabeticList'>";

            $premalink = get_permalink($parent_id);

            foreach ($alphabeticList as $initial => $group) {
                if ($group) {
                    $output .= "<a href='".$premalink.'#'.$initial."'>".$initial.'</a> ';
                }
            }

            $output .= '</div>';
        }

        return $output.$this->content;
    }

    public function CreateSummary()
    {
        $alphabeticList = seo_lexikon_3task::GetAlphabeticList($this->post_id);

        if (count($alphabeticList) > 0) {
            $output = null;

            $output = "<div class='AlphabeticList'>";

            foreach ($alphabeticList as $initial => $group) {
                if ($group) {
                    $output .= "<a href='#".$initial."'>".$initial.'</a> ';
                }
            }

            $output .= '</div>';

            foreach ($alphabeticList as $initial => $group) {
                if ($group) {
                    $output .= "<h2 class='initial' id='$initial'>".$initial.'</h2>';
                }

                for ($i = 0, $x = count($group); $i < $x; ++$i) {
                    $output .= "<a href='".$group[$i]['post_url']."'>".$group[$i]['post_title'].'</a><br>';
                }
            }
            if ('checked' === $this->options['ltAddFooter']) {
                $output .= '<p style="padding-top: 15px;text-align:right;"><small>&copy; 3task.de - <a href="http://www.3task.de/" title="Webdesign Agentur">Webdesign Agentur</a></small></p>';
            }
        }

        return $this->content.$output;
    }

    public static function replace_callback($match)
    {
        static::$GLOBALS['seo_lexikon_replace_array'][static::$GLOBALS['s']] = '<a href="'.get_permalink(static::$GLOBALS['r']).'" title="'.static::$GLOBALS['s'].'">'.static::$GLOBALS['s'].'</a>';
        static::$GLOBALS['seo_lexikon_replace_num']++;

        return $match[1].'[lexikonflag]'.static::$GLOBALS['s'].'[/lexikonflag]'.$match[3];
    }

    public static function getResults($id)
    {
        global $wpdb;
        global $post;

        // $pagelength = static::paginate_length();
        $lex_query = $wpdb->prepare(
            "SELECT post_title,post_name,ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' AND ID <> $post->ID AND post_parent = %d ORDER BY post_title",
            $id
        );

        return $wpdb->get_results($lex_query, ARRAY_A);
    }

    public static function getInitial($string)
    {
        $string = trim($string);

        $chars['feed'] = [
            '&#196;', '&#228;', '&#214;', '&#246;', '&#220;', '&#252;', '&#223;',
            ];

        $chars['chars'] = [
            'Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß;',
            ];

        $chars['perma'] = [
            'Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss',
            ];

        $string = str_replace($chars['feed'], $chars['perma'], $string);
        $string = str_replace($chars['chars'], $chars['perma'], $string);

        $initial = $string[0];

        if (preg_match('/^[a-z]$/i', $initial)) {
            return strtoupper($initial);
        }

        switch ($initial) {
            case 'ä':
            case 'Ä':
                return 'A';
            case 'ö':
            case 'Ö':
                return 'O';
            case 'ü':
            case 'Ü':
                return 'U';
            default:
                return '#';
        }
    }

    public static function GetAlphabeticList($postID)
    {
        global $wpdb;

        $results = seo_lexikon_3task::getResults($postID);

        if (!$results) {
            return [];
        }

        $keys = range('A', 'Z');
        array_unshift($keys, '#');
        $values = array_fill(0, 27, []);
        $data = array_combine($keys, $values);

        foreach ($results as $result) {
            $initial = seo_lexikon_3task::getInitial($result['post_title']);
            $result['post_url'] = get_permalink($result['ID']);
            $data[$initial][] = $result;
        }

        return $data;
    }

    public static function paginate_length()
    {
        // Mögliche Verbesserung: Option posts_per_page verwenden
        // return max(intval(get_option('posts_per_page', 20)), 30);
        $length = max(intval(substr(get_bloginfo('version'), 0, 1)), 8);

        return $length * 2.5;
    }
}

function seo_lexikon_admin()
{
    ?><div class="wrap">
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Lexikon</h2>

	<?php

    if (!class_exists('WPlize')) {
        require_once 'inc/wplize.class.php';
    }

    $WPlize = new WPlize('seo_lexikon_options');

    if (isset($_POST['ltSubmitOptions'])) {
        $options = ['ltLinking', 'ltAddFooter'];

        foreach ($options as $option) {
            $options[$option] = stripslashes(htmlspecialchars($_POST[$option]));
        }
        if (isset($_POST['ltAddFooter'])) {
            $options['ltAddFooter'] = 'checked';
        } else {
            $options['ltAddFooter'] = '';
        }

        $WPlize->update_option('options', $options);
        define('LTNOTICE', 'Allgemeine Einstellungen gespeichert!');
    }

    if (isset($_POST['ltSubmitAdd'])) {
        $items = $WPlize->get_option('ltItems');

        if (!is_array($items)) {
            $items = [];
        }

        $options = ['ltAddId', 'ltAddNav'];

        foreach ($options as $option) {
            $options[$option] = stripslashes(htmlspecialchars($_POST[$option]));
        }

        $items[] = $options;

        $WPlize->update_option('ltItems', $items);
        define('LTNOTICE', 'Lexikon wurde hinzugefügt!');
    }

    if (isset($_POST['ltSubmitEdit'])) {
        $items = [];

        if (is_array($_POST['ltAddId'])) {
            foreach ($_POST['ltAddId'] as $key => $value) {
                $items[$key]['ltAddId'] = stripslashes(htmlspecialchars($_POST['ltAddId'][$key]));
                if (!isset($_POST['ltAddNav'][$key])) {
                    $_POST['ltAddNav'][$key] = 0;
                }

                $items[$key]['ltAddNav'] = stripslashes(htmlspecialchars($_POST['ltAddNav'][$key]));
            }
        }

        $WPlize->update_option('ltItems', $items);
        define('LTNOTICE', 'Einstellungen gespeichert!');
    }

    // Statusausgabe
    if (defined('LTNOTICE')) {
        echo "<div class='updated'><p><strong>".LTNOTICE.'</strong></p></div>';
    }

    $items = $WPlize->get_option('ltItems');

    if (is_array($items) && count($items) > 0) {
        ?>

		<script type="text/javascript">
			jQuery(document).ready(function(){

				jQuery('a.delete').live('click',function(){
					jQuery('p.' + jQuery(this).attr('rel')).fadeTo(500, '0.5',function(){ jQuery(this).remove(); });
					return false;
				});

			});
		</script>

		<div style="padding: 4px 18px 18px 18px; background:#ffffff; border: 1px solid #cccccc; -moz-border-radius: 4px; border-radius: 4px; margin: 13px 0 0 0;">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<h3>Lexikon bearbeiten</h3>

				<?php foreach ($items as $key => $item) { ?>

				<p class="row_<?php echo $key; ?>">
					<label>Übersichtseite:</label>
					<?php
                    echo $pages = wp_dropdown_pages(
                        ['post_type' => 'page',
                                        'selected' => $item['ltAddId'],
                                        'name' => 'ltAddId[]',
                                        'show_option_none' => __('(no parent)'),
                                        'sort_column' => 'menu_order, post_title',
                                        'echo' => 0,
                                        ]
                    );
				    ?>

						<label>A-Z Navigation:</label>
						<select name="ltAddNav[]">
							<option value="1" <?php if (1 === intval($item['ltAddNav'])) { ?>selected="selected"<?php } ?>>aktiviert</option>
							<option value="2" <?php if (2 === intval($item['ltAddNav'])) { ?>selected="selected"<?php } ?>>deaktiviert</option>
						</select>

						<a href="#" class="delete" rel="row_<?php echo $key; ?>">Löschen</a>
					</p>

					<?php } ?>

					<hr style="border:none; background:none; border-top: 1px solid #ccc; " />

					<p class="submit" style="padding: 5px 0 0 0;">
						<input name="ltSubmitEdit" class="button-primary" value="Änderungen speichern" type="submit">
					</p>
				</form>
			</div>

			<?php
    }

    if (null === $items || 0 === count($items)) {
        ?>

			<div style="padding: 4px 18px 18px 18px; background:#ffffff; border: 1px solid #cccccc; -moz-border-radius: 4px; border-radius: 4px; margin: 13px 0 0 0;">
				<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<h3>Lexikon hinzufügen</h3>

					<p>
						<label>Lexikon Übersichtsseite:</label>
						<?php
                    echo $pages = wp_dropdown_pages(
                        ['post_type' => 'page',
                                        'selected' => null,
                                        'name' => 'ltAddId',
                                        'show_option_none' => __('(no parent)'),
                                        'sort_column' => 'menu_order, post_title',
                                        'echo' => 0,
                                        ]
                    );
        ?>
						</p>

						<p>
							<input name="ltAddNav" type="checkbox" value="1"  /> Lexikoneinträge mit A-Z Navigation anzeigen.
						</p>

						<hr style="border:none; background:none; border-top: 1px solid #cccccc; " />

						<p class="submit" style="padding: 5px 0 0 0;">
							<input name="ltSubmitAdd" class="button-primary" value="Lexikon erstellen" type="submit">
						</p>
					</form>
				</div>

				<?php

    }

    ?>

				<div style="padding: 4px 18px 18px 18px; background:#ffffff; border: 1px solid #cccccc; -moz-border-radius: 4px; border-radius: 4px; margin: 13px 0 0 0;">
					<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
						<h3>Allgemeine Einstellungen</h3>
						<?php $options = $WPlize->get_option('options'); ?>
						<p>
							<label>Interne Verlinkung:</label>
							<select name="ltLinking">
								<option value="null">deaktiviert</option>
								<!-- <option value="all" <?php if ('all' === $options['ltLinking']) { ?>selected="selected"<?php } ?>>komplette Seite</option> -->
								<option value="selective" <?php if ('selective' === $options['ltLinking']) { ?>selected="selected"<?php } ?>>nur innerhalb des Lexikas</option>
							</select>
						</p>
						<p>
							<input name="ltAddFooter" type="checkbox" value="ltAddFooter" <?php echo $options['ltAddFooter']; ?> /> Geiz ist geil? Ich hoffe sie Würdigen die viele Arbeit, mit einem Backlink zu meiner Seite, damit ich auch weiterhin kostenlose Plugins anbiete. Tut ihnen sicher nicht weh und mir würde es wirklich helfen. Vielen Dank!
						</p>

						<hr style="border:none; background:none; border-top: 1px solid #ccc; " />

						<p class="submit" style="padding: 5px 0 0 0;">
							<input name="ltSubmitOptions" class="button-primary" value="Änderungen speichern" type="submit">
						</p>
					</form>
				</div>
			</div><?php

}

function seo_lexikon_contentfilter($content)
{
    $lexikon = new seo_lexikon_3task();
    $lexikon->int($content);

    return $lexikon->content;
}

if (defined('ABSPATH')) {
    $lexikon = new seo_lexikon_3task();
    $lexikon->Enable();
}
