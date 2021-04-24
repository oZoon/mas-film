<?php
include_once 'core.functions.php';

/**
 * проверка входящего запроса и сохранение в глобальной переменной $state['request']
 */
function doDefineMethod()
{
    global $state;
    $temp = false;
    if ($_REQUEST) {
        if (
            $_SERVER['REQUEST_METHOD'] == 'GET' &&
            isset($_GET['action']) &&
            in_array($_GET['action'], $state['allowGET'])
            ) {
            $state['request'] = &$_GET;
            $temp = true;
        }
        if (
            $_SERVER['REQUEST_METHOD'] == 'POST' &&
            isset($_POST['action']) &&
            in_array($_GET['action'], $state['allowPOST'])
            ) {
            $state['request'] = &$_POST;
            $temp = true;
        }
        if (!$temp) {
            sendError('invalid action type');
        }
    }
}

/**
 * проверка параметров запроса
 */
function doCheckParams()
{
    global $state;
    $state['params'] = $state['request'];
    unset($state['params']['action']);
    $temp = true;
    $paramKeys = array_keys($state['params']);
    for ($i = 0; $i <= count($state['params']) - 1; $i++) {
        if (!in_array($state['params'][$i], $state['allowParams'])) {
            $temp = false;
            break;
        }
        if (in_array($paramKeys[$i], $state['checkNum'])) {
            if (!checkNum($state['params'][$i])) {
                sendError('invalid ' . $paramKeys[$i] . ' value');
            }
        }
        if (in_array($paramKeys[$i], $state['checkStr'])) {
            if (!checkStr($state['params'][$i])) {
                sendError('invalid ' . $paramKeys[$i] . ' value');
            }
        }
    }
    if (!$temp) {
        sendError('invalid parameter value');
    }
}

/**
 * запрос в БД
 */
function doQuery()
{
    global $state;
    call_user_func($state['request']['action']);
}

/**
 * список фильмов с фильтрацией и сортировкой
 *
 * filterGenre
 * filterActor
 * sort
 */
function getAllFilms()
{
    global $state;
    $temp = array_keys($state['params']);
    $state['queryArray'] = array();
    $state['queryArray']['sort'] = '';
    $state['queryArray']['filterActor'] = ' 1';
    $state['queryArray']['filterGenre'] = ' 1';
    for ($i = 0; $i <= count($temp) - 1; $i++) {
        switch ($temp[$i]) {
            case 'filterGenre':
            $state['queryArray']['filterGenre'] = ' `films`.`genreID` = `genres`.`genreID` AND `genres`.`genreName` = \'' . $state['params'][$temp[$i]] . '\'';
            break;

            case 'filterActor':
            $state['queryArray']['filterActor'] = '`films`.`filmID` = `links`.`filmID` AND `links`.`actorID` IN (SELECT `actorID` FROM `actors` WHERE `actorFullName` = \'' . $state['params'][$temp[$i]] . '\')';
            break;

            case 'sort':
            $state['queryArray']['sort'] = ' ORDER BY `films`.`filmID` ' . $state['params'][$temp[$i]];
            break;
        }
    }
    $temp = request('SELECT `films`.`filmID` as `filmID`, `films`.`filmName` as `filmName` FROM `films`, `links`, `genres` WHERE' . $state['queryArray']['filterGenre'] . ' AND' . $state['queryArray']['filterActor'] . $state['queryArray']['sort']);
    $state['response'] = $temp['res'];
    unset($state['queryArray'], $temp);
}

/**
 * запрос фильма по ID
 *
 * filmID
 */
function getFilmID()
{
    global $state;
    if (!isset($state['params']['filmID'])) {
        sendError('invalid film ID');
    }
    $temp = request('SELECT `films`.`filmName` as `filmName`, `genres`.`genreName` as `genreName`, `genres`.`genreID` as `genreID` FROM `films`, `genres` WHERE `filmID` = ' . $state['params']['filmID'] . ' AND `films`.`genreID` = `genres`.`genreID` LIMIT 1');
    if ($temp['numRows'] == 0) {
        sendError('unknown film ID');
    } else {
        $state['response']['filmID'] = $state['params']['filmID'];
        $state['response']['filmName'] = $temp['res']['filmName'];
        $state['response']['genreName'] = $temp['res']['genreName'];
        $state['temp'] = request('SELECT `actors`.`actorFullName` as `actorName`, `actors`.`actorID` as `actorID` FROM `actors`, `links` WHERE `actors`.`actorID` = `links`.`actorID` AND `links`.`filmID` = ' . $state['params']['filmID']);
        $state['response']['actors'] = $temp['res'];
        unset($temp);
    }
}

/**
 * добавление фильма
 *
 * filmName
 */
function addFilm()
{
    global $state;
    if (!isset($state['params']['filmName'])) {
        sendError('invalid film name');
    }
    if (request('SELECT `filmID` FROM `films` WHERE `filmName` = \'' . $state['params']['filmName'] . '\' LIMIT 1')['numRows'] == 0) {
        request('INSERT INTO `films` (`filmName`) VALUES (\'' . $state['params']['filmName'] . '\')');
        $state['response'] = array(
                'answer' => 'filmName ' . $state['params']['filmName'] . ' successfully added'
            );
    } else {
        sendError('film name ' . $state['params']['filmName'] . ' exists');
    }
}

/**
 * удаление фильма
 *
 * filmName
 */
function deleteFilm()
{
    global $state;
    if (!isset($state['params']['filmName'])) {
        sendError('invalid film name');
    }
    if (request('SELECT `filmID` FROM `films` WHERE `filmName` = \'' . $state['params']['filmName'] . '\' LIMIT 1')['numRows'] == 1) {
        request('DELETE `filmName` FROM `films` WHERE `filmName` = \'' . $state['params']['filmName'] . '\' LIMIT 1');
        $state['response'] = array(
                'answer' => 'filmName ' . $state['params']['filmName'] . ' successfully deleted'
            );
    } else {
        sendError('film name ' . $state['params']['filmName'] . ' not exists');
    }
}

/**
 * редактирование фильма
 *
 * filmID
 * filmName
 */
function editFilmName()
{
    global $state;
    if (!isset($state['params']['filmID']) || !isset($state['params']['filmName'])) {
        sendError('invalid film data');
    }
    if (request('SELECT `filmID` FROM `films` WHERE `filmID` = ' . $state['params']['filmID'] . ' LIMIT 1')['numRows'] == 1) {
        request('UPDATE `films` SET `filmName` = \'' . $state['params']['filmName'] . '\' WHERE `filmID` = ' . $state['params']['filmID'] . ' LIMIT 1 ');
        $state['response'] = array(
                'answer' => 'filmName ' . $state['params']['filmName'] . ' successfully modified'
            );
    } else {
        sendError('invalid film ID to search and edit');
    }
}

/**
 * редактирование жанра фильма
 *
 * filmID
 * genreID
 */
function editFilmGenre()
{
    global $state;
    if (!isset($state['params']['filmID']) || !isset($state['params']['genreID'])) {
        sendError('invalid film ID or genre ID');
    }
    if (
        request('SELECT `filmID` FROM `films` WHERE `filmID` = ' . $state['params']['filmID'] . ' LIMIT 1')['numRows'] == 1 &&
        request('SELECT `genreID` FROM `genres` WHERE `genreID` = ' . $state['params']['genreID'] . ' LIMIT 1')['numRows'] == 1
        ) {
        request('UPDATE `films` SET `genreID` = ' . $state['params']['genreID'] . ' WHERE `filmID` = ' . $state['params']['filmID'] . ' LIMIT 1 ');
        $state['response'] = array(
                'answer' => 'filmName\'s genre successfully modified'
            );
    } else {
        sendError('invalid film ID or genre ID to modify');
    }
}

/**
 * добавление актера
 *
 * actorFullName
 */
function addActor()
{
    global $state;
    if (!isset($state['params']['actorFullName'])) {
        sendError('invalid actor name');
    }
    if (request('SELECT `actorID` FROM `actors` WHERE `actorFullName` = \'' . $state['params']['actorFullName'] . '\' LIMIT 1')['numRows'] == 0) {
        request('INSERT INTO `actors` (`actorFullName`) VALUES (\'' . $state['params']['actorFullName'] . '\')');
        $state['response'] = array(
            'answer' => 'actor name ' . $state['params']['actorFullName'] . ' successfully added'
        );
    } else {
        sendError('actor name ' . $state['params']['actorFullName'] . ' exists');
    }
}

/**
 * удаление актера
 *
 * actorID
 */
function deleteActor()
{
    global $state;
    if (!isset($state['params']['actorID'])) {
        sendError('invalid actor ID');
    }
    if (request('SELECT `actorID` FROM `actors` WHERE `actorID` = \'' . $state['params']['actorID'] . '\' LIMIT 1')['numRows'] == 1) {
        request('DELETE `actorID` FROM `actors` WHERE `actorID` = \'' . $state['params']['actorID'] . '\' LIMIT 1');
        request('DELETE `actorID` FROM `links` WHERE `actorID` = ' . $state['params']['actorID']);
        $state['response'] = array(
            'answer' => 'actor ID ' . $state['params']['actorID'] . ' successfully deleted'
        );
    } else {
        sendError('actor ID ' . $state['params']['actorID'] . ' not exists');
    }
}

/**
 * редактирование имени актера
 *
 * actorID
 * actorFullName
 */
function editActorName()
{
    global $state;
    if (!isset($state['params']['actorID']) || !isset($state['params']['actorFullName'])) {
        sendError('invalid actor data');
    }
    if (request('SELECT `actorID` FROM `actors` WHERE `actorID` = ' . $state['params']['actorID'] . ' LIMIT 1')['numRows'] == 1) {
        request('UPDATE `actors` SET `actorFullName` = \'' . $state['params']['actorFullName'] . '\' WHERE `actorID` = ' . $state['params']['actorID'] . ' LIMIT 1 ');
        $state['response'] = array(
            'answer' => 'actor name ' . $state['params']['actorFullName'] . ' successfully modified'
        );
    } else {
        sendError('invalid actor ID to search and edit');
    }
}

/**
 * добавление жанра
 *
 * genreName
 */
function addGenre()
{
    global $state;
    if (!isset($state['params']['genreName'])) {
        sendError('invalid genre name');
    }
    if (request('SELECT `genreID` FROM `genres` WHERE `genreName` = \'' . $state['params']['genreName'] . '\' LIMIT 1')['numRows'] == 0) {
        request('INSERT INTO `genres` (`genreName`) VALUES (\'' . $state['params']['genreName'] . '\')');
        $state['response'] = array(
            'answer' => 'genre name ' . $state['params']['genreName'] . ' successfully added'
        );
    } else {
        sendError('genre name ' . $state['params']['genreName'] . ' exists');
    }
}

/**
 * удаление жанра
 *
 * genreID
 */
function deleteGenre()
{
    global $state;
    if (!isset($state['params']['genreID'])) {
        sendError('invalid genre ID');
    }
    if (request('SELECT `genreID` FROM `genres` WHERE `genreID` = \'' . $state['params']['genreID'] . '\' LIMIT 1')['numRows'] == 1) {
        if (request('SELECT `genreID` FROM `films` WHERE `genreID` = \'' . $state['params']['genreID'] . '\' LIMIT 1')['numRows'] == 0) {
            request('DELETE `genreID` FROM `genres` WHERE `genreID` = \'' . $state['params']['genreID'] . '\' LIMIT 1');
            $state['response'] = array(
                'answer' => 'genre ID ' . $state['params']['genreID'] . ' successfully deleted'
            );
        } else {
            sendError('genre ID ' . $state['params']['genreID'] . ' connected some films');
        }
    } else {
        sendError('genre ID ' . $state['params']['genreID'] . ' not exists');
    }
}

/**
 * редактирование названия жанра
 *
 * genreID
 * genreName
 */
function editGenreName()
{
    global $state;
    if (!isset($state['params']['genreID']) || !isset($state['params']['genreName'])) {
        sendError('invalid genre data');
    }
    if ($request('SELECT `genreID` FROM `genres` WHERE `genreID` = ' . $state['params']['genreID'] . ' LIMIT 1')['numRows'] == 1) {
        request('UPDATE `genres` SET `genreName` = \'' . $state['params']['genreName'] . '\' WHERE `genreID` = ' . $state['params']['genreID'] . ' LIMIT 1 ');
        $state['response'] = array(
            'answer' => 'genre name ' . $state['params']['genreName'] . ' successfully modified'
        );
    } else {
        sendError('invalid genre ID to search and edit');
    }
}

/**
 * добавление актера к фильму
 *
 * filmID
 * actorID
 */
function addActorToFilm()
{
    global $state;
    if (!isset($state['params']['filmID']) || !isset($state['params']['actorID'])) {
        sendError('invalid genre data');
    }
    if ($request('SELECT `actorID` FROM `links` WHERE `actorID` = ' . $state['params']['actorID'] . ' AND `filmID` = ' . $state['params']['actorID'] . ' LIMIT 1')['numRows'] == 0) {
        request('INSERT INTO `links` (`actorID`, `filmID`) VALUES (' . $state['params']['actorID'] . ', ' . $state['params']['filmID'] . ')');
        $state['response'] = array(
            'answer' => 'actor ID ' . $state['params']['actorID'] . ' successfully added to film ID ' . $state['params']['filmID']
        );
    } else {
        sendError('actor ID and film ID connected already');
    }
}

/**
 * удаление актера у фильма
 *
 * filmID
 * actorID
 */
function delActorFromFilm()
{
    global $state;
    if (!isset($state['params']['filmID']) || !isset($state['params']['actorID'])) {
        sendError('invalid genre data');
    }
    if ($request('SELECT `actorID` FROM `links` WHERE `actorID` = ' . $state['params']['actorID'] . ' AND `filmID` = ' . $state['params']['actorID'] . ' LIMIT 1')['numRows'] == 1) {
        request('DELETE `filmID` FROM `links` WHERE `actorID` = ' . $state['params']['genreID'] . ' AND `filmID` = ' . $state['params']['filmID'] . ' LIMIT 1');
        $state['response'] = array(
            'answer' => 'actor ID ' . $state['params']['actorID'] . ' successfully added to film ID ' . $state['params']['filmID']
        );
    } else {
        sendError('actor ID and film ID connected already');
    }
}
