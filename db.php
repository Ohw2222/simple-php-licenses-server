<?php
// Include configuration
require_once 'config.php';

/**
 * Establishes a database connection using PDO.
 * Tries MySQL first, then falls back to SQLite.
 *
 * @return PDO The PDO database connection object.
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $pdo = null;
    $dsn_mysql = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $dsn_sqlite = "sqlite:" . SQLITE_FILE;

    // 1. Try MySQL Connection
    if (DB_TYPE === 'mysql') {
        try {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn_mysql, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // MySQL connection failed, log error (in a real app) and try SQLite
            // error_log("MySQL connection failed: " . $e->getMessage());
            $pdo = null;
        }
    }

    // 2. Try SQLite Connection (if MySQL failed or DB_TYPE is not mysql)
    if ($pdo === null) {
        try {
            // Ensure the directory for SQLite exists
            if (!file_exists(dirname(SQLITE_FILE))) {
                mkdir(dirname(SQLITE_FILE), 0755, true);
            }
            $pdo = new PDO($dsn_sqlite);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign keys for SQLite
            $pdo->exec('PRAGMA foreign_keys = ON;');

        } catch (PDOException $e) {
            // Both connections failed
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Creates all necessary tables in the database if they don't exist.
 *
 * @param PDO $pdo The database connection.
 */
function create_tables(PDO $pdo) {

    if(!$pdo->inTransaction()) $pdo->beginTransaction();
    $db_type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Common column types
    $autoincrement_pk = $db_type === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $varchar_255_not_null = 'VARCHAR(255) NOT NULL';
    $varchar_255_null = 'VARCHAR(255) NULL';
    $text_type = 'TEXT';
    $date_type = 'DATE';
    $decimal_type = $db_type === 'mysql' ? 'DECIMAL(10, 2)' : 'REAL';

    // SQL statements
    $queries = [
        // Users Table
        "CREATE TABLE IF NOT EXISTS users (
            id $autoincrement_pk,
            username $varchar_255_not_null UNIQUE,
            password_hash $varchar_255_not_null,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );",

        // Softwares Table
        "CREATE TABLE IF NOT EXISTS softwares (
            id $autoincrement_pk,
            name $varchar_255_not_null,
            languages $varchar_255_null COMMENT 'Comma-separated list, e.g., php,js,java'
        );",

        // Versions Table
        "CREATE TABLE IF NOT EXISTS versions (
            id $autoincrement_pk,
            software_id INT NOT NULL,
            version_number $varchar_255_not_null,
            FOREIGN KEY (software_id) REFERENCES softwares(id) ON DELETE CASCADE
        );",

        // Customers Table
        "CREATE TABLE IF NOT EXISTS customers (
            id $autoincrement_pk,
            name $varchar_255_not_null,
            siret $varchar_255_null,
            address $text_type NULL,
            email $varchar_255_null,
            phone $varchar_255_null
        );",

        // Licences Table
        "CREATE TABLE IF NOT EXISTS licences (
            id $autoincrement_pk,
            licence_name $varchar_255_not_null,
            software_id INT NOT NULL,
            version_id INT NOT NULL,
            customer_id INT NOT NULL,
            start_date $date_type NOT NULL,
            end_date $date_type NOT NULL,
            price $decimal_type DEFAULT 0.00,
            secret_key $varchar_255_not_null UNIQUE,
            message $text_type NULL COMMENT 'Message for inactive licenses',
            FOREIGN KEY (software_id) REFERENCES softwares(id) ON DELETE CASCADE,
            FOREIGN KEY (version_id) REFERENCES versions(id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        );"
    ];

    // Execute all table creation queries
    try {
        if(!$pdo->inTransaction()) $pdo->beginTransaction();
        foreach ($queries as $query) {
            // Remove MySQL-specific comments for SQLite
            if ($db_type === 'sqlite') {
                $query = preg_replace('/COMMENT\s+\'.*?\'/', '', $query);
            }
            $pdo->exec($query);
        }
        if($pdo->inTransaction()) $pdo->commit();
    } catch (PDOException $e) {
        var_dump($e);
        if($pdo->inTransaction()) $pdo->rollBack();
        die("Table creation failed: " . $e->getMessage());
    }
}

// Get the connection
$pdo = get_db_connection();

// Create tables if they don't exist
create_tables($pdo);

?>
