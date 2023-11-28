<?php
namespace Mainio\C5\Twig;

use Core;
use Page;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Concrete5Extension extends AbstractExtension
{

    protected $uiHelper;

    public function __construct()
    {
        $this->uiHelper = Core::make('helper/concrete/ui');
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('url_to', [$this, 'urlTo']),
            new TwigFunction('action', [$this, 'action']),
            new TwigFunction('interface_button', [$this, 'interfaceButton'], ['is_safe' => ['html']]),
            new TwigFunction('interface_submit', [$this, 'interfaceSubmit'], ['is_safe' => ['html']]),
        ];
    }

    public function action()
    {
        $c = Page::getCurrentPage();
        $controller = $c->getPageController();
        return call_user_func_array([$controller, 'action'], func_get_args());
    }

    public function urlTo()
    {
        return call_user_func_array(['URL', 'to'], func_get_args());
    }

    public function interfaceButton()
    {
        return call_user_func_array([$this->uiHelper, 'button'], func_get_args());
    }

    public function interfaceSubmit()
    {
        return call_user_func_array([$this->uiHelper, 'submit'], func_get_args());
    }

    public function getName()
    {
        return 'concrete5';
    }
}
