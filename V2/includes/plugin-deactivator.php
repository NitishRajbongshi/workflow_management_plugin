<?php
function my_plugin_uninstall()
{
    global $wpdb;

    $login = $wpdb->prefix . 'login';
    $workflow = $wpdb->prefix . 'workflow';
    $workflow_step = $wpdb->prefix . 'workflow_step';

    $wpdb->query("DROP TABLE IF EXISTS $login");
    $wpdb->query("DROP TABLE IF EXISTS $workflow");
    $wpdb->query("DROP TABLE IF EXISTS $workflow_step");
}