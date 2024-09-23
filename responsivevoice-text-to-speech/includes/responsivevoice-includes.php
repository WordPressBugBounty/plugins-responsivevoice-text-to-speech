<?php

function RV_load_scripts()
{
    $rvUrl = 'https://code.responsivevoice.org/responsivevoice.js';
    $queryParams = array();

    $options = get_option('RV_settings', '');
    $apiKey  = !empty($options) && array_key_exists('RV_text_api_key', $options) ? $options['RV_text_api_key'] : '';

    if(!empty($apiKey)){
        $queryParams['key'] = $apiKey;
    }

    if(!empty($queryParams)){
        $rvUrl .= '?' . http_build_query($queryParams);
    }

    wp_enqueue_style('rv-style', plugin_dir_url(__FILE__) . 'css/responsivevoice.css');
    wp_enqueue_script('responsive-voice', $rvUrl, array(), null, array());
}

add_action('wp_enqueue_scripts', 'RV_load_scripts');