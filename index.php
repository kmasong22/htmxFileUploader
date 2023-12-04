<?php
include_once( __DIR__ . "/class.htmxFileUploader.php" ) ;
include_once( __DIR__ . "/class.DQX.php" ) ;
?><!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/bootstrap.css">
    <link rel="stylesheet" href="./styles.css">
    <script src="./assets/bootstrap.js"></script>
    <script src="./assets/htmx.min.js"></script>
</head>
<body style="margin:2rem;">

<h2>Modal Example</h2>

<?php print_r( HTMXFileUploader::htmxUploadBtn( '35C1-DCB9-BA716D' ) ) ?>
<br><br>
<?php print_r( HTMXFileUploader::htmxUploadBtn() ) ?>

</body>
</html>
