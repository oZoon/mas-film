<?php
$state = array();

error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('expose_php', 0);
ini_set('request_order', 'EGPCS');
ini_set('variables_order', 'EGPCS');
ini_set('session.use_cookies', 0);
header_remove('x-powered-by');
mb_internal_encoding("UTF-8");

$state['mysql'] = new mysqli('localhost', 'masha', '12345', 'masha');
$state['allowGET'] = array('getAllFilms', 'getFilmID');
$state['allowPOST'] = array(
    'addFilm',
    'deleteFilm',
    'editFilmName',
    'editFilmGenre',
    'addActor',
    'deleteActor',
    'editActorName',
    'addGenre',
    'deleteGenre',
    'editGenreName',
    'addActorToFilm',
    'delActorFromFilm'
);
$state['allowParams'] = array('filterActor', 'filterGenre', 'sort', 'filmID');
$state['checkNum'] = array('filmID', 'genreID', 'actorID');
$state['checkStr'] = array('filmName', 'actorFullName', 'genreName');

include_once 'functions.php';
