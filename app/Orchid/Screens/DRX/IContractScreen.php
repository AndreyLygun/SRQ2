<?php

namespace App\Orchid\Screens\DRX;

use http\Client\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use App\DRX\DRXClient;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class IContractScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $DRXEntity = "IContracts";

    public function query(): iterable
    {
        $id = (int) \request("id");
            $odata = new DRXClient();


        $entity = $odata->from($this->DRXEntity)->find($id);
        return [
                "entity" => $entity,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return($this->query()["entity"]["Name"]);
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
//        dd($this->query());
        return [
            Layout::rows([
                Input::make("entity.Name")->title("Имя документа"),
                Input::make("entity.Id")->title("Id")
            ])
        ];
    }
}
