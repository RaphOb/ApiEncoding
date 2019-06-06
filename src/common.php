<?php
function displayErrorJSON($msg, $code = NULL, $data = NULL)
{
    $array['message'] = $msg;
    if ($code !== NULL) $array['code'] = $code;
    if ($data !== NULL) $array['data'] = $data;
    echo json_encode($array);
}