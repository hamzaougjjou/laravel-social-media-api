<?php

namespace App\Http\Controllers;

use App\Models\AnimalsBreed;
use Illuminate\Http\Request;

class AnimalsBreedsController extends Controller
{
    //get all animales breeds 
    public function index()
    {
        //$breed = AnimalsBreed::all();
        $breed =$breeds = AnimalsBreed::all(['id','name']);
        return response()->json([
            "success"=>true ,
            "breeds" => $breed,
            "message"=>'animales breeds retrieved successfully'
        ],200);
    }
}
