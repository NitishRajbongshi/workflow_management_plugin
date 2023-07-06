<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
/*
 * Plugin Name:       workflow_management_v1
 * Plugin URI:        http://localhost/workflow_management_system_v1/workflow_management/plugin
 * Description:       REST API to handle the workflow management system
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Version:           1.1.0
 * Author:            Nitish Rajbongshi
 * Author URI:        https://nitishrajbongshi.github.io/visit_portfolio/
 * License:           GPL v2 or later
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once __DIR__ . '/backend/Workflow.php';
include_once __DIR__ . '/backend/Step.php';
include_once __DIR__ . '/backend/Authentication.php';

register_activation_hook(__FILE__, 'my_plugin_activate');
register_uninstall_hook(__FILE__, 'my_plugin_uninstall');
include_once __DIR__ . '/includes/plugin-activator.php';
include_once __DIR__ . '/includes/plugin-deactivator.php';


include_once __DIR__ . '/routes/endpoints.php';


/**
 * Get all workflow
 */
function get_workflow()
{
    $obj = new Workflow();

    $workflow = $obj->get_all();

    if ($workflow == false) {
        return array(
            "message" => "No workflow found",
        );
    } else
        return $workflow;
}

/**
 * Get a single workflow by id
 */
function get_single_workflow_by_id(WP_REST_Request $request)
{
    $workflow_id = $request['id'];
    $obj = new Workflow();
    $obj->load_by_id($workflow_id);
    $res = $obj->get();
    return $res;
}

/**
 * Get single workflow
 */
function get_single_workflow_by_name(WP_REST_Request $request)
{
    $workflow_name = $request['name'];

    $obj = new Workflow();

    $obj->load($workflow_name);
    $res = $obj->get();
    return $res;
}

/**
 * Create a workflow
 */
function create_workflow(WP_REST_Request $request)
{
    $workflow_name = $request['name'];
    $workflow_desc = $request['description'];

    $obj = new Workflow();
    $obj->set_workflow_values($workflow_name, $workflow_desc);
    if ($obj->create()) {
        return array(
            "status" => "success",
            "message" => "Workflow added successfully",
        );
    } else {
        return array(
            "status" => "failed",
            "message" => "Workflow not added",
        );
    }
}

/**
 * update a workflow
 */
function update_workflow(WP_REST_Request $request)
{
    $workflow_name = $request['new_name'];
    $workflow_desc = $request['new_description'];

    $update_workflow = $request['old_name'];

    $obj = new Workflow();
    $obj->set_workflow_values($workflow_name, $workflow_desc);
    if ($obj->update($update_workflow)) {
        return array(
            "status" => "success",
            "message" => "Workflow updated successfully",
        );
    } else {
        return array(
            "status" => "failed",
            "message" => "Workflow not updated",
        );
    }
}


/**
 * Delete a workflow
 */
function delete_workflow(WP_REST_Request $request)
{
    $workflow_name = $request['name'];

    $obj = new Workflow();
    if ($obj->delete($workflow_name)) {
        return array(
            "status" => "success",
            "message" => "Workflow updated successfully",
        );
    } else {
        return array(
            "status" => "failed",
            "message" => "Workflow not deleted",
        );
    }
}

/**
 * Add a workflow step
 */
function add_workflow_step(WP_REST_Request $request)
{
    $workflow_name = $request['workflow'];
    $step_name = $request['name'];
    $step_description = $request['description'];
    $step_type = $request['type'];
    $step_handledby = $request['handledby'];
    $step_order = $request['order'];

    $obj = new Workflow();

    $obj->load_workflow($workflow_name);

    $step1 = new Step();
    $step1->set_values($step_name, $step_description, $step_type, $step_handledby);
    if ($obj->add_step_in_position($step_order, $step1)) {
        return array(
            "status" => "success",
            "message" => "Workflow step added successfully",
        );
    } else {
        return array(
            "status" => "failed",
            "message" => "Workflow step not added",
        );
    }
}

/**
 * Update a workflow step
 */
function update_workflow_step(WP_REST_Request $request)
{
    $workflow_name = $request['workflow'];
    $step_name_old = $request['name'];
    $step_name_new = $request['new_name'];
    $step_description = $request['new_description'];
    $step_order = $request['new_order'];
    $step_type = $request['new_type'];
    $step_handledby = $request['new_handledby'];

    $obj = new Workflow();

    $obj->set_step_values($step_name_new, $step_description, $step_order, $step_type, $step_handledby);
    if ($obj->update_step_by_name($workflow_name, $step_name_old)) {
        return array(
            "status" => "success",
            "message" => "Workflow step updated successfully",
        );
    } else {
        return array(
            "status" => "failed",
            "message" => "Workflow step not updated",
        );
    }
}


/**
 * Delete a workflow step
 */
function delete_workflow_step(WP_REST_Request $request)
{
    $workflow_name = $request['workflow'];
    $step_name = $request['name'];

    $obj = new Workflow();

    if ($obj->delete_step_by_name($workflow_name, $step_name)) {
        return array(
            "status" => "success",
            "message" => "Workflow step updated successfully",
        );
    } else {
        return array(
            "status" => "failed",
            "message" => "Workflow step not updated",
        );
    }
}

/**
 * Check for valid user
 */
function validate_user(WP_REST_Request $request)
{
    $email = $request['email'];
    $password = $request['password'];

    $login = new Authentication();
    $login->set_values($email, $password);
    if ($login->user_login())
        return true;
    return false;
}

/**
 * Check for valid user in a group
 */
function validate_group(WP_REST_Request $request)
{
    $email = $request['email'];
    $password = $request['password'];
    $group = $request['group'];

    if (!isset($group)) {
        return new WP_Error('unauthorized', 'Invalid credentials', array('status' => 401));
    }

    $login = new Authentication();

    $login->set_values($email, $password, $group);
    if ($login->group_login())
        return true;
    return false;
}


/**
 * Get a token for valid user
 */
function get_user_token($email, $password)
{
    $secret_key = 'workflow-management-system';

    $issued_at = time();
    $expiration_time = $issued_at + (10 * 60);

    $payload = array(
        'cur_time' => $issued_at,
        'exp_time' => $expiration_time,
        'email' => $email,
        'password' => $password,
    );

    $jwt_token = JWT::encode($payload, $secret_key, 'HS256');

    $token =  rest_ensure_response($jwt_token);
    return $token->data;
}

/**
 * Get a token for valid user
 */
function get_group_token($email, $password, $group)
{
    $secret_key = 'workflow-management-system';

    $issued_at = time();
    $expiration_time = $issued_at + (10 * 60);

    $payload = array(
        'cur_time' => $issued_at,
        'exp_time' => $expiration_time,
        'email' => $email,
        'password' => $password,
        'group' => $group
    );

    $jwt_token = JWT::encode($payload, $secret_key, 'HS256');

    $token =  rest_ensure_response($jwt_token);
    return $token->data;
}

/**
 * Return response on successfully login
 */
function user_login(WP_REST_Request $request)
{
    $email = $request['email'];
    $password = $request['password'];

    $token = get_user_token($email, $password);

    if (isset($token)) {
        return array(
            "login" => "true",
            "token" => $token,
        );
    }
}

/**
 * Return response on successfully login
 */
function group_login(WP_REST_Request $request)
{
    $email = $request['email'];
    $password = $request['password'];
    $group = $request['group'];

    $token = get_group_token($email, $password, $group);

    if (isset($token)) {
        return array(
            "login" => "true",
            "token" => $token,
        );
    }
}

/**
 * Function to validate the JWT token
 */
function validate_token(WP_REST_Request $request)
{
    try {
        $secret_key = 'workflow-management-system';

        $authorization = $request->get_header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);

        if (empty($authorization)) {
            return false;
        }

        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

        $email = $decoded->email;
        $password = $decoded->password;

        $expiration_time = $decoded->exp_time;
        $current_time = time();

        if($expiration_time < $current_time) 
            return new WP_Error('timeout', 'Token has expired', array('status' => 408));

        $login = new Authentication();
        $login->set_values($email, $password);

        if ($login->user_login())
            return true;
        return false;
    } catch (Exception $ex) {
        return false;
    }
}

/**
 * Function to validate the JWT token
 */
function validate_admin_token(WP_REST_Request $request)
{
    try {
        $secret_key = 'workflow-management-system';

        $authorization = $request->get_header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);

        if (empty($authorization)) {
            return false;
        }

        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

        $email = $decoded->email;
        $password = $decoded->password;
        $group = $decoded->group;

        $expiration_time = $decoded->exp_time;
        $current_time = time();

        if($expiration_time < $current_time) 
            return new WP_Error('timeout', 'Token has expired', array('status' => 408));
        
        if($group == "admin") {
            $login = new Authentication();
            $login->set_values($email, $password);
    
            if ($login->user_login())
                return true;
            return false;
        } 
        else {
            return new WP_Error('unauthorized', 'Not a authorized person', array('status' => 401));
        }

    } catch (Exception $ex) {
        return false;
    }
}

// function validate_token(WP_REST_Request $request)
// {
//     $authorization = $request->get_header('Authorization');
//     $token = str_replace('Bearer ', '', $authorization);

//     if (empty($authorization)) {
//         return false;
//     }

//     $endpoint = 'http://localhost/workflow_management_system_v1/wp-json/jwt-auth/v1/token/validate';

//     $headers = array(
//         'Content-Type: application/json',
//         'Authorization: Bearer ' . $token
//     );

//     $options = array(
//         'http' => array(
//             'header'  => $headers,
//             'method'  => 'POST'
//         )
//     );

//     $context  = stream_context_create($options);
//     $response = file_get_contents($endpoint, false, $context);

//     if ($response === false) {
//         return false;
//     } else {
//         $data = json_decode($response, true);
//         return true;
//     }
// }
