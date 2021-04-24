<?php

function request(string $query)
{
    global $state;
    switch (explode(' ', $query)[0]) {
        case 'SELECT':
            $result = array();
            $qr = $state['mysql']->query($query);
            $result['numRows'] = $qr->num_rows;
            $result['res'] = $qr->fetch_assoc();
            return $result;
        break;
        default:
            $state['mysql']->query($query);
        break;
    }
}

function doSendJson()
{
    global $state;
    header('HTTP/1.1 200 Ok');
    header('Content-Type: application/json');
    echo json_encode($state['response']);
    exit;
}

function doSendError(string $string)
{
    global $state;
    $state['error'] = $string;
    include_once './error.php';
}

function checkNum($num)
{
    return !preg_match_all('/[^0-9]{1,}/giu', $num);
}

function checkStr($str)
{
    return !preg_match_all('/[^0-9A-Za-zа-яА-Я ]{1,}/giu', $str);
}
