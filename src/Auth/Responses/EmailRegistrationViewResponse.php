<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\View\ViewFactory;

    class EmailRegistrationViewResponse extends RegistrationViewResponse
    {

        /**
         * @var ViewFactory
         */
        private $view_factory;

        /**
         * @var UrlGenerator
         */
        private $url;

        public function __construct(ViewFactory $view_factory, UrlGenerator $url)
        {

            $this->view_factory = $view_factory;
            $this->url = $url;
        }

        public function toResponsable()
        {

            return $this->view_factory->make('auth-parent')->with([
                'view' => 'auth-registration-email',
                'post_to' => $this->request->path()
            ]);

        }

    }