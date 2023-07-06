<?php

/**
 * Creating the endpoint for the REST API
 */

add_action('rest_api_init', 'handle_workflow');

function handle_workflow()
{
    // user login
    register_rest_route(
        'workflow-management/v1',
        '/workflow/login',
        array(
            'methods' => 'POST',
            'callback' => 'user_login',
            'permission_callback' => 'validate_user'
        )
    );

    // group login
    register_rest_route(
        'workflow-management/v1',
        '/workflow/login/group',
        array(
            'methods' => 'POST',
            'callback' => 'group_login',
            'permission_callback' => 'validate_group'
        )
    );

    // get all workflow
    register_rest_route(
        'workflow-management/v1',
        '/workflow/get',
        array(
            'methods' => 'GET',
            'callback' => 'get_workflow',
            'permission_callback' => 'validate_token'
        )
    );

    // get single workflow by id
    register_rest_route(
        'workflow-management/v1',
        '/workflow/get/(?P<id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'get_single_workflow_by_id',
            'permission_callback' => 'validate_token'
        )
    );

    // get single workflow by name
    register_rest_route(
        'workflow-management/v1',
        '/workflow/get/',
        array(
            'methods' => 'POST',
            'callback' => 'get_single_workflow_by_name',
            'permission_callback' => 'validate_token'
        )
    );

    // create workflow
    register_rest_route(
        'workflow-management/v1',
        '/workflow/create',
        array(
            'methods' => 'POST',
            'callback' => 'create_workflow',
            'permission_callback' => 'validate_admin_token'
        )
    );

    // update workflow
    register_rest_route(
        'workflow-management/v1',
        '/workflow/update',
        array(
            'methods' => 'PUT',
            'callback' => 'update_workflow',
            'permission_callback' => 'validate_admin_token'
        )
    );

    // delete a workflow
    register_rest_route(
        'workflow-management/v1',
        '/workflow/delete',
        array(
            'methods' => 'DELETE',
            'callback' => 'delete_workflow',
            'permission_callback' => 'validate_admin_token'
        )
    );

    // add a workflow step
    register_rest_route(
        'workflow-management/v1',
        '/workflow/step',
        array(
            'methods' => 'POST',
            'callback' => 'add_workflow_step',
            'permission_callback' => 'validate_admin_token'
        )
    );

    // delete a workflow step
    register_rest_route(
        'workflow-management/v1',
        '/workflow/step',
        array(
            'methods' => 'DELETE',
            'callback' => 'delete_workflow_step',
            'permission_callback' => 'validate_admin_token'
        )
    );

    // update a workflow step
    register_rest_route(
        'workflow-management/v1',
        '/workflow/step',
        array(
            'methods' => 'PUT',
            'callback' => 'update_workflow_step',
            'permission_callback' => 'validate_admin_token'
        )
    );

    // get a workflow step
    register_rest_route(
        'workflow-management/v1',
        '/workflow/step',
        array(
            'methods' => 'POST',
            'callback' => 'get_workflow_step',
            'permission_callback' => 'validate_token'
        )
    );


    // create workflow instance
    register_rest_route(
        'workflow-management/v1',
        '/workflow/instance/create',
        array(
            'methods' => 'POST',
            'callback' => 'create_instance',
            'permission_callback' => 'validate_token'
        )
    );

    // get workflow instance for the request person
    register_rest_route(
        'workflow-management/v1',
        '/workflow/instance/get',
        array(
            'methods' => 'POST',
            'callback' => 'get_all_instance',
            'permission_callback' => 'validate_token'
        )
    );

    // get workflow instance
    register_rest_route(
        'workflow-management/v1',
        '/workflow/instance/get/(?P<id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'get_single_instance',
            'permission_callback' => 'validate_token'
        )
    );

    // show all the request to a person
    register_rest_route(
        'workflow-management/v1',
        '/workflow/instance/show',
        array(
            'methods' => 'POST',
            'callback' => 'show_instance_to_handler_person',
            'permission_callback' => 'validate_token'
        )
    );
}
