<?php 
    require_once __DIR__ . '/../vendor/autoload.php';

    $key = "REDACTED";
    if (empty($_COOKIE["cred"])) {
        header("Location: /api/signin.php");
        exit;
    } else {
        $decodedObj = Firebase\JWT\JWT::decode($_COOKIE["cred"], $key, array('HS256'));
        $decodedArray = (array) $decodedObj;
        if (empty($decodedArray["warehouse"])) {
            header("Location: /api/signin.php");
            exit;
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Home - JajaTruck Warehouse</title>
        <link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Montserrat', 'sans-serif';
            }
        </style>
    </head>
    <body>
        <div>
            <h1>JajaTruck Warehouse</h1>
            <div>
                <p><?php echo $decodedArray["name"] ?> (<a href="/api/signout">Sign Out</a>)</p>
                <div style="display:flex"><a href="/api/home.php">Home</a><a style="margin-left:15px" href="/api/assignment.php">Route Assignment</a></div>
            </div>
            <br />
            <p>Enter Delivery Id to register parcel into warehouse</p>
            <form onsubmit="event.preventDefault();registerParcel()">
                <input type="text" id="deliveryId" autofocus />
                <br /><br />
                <button id="submit">Register Parcel</button>
            </form>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.11.0/toastify.js" integrity="sha512-sEHBdwx12SnNIw4XLYlcztPfPMpDfEfw17Cl7LSXwEXQIKztb96Mx5TKizezvMtJ0rIutCzGCw7NFXnPeBWdSg==" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.11.0/toastify.min.css" integrity="sha512-qd9G8+DpIoJdcO5i+ZGH0v0lz92U1XlMtKggIFr59wQjRWorqfHZ5EtgRBhUPKEWbKW+GD+0iGgzL3g1Unx7LQ==" crossorigin="anonymous" />
        <script>
            function registerParcel() {
                const deliveryId = document.querySelector("#deliveryId").value;
                $.post("/api/bc9d6ed2-9896-4ad1-b938-df163031fdd9", {
                    "endpoint": "warehouseRegisterParcel",
                    "token": "<?php echo $_COOKIE["cred"] ?>",
                    "deliveryId": deliveryId
                }).then(res => {
                    if (res.status == "ok") {
                        document.querySelector("#deliveryId").value = "";
                        Toastify({
                        text: `${deliveryId} registered in <?php echo $decodedArray["name"] ?>`,
                        duration: 3000,
                        position: "left"
                        }).showToast();
                    }
                })
            }
        </script>
    </body>
</html>