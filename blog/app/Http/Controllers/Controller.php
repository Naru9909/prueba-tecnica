<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * PREGUNTA: En Laravel 12 el Controller base ya no incluye el trait
 * AuthorizesRequests por defecto. ¿Por qué crees que se quitó?
 * ¿Qué alternativas existen para autorizar acciones sin este trait?
 */
abstract class Controller
{
    use AuthorizesRequests;
}
