<?php

class FlatfileSearch {

/*
// Example
header('Content-type: text/plain');
$conf = array('host' => 'localhost',
              'dbname' => 'witt_pubs',
              'user' => $username,
              'pass' => $password,
              'charset' => 'utf8');
$db = new FlatfileSearch($conf);
$table = 'torch_data';
$terms = 'baird tipson';
$fields = ['subject','title'];
$conf = array ('orderby' => 'subject, title year');
$db->booleanAnd($table,$terms,$fields);
var_dump($db->rowCount);
var_dump($db->headers);
var_dump($db->rows);
*/

/*
Dev Agenda
* conf array will handle:
  limit
  return only certain columns
*/

    public function __construct (array $db_config) {
        try { 
            $this->db = new PDO('mysql:host='.$db_config['host'].';dbname='.$db_config['dbname'].';charset='.$db_config['charset'], $db_config['user'], $db_config['pass']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            print $e->getMessage();
        }
    }

    public function test() {
        $query = 'SELECT * FROM torch_data WHERE `title` LIKE ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array('%baird%'));
        print ($stmt->rowCount().PHP_EOL);
    }
    
    public function booleanAnd($table, $terms, $fields, $conf = []) {
        $parts = $this->buildQueryAnd($table, $terms, $fields, $conf);
        // parts has  'query' 'placeholders'
        $stmt = $this->db->prepare($parts['query']);
        $stmt->execute($parts['placeholders']);
        $this->rowCount = $stmt->rowCount();
        $this->rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->headers = array_keys($this->rows[0]);

    }
    
    private function buildQueryAnd($table, $terms, $fields, $conf = []) {
        $size = sizeof($fields);
        $terms = preg_split ("/\s+/", $terms);
        $placeholders = array();
        $j = 0;
        foreach ($terms as $item) { 
            for ($i=0; $i<$size; $i++) {
                $temp[$i] = "$fields[$i] like ?";
                array_push($placeholders, '%'.$item.'%');
            } // End for each field
            $temp_str = join (" or ", $temp);
            $sub_clause[$j] = "($temp_str)";
            $j++;
        } // end foreach search term
        $searchstring = join (" and ", $sub_clause);
        if (array_key_exists('orderby',$conf)) {
            $order = 'ORDER BY '.$conf['orderby'];
        }
        else { $order = ''; }
        $query = "SELECT * FROM $table WHERE $searchstring $order";
        $return = array(
            "query" => $query,
            "placeholders" => $placeholders
        );
        return $return;
    }
}

