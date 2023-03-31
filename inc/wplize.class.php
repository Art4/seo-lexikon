<?php
/**
* WPlize [Klasse].
*
* Updaten, Setzen, Holen und L&ouml;schen von Optionen in WordPress
*
* WPlize gruppiert und verwaltet alle Optionen eines Plugins bzw.
* Themes in einem einzigen Optionsfeld. Die Anzahl der
* Datenbankabfragen und somit die Ladezeit des Blogs k&ouml;nnen sich
* sich enorm verringern. WPlize richtet sich an die Entwickler
* von WordPress-Plugins und -Themes.
*
* @author   Sergej M&uuml;ller und Frank B&uuml;ltge
*
* @since    26.09.2008
*
* @change   26.09.2008
*/
class WPlize
{
    public $multi_option;

    /**
     * WPlize [Konstruktor].
     *
     * Setzt Eigenschafen fest und startet die Initialisierung
     *
     * @author   Sergej Müller
     *
     * @since    26.09.2008
     *
     * @change   03.12.2008
     *
     * @param array $option Name der Multi-Option in der DB [optional]
     * @param array $data   Array mit Anfangswerten [optional]
     */
    public function __construct($option = '', $data = [])
    {
        if (true === empty($option)) {
            $this->multi_option = 'WPlize_'.md5(get_bloginfo('home'));
        } else {
            $this->multi_option = $option;
        }

        if ($data) {
            $this->init_option($data);
        }
    }

    /**
     * init_option.
     *
     * Initialisiert die Multi-Option in der DB
     *
     * @author   Sergej Müller
     *
     * @since    26.09.2008
     *
     * @change   26.09.2008
     *
     * @param array $data Array mit Anfangswerten [optional]
     */
    public function init_option($data = [])
    {
        add_option($this->multi_option, $data);
    }

    /**
     * delete_option.
     *
     * Entfernt die Multi-Option aus der DB
     *
     * @author   Sergej Müller
     *
     * @since    26.09.2008
     *
     * @change   26.09.2008
     */
    public function delete_option()
    {
        delete_option($this->multi_option);
    }

    /**
     * get_option.
     *
     * Liefert den Wert einer Option
     *
     * @author   Sergej Müller
     *
     * @since    26.09.2008
     *
     * @change   26.09.2008
     *
     * @param string $key Name der Option
     *
     * @return mixed Wert der Option [false im Fehlerfall]
     */
    public function get_option($key)
    {
        if (true === empty($key)) {
            return false;
        }

        $data = get_option($this->multi_option);

        return @$data[$key];
    }

    /**
     * update_option.
     *
     * Weist den Optionen neue Werte zu
     *
     * @author   Sergej Müller
     *
     * @since    26.09.2008
     *
     * @change   07.12.2008
     *
     * @param mixed  $key   Name der Option [alternativ Array mit Optionen]
     * @param string $value Wert der Option [optional]
     *
     * @return bool False im Fehlerfall
     */
    public function update_option($key, $value = '')
    {
        if (true === empty($key)) {
            return false;
        }

        if (true === is_array($key)) {
            $data = $key;
        } else {
            $data = [$key => $value];
        }

        if (true === is_array(get_option($this->multi_option))) {
            $update = array_merge(
                get_option($this->multi_option),
                $data
            );
        } else {
            $update = $data;
        }

        update_option(
            $this->multi_option,
            $update
        );
    }
}
