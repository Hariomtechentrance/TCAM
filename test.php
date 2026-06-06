<?php
// Test PHP is working
echo "PHP is working!<br>";

// Test MySQLi connection
$host = 'localhost';
$user = 'root'; // default XAMPP username
$pass = '';     // default XAMPP password is empty

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("MySQL Connection failed: " . $conn->connect_error);
} 
echo "MySQL Connected successfully";
?>