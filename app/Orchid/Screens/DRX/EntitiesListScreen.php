<?php

namespace App\Orchid\Screens\DRX;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use App\DRX\DRXClient;
use App\DRX\ExtendedTD;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Carbon\Carbon;



class EntitiesListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $DRXEntity = "IsrqAbstractRequests";

    //Возвращает список ссылочных свойств (через запятую), которые должны быть получены в запросе
    public function ExpandFields(): string
    {
        return "Author";
    }

    public function query(): iterable
    {
        $odata = new DRXClient();
        $total = $odata->from($this->DRXEntity)->count();
        $p = $this->pagination($total);
        $entities = $odata->from($this->DRXEntity)
            ->take($p["per_page"])
            ->skip(($p["page"]-1)*$p["per_page"])
            ->expand($this->ExpandFields())
            ->get();
        // dd($entities);
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
        return 'Все заявки';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            DropDown::make("Создать заявку...")->list([
                    Link::make("...на разовый пропуск")->route("drx.GuestPassSRQDto"),
                    Link::make("...на разовый автопропуск")->route("drx.AutoPassSRQDto")
            ])
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        //dd($this->query()["entities"]);
        $LifeCycles = config('srq.LifeCycles');
        return [
            Layout::table("entities", [
                ExtendedTD::make("Id", "№")
                    ->render(fn($item)=>$item["Id"])
                    ->cssClass(fn($item)=>$item["LifeCycleState"]),
                ExtendedTD::make("Kind", "Вид заявки")
                    ->render(fn($item)=>"<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Kind"]}</a>")
                    ->cssClass(fn($item)=>$item["LifeCycleState"])
                    ->sort(),
                ExtendedTD::make("Name", "Содержание")
                    ->render(fn($item)=>"<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Name"]}</a>")
                    ->cssClass(fn($item)=>$item["LifeCycleState"])
                    ->sort(),
                ExtendedTD::make("Created", "Дата создания")
                    ->render(fn($item)=>Carbon::parse($item["Created"])->format('d/m/y'))
                    ->cssClass(fn($item)=>$item["LifeCycleState"])
                    ->sort()->filter(),
                ExtendedTD::make("LifeCycleState", "Статус")
                    ->render(fn($item)=>$LifeCycles[$item["LifeCycleState"]])
                    ->cssClass(fn($item)=>$item["LifeCycleState"])
                    ->filter(TD::FILTER_SELECT, $LifeCycles)
            ]),
            Layout::view("Pagination", ["pagination" => $this->query("pagination")])
        ];
    }
}
