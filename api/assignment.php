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
        <title>Assignment - JajaTruck Warehouse</title>
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
            <p>Enter Driver Id to search driver route</p>
            <form onsubmit="event.preventDefault();searchRoute()">
                <input type="text" id="driverId" value="9000001" autofocus />
                <br /><br />
                <button id="submit">Search</button>
            </form>
            <br />
            <div style="display:flex;">
                <div id='map' style='width: 1000px; height: 400px;'></div>
                <table id="routeTable" style="margin-left:15px" border>
                </table>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.11.0/toastify.js" integrity="sha512-sEHBdwx12SnNIw4XLYlcztPfPMpDfEfw17Cl7LSXwEXQIKztb96Mx5TKizezvMtJ0rIutCzGCw7NFXnPeBWdSg==" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.11.0/toastify.min.css" integrity="sha512-qd9G8+DpIoJdcO5i+ZGH0v0lz92U1XlMtKggIFr59wQjRWorqfHZ5EtgRBhUPKEWbKW+GD+0iGgzL3g1Unx7LQ==" crossorigin="anonymous" />
        <script>
            const warehouseToDriver = {
                "90000010": {
                    "coords": [103.802836665616, 1.43488097187658],
                    "address": "11 WOODLANDS CLOSE S(737853)"
                },
                "90000011": {
                    "coords": [103.802836665616, 1.43488097187658],
                    "address": "11 WOODLANDS CLOSE S(737853)"
                },
                "90000012": {
                    "coords": [103.965626781031, 1.3508444949497],
                    "address": "3 CHANGI NORTH STREET 2 S(498827)"
                },
                "90000013": {
                    "coords": [103.965626781031, 1.3508444949497],
                    "address": "3 CHANGI NORTH STREET 2 S(498827)"
                },
                "90000014": {
                    "coords": [103.820445705146, 1.28087698138876],
                    "address": "205 HENDERSON ROAD S(159549)"
                },
                "90000015": {
                    "coords": [103.820445705146, 1.28087698138876],
                    "address": "205 HENDERSON ROAD S(159549)"
                },
                "90000016": {
                    "coords": [103.714774034284, 1.33420771155321],
                    "address": "6 CHIN BEE AVENUE S(619930)"
                },
                "90000017": {
                    "coords": [103.714774034284, 1.33420771155321],
                    "address": "6 CHIN BEE AVENUE S(619930)"
                }
            }
            mapboxgl.accessToken = 'REDACTED';
            function searchRoute() {
                const driverId = document.querySelector("#driverId").value;
                $.post("/api/bc9d6ed2-9896-4ad1-b938-df163031fdd9", {
                    "endpoint": "warehouseGetDriverParcels",
                    "token": "<?php echo $_COOKIE["cred"] ?>",
                    "driverId": driverId
                }).then(res => {
                    if (res.status == "ok") {
                        var map = new mapboxgl.Map({
                            container: 'map',
                            style: 'mapbox://styles/mapbox/streets-v11',
                            center: [103.8, 1.366667], // starting position [lng, lat],
                            zoom: 10 // starting zoom
                        });
                        var coordArray = [];
                        var sequenceCounter = 1;
                        var routeHtml = `<tr>
                                            <th>Sequence No.</th>
                                            <th>Address</th>
                                            <th>Remarks</th>
                                        </tr>
                                        <tr>
                                            <td>1</td>
                                            <td>${warehouseToDriver[driverId].address}</td>
                                            <td>Start Location - Warehouse</td>
                                        </tr>`;
                        coordArray.push(warehouseToDriver[driverId].coords);
                        const parcels = res.parcels;
                        for (var i in parcels) {
                            if (parcels[i].data.status.withinJajaTruck.current == "onVehicleForDelivery") {
                                coordArray.push([parseFloat(parcels[i].data.recipient.address.lon), parseFloat(parseFloat(parcels[i].data.recipient.address.lat))]);
                                sequenceCounter += 1;
                                routeHtml += `<tr>
                                                <td>${sequenceCounter}</td>
                                                <td>${parcels[i].data.recipient.address.street}, ${parcels[i].data.recipient.address.unit}, S(${parcels[i].data.recipient.address.postal})</td>
                                                <td>Delivery</td>
                                            </tr>`;
                            } else {
                                coordArray.push([parseFloat(parcels[i].data.sender.address.lon), parseFloat(parseFloat(parcels[i].data.sender.address.lat))]);
                                sequenceCounter += 1;
                                routeHtml += `<tr>
                                                <td>${sequenceCounter}</td>
                                                <td>${parcels[i].data.sender.address.street}, ${parcels[i].data.sender.address.unit}, S(${parcels[i].data.sender.address.postal})</td>
                                                <td>Pick-up</td>
                                            </tr>`;
                            }
                        }
                        coordArray.push(warehouseToDriver[driverId].coords);
                        sequenceCounter += 1;
                            routeHtml += `<tr>
                                            <td>${sequenceCounter}</td>
                                            <td>${warehouseToDriver[driverId].address}</td>
                                            <td>End Location - Warehouse</td>
                                          </tr>`;
                        document.querySelector("#routeTable").innerHTML = routeHtml;
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
                            map.addLayer({
                                'id': 'stops',
                                'type': 'circle',
                                'source': 'route',
                                'paint': {
                                    'circle-radius': 5,
                                    'circle-color': '#ff0000'
                                }
                            });
                        });
                    }
                })
            }
        </script>
    </body>
</html>