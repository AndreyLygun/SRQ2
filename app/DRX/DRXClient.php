<?php
namespace App\DRX;

use Illuminate\Database\Eloquent\Collection;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\IProcessor;
use Orchid\Filters\Filterable;

class PostProcessor implements IProcessor {
    public function processSelect(Builder $query, $response)
    {
        if (gettype($response) == "string") return $response;
        $result = [];
        foreach($response as $item) {
            $entity = $item["properties"];
            if (isset($entity["@odata.type"])) {
                $chuncks = explode(".", $entity["@odata.type"]);
                $entity["@odata.type"] = end($chuncks);
            }
            $result[] = $entity;
        }
        return $result;
    }
}

class DRXClient extends ODataClient
{
    use Filterable;

    protected $allowedSorts = [
        'id',
        'Name',
        'DocumentDate',
    ];


    protected string $url;
    protected string $login;
    protected string $password;
	public function __construct() {
        $url = env("DIRECTUM_INTEGRATION_URL");
        $login = Auth()->user()->DrxAccount->drx_login;
        $password = Auth()->user()->DrxAccount->drx_password;
        parent::__construct($url, function($request) use ($login, $password) {
            $request->headers['Authorization'] = 'Basic '.base64_encode($login.':'.$password);
        });
        $this->postProcessor = new PostProcessor();
    }

}

?>
