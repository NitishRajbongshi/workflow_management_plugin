<?php
include_once __DIR__ . "/Workflow.php";
include_once __DIR__ . "/Step.php";
include_once __DIR__ . "/InstanceController.php";

class WorkflowInstance extends InstanceController
{
    private $conn;

    private $instance_table;
    private $workflow_table;
    private $employee_table;
    private $step_table;
    private $status_code_table;

    private $instance_id;
    private $employee_id;
    private $group_id;
    private $workflow_id;
    private $workflow_name;
    private $instance_name;
    private $instance_description;
    private $instance_status;
    private $status_code;
    private $step_number;
    private $created_at;
    private $updated_at;

    private $step_obj;
    private $workflow_obj;

    public function __construct()
    {
        $this->conn = connect_db();

        // calling the constructor of InstanceController
        parent::__construct();

        $data = json_decode(file_get_contents(__DIR__ . '/config.json'), TRUE);
        $this->instance_table = $data['instance_table'];
        $this->workflow_table = $data['workflow_table'];
        $this->employee_table = $data['employee_table'];
        $this->step_table = $data['step_table'];
        $this->status_code_table = $data['status_code'];

        $this->step_obj = new Step();
        $this->workflow_obj = new Workflow();
    }

    public function set_employee_id($employee_id)
    {
        InstanceController::set_handleby_id($employee_id);
        $this->employee_id = $employee_id;
    }

    public function get_employee_id()
    {
        return $this->employee_id;
    }

    public function set_group_id($group_id)
    {
        InstanceController::set_group_id($group_id);
        $this->group_id = $group_id;
    }

    public function get_group_id()
    {
        return $this->group_id;
    }

    /**
     * Set the user who is creating an instance of the workflow model
     */
    public function set_user_by_name($employee_name)
    {
        $name = htmlspecialchars(strip_tags($employee_name));
        $this->get_id_by_name($name);
    }

    /**
     * Get the id of the employee by providing the name
     */
    private function get_id_by_name($employee_name)
    {
        try {
            $query = '
                SELECT employee_id FROM ' . $this->employee_table . ' WHERE employee_name = :employee_name;
                ';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('employee_name', $employee_name);
            $stmt->execute();
            if ($stmt->rowCount() == 1) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->employee_id = $row['employee_id'];
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Set the workflow before create an instance of the workflow model
     */
    public function set_workflow($workflow_name)
    {
        $this->get_id_by_workflow_name($workflow_name);
    }

    /**
     * Get the id of the workflow by providing the name of the workflow
     */
    private function get_id_by_workflow_name($workflow_name)
    {
        try {
            $query = '
                SELECT workflow_id FROM ' . $this->workflow_table . ' WHERE workflow_name = :workflow_name;
                ';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('workflow_name', $workflow_name);
            $stmt->execute();
            if ($stmt->rowCount() == 1) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->workflow_id = $row['workflow_id'];
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }


    /**
     * Set the values for an instance
     */
    public function set_instance_values($name, $description, $status = 1)
    {
        $this->instance_name = htmlspecialchars(strip_tags($name));
        $this->instance_description = htmlspecialchars(strip_tags($description));
        $this->instance_status = htmlspecialchars(strip_tags($status));
    }

    public function set_remarks($remarks)
    {
        try {
            InstanceController::set_remarks($remarks);
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    /**
     * Creating a new instance
     */
    public function create()
    {
        try {
            $query = '
                    INSERT INTO ' . $this->instance_table . ' SET `employee_id` = :employee_id, `workflow_id` = :workflow_id, `instance_name` = :name, `instance_description` = :description, `instance_status`=:status
                ';
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam('employee_id', $this->employee_id);
            $stmt->bindParam('workflow_id', $this->workflow_id);
            $stmt->bindParam('name', $this->instance_name);
            $stmt->bindParam('description', $this->instance_description);
            $stmt->bindParam('status', $this->instance_status);

            if ($stmt->execute()) {
                $last_id = $this->conn->lastInsertId();
                $this->handle_instance($last_id);
                $this->load_instance($last_id);
                return true;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    /**
     * This function will create an instance controller 
     * According to the instance controller a person or a group can view a particular intance
     * which is only related to the person or the group 
     */
    private function handle_instance($instance_id)
    {
        try {
            // var_dump("Ready to create an instance handler");
            $query = "
            SELECT * FROM " . $this->instance_table . " WHERE instance_id = :instance_id;
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('instance_id', $instance_id);
            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // var_dump("Getting all information required to create an instance controller from the instance");
                $this->employee_id = $row['employee_id'];
                $this->instance_id = $row['instance_id'];
                $this->workflow_id = $row['workflow_id'];
                $this->instance_status = $row['instance_status'];

                // var_dump("Check for handler id to handle the step");
                // get id handler id who is responsible for the handling the step of the instance
                $handler_id = $this->step_obj->get_step_handler_id($this->employee_id, $this->workflow_id, $this->instance_status);

                // var_dump("Responsible handler id found : ", $handler_id);

                // check for either group or a single person 
                $is_group = $this->step_obj->is_group();

                // var_dump("The responsible handler id is a group:  ", $is_group);

                InstanceController::set_values($this->instance_id, $handler_id, $is_group);
                if (InstanceController::create()) {
                    // var_dump("All step completed");
                    return true;
                } else
                    return false;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    /**
     * Load the instance details 
     */
    // public function load($instance_id)
    // {
    //     try {
    //         $query = "
    //         SELECT * FROM " . $this->instance_table . " WHERE instance_id = :instance_id;
    //         ";

    //         $stmt = $this->conn->prepare($query);
    //         $stmt->bindParam('instance_id', $instance_id);

    //         if($stmt->execute()) {
    //             $row = $stmt->fetch(PDO::FETCH_ASSOC);
    //             $this->instance_name = $row['instance_name'];
    //             $this->instance_description = $row['instance_description'];
    //             $this->instance_status = $row['instance_status'];
    //             $this->workflow_name = $this->workflow_obj->get_name_by_id($row['workflow_id']);
    //         }
    //     } catch (PDOException $e) {
    //         echo json_encode($e);
    //         return false;
    //     }
    // }

    // public function show_instance_details()
    // {
    //     $output = "\nInstance name: " . $this->instance_name . "\nDescription: " . $this->instance_description . "\nWorkflow: " . $this->workflow_name . "\nStatus: " . $this->instance_status . "";
    //     echo $output;
    // }

    /**
     * Load function to load the current status of an instance
     */
    public function load_instance($id)
    {
        try {
            $this->instance_id = $id;
            InstanceController::set_instance_id($id);
            $query = "
                SELECT * FROM " . $this->instance_table . " WHERE instance_id = :instance_id;
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("instance_id", $id);
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        return array(
                            "instance_id" => $row['instance_id'],
                            "workflow_id" => $row['workflow_id'],
                            "employee_id" => $row['employee_id'],
                            "instance_name" => $row['instance_name'],
                            "instance_description" => $row['instance_description'],
                            "instance_status" => $row['instance_status'],
                            "created_at" => $row['created_at'],
                            "updated_at" => $row['updated_at'],
                            "workflow_name" => $this->workflow_obj->get_name_by_id($row['workflow_id'])
                        );
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Load function to load the current status of an instance
     */
    public function load_all_instance($id)
    {
        try {
            $this->instance_id = $id;
            InstanceController::set_instance_id($id);
            $query = "
                SELECT * FROM " . $this->instance_table . " WHERE employee_id = :instance_id;
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("instance_id", $id);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $instance_array = array();
                    foreach($instances as $instance) {
                        $instance_item = array();
                        $instance_item['instance_id'] = $instance['instance_id'];
                        $instance_item['workflow_id'] = $instance['workflow_id'];
                        $instance_item['instance_name'] = $instance['instance_name'];
                        $instance_item['instance_description'] = $instance['instance_description'];
                        $instance_item['instance_status'] = $instance['instance_status'];
                        $instance_item['created_at'] = $instance['created_at'];
                        $instance_item['updated_at'] = $instance['updated_at'];
                        $instance_item['workflow_name'] = $this->workflow_obj->get_name_by_id($instance['workflow_id']);
                        $instance_array[] = $instance_item;
                    }
                    return $instance_array;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Set the instance value
     */
    public function show_instance() {
        $output = "
        Intance Details:\n
        Instance Name     : ".$this->instance_name."
        Instance Desc     : ".$this->instance_description."
        Instance status   : ".$this->instance_status."
        Created           : ".$this->created_at."
        Last updated      : ".$this->updated_at."
        ";
        echo $output;
    }

    /**
     * Function to show details of workflow and steps
     */
    public function show_workflow() {
        $this->workflow_obj->load($this->workflow_name);
        $this->workflow_obj->print();
    }

    /**
     * Set the current status of the intance 
     */
    // public function set_current_status()
    // {
    //     try {
    //         $query = "
    //         SELECT w.workflow_name, s.* FROM " . $this->workflow_table . " w INNER JOIN " . $this->step_table . " s ON w.workflow_id = s.workflow_id WHERE w.workflow_id = :workflow_id AND step_order = :step_order;
    //         ";
    //         $stmt = $this->conn->prepare($query);
    //         $stmt->bindParam("workflow_id", $this->workflow_id);
    //         $stmt->bindParam("step_order", $this->instance_status);
    //         if ($stmt->execute()) {
    //             if ($stmt->rowCount() == 1) {
    //                 while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    //                     $this->step_obj->set_step_values('null', $row['step_name'], $row['step_description'], $this->instance_status, $row['step_type'], $row['request_handler']);
    //                 }
    //             }
    //         }
    //     } catch (PDOException $e) {
    //         echo json_encode($e);
    //     }
    // }



    /**
     * The below three function will handle the operation related to status of an instance
     */
    private function go_next_step()
    {
        try {
            // var_dump("Looking for the next step available");
            $updatedAt = date('Y-m-d H:i:s');
            $next_step = $this->instance_status + 1;
            // var_dump("Next step count ", $next_step);
            $total_steps = $this->workflow_obj->step_count($this->workflow_id);
            // var_dump("Total step available ", $total_steps);
            if ($next_step <= $total_steps) {
                // var_dump("Can go to next step");
                $query = "
                UPDATE " . $this->instance_table . " SET instance_status = :status, updated_at = :updated_at WHERE instance_id = :instance_id
                ";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam("instance_id", $this->instance_id);
                $stmt->bindParam("updated_at", $updatedAt);
                $stmt->bindParam("status", $next_step);
                if ($stmt->execute()) {
                    // var_dump("Move to the next step succesfully");
                    return true;
                } else {
                    // var_dump("Final step found");
                    return false;
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    private function go_previous_step()
    {
        try {
            // var_dump("Looking for the previous step");
            if ($this->instance_status > 1) {
                $updatedAt = date('Y-m-d H:i:s');
                $next_step = $this->instance_status - 1;
                $query = "
                    UPDATE " . $this->instance_table . " SET instance_status = :status, updated_at = :updated_at WHERE instance_id = :instance_id
                 ";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam("instance_id", $this->instance_id);
                $stmt->bindParam("updated_at", $updatedAt);
                $stmt->bindParam("status", $next_step);
                if ($stmt->execute())
                    return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    private function go_particular_step($step_number)
    {
        try {
            // var_dump("Going to a particular step.");
            // $total_steps = $this->workflow_obj->step_count($this->workflow_id);
            // var_dump("Current instance status: ", $this->instance_status);
            $updatedAt = date('Y-m-d H:i:s');
            if ($step_number >= 1 and $step_number <= $this->instance_status) {
                $query = "
                    UPDATE " . $this->instance_table . " SET instance_status = :status, updated_at = :updated_at WHERE instance_id = :instance_id
                 ";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam("instance_id", $this->instance_id);
                $stmt->bindParam("updated_at", $updatedAt);
                $stmt->bindParam("status", $step_number);
                if ($stmt->execute())
                    return true;
                else
                    return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }


    /**
     * Show instance to a perticular person
     */
    public function show_single_instance()
    {
        try {
            $employee_id = $this->get_employee_id();
            $response = InstanceController::load_single_instance($employee_id);
            return $response;
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    /**
     * Show instance to a perticular group
     */
    public function show_group_instance()
    {
        try {
            $group_id = $this->get_group_id();
            InstanceController::load_group_instance($group_id);
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    // public function set_status($status)
    // {
    //     $this->status_code = $this->get_status_code($status);
    //     // var_dump("this is the status code: ", $this->status_code);
    //     InstanceController::set_status($this->status_code);
    // }

    /**
     * Update the intance by new status code alone with the ack from the handler
     */
    public function update()
    {
        try {
            if (InstanceController::can_update()) {
                if (InstanceController::update())
                    $this->update_instance();
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    private function update_instance()
    {
        try {
            // // var_dump("Check for status code: ", $this->status_code);
            if ($this->status_code == 1) {
                if ($this->go_next_step())
                    if ($this->handle_instance($this->instance_id))
                        return true;
                    else
                        return false;
            }

            if ($this->status_code == -2) {
                if ($this->go_previous_step())
                    if ($this->handle_instance($this->instance_id))
                        return true;
                    else
                        return false;
            }

            if ($this->status_code == 2) {
                if ($this->go_particular_step($this->step_number))
                    if ($this->handle_instance($this->instance_id))
                        return true;
                    else
                        return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    /**
     * Getting the status code name by providing the status number
     */
    // private function get_status_code($status)
    // {
    //     try {
    //         $query = "
    //             SELECT * FROM " . $this->status_code_table . " WHERE status_name = :status_name;
    //         ";
    //         $stmt = $this->conn->prepare($query);
    //         $stmt->bindParam("status_name", $status);
    //         $stmt->execute();
    //         if ($stmt->rowCount() == 1) {
    //             $row = $stmt->fetch(PDO::FETCH_ASSOC);
    //             return $row['status_code'];
    //         }
    //     } catch (PDOException $e) {
    //         echo json_encode($e);
    //         return false;
    //     }
    // }

    /**
     * Accept the step
     */
    public function accept()
    {
        $this->status_code = 1;
        InstanceController::set_status($this->status_code);
    }

    /**
     * Reject the step
     */
    public function reject()
    {
        $this->status_code = -1;
        InstanceController::set_status($this->status_code);
    }

    /**
     * Reject and rollback to the previous step
     */
    public function rollback()
    {
        $this->status_code = -2;
        InstanceController::set_status($this->status_code);
    }

    /**
     * goto a particular step
     */
    public function goto($step_number)
    {
        $this->status_code = 2;
        $this->step_number = $step_number;
        InstanceController::set_step($step_number);
        InstanceController::set_status($this->status_code);
    }


    /**
     * Get the current step
     */
    public function current_step() {
        $this->workflow_obj->get_step_details_by_id($this->workflow_id, $this->instance_status);
    }

    /**
     * Get the current step
     */
    public function previous_step() {
        $previous_step = $this->instance_status - 1;
        $this->workflow_obj->get_step_details_by_id($this->workflow_id, $previous_step);
    }

    /**
     * Get the current step
     */
    public function next_step() {
        $next_step = $this->instance_status + 1;
        $this->workflow_obj->get_step_details_by_id($this->workflow_id, $next_step);
    }
    /**
     * Function to get Status of a instance
     */
    public function logs() {
        InstanceController::logs();
    }
}
