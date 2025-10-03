<?php

// Manages plugin translations
class shortcodr_i18n {

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'shortcodr',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}