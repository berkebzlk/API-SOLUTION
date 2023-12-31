<?php

/**
 * Dump and die function for easy debugging.
 *
 * @param mixed $value The value to be dumped.
 */
function dd($value)
{
    echo "<pre>";
    var_dump($value);
    echo "</pre>";

    die();
}

/**
 * Send a JSON response with the appropriate headers and exit the script.
 *
 * @param mixed $data The data to be encoded as JSON.
 */
function json_response($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
