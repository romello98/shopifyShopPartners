<?php

function dateFormat($dateStr, $withTime = true)
{
    if(empty($dateStr)) return 'Date inconnue';
    return strftime('%d/%m/%Y' . ($withTime ? ' - %T' : ''), strtotime($dateStr));
}