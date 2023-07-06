<?php

function my_plugin_activate()
{
    global $wpdb;

    $login = $wpdb->prefix . 'login';
    $employee_details = $wpdb->prefix . 'employee_details';
    $group_details = $wpdb->prefix . 'group_details';
    $employee_group = $wpdb->prefix . 'employee_group';
    $workflow = $wpdb->prefix . 'workflow';
    $workflow_step = $wpdb->prefix . 'workflow_step';
    $workflow_instance = $wpdb->prefix . 'workflow_instance';
    $status_code = $wpdb->prefix . 'status_code';
    $handling_request_person = $wpdb->prefix . 'handling_request_person';
    $handling_request_group = $wpdb->prefix . 'handling_request_group';

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

    $employee_detail = "
        CREATE TABLE if NOT EXISTS $employee_details (
        `employee_id` int(11) NOT NULL,
        `employee_name` varchar(255) NOT NULL,
        `employee_phone` varchar(15) NOT NULL,
        `FLA` varchar(255) NULL,
        `HR_id` varchar(255) NULL,
        FOREIGN KEY (`employee_id`) REFERENCES `wp_login`(`employee_id`)
        )  $charset_collate;
    ";
    dbDelta($employee_detail);

    $instance_table = "
        CREATE TABLE IF NOT EXISTS $workflow_instance (
        `instance_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `employee_id` int(11) NOT NULL,
        `workflow_id` int(11) NOT NULL,
        `instance_name` varchar(255) NOT NULL,
        `instance_description` varchar(255) NOT NULL,
        `instance_status` tinyint(1) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
        FOREIGN KEY (`employee_id`) REFERENCES `wp_login`(`employee_id`),
        FOREIGN KEY (`workflow_id`) REFERENCES `wp_workflow`(`workflow_id`)
        )  $charset_collate;
    ";
    dbDelta($instance_table);

    $status = "
        CREATE TABLE IF NOT EXISTS $status_code (
        `status_code` int(11) NOT NULL PRIMARY KEY,
        `status_name` varchar(50) NOT NULL,
        `status_description` varchar(255) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp()
        ) $charset_collate;
    ";
    dbDelta($status);

    $handle_person_request = "
        CREATE TABLE IF NOT EXISTS $handling_request_person (
        `request_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `instance_id` int(11) NOT NULL,
        `request_handler` int(11) NOT NULL,
        `status` int(11) NOT NULL DEFAULT 0,
        `remarks` varchar(255) NOT NULL DEFAULT 'Pending...',
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
        FOREIGN KEY (`instance_id`) REFERENCES `wp_workflow_instance`(`instance_id`),
        FOREIGN KEY (`request_handler`) REFERENCES `wp_login`(`employee_id`),
        FOREIGN KEY (`status`) REFERENCES `wp_status_code`(`status_code`)
        ) $charset_collate;
    ";

    dbDelta($handle_person_request);

    $handle_group_request = "
        CREATE TABLE $handling_request_group (
        `request_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `instance_id` int(11) NOT NULL,
        `group_id` int(11) NOT NULL,
        `request_handler` int(11) NOT NULL DEFAULT 0,
        `status` int(11) NOT NULL DEFAULT 0,
        `remarks` varchar(255) NOT NULL DEFAULT 'Pending...',
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
        FOREIGN KEY (`instance_id`) REFERENCES `wp_workflow_instance`(`instance_id`),
        FOREIGN KEY (`group_id`) REFERENCES `wp_group_details`(`group_id`),
        FOREIGN KEY (`status`) REFERENCES `wp_status_code`(`status_code`)
        ) $charset_collate;
    ";
    dbDelta($handle_group_request);
}
