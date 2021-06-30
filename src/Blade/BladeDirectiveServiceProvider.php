<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

    use Illuminate\Support\Facades\Blade;
    use Tests\unit\View\MethodField;
    use WPEmerge\Application\ApplicationTrait;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Facade\WP;
    use WPEmerge\Session\CsrfField;

    class BladeDirectiveServiceProvider extends ServiceProvider
    {

        public function register() : void
        {
           //
        }

        function bootstrap() : void
        {

            $foo = 'bar';

            Blade::if('auth', function () {

                return WP::isUserLoggedIn();

            });

            Blade::if('guest', function () {

                return ! WP::isUserLoggedIn();

            });

            Blade::if('role', function ($expression) {

                if ($expression === 'admin') {

                    $expression = 'administrator';

                }

                return WP::userIs($expression);

            });

            Blade::directive('service', function ($expression) {

                $segments = explode(',', preg_replace("/[()]/", '', $expression));

                $variable = trim($segments[0], " '\"");

                $service = trim($segments[1]);

                $app = $this->container->make(ApplicationTrait::class);

                $php = "<?php \${$variable} = {$app}::resolve({$service}::class); ?>";

                return $php;

            });

            Blade::directive('csrf', function () {

                /** @var CsrfField $csrf_field */
                $csrf_field = $this->container->make(CsrfField::class);

                $html = $csrf_field->asHtml();

                return "<?php echo '{$html}' ?>";


            });

            Blade::directive('method', function ($method) {

                /** @var MethodField $method_field */
                $method_field = $this->container->make(MethodField::class);
                $html = $method_field->html(str_replace("'", "", $method));

                return "<?php declare(strict_types=1); echo \"{$html}\";";


            });


        }

    }