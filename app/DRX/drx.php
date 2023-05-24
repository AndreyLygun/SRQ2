<?php
namespace DrxClient;

use Faker\Provider\Base;
use SaintSystems\OData\ODataClient;



class DrxClient extends ODataClient
{
    protected string $url;
    protected string $login;
    protected string $password;
	public function __construct(string $url, string $login, string $password) {
        $this->url = $url;
        $this->login = $login;
        $this->password = $password;
        parent::__construct($url, function($request) {
            // OAuth Bearer Token Authentication
            //$request->headers['Authorization'] = 'Bearer '.$accessToken;
            // OR Basic Authentication
            $username = 'lygun';
            $password = '31185';
            $request->headers['Authorization'] = 'Basic '.base64_encode($username.':'.$password);
        });
    }
}

?>
