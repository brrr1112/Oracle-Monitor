<?php
header('Content-Type: application/json');

$server = array();

if (($handle = fopen("log.csv","r")) !== FALSE) {
    while (($data = fgetcsv($handle)) !== FALSE) {
        $aux = array();
        for ($c=0; $c < count($data); $c++) {
            $aux[] = $data[$c];
        }
        $server[] = $aux;
    }
    fclose($handle);
    echo json_encode($server);
}
else {
    echo json_encode('File Not Found');
}
?>