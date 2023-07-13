<?php

namespace App\Orchid\Screens\DRX;

use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Actions\Button;
use Carbon\Carbon;



class SecuritySRQScreen extends AbstractSRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsSecuritySRQScreen";

    public function ExpandFields() {
        return parent::ExpandFields();
    }

    public function NewEntity() {
        $entity = parent::NewEntity();
        $entity["ResponsibleName"] = Auth()->user()->name;
        return $entity;
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        //dd($this->query());
        $layout = Layout::rows([
                Input::make("entity.ResponsibleName")->title("Ответственный сотрудник")->horizontal(),
                Input::make("entity.ResponsiblePhone")->title("Телефон ответственного сотрудника")->horizontal(),
            ]);
        return array_merge(parent::layout(), [$layout]);
    }
}
