<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Exceptions;

    use Throwable;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;
    use WPEmerge\Http\ResponseFactory;

    class InvalidSignatureException extends HttpException
    {

        public function __construct(?string $message_for_humans = 'You cant access this page.', Throwable $previous = null, ?int $code = 0)
        {
            parent::__construct(403, $message_for_humans, $previous, $code);
        }

        public function render(ResponseFactory $response_factory ) {

            /** @todo Refactor once we have inbuilt error pages. */
            return $response_factory->html($this->getMessage(), $this->getStatusCode());

        }


    }