<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\TimeConverter;

class TestController extends TimeConverter
{
    public function index(){
        return TimeConverter::secondsToTime(1000);
    }

}