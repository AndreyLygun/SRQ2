<?php

namespace App\Orchid\Screens\DRX;

use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Carbon\Carbon;



class SecuritySRQScreen extends BaseSRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsSecuritySRQScreen";
    public $entity;

    public function ExpandFields() {
        return parent::ExpandFields();
    }

    public function NewEntity() {
        $entity = parent::NewEntity();
        $entity["ResponsibleName"] = Auth()->user()->name;
        $entity["ValidTo"] = $entity["ValidFrom"] = $entity["ValidOn"] = Carbon::tomorrow();
        return $entity;
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $requestState = $this->entity["RequestState"];
        $layout = Layout::rows([
                Input::make("entity.ResponsibleName")->title("Ответственный сотрудник")->horizontal()->readonly(!in_array($requestState, ["Draft", "Declined"])),
                Input::make("entity.ResponsiblePhone")->title("Телефон сотрудника")->horizontal()->readonly(!in_array($requestState, ["Draft", "Declined"])),
            ]);
        return array_merge(parent::layout(), [$layout]);
    }
}
