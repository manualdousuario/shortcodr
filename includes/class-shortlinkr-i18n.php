<?php

class shortlinkr_i18n {

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'shortlinkr',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}