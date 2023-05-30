<?php
include_once __DIR__ . "/connection.php";

class Step
{
    private $conn;
    private $id;
    private $name;
    private $description;
    private $workflow_id;
    private $order;
    private $type;
    private $handledby;

    private $step_table;
    private $group_table;
    private $employee_table;

    private $is_group = false;

    protected function set_step_name($step_name)
    {
        $this->name = $step_name;
    }

    protected function get_step_name()
    {
        return $this->name;
    }

    protected function set_workflow_id($workflow_id)
    {
        $this->workflow_id = $workflow_id;
    }

    protected function get_workflow_id()
    {
        return $this->workflow_id;
    }

    protected function set_step_description($step_description)
    {
        $this->description = $step_description;
    }

    protected function get_step_description()
    {
        return $this->description;
    }

    protected function set_step_order($step_order)
    {
        $this->order = $step_order;
    }

    protected function get_step_order()
    {
        return $this->order;
    }

    protected function set_step_type($step_type)
    {
        $this->type = $step_type;
    }

    protected function get_step_type()
    {
        return $this->type;
    }

    protected function set_step_handleby($step_handledby)
    {
        $this->handledby = $step_handledby;
    }

    protected function get_step_handleby()
    {
        return $this->handledby;
    }

    public function __construct()
    {
        $this->conn = connect_db();
        $data = json_decode(file_get_contents(__DIR__ . '/config.json'), TRUE);
        $this->step_table = $data['step_table'];
        $this->employee_table = $data['employee_table'];
        $this->group_table = $data['group_table'];
    }

    public function set_step_values($workflow_id, $step_name, $step_description, $step_order, $step_type, $step_handledby)
    {
        $this->workflow_id = $workflow_id;
        $this->name = $step_name;
        $this->description = $step_description;
        $this->order = $step_order;
        $this->type = $step_type;
        $this->handledby = $step_handledby;
    }

    public function set_values($step_name, $step_description, $step_type, $step_handledby)
    {
        $this->name = $step_name;
        $this->description = $step_description;
        $this->type = $step_type;
        $this->handledby = $step_handledby;
    }

    /**
     * This function is responsible for create a new step in the database internally.
     * This function can not call directly and only posible to call using add_step() method.
     */
    private function create()
    {
        try {
            $query = "
            INSERT INTO " . $this->step_table . " SET workflow_id = :workflow_id, step_name = :step_name, step_description = :step_description, step_order = :step_order, step_type = :step_type, step_handleby = :step_handleby
            ";

            $stmt = $this->conn->prepare($query);

            // clean data and bind parameter to avoid SQL injection and make sql query secure
            // clean data
            $name = htmlspecialchars(strip_tags($this->name));
            $description = htmlspecialchars(strip_tags($this->description));
            $workflow_id = htmlspecialchars(strip_tags($this->workflow_id));
            $order = htmlspecialchars(strip_tags($this->order));
            $type = htmlspecialchars(strip_tags($this->type));
            $handleby = htmlspecialchars(strip_tags($this->handledby));

            // bind data
            $stmt->bindParam("step_name", $name);
            $stmt->bindParam("step_description", $description);
            $stmt->bindParam("workflow_id", $workflow_id);
            $stmt->bindParam("step_order", $order);
            $stmt->bindParam("step_type", $type);
            $stmt->bindParam("step_handleby", $handleby);

            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Function to find the duplicacy of step name under same workflow
     */
    private function is_name_duplicacy()
    {
        try {
            $query = "
            SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id AND step_name = :step_name
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("workflow_id", $this->workflow_id);
            $stmt->bindParam("step_name", $this->name);
            $stmt->execute();
            if ($stmt->rowCount() == 0)
                return false;
            else
                return true;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Calling this function whenever new step has to inserted.
     * This function will call the create() method internally to perform the create operation.
     */
    protected function add_steps()
    {
        try {
            $new_order = $this->order;

            if ($this->is_name_duplicacy())
                return false;
            else {
                $select_query = "
                SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id
                ";

                $stmt = $this->conn->prepare($select_query);
                $stmt->bindParam("workflow_id", $this->workflow_id);
                if ($stmt->execute()) {
                    $num_of_rows = $stmt->rowCount();
                    if ($num_of_rows == 0) {
                        // add the very first step 
                        if ($this->create()) {
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $steps[] = $row;
                        }

                        foreach ($steps as $step) {
                            if ($step['step_order'] >= $new_order) {
                                $stepID = $step['step_id'];
                                $new_step_order = $step['step_order'] + 1;
                                $update_sql = "
                                    UPDATE " . $this->step_table . " SET step_order = :step_order where step_id = :step_id;
                                ";
                                $stmt = $this->conn->prepare($update_sql);
                                $stmt->bindParam("step_order", $new_step_order);
                                $stmt->bindParam("step_id", $stepID);
                                if ($stmt->execute())
                                    continue;
                                else
                                    break;
                            }
                        }

                        // add the new step in the correct position
                        if ($this->create()) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * This function is responsible for load a workflow step that belongs to a particular workflow
     * It will return the available rows to the workflow class
     */
    protected function load_step($workflow_id)
    {
        try {
            $query = "
                SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id ORDER BY step_order
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('workflow_id', $workflow_id);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0)
                    return $stmt;
                else
                    return null;
            } else
                return null;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * DELETE
     * Delete a step by name
     */
    protected function delete_step_by_name($workflow_id, $step_name)
    {
        try {
            $step_id = $this->get_step_id($workflow_id, $step_name);
            if ($this->delete_step_by_id($step_id))
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Delete the step by the id
     */
    protected function delete_step_by_id($id)
    {
        try {
            $search_query = '
                SELECT * FROM ' . $this->step_table . ' WHERE step_id = :step_id;
            ';

            $stmt = $this->conn->prepare($search_query);
            $stmt->bindParam('step_id', $id);
            $stmt->execute();
            $row_count = $stmt->rowCount();

            if ($row_count) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $workflow_id = $row['workflow_id'];
                    $step_order = $row['step_order'];

                    // delete the steps
                    $delete_query = "
                        DELETE FROM " . $this->step_table . " WHERE workflow_id = :workflow_id AND step_id = :step_id;
                    ";

                    // prepare, bind and execute
                    $delete_stmt = $this->conn->prepare($delete_query);
                    $delete_stmt->bindParam("workflow_id", $workflow_id);
                    $delete_stmt->bindParam("step_id", $id);

                    // if successfully deleted it will update further steps
                    if ($delete_stmt->execute()) {

                        // update the steps after deleted
                        $update_query = "
                        UPDATE " . $this->step_table . " SET `step_order` = `step_order` - 1 WHERE workflow_id = :workflow_id AND step_order > :step_order
                        ";

                        $update_stmt = $this->conn->prepare($update_query);
                        $update_stmt->bindParam("workflow_id", $workflow_id);
                        $update_stmt->bindParam("step_order", $step_order);

                        if ($update_stmt->execute()) {
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * UPDATE
     * Update the step by name
     */
    protected function update_step_by_name($workflow_id, $step_name)
    {
        try {
            $step_id = $this->get_step_id($workflow_id, $step_name);
            if ($this->update_step_by_id($step_id))
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
            return false;
        }
    }

    /**
     * Update the step by id
     */
    protected function update_step_by_id($id)
    {
        try {
            $select_query = '
            SELECT * FROM ' . $this->step_table . ' WHERE step_id = :step_id;
            ';

            $select_stmt = $this->conn->prepare($select_query);
            $select_stmt->bindParam('step_id', $id);
            $select_stmt->execute();
            $row_count = $select_stmt->rowCount();

            if ($row_count == 1) {
                $update_query = "
                UPDATE " . $this->step_table . " SET step_name = :step_name, step_description = :step_description, step_order = :step_order, step_type = :step_type, step_handleby = :step_handleby WHERE step_id = :step_id;
                ";

                $stmt = $this->conn->prepare($update_query);

                // clean data
                $step_name = htmlspecialchars(strip_tags($this->name));
                $step_description = htmlspecialchars(strip_tags($this->description));
                $step_order = htmlspecialchars(strip_tags($this->order));
                $step_type = htmlspecialchars(strip_tags($this->type));
                $step_handleby = htmlspecialchars(strip_tags($this->handledby));

                // bind data
                $stmt->bindParam("step_id", $id);
                $stmt->bindParam("step_name", $step_name);
                $stmt->bindParam("step_description", $step_description);
                $stmt->bindParam("step_order", $step_order);
                $stmt->bindParam("step_type", $step_type);
                $stmt->bindParam("step_handleby", $step_handleby);

                if ($stmt->execute())
                    return true;
                else
                    return false;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * This function is responsible for handling deletion annomaly
     * This function will called when a workflow is deleted from the database
     * Since the workflow id is foreign key so step also be deleted on deleted the workflow
     */
    protected function delete_steps($workflow_id)
    {
        try {
            $delete_query = "
                DELETE FROM " . $this->step_table . " WHERE workflow_id = :workflow_id;
            ";

            $stmt = $this->conn->prepare($delete_query);
            $stmt->bindParam('workflow_id', $workflow_id);
            if ($stmt->execute())
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * This function is called whenever we need to find out the step id of a step
     * This is private function can not called by outside
     */
    private function get_step_id($workflow_id, $step_name)
    {
        try {
            $query = '
            SELECT step_id FROM ' . $this->step_table . ' WHERE step_name = :step_name AND workflow_id = :workflow_id;
            ';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('step_name', $step_name);
            $stmt->bindParam('workflow_id', $workflow_id);
            $stmt->execute();
            if ($stmt->rowCount() == 1) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->id = $row['step_id'];
                    return $row['step_id'];
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Function to load and get the step details of a step
     */
    protected function get_step_details($workflow_id, $step_name)
    {
        try {
            $this->get_step_id($workflow_id, $step_name);
            $query = "
                SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id AND step_id = :step_id;
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('workflow_id', $workflow_id);
            $stmt->bindParam('step_id', $this->id);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->id = $row['step_id'];
                    $this->name = $row['step_name'];
                    $this->description = $row['step_description'];
                    $this->order = $row['step_order'];
                    $this->type = $row['step_type'];
                    $this->handledby = $row['step_handleby'];
                }
                $this->display_step_details();
                return true;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    protected function get_step_details_by_id($workflow_id, $step_order)
    {
        try {
            $query = "
                SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id AND step_order = :step_order;
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('workflow_id', $workflow_id);
            $stmt->bindParam('step_order', $step_order);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->id = $row['step_id'];
                    $this->name = $row['step_name'];
                    $this->description = $row['step_description'];
                    $this->order = $row['step_order'];
                    $this->type = $row['step_type'];
                    $this->handledby = $row['step_handleby'];
                }
                $this->display_step_details();
                return true;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Function to display the related data to a step
     *  */
    private function display_step_details()
    {
        try {
            $output = "\n\nStep ID: " . $this->id . "\nStep Name: " . $this->name . "\nStep Order: " . $this->order . "\nStep type: " . $this->type . "\nStep handledby: " . $this->handledby . "";
            echo $output;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }


    /**
     * Adding a step into the database
     * It takes position value and the step object
     */
    protected function add_step_in_position($postion, $step)
    {
        try {
            $this->order = $postion;
            $this->name = $step->get_step_name();
            $this->description = $step->get_step_description();
            $this->type = $step->get_step_type();
            $this->handledby = $step->get_step_handleby();
            if ($this->add_steps())
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }


    /**
     * Find the number of step available for a workflow
     */
    protected function steps_count($workflow_id)
    {
        $query = "
            SELECT COUNT(*) AS total_step FROM " . $this->step_table . " WHERE workflow_id = :workflow_id
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam('workflow_id', $workflow_id);
        if ($stmt->execute()) {
            $row = $stmt->fetch();
            $total_steps = $row['total_step'];
            return $total_steps;
        }
    }

    /**
     * Function to find who is responsible for handling the function
     * 
     */
    public function get_step_handler_id($employee_id, $workflow_id, $step_order)
    {
        try {
            $query = "
                SELECT * FROM " . $this->step_table . " WHERE workflow_id = :workflow_id AND step_order = :step_order;
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("workflow_id", $workflow_id);
            $stmt->bindParam("step_order", $step_order);
            $stmt->execute();
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $type = $row['step_type'];
                $step_handleby = $row['step_handleby'];
                if ($type == 'custom') {
                    return $step_handleby;
                } elseif ($type == 'group') {
                    $this->is_group = true;
                    return $this->get_group_id($step_handleby);
                } else {
                    $person_id = $this->get_person_id($employee_id, $step_handleby);
                    return $person_id;
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Find group id and person id
     */
    private function get_group_id($group_name)
    {
        try {
            $query = "
                SELECT group_id FROM " . $this->group_table . " WHERE group_name = :group_name;
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("group_name", $group_name);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['group_id'];
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    private function get_person_id($employee_id, $person)
    {
        try {
            $query = "
                SELECT * FROM " . $this->employee_table . " WHERE employee_id = :employee_id;
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("employee_id", $employee_id);
            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row[$person];
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Find step handler is a person or a group 
     */
    public function is_group()
    {
        $status =  $this->is_group;
        $this->is_group = false;
        return $status;
    }
}