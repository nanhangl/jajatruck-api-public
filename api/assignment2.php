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
        <title>Route Assignment - JajaTruck Warehouse</title>
        <link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Montserrat', 'sans-serif';
            }
        </style>
        <script src='https://api.mapbox.com/mapbox-gl-js/v2.1.1/mapbox-gl.js'></script>
        <link href='https://api.mapbox.com/mapbox-gl-js/v2.1.1/mapbox-gl.css' rel='stylesheet' />
    </head>
    <body>
        <div>
            <h1>JajaTruck Warehouse</h1>
            <div>
                <p><?php echo $decodedArray["name"] ?> (<a href="/api/signout">Sign Out</a>)</p>
                <div style="display:flex"><a href="/api/home.php">Home</a><a style="margin-left:15px" href="/api/assignment.php">Route Assignment</a></div>
            </div>
            <br />
            <p>Route Assignment</p>
            <div id='map' style='width: 1000px; height: 400px;'></div>
            <script>
            mapboxgl.accessToken = 'REDACTED';
            var map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [103.851784, 1.287953], // starting position [lng, lat],
                zoom: 10 // starting zoom
            });
            map.on('load', function () {
            map.addSource('route', {
            'type': 'geojson',
            'data': {
            'type': 'Feature',
            'properties': {},
            'geometry': {
            'type': 'LineString',
            'coordinates': coordArray
            }
            }
            });
            map.addLayer({
            'id': 'route',
            'type': 'line',
            'source': 'route',
            'layout': {
            'line-join': 'round',
            'line-cap': 'round'
            },
            'paint': {
            'line-color': '#888',
            'line-width': 5
            }
            });
            });
            </script>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.11.0/toastify.js" integrity="sha512-sEHBdwx12SnNIw4XLYlcztPfPMpDfEfw17Cl7LSXwEXQIKztb96Mx5TKizezvMtJ0rIutCzGCw7NFXnPeBWdSg==" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.11.0/toastify.min.css" integrity="sha512-qd9G8+DpIoJdcO5i+ZGH0v0lz92U1XlMtKggIFr59wQjRWorqfHZ5EtgRBhUPKEWbKW+GD+0iGgzL3g1Unx7LQ==" crossorigin="anonymous" />
    </body>
</html>