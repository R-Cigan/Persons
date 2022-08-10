<?php
// placeholder for a database connection object
class database {
    public $conn;
    
    function __construct() {
        $servername = "localhost";
        $username = "username";
        $password = "password";
        $dbname = "myDB";
        
        // Create connection
        $this->conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }   
}

class Person {
        // variables
	protected $person_id;
	protected $mother_id;
	protected $father_id;
	protected $first_name;
	protected $last_name;
	protected $date_birth;
        protected $alive;
        
        protected $data_loaded;
	
        // construct
	function __construct() {
	}
	
        // getters
	public function get_person_id() {
		return $this->person_id;
	}
        
	public function get_mother_id() {
		return $this->mother_id;
	}
	
	public function get_father_id() {
		return $this->father_id;
	}
        // ......
        
        // setters
	public function set_mother_id() {
		return $this->mother_id;
	}
	
	public function set_father_id() {
		return $this->father_id;
	}
        // ......
        
        // function
	
	
	public function load($person_id) {
		// load person from database
		if ($person_id) {
                    // get data from table person for specific ID
                    $db = new database();
                    $qry_get_person = "SELECT * FROM persons WHERE person_id = $person_id";
                    $person = $db->conn->query($qry_get_person);
                    
                    if ($person->num_rows === 1) {
                        // save data of person
                        $row = $person->fetch_assoc();
                        $this->mother_id = $row["mother_id"];
                        $this->father_id = $row["father_id"];
                        $this->first_name = $row["first_name"];
                        $this->last_name = $row["last_name"];
                        $this->date_birth = strtotime($row["date_birth"]);
                        $this->alive = $row["alive"];
                    // some error handling
                    } elseif ($result->num_rows === 0) {
                        $error = "0 results";
                    } else {
                        $error = "too many results";
                    }
                    // close connection
                    mysqli_close($db->conn);
                    
                    if (!empty($error)) {
                        die ($error);
                    }
                } else {
                    die("person_id cannot be empty");
                }
                return true;
	}
	
        public function save() {
                $db = new database();

                $qry_save_person = "
                    INSERT INTO persons (
                        mother_id,
                        father_id,
                        first_name,
                        last_name,
                        date_birth,
                        alive
                    ) VALUES (
                        $this->mother_id,
                        $this->father_id,
                        $this->first_name,
                        $this->last_name,
                        ".date( 'Y-m-d H:i:s', $this->date_birth).",
                        $this->alive
                    )
                ";
                
                if ($db->conn->query($qry_save_person) === TRUE) {
                    //save generated person id
                    $this->person_id = $db->conn->insert_id;
                } else {
                    $error = $db->conn->error;
                }
                
                // close connection
                mysqli_close($db->conn); 
                
                if (!empty($error)) {
                    die ($error);
                }
                return true;
        }
        
        public function get_children() {
                // query to get only direct children
                $qry_get_children = "
                    SELECT 
                            person_id as child_id,
                            first_name,
                            last_name
                    FROM 
                            persons
                    WHERE
                            mother_id = $this->person_id OR 
                            father_id = $this->person_id
                ";
                
                return process_descendants($qry_get_children);
        }
        
        public function get_grandchildren() {
                // query for getting only grandchildren
                $qry_get_grandchildren = "
                    SELECT 
                            gc.person_id as grandchild_id,
                            gc.first_name,
                            gc.last_name
                    FROM 
                            -- generation 0
                            persons p
                            -- generation 1
                            JOIN persons c ON c.mother_id = p.person_id OR c.father_id = p.person_id
                            -- generation 2
                            JOIN persons gc ON gc.mother_id = c.person_id OR gc.father_id = c.person_id
                    WHERE 
                            p.person_id = $this->person_id
                ";
                
                return process_descendants($qry_get_grandchildren);
        }
        
        public function get_descendants() {
                // query for getting all descendants
                $qry_get_descendants = "
                    -- create recursive table to get a unknown number of connecting levels 
                    -- if recursions is not possible in current DB then use the connected programming language to iterate trough descendants
                    WITH RECURSIVE 
                        descendant (person_id, first_name, last_name, mother_id, father_id, generation) 
                    AS (
                        -- get person whos descedants are being searched for
                        SELECT  
                                person_id,
                                first_name,
                                last_name,
                                mother_id,
                                father_id,
                                0 AS generation
                        FROM 
                                persons
                        WHERE 
                                person_id = $this->person_id

                        UNION ALL

                        -- recursivly move trough the generations joining either trough father_id or mother_id
                        SELECT  
                                p.person_id,
                                p.first_name,
                                p.last_name,
                                p.mother_id,
                                p.father_id,
                                generation + 1
                        FROM 
                                persons p
                                JOIN descendant d ON d.mother_id = p.person_id OR d.father_id = p.person_id
                    )
                    
                    -- output the CTE table while ignoring generation 0 (not a descendant of themself)
                    SELECT person_id
                    FROM descendant
                    WHERE generation > 0
                    ORDER BY generation, mother_id, father_id
                ";
                
                return $this->process_descendants($qry_get_descendants);
            
        }
        
        public function process_descendants($query) {
                // load person from database
		if ($this->person_id) {
                    // get data from table person for specific ID
                    $db = new database();
                    $descendants = $db->conn->query($query);
                    $descendants_array = array();
                    
                    while($descendant = $descendants->fetch_assoc()) {
                        $descendant_object = new Person();
                        $descendant_object->load($descendant["child_id"]);
                        $descendants_array[] = $descendant_object;
                    }

                    // close connection
                    mysqli_close($db->conn);
                    // function returns filled out person objects
                    return $descendants_array;
                } else {
                    die("person must be loaded");
                    // return false
                }
        }
        
        public function get_descendants_recursive($descendant_array = array()) {
            // get children objects of this object into a object array
            $descendants = $this->get_children();
            if(count($descendants) > 0 && is_array($descendants)){
                // merge carried over object array with new object array
                $descendant_array = array_merge($descendant_array, $descendants);
            }
            // for each found descendant find their children
            foreach ($descendants as $key => $descendant) {
                // recursive call of function for currently selected child - carrying over the array of found objects
                $descendant->get_descendants_recursive(&$descendant_array);
            }   
            return $descendant_array;
        }
}

?>