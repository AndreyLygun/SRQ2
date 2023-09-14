<?php
namespace App\DRX;

use Illuminate\Database\Eloquent\Collection;
use Orchid\Screen\Repository;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\IProcessor;
use Orchid\Filters\Filterable;

class PostProcessor implements IProcessor {
    public function processSelect(Builder $query, $response)
    {
        if (!is_array($response)) return $response;
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
	public function __construct() {
        $url = env("DIRECTUM_INTEGRATION_URL");
        $login = Auth()->user()->DrxAccount->DRX_Login;
        $password = Auth()->user()->DrxAccount->DRX_Password;
        parent::__construct($url, function($request) use ($login, $password) {
            $request->headers['Authorization'] = 'Basic '.base64_encode($login.':'.$password);
        });
        $this->postProcessor = new PostProcessor();
//        $this->setEntityReturnType(Repository::class);
    }

}

?>
