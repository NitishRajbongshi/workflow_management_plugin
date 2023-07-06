<?php

function my_plugin_activate()
{
    global $wpdb;

    $login = $wpdb->prefix . 'login';
    $group_details = $wpdb->prefix . 'group_details';
    $employee_group = $wpdb->prefix . 'employee_group';
    $workflow = $wpdb->prefix . 'workflow';
    $workflow_step = $wpdb->prefix . 'workflow_step';

    $charset_collate = $wpdb->get_charset_collate();

    $login_table = "
        CREATE TABLE IF NOT EXISTS $login ( 
        `employee_id` int NOT NULL PRIMARY KEY, 
        `employee_email` varchar(90) NOT NULL, 
        `password` varchar(255) NOT NULL, 
        UNIQUE (`employee_email`)
        ) $charset_collate;
    ";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($login_table);

    $group_details_table = "
        CREATE TABLE IF NOT EXISTS $group_details ( 
        `group_id` int NOT NULL PRIMARY KEY, 
        `group_name` varchar(50) NOT NULL, 
        `group_description` varchar(255) NOT NULL, 
        UNIQUE (`group_name`)
        ) $charset_collate;
    ";
    dbDelta($group_details_table);

    $employee_group_table = "
        CREATE TABLE IF NOT EXISTS $employee_group (
        `employee_id` int NOT NULL,
        `group_id` int NOT NULL,
        FOREIGN KEY (`employee_id`) REFERENCES `wp_login`(`employee_id`),
        FOREIGN KEY (`group_id`) REFERENCES `wp_group_details` (`group_id`)
        ) $charset_collate;
    "; 
    dbDelta($employee_group_table);

    $workflow_table = "
        CREATE TABLE IF NOT EXISTS $workflow (
        `workflow_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `workflow_name` varchar(100) NOT NULL,
        `workflow_description` varchar(255) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
        UNIQUE(`workflow_name`)
        ) $charset_collate;
    ";
    dbDelta($workflow_table);

    $step_table = "
        CREATE TABLE IF NOT EXISTS $workflow_step (
        `step_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `workflow_id` int(11) NOT NULL,
        `step_name` varchar(150) NOT NULL,
        `step_description` varchar(255) NOT NULL,
        `step_order` int(11) NOT NULL,
        `step_type` varchar(50) NOT NULL,
        `step_handleby` varchar(100) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
         FOREIGN KEY (`workflow_id`) REFERENCES `wp_workflow`(`workflow_id`)
        ) $charset_collate;
    ";
    dbDelta($step_table);

    
}
