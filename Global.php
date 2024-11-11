<?php
$servidor = "localhost";
$user = "root";
$password = "";
$db = "FORMULARIO";

try {
    $conexion = new PDO ("mysql:host=$servidor; dbname=$db",$user, $password);
    $conexion-> setAttribute (PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexion establecida" . "<br>"; 
    echo "<br>";
}
catch (PDOException $error){
    echo "Error" . $error;
}

?>