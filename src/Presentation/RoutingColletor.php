<?php
namespace App\Presentation;

use Core\Http\Action;
use Core\Http\Interfaces\ActionInterface;
use Spatie\StructureDiscoverer\Discover;

final class RoutingColletor
{

    public static function getActions()
    {
        

        $el = Discover::in(__DIR__)->classes();
        return $el->extending(Action::class)->get();
    }
}
