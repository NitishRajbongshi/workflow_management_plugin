<?php
include_once __DIR__ . "/connection.php";

class Authentication
{
    private $conn;
    private $employee_id;
    private $employee_email;
    private $password;
    private $group_id;
    private $group_name;

    private $login_table;
    private $employee_table;
    private $employee_group_table;
    private $group_details_table;

    public function __construct()
    {
        $this->conn = connect_db();
        $data = json_decode(file_get_contents(__DIR__ . '/config.json'), TRUE);
        $this->login_table = $data['login'];
        $this->employee_group_table = $data['employee_group'];
        $this->group_details_table = $data['group_table'];
        $this->employee_table = $data['employee_table'];
    }

    public function set_values($email, $password, $group_name = null)
    {
        $this->employee_email = $email;
        $this->password = $password;
        $this->group_name = $group_name;
    }

    public function user_login()
    {
        $query = "
        SELECT * FROM " . $this->login_table . " WHERE employee_email = :email AND password = :password;
        ";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam("email", $this->employee_email);
        $stmt->bindParam("password", $this->password);

        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            return true;
        }
        return false;
    }

    public function group_login()
    {
        $query = "
        SELECT * FROM " . $this->login_table . " WHERE employee_email = :email AND password = :password;
        ";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam("email", $this->employee_email);
        $stmt->bindParam("password", $this->password);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->employee_id = $row['employee_id'];
            if ($this->is_in_group()) {
                return true;
            }
            return false;
        }
        return false;
    }

    private function is_in_group()
    {
        $group_id = $this->get_group_id();
        if(isset($group_id)) {
            $query = "
                SELECT * FROM " . $this->employee_group_table . " 
                WHERE employee_id = :employee_id AND group_id = :group_id
            ";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":employee_id", $this->employee_id);
            $stmt->bindParam(":group_id", $group_id);

            if($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    return true;
                }
                return false;
            }
            return false;
        } 
        else
            return false; 
    }

    private function get_group_id() {
        $query = "
            SELECT * FROM " . $this->group_details_table . " WHERE group_name = :group_name
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam("group_name", $this->group_name);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['group_id'];
        }
        else 
            return false;
    }

    public function get_employee_id($email) {
        $query = "
            SELECT employee_id FROM " . $this->login_table . " WHERE employee_email = :email
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam("email", $email);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['employee_id'];
        }
        else 
            return false;
    }

    public function get_employee_details($employee_id) {
        $query = "
            SELECT * FROM " . $this->employee_table . " WHERE employee_id = :employee_id
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam("employee_id", $employee_id);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return array(
                "employee_id"=> $row['employee_id'],
                "employee_name"=> $row['employee_name'],
                "employee_phone"=> $row['employee_phone'],
                "employee_fla"=> $row['FLA'],
                "employee_hr"=> $row['HR_id'],
            );
        }
        else
            return false;
    }
}
