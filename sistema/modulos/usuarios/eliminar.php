<?php
// Este archivo ahora solo redirige a procesos.php para manejar la eliminación.
// No se necesita verificar permisos aquí, ya se hace en index.php y procesos.php

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirigir a procesos.php para manejar la eliminación
header("Location: procesos.php?accion=eliminar&id={$id}");
exit;
?>
