<!DOCTYPE html>
<html>
    <head>
        <title>JajaTruck Warehouse Sign In</title>
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
            <h3>Sign In</h3>
            <?php
                if (!empty($_GET["err"])) {
                    echo "<p style='color:red'>Invalid Warehouse Id or Passphrase</p>";
                }
            ?>
            <form method="POST" action="post_signin.php">
                <label for="warehouseId">Warehouse Id:</label>
                <input type="text" name="warehouseId" autofocus />
                <br /><br />
                <label for="passphrase">Passphrase:</label>
                <input type="password" name="passphrase" />
                <br /><br />
                <button id="submit">Sign In</button>
            </form>
            <br /><br />
            <h3>Warehouses</h3>
            <table border>
                <tr>
                    <th>Id</th>
                    <th>Name</th>
                </tr>
                <tr>
                    <td>737853</td>
                    <td>North</td>
                </tr>
                <tr>
                    <td>498827</td>
                    <td>East</td>
                </tr>
                <tr>
                    <td>159549</td>
                    <td>South</td>
                </tr>
                <tr>
                    <td>619930</td>
                    <td>West</td>
                </tr>
            </table>
        </div>
    </body>
</html>