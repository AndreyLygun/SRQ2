<?php

namespace App\Orchid\Screens\DRX;


use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;



class Pass4AssetsMovingSRQScreen extends SecuritySRQScreen
{

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsPass4AssetsMovings";
    // Название документа
    public $Title = "Заявка на перемещение ТМЦ";
    // Перечнь полей-коллекций (табличных), которые обрабатываются специальным образом
    public $CollectionFields = ["Cars", "Loaders"];

    public function ExpandFields()
    {
        $ExpandFields = ["Loaders", "Cars"];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOn")->title("Дата посещения")->horizontal()->enableTime(false)->format('Y-m-d'),
                TextArea::make('entity.Assets')->title('Описание ТМЦ')->horizontal(),
                Matrix::make("entity.Loaders")->columns(['ФИО' => 'Name'])->title("Сотрудники перевозчика")->horizontal(),
                Matrix::make("entity.Cars")->columns(['Модель' => 'Model', 'Номер' => 'Number', "Примечание" => 'Note'])->title("Автомобили")->horizontal()
            ]);
        return $layout;
    }
}
