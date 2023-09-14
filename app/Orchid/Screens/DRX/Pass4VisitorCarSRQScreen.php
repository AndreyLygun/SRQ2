<?php

namespace App\Orchid\Screens\DRX;

use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Actions\Button;
use Carbon\Carbon;
use Orchid\Support\Facades\Toast;



class Pass4VisitorCarSRQScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsPass4VisitorCars";
    public $Title = "Заявка на разовый автопропуск";

    public function ExpandFields() {
        $ExpandFields = parent::ExpandFields();
        $ExpandFields[] = "Visitors";
        return $ExpandFields;
    }

    public function NewEntity() {
        $entity = parent::NewEntity();
        $entity["ValidDate"] = Carbon::tomorrow()->toDateString();
        return $entity;
    }

    public function Save() {
        $this->entity = \Request()->get('entity');
        if (isset($this->entity['Visitors']))
            $this->entity['Visitors'] = array_values($this->entity['Visitors']);
        $this->DeleteCollectionProperty('Visitors');
        parent::Save();
    }
    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        //dd($this->query());
        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOn")->title("Дата посещения")->horizontal()->enableTime(false)->format('Y-m-d'),
                Input::make("entity.CarModel")->title("Модель автомобиля")->horizontal(),
                Input::make("entity.CarNumber")->title("Номер автомобиля")->horizontal(),
                Matrix::make("entity.Visitors")->columns(['ФИО' => 'Name'])->title("Посетители")->horizontal()
            ]);
        return $layout;
    }
}
