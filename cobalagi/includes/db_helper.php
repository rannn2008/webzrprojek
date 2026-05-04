<?php
// includes/db_helper.php - Wrapper for secure prepared statements

if (!defined('DB_HELPER_INCLUDED')) {
    define('DB_HELPER_INCLUDED', true);

    /**
     * Execute a prepared query (SELECT, INSERT, UPDATE, DELETE)
     * 
     * @param mysqli $conn The database connection
     * @param string $query The SQL query with placeholders (?)
     * @param string $types String containing types of parameters (e.g., "ssi")
     * @param array $params Array of parameters to bind
     * @param bool $return_result Whether to return the result set (true) or affected rows/insert id (false)
     * @return mixed 
     */
    function secure_query($conn, $query, $types = "", $params = [], $return_result = true) {
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            die("Database Error: " . mysqli_error($conn));
        }

        if ($types && !empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        if (!mysqli_stmt_execute($stmt)) {
            die("Execution Error: " . mysqli_stmt_error($stmt));
        }

        if ($return_result) {
            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        } else {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            $insert_id = mysqli_stmt_insert_id($stmt);
            mysqli_stmt_close($stmt);
            return ['affected_rows' => $affected_rows, 'insert_id' => $insert_id];
        }
    }

    /**
     * Fetch a single row as an associative array
     */
    function fetch_one($result) {
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /**
     * Fetch all rows as an associative array
     */
    function fetch_all($result) {
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }
}
?>
