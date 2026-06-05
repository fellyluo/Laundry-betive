<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    /** Halaman landing publik (etalase fitur aplikasi). */
    public function index()
    {
        return view('landing.index');
    }
}
