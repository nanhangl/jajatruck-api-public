<?php
    unset($_COOKIE["cred"]);
    setcookie("cred", "", time()-3600, "/", "jajatruck.vercel.app", TRUE, TRUE);
    header("Location: /api/signin.php");
    exit;
?>