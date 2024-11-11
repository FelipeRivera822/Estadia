<?php 
$servidor = "localhost";
$user = "root";
$password = "";
$db = "FELIPE";

try{
    $conexion = new PDO ("mysql:host=$servidor;dbname=$db",$user, $password);
    $conexion-> setAttribute (PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexion establecida"; 

    $sql = "SELECT * FROM `practica` ";
    $consulta = $conexion->prepare($sql);
    $consulta->execute();
    $resultado = $consulta->fetchAll();

    echo "<br>";
    foreach ($resultado as $key => $value){
        echo "Edad: " . $value['Edad'];
        echo " Nombre: " . $value['Nombre'] . "<br>"; 
    }
}
catch (PDOException $error){
    echo "Error" . $error;
}

/*
+------+--------------+
| Edad | Nombre       |
+------+--------------+
|   18 | Oswaldo      |
|   20 | Gustavo Malo |
|   18 | Alfonso      |
+------+--------------+
*/

?>