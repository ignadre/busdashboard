<?php
// $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

	/* $host = 'localhost';
	$username = 'root';
	$password = 'Skyrelia07';
	$database = 'mybusz'; */

	// // Production DB
	// // $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
	// $url = 'mysql://b0460620cfd6d0:dcb47d73@us-cdbr-east-05.cleardb.net/heroku_fff5c00d3279bd0?reconnect=true';
	// $host = 'otmaa16c1i9nwrek.cbetxkdyhwsb.us-east-1.rds.amazonaws.com';
	// // $host = getenv("DB_URL");
	// $username = 'poqj0ipcj4rj1rky';
	// // $username = getenv("DB_USER");
	// $password = 've8bou9asofiswlr';
	// // $password = getenv("DB_PASSWORD");
	// $database = 'q0e5e6jn5bsxsky8';
	// // $database = getenv("DB_NAME");

        //// SLK local development DB
        // $url = 'root@127.0.0.1:3306';
        // $url = 'mysql://b21fb22053657c:b5826f74@us-cdbr-iron-east-05.cleardb.net/heroku_b44dfea05dd9713?reconnect=true';
        // $host = '127.0.0.1';
        // $username = 'ignatius';
        // $password = 'ignatius';
        // $database = 'buszdashboard';

    // Load environment variables using vlucas/phpdotenv (if needed)
    require_once __DIR__ . '/../vendor/autoload.php';  // Correct the path to go up one directory
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Production DB connection configuration
    $host = getenv('DB_HOST');      // 'otmaa16c1i9nwrek.cbetxkdyhwsb.us-east-1.rds.amazonaws.com'
    $username = getenv('DB_USERNAME');  // 'poqj0ipcj4rj1rky'
    $password = getenv('DB_PASSWORD');  // 've8bou9asofiswlr'
    $database = getenv('DB_DATABASE');  // 'q0e5e6jn5bsxsky8'
    $port = getenv('DB_PORT');          // '3306'

    // Establish the database connection
    $mysqli = new mysqli($host, $username, $password, $database, $port);

    // Check for connection errors
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    } else {
        echo "Connected successfully to the production database!";
    }

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */



    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        'mysql' => [
            'driver'    => 'mysql',
						'host'      => $host,
						'database'  => $database,
						'username'  => $username,
						'password'  => $password,
						'charset'   => 'utf8',
						'collation' => 'utf8_unicode_ci',
						'prefix'    => '',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => 'predis',

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],

    ],

];
