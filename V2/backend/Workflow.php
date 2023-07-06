<?php
include_once __DIR__ . "/connection.php";
include_once __DIR__ . "/Step.php";

class Workflow extends Step
{
    private $conn;
    private $id;
    private $name;
    private $description;
    private $created_at;
    private $steps = array();
    private $step_count = 0;
    private $workflow_table;

    public function __construct()
    {
        parent::__construct();

        $this->conn = connect_db();
        $data = json_decode(file_get_contents(__DIR__ . '/config.json'), TRUE);
        $this->workflow_table = $data['workflow_table'];
    }

    public function set_name($workflow_name)
    {
        $this->name = $workflow_name;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_description($workflow_description)
    {
        $this->description = $workflow_description;
    }

    public function get_description()
    {
        return $this->description;
    }

    /**
     * To create a workflow, this function will take the responsibility to set the values which are required for a workflow
     */
    public function set_workflow_values($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * This function is resonsible for getting all the available  workflow
     */
    public function get_all()
    {
        try {
            $query = "
                SELECT * FROM " . $this->workflow_table . " WHERE 1;
                ";

            $stmt = $this->conn->prepare($query);
            $workflow_array = [];
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $items = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);

                        $items = array(
                            "workflow_id" => $workflow_id,
                            "workflow_title" => $workflow_name,
                            "workflow_description" => $workflow_description,
                            "created_at" => $created_at,
                            "updated_at" => $created_at,
                        );

                        // push data to the array
                        array_push($workflow_array, $items);
                    }

                    return $workflow_array;
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return json_encode($e);
        }
    }

    /**
     * This function is resonsible for creating a new workflow
     * It will create the workflow according to the values assign using the set_values() method
     */
    public function create()
    {
        try {
            // check name duplicacy before add a workflow
            if ($this->is_name_duplicacy()) {
                return false;
            } else {
                $query = "
                INSERT INTO " . $this->workflow_table . " SET workflow_name = :workflow_name, workflow_description = :workflow_description
            ";

                $stmt = $this->conn->prepare($query);

                $name = htmlspecialchars(strip_tags($this->name));
                $description = htmlspecialchars(strip_tags($this->description));

                $stmt->bindParam("workflow_name", $name);
                $stmt->bindParam("workflow_description", $description);

                if ($stmt->execute()) {
                    return true;
                } else {
                    return false;
                }
            }
        } catch (PDOException $e) {
            return json_encode($e);
        }
    }


    /**
     * Function to find the duplicacy of step name under same workflow
     */
    private function is_name_duplicacy()
    {
        try {
            $query = "
            SELECT * FROM " . $this->workflow_table . " WHERE workflow_name = :workflow_name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("workflow_name", $this->name);
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
     * function responsible for update an existed workflow by providing the name
     * First, calling the set_value() method to set the values
     * Call this function to update the workflow according to the values
     */
    public function update($workflow_name)
    {
        try {
            $this->get_id_by_name($workflow_name);

            $query = "
                UPDATE " . $this->workflow_table . " SET workflow_name = :workflow_name, workflow_description = :workflow_description WHERE workflow_id = :workflow_id;
            ";

            $stmt = $this->conn->prepare($query);

            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->description = htmlspecialchars(strip_tags($this->description));

            $stmt->bindParam("workflow_id", $this->id);
            $stmt->bindParam("workflow_name", $this->name);
            $stmt->bindParam("workflow_description", $this->description);

            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1)
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
     * This function is responsible for delete a workflow and the steps that belongs to that workflow
     * Since the workflow id is the foreign constained for the step table so first it will delete the steps
     * to prevent the deletion annomaly
     */
    public function delete($workflow_name)
    {
        try {
            $this->get_id_by_name($workflow_name);

            if (Step::delete_steps($this->id)) {
                $query = "
                DELETE FROM " . $this->workflow_table . " WHERE workflow_name = :workflow_name
                ";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam("workflow_name", $workflow_name);

                if ($stmt->execute()) {
                    if ($stmt->rowCount() == 1)
                        return true;
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
     * This function is responsible for load a particular workflow if exists along with the 
     * available steps coresponding to that workflow
     */
    public function load($name)
    {
        try {
            $get_workflow = "
                SELECT * FROM " . $this->workflow_table . " WHERE workflow_name = :workflow_name
            ";

            $stmt = $this->conn->prepare($get_workflow);
            $stmt->bindParam(':workflow_name', $name);

            if ($stmt->execute()) {
                $num_of_rows = $stmt->rowCount();
                if ($num_of_rows > 0) {
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($results as $r) {
                        $this->id = $r['workflow_id'];
                        $this->name = $r['workflow_name'];
                        $this->description = $r['workflow_description'];
                        $this->created_at = $r['created_at'];
                    }

                    // get the steps
                    $step_stmt = Step::load_step($this->id);
                    if ($step_stmt != null) {
                        $result = $step_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // get each step and insert into the step array
                        foreach ($result as $step) {
                            $step_array = array(
                                "step_id" => $step['step_id'],
                                "step_name" => $step['step_name'],
                                "step_description" => $step['step_description'],
                                "step_order" => $step['step_order'],
                                "step_type" => $step['step_type'],
                                "step_handleby" => $step['step_handleby'],
                            );

                            $this->steps[] = $step_array;
                            $this->step_count++;
                        }
                    }
                } else {
                    return false;
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * This function is responsible for load a particular workflow if exists along with the 
     * available steps coresponding to that workflow
     */
    public function load_by_id($id)
    {
        try {
            $get_workflow = "
                SELECT * FROM " . $this->workflow_table . " WHERE workflow_id = :workflow_id
            ";

            $stmt = $this->conn->prepare($get_workflow);
            $stmt->bindParam(':workflow_id', $id);

            if ($stmt->execute()) {
                $num_of_rows = $stmt->rowCount();
                if ($num_of_rows > 0) {
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($results as $r) {
                        $this->id = $r['workflow_id'];
                        $this->name = $r['workflow_name'];
                        $this->description = $r['workflow_description'];
                        $this->created_at = $r['created_at'];
                    }

                    // get the steps
                    $step_stmt = Step::load_step($this->id);
                    if ($step_stmt != null) {
                        $result = $step_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // get each step and insert into the step array
                        foreach ($result as $step) {
                            $step_array = array(
                                "step_id" => $step['step_id'],
                                "step_name" => $step['step_name'],
                                "step_description" => $step['step_description'],
                                "step_order" => $step['step_order'],
                                "step_type" => $step['step_type'],
                                "step_handleby" => $step['step_handleby'],
                            );

                            $this->steps[] = $step_array;
                            $this->step_count++;
                        }
                    }
                } else {
                    return false;
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }
    /**
     * Display the output regarding the workflow and the steps
     */
    public function get()
    {
        if (is_null($this->id))
            return array("message" => "No workflow found");
        else {
            return array(
                "id" => $this->id,
                "name" => $this->name,
                "description" => $this->description,
                "creation Time" => $this->created_at,
                "steps" => $this->steps
            );
        }
    }

    /**
     * This function will only display the details related to one workflow
     */
    public function get_workflow($workflow_name)
    {
        try {
            $this->get_id_by_name($workflow_name);
            $query = "
                SELECT * FROM " . $this->workflow_table . " WHERE workflow_id = :workflow_id
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('workflow_id', $this->id);
            $stmt->execute();
            if ($stmt->rowCount() == 1) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->name = $row['workflow_name'];
                    $this->description = $row['workflow_description'];
                    $this->created_at = $row['created_at'];
                }
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * This function will set the values for a step
     * Before create and update a step need to set the step values
     */
    public function set_step_values($step_name, $step_description, $step_order, $step_type, $step_handleby, $workflow_name = null)
    {
        try {
            if (is_null($workflow_name)) {
                $this->id = null;
            } else {
                /**
                 * since the workflow id is the foreign key
                 * so first have to fetch workflow id by using given workflow_name 
                 */
                $this->get_id_by_name($workflow_name);
            }

            // call function to add the step 
            Step::set_step_values($this->id, $step_name, $step_description, $step_order, $step_type, $step_handleby);
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Adding a new steps by calling this function
     * First need to set the required values to add the step by calling the step_step_values() method
     */
    public function add_step()
    {
        try {
            if (Step::add_steps()) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * UPDATE STEP
     * update the step by using the id of the step
     */
    public function update_step_by_id($step_id)
    {
        try {
            if (Step::update_step_by_id($step_id)) {
                return true;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * update the step by using the name of the step
     * This function takes two parameter, workflow name should be there to find the exact step 
     */
    public function update_step_by_name($workflow_name, $step_name)
    {
        try {
            $this->get_id_by_name($workflow_name);
            if (Step::update_step_by_name($this->id, $step_name))
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * DELETE STEP
     * Delete a step by id
     */
    public function delete_step_by_id($step_id)
    {
        try {
            if (Step::delete_step_by_id($step_id)) {
                return true;
            } else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Delete a step by name
     */
    public function delete_step_by_name($workflow_name, $step_name)
    {
        try {
            $this->get_id_by_name($workflow_name);
            if (Step::delete_step_by_name($this->id, $step_name))
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Get the details about a single workflow only
     */
    public function get_step_details($workflow_name, $step_name)
    {
        $this->get_id_by_name($workflow_name);
        // $this->load_workflow($workflow_name);
        // $this->print_workflow();
        if (Step::get_step_details($this->id, $step_name))
            return true;
        else
            return false;
    }

    public function get_step_details_by_id($workflow_id, $step_order)
    {
        if (Step::get_step_details_by_id($workflow_id, $step_order))
            return true;
        else
            return false;
    }

    /**
     * Workflow id may pass to step class due to load step or add step
     * THis function will take the responsibility
     */
    public function load_workflow($workflow_name)
    {
        try {
            $this->get_id_by_name($workflow_name);
            Step::set_workflow_id($this->id);
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * This function will find the id by workflow name
     */
    private function get_id_by_name($workflow_name)
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
                    $this->id = $row['workflow_id'];
                }
            }
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * This function will return a step object
     */
    public function get_step_object()
    {
        return new Step();
    }

    /**
     * Another method to add a step into the position
     * It will take the position and a step object itselt as parameter
     * Before creating the step required values should be set first
     */
    public function add_step_in_position($position, $step)
    {
        try {
            if (Step::add_step_in_position($position, $step))
                return true;
            else
                return false;
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }


    /**
     * Find total step for a workflow
     */
    public function step_count($workflow_id)
    {
        $this->id = $workflow_id;
        // $this->get_id_by_name($workflow_name);
        $total_steps = Step::steps_count($this->id);
        return $total_steps;
    }

    /**
     * This function will find the name by workflow id
     */
    public function get_name_by_id($workflow_id)
    {
        try {
            $query = '
            SELECT workflow_name FROM ' . $this->workflow_table . ' WHERE workflow_id = :workflow_id;
            ';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam('workflow_id', $workflow_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['workflow_name'];
        } catch (PDOException $e) {
            echo json_encode($e);
        }
    }

    /**
     * Display the output regarding the workflow and the steps
     */
    public function print()
    {
        echo "\nWorkflow Details";
        echo "\nId              : " . $this->id;
        echo "\nName            : " . $this->name;
        echo "\nDescription     : " . $this->description;
        echo "\nCreation Time   : " . $this->created_at;
        echo "\n\nSteps Belongs to the workflow";
        echo "\nTotal steps: " . $this->step_count;
        foreach ($this->steps as $step) {
            echo "\n\nID          : " . $step['step_id'];
            echo "\nName        : " . $step['step_name'];
            echo "\nDescription : " . $step['step_description'];
            echo "\nOrder       : " . $step['step_order'];
            echo "\nType        : " . $step['step_type'];
            echo "\nHandleby    : " . $step['step_handleby'];
        }
    }
}
