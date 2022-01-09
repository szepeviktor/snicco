<?php

declare(strict_types=1);

namespace Snicco\Core\EventDispatcher\Events;

use Snicco\Support\Str;

use function rest_get_url_prefix;

class IncomingWebRequest extends IncomingRequest
{
    
    public function shouldDispatch() :bool
    {
        return $this->request->isFrontend()
               && ! Str::contains(
                $this->request->path(),
                rest_get_url_prefix()
            );
    }
    
}