<?php

namespace App\Http\Controllers;

class TimeConverter extends Controller
{

    public function _construct(){
        
    }

    public function secondsToTime(string $time_on_seconds)
    {

        $minutes = floor($time_on_seconds / 60);
        $hours = floor($time_on_seconds / 3600);
        $days = floor($time_on_seconds / 86400);
        $months = floor($time_on_seconds / 2628000);

        // return array(
        //     "ceconds"=> $time_on_seconds,
        //     'minutes' => $minutes,
        //     'hours' => $hours,
        //     'days' => $days,
        //     'months' => $months
        // );

        if ( $months > 0 )
            return $months . " months ago";
        if ( $days > 0 )
            return $days . " days ago";
        if ( $hours > 0 )
            return $hours . " hours ago";
        if ( $minutes > 0 )
            return $minutes . " minutes ago";
        if ( $time_on_seconds > 30 )
            return $time_on_seconds . " seconds ago";
        if ( $time_on_seconds > 0 )
            return "just now";

    }
}