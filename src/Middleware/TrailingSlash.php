<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\Str;
    use Snicco\Support\Url;

    class TrailingSlash extends Middleware
    {

        private bool $trailing_slash;

        public function __construct(bool $trailing_slash = true)
        {
            $this->trailing_slash = $trailing_slash;
        }

        public function handle(Request $request, Delegate $next) :ResponseInterface
        {

            if ( ! $request->isWpFrontEnd() ) {
                return $next($request);
            }

            $path = $request->path();

            $accept_request = $this->trailing_slash
                ? Str::endsWith($path, '/')
                : Str::doesNotEndWith($path, '/');

            if ( $accept_request || $path === '/') {

                return $next($request);

            }

            $redirect_to = $this->trailing_slash
                ? Url::addTrailing($path)
                : Url::removeTrailing($path);

            return $this->response_factory->permanentRedirectTo($redirect_to);

        }

    }