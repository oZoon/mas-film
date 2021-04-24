<?php
include_once 'core/config.php';

doDefineMethod(); // проверка входящего запроса и сохранение в глобальной переменной

doCheckParams(); // проверка параметров запроса

doQuery(); // запрос в БД

doSendJson(); // отправка ответа
