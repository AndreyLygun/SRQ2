<?php

namespace App\Orchid\Screens\DRX;



use App\DRX\DRXClient;
use Orchid\Screen\Fields\Select;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;



class Pass4AssetsMovingSRQScreen extends SecuritySRQScreen
{

    public $EntityType = 'IServiceRequestsPass4AssetsMovings';
    public $Title = 'Заявка на перемещение ТМЦ';
    public $CollectionFields = ["Loaders", "Inventory"];
    protected static $ExpandFields = ["Asset1", "Asset2"];
    public $Sites;
    public $TimeSpans;

    public function ExpandFields()
    {
        $ExpandFields = ["Loaders", "Cars", 'Site', 'TimeSpan', 'Inventory'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function query($id = null):iterable {
        $result = parent::query($id);
        $odata = new DRXClient();
        $result['Sites'] = array_reduce($odata->from('IServiceRequestsSites')->get()->toArray(),
            function($carry, $item) {
                $carry[$item['Id']] = $item['Name'];
                return $carry;
            }
        );
        $result['TimeSpans'] = array_reduce($odata->from('IServiceRequestsTimeSpans')->get()->toArray(),
            function($carry, $item) {
                $carry[$item['Id']] = $item['Name'];
                return $carry;
            }
        );
        return $result;
    }

    public function layout(): iterable
    {
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        $layout[] = Layout::rows([
            Select::make('entity.MovingDirection')
                ->title('Направление перемещения')
                ->options(config('srq.MovingDirection'))
                ->horizontal()->required()->empty("Выберите тип перемещения")->disabled($readonly),
            DateTimer::make("entity.ValidOn")->title("Дата перемещения")
                ->horizontal()->enableTime(false)->format('Y-m-d')->disabled($readonly),
            Select::make('entity.Site.Id')->title('Место разгрузки')
                ->options($this->Sites)->horizontal()->empty('')->help('Указать при оформлении ввоза-вывоза')->disabled($readonly),
            Select::make('entity.TimeSpan.Id')->title('Время ввоза-вывоза')
                ->options($this->TimeSpans)->horizontal()->required()->disabled($readonly),
            ExtendedMatrix::make('entity.Inventory')->columns(['Описание' => 'Name', 'Количество' => 'Quantity'])->title("Описание ТМЦ")
                ->horizontal()->readonly($readonly)
        ])->title('Сведения о перемещении');

        $layout[] = Layout::rows([
            Input::make("entity.CarModel")->title("Модель автомобиля")->horizontal(),
            Input::make("entity.CarNumber")->title("Номер автомобиля")->horizontal()->help('Обязательно указать при оформлении ввоза/вывоза'),
            Matrix::make("entity.Loaders")->columns(['ФИО' => 'Name'])->title("Персонал")->horizontal(),
        ])->title('Сведения о перевозчике');
        return $layout;
    }
}
