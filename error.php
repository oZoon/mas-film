<?php
header('HTTP/1.1 404');
if (!isset($state['error'])) {
    $state['error'] = 'bad request';
}
echo json_encode($state['error']);
exit;
