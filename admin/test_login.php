<?php
// Test login script để debug admin system
session_start();

@define('_template', '/templates/');
@define('_source', '/sources/');
@define('_lib', '/lib/');

include "lib/config.php";
include "lib/function.php";
include "lib/class.php";

global $d;
$d = new func_index($config['database']);

echo "<h2>Admin Login Test</h2>";

// Test database connection
try {
    $test_query = $d->o_fet("SELECT COUNT(*) as count FROM #_user");
    echo "<p style='color: green;'>✓ Database connection: OK</p>";
    echo "<p>Total users in database: " . $test_query[0]['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test user credentials
$username = 'admin';
$password = '123456';

echo "<h3>Testing login with username: $username, password: $password</h3>";

$user_hash = sha1($username);
$pass_hash = sha1($password);

echo "<p>User hash: $user_hash</p>";
echo "<p>Pass hash: $pass_hash</p>";

// Check if user exists
$login = $d->o_fet("SELECT * FROM #_user WHERE user_hash = '$user_hash' AND pass_hash = '$pass_hash' AND quyen_han >= 1");

if (count($login) > 0) {
    echo "<p style='color: green;'>✓ Login credentials are valid!</p>";
    echo "<pre>";
    print_r($login[0]);
    echo "</pre>";
    
    // Set session
    $_SESSION['id_user'] = $login[0]['id'];
    $_SESSION['user_admin'] = $login[0]['tai_khoan'];
    $_SESSION['user_hash'] = $user_hash;
    $_SESSION['quyen'] = @$login[0]['quyen_han'];
    $_SESSION['name'] = @$login[0]['ho_ten'];
    $_SESSION['is_admin'] = $login[0]['is_admin'];
    
    echo "<p style='color: green;'>✓ Session set successfully!</p>";
    echo "<p><a href='index.php'>Go to Admin Dashboard</a></p>";
} else {
    echo "<p style='color: red;'>✗ Login failed!</p>";
    
    // Check all users
    $all_users = $d->o_fet("SELECT id, tai_khoan, user_hash, pass_hash, quyen_han FROM #_user");
    echo "<h4>All users in database:</h4>";
    echo "<pre>";
    print_r($all_users);
    echo "</pre>";
}

// Test session
echo "<h3>Current Session:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test security patches
echo "<h3>Security Patches Status:</h3>";
if (file_exists('security_patches.php')) {
    echo "<p style='color: green;'>✓ security_patches.php exists</p>";
    try {
        include_once 'security_patches.php';
        echo "<p style='color: green;'>✓ security_patches.php loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error loading security_patches.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ security_patches.php not found</p>";
}

// Test .htaccess files
echo "<h3>.htaccess Files Status:</h3>";
$htaccess_files = [
    '.htaccess' => 'Main admin .htaccess',
    'sources/.htaccess' => 'Sources protection',
    'filemanager/.htaccess' => 'File manager protection',
    'ckeditor/.htaccess' => 'CKEditor protection',
    'lib/.htaccess' => 'Library protection'
];

foreach ($htaccess_files as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: orange;'>⚠ $description: EXISTS (may cause issues)</p>";
    } else {
        echo "<p style='color: green;'>✓ $description: NOT FOUND (good for testing)</p>";
    }
}

?>
