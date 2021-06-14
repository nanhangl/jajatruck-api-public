<?php
    if (!isset($_COOKIE["cred"])) {
        header("Location: /api/signin.php");
    } else {
        header("Location: /api/home.php");
    }
    exit;
?>