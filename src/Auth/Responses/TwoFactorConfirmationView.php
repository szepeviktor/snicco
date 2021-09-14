<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewFactory;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ViewInterface;
use Snicco\Auth\Contracts\Abstract2FAuthConfirmationView;

class TwoFactorConfirmationView extends Abstract2FAuthConfirmationView
{
    
    private ViewFactory $view_factory;
    
    public function __construct(ViewFactory $view_factory)
    {
        
        $this->view_factory = $view_factory;
    }
    
    public function toView(Request $request) :ViewInterface
    {
        return $this->view_factory->make('auth-layout')->with([
            'view' => 'auth-two-factor-challenge',
            'post_to' => $request->path(),
        ]);
    }
    
}