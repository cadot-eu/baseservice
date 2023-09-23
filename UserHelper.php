<?php

namespace App\Service\base;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use App\Entity\User;

class UserHelper
{

    private $loginLinkHandler;

    public function __construct(LoginLinkHandlerInterface $loginLinkHandler)
    {
        $this->loginLinkHandler = $loginLinkHandler;
    }
    function generate(User $user)
    {
        $loginLinkDetails = $this->loginLinkHandler->createLoginLink($user);
        $loginLink = $loginLinkDetails->getUrl();
        return $loginLink;
    }
}
