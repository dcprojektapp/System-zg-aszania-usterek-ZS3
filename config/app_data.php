<?php
// config/app_data.php

// Posortowana statyczna lista nauczycieli używana w widoku
$teachers = [
    'Jan Nowak',
    'Jan Kowalski'
    
];
sort($teachers, SORT_LOCALE_STRING); // Gwarancja sortowania alfabetu PL np. (Ś, Ż), bez narzutu w GUI per req


// Posortowana naturalnie statyczna lista sal lekcyjnych
$rooms = [
    '1',
    '1a',
    '1b',
    '2',
    '3',
    '4a',
    '4b',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
    '11',
    '12',
    '13',
    '14',
    '15',
    '16',
    '17',
    '18',
    '19',
    '20',
    'B1',
    'B2',
    'B3',
    'B4',
    'B5',
    'B6',
    'bo1',
    'bo2',
    'bo3',
    'bo4',
    'cen',
    'int',
    'JO',
    'k1',
    'k2',
    'k3',
    'k4',
    'K5',
    'kate1',
    'kate2',
    'kate3',
    'kate4',
    'kuch.',
    'm1',
    'm2',
    'm3',
    'm4',
    'm5',
    'Sala',
    'wdż'
];
sort($rooms, SORT_NATURAL | SORT_FLAG_CASE);
