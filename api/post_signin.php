<?php
    require_once __DIR__ . '/../vendor/autoload.php';

    $key = "REDACTED";

    $warehouseId = $_POST["warehouseId"];
    $passphrase = $_POST["passphrase"];
    if ($passphrase == "letmein") {
        switch ($warehouseId) {
            case "737853":
                $payload = array(
                    "warehouse" => "737853",
                    "name" => "North Warehouse"
                );
                $jwt = Firebase\JWT\JWT::encode($payload, $key);
                setcookie("cred", $jwt, 0, "/", "jajatruck.vercel.app", TRUE, TRUE);
                header("Location: /api/home.php");
                break;
            case "498827":
                $payload = array(
                    "warehouse" => "498827",
                    "name" => "East Warehouse"
                );
                $jwt = Firebase\JWT\JWT::encode($payload, $key);
                setcookie("cred", $jwt, 0, "/", "jajatruck.vercel.app", TRUE, TRUE);
                header("Location: /api/home.php");
                break;
            case "159549":
                $payload = array(
                    "warehouse" => "159549",
                    "name" => "South Warehouse"
                );
                header("Location: /api/home.php");
                $jwt = Firebase\JWT\JWT::encode($payload, $key);
                setcookie("cred", $jwt, 0, "/", "jajatruck.vercel.app", TRUE, TRUE);
                break;
            case "619930":
                $payload = array(
                    "warehouse" => "619930",
                    "name" => "West Warehouse"
                );
                $jwt = Firebase\JWT\JWT::encode($payload, $key);
                setcookie("cred", $jwt, 0, "/", "jajatruck.vercel.app", TRUE, TRUE);
                header("Location: /api/home.php");
                break;
            default:
                header("Location: /api/signin?err=invalid");
        }
    } else {
        header("Location: /api/signin?err=invalid");
    }
    exit;
?>