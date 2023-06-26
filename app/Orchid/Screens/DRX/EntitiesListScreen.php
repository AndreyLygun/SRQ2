<?php

namespace App\Orchid\Screens\DRX;

use http\Client\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Orchid\Screen\Screen;
use App\DRX\DRXClient;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class EntitiesListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $DRXEntity = "IOfficialDocuments";

    public function query(): iterable
    {
        $odata = new DRXClient();
        $total = $odata->from($this->DRXEntity)->count();
        $p = $this->pagination($total);
        $entities = $odata->from($this->DRXEntity)->take($p["per_page"])->skip(($p["page"]-1)*$p["per_page"])->get();
        return [
                "entities" => $entities,
                "pagination" => $p
        ];
    }

    public function pagination($total, $perPage = 10)
    {
        $pagination = [];
        $pagination["total"] = $total;
        $page = request("page")??1;
        $pagination["per_page"] = request("per_page")??$perPage;
        $pagination["page"] = $page;
        $pagination["last_page"] = (int)ceil($total / $pagination["per_page"]);
        $pagination["first_page_url"] = http_build_query(array_merge(\request()->all(), ["page" => 1]));
        $pagination["last_page_url"] = http_build_query(array_merge(\request()->all(), ["page" => $pagination["last_page"]]));
        $pagination["prev_page_url"] = $page>0?http_build_query(array_merge(request()->all(), ["page" => $page-1])):"";
        $pagination["next_page_url"] = $page<$total?http_build_query(array_merge(request()->all(), ["page" => $page+1])):"";
        return $pagination;
    }


    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Все документы';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
//        dd($this->query()["entities"]);
        return [
            Layout::table("entities", [
                TD::make("Id", "Id")->render(fn($item)=>$item["Id"]),
                TD::make("Name", "Название")->render(fn($item)=>"<a href='/admin/{$item["@odata.type"]}?id={$item["Id"]}'>{$item["Name"]}</a>")->sort(),
                TD::make("DocumentDate", "Дата")->render(fn($item)=>$item["DocumentDate"])->sort()
            ]),
            Layout::view("Pagination", ["pagination" => $this->query("pagination")])
        ];
    }
}
