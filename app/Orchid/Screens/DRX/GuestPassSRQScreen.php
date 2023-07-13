<?php

namespace App\Orchid\Screens\DRX;

use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Actions\Button;
use Carbon\Carbon;
use Orchid\Support\Facades\Toast;



class GuestPassSRQScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsGuestPassSRQs";
    public $Title = "Заявка на разовый пропуск";

    public function ExpandFields() {
        $ExpandFields = parent::ExpandFields();
        $ExpandFields[] = "Guests";
        return $ExpandFields;
    }

    public function NewEntity() {
        $entity = parent::NewEntity();
        $entity["ValidDate"] = Carbon::tomorrow()->toDateString();
        return $entity;
    }

    public function Save() {
        $this->entity = \Request()->get('entity');
        if (isset($this->entity['Guests']))
            $this->entity['Guests'] = array_values($this->entity['Guests']);
        $this->DeleteCollectionProperty('Guests');
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
                DateTimer::make("entity.ValidDate")->title("Дата посещения")->horizontal()->enableTime(false)->format('Y-m-d'),
                Matrix::make("entity.Guests")->columns(['ФИО' => 'Name'])->title("Посетители")->horizontal()
            ]);
        return $layout;
    }
}
