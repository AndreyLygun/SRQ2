<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Illuminate\Support\Facades\Request;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;



class BaseSRQScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */


    public $EntityType = "IServiceRequestsBaseSRQs";     // Имя сущности в сервисе интеграции, например IOfficialDocuments
    public $CollectionFields = [];                      // Список полей-коллекций, которые нужно пересоздавать заново при каждом сохранении
    public $Title = "Заявка";
    protected  $exFields = ["Car2", "Car3"];

    public $entity;

    // Возвращает список полей-ссылок и полей-коллекций, который используются в форме. Нужен, чтобы OData-API вернул значения этих полей
    // Как правило, перекрытый метод в классе-наследнике добавляет свои поля к результату метода из класса-предка
    public function ExpandFields() {
        $ExpandFields = ["Author", "DocumentKind", "Renter"];
        return $ExpandFields;
    }



    // Используется для заполнения значений для новых сущностей (значения по-умолчанию).
    public function NewEntity() {
        $entity = [
            "Renter" => ['Name' => Auth()->user()->DrxAccount->Name],
            "Creator" => Auth()->user()->name,
            "RequestState" => "Draft"
        ];
        return $entity;
    }

    public function query($id = null): iterable
    {
        if ($id) {
            $odata = new DRXClient();
            $query = $odata->from($this->EntityType);
            if ($this->ExpandFields())
                $query = $query->expand($this->ExpandFields());
            $entity = $query->find((int)$id);
        } else {
            $entity = $this->NewEntity();
        }
        return ["entity" => $entity];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->Title;
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {

        $buttons = [];
        switch ($this->entity["RequestState"]) {
            case 'Draft':
                if (isset($this->entity["Id"]))
                    $buttons[] = Button::make("Отправить на согласование")->method("Submit");
                $buttons[] = Button::make("Сохранить")->method("Save");
                break;
            case 'Active':
                $buttons[] = Button::make("Одобрено")->disabled();
                break;
            case 'Obsolete':
                $buttons[] = Button::make("Устарел")->disabled();
                break;
            case 'OnReview':
                $buttons[] = Button::make("На рассмотрении")->disabled();
                break;
            case 'Prelimenary':
                break;
            case 'Declined':
                $buttons[] = Button::make("Отказ")->disabled();
                break;
        }

        return $buttons;
    }


    //Полностью удаляет свойство коллекцию экземпляра
    //Метод нужно вызвать из перекрытого класса Save для свойств-коллекцйи
    public function DeleteCollectionProperty($CollectionName) {
        if (!isset($this->entity['Id'])) return;
        $odata = new DRXClient();
        $odata->delete("{$this->EntityType}({$this->entity['Id']})/$CollectionName");
    }

    public function Save() {
//        $this->entity["Name"] = '-';
        $odata = new DRXClient();
        $entity = request()->get('entity');
        $entityType = $this->EntityType;
        $Id = $entity['Id']??null;
        unset($entity['Id']);

        // Обрабатываем поля-коллекции из списка $this->CollectionFields
        foreach($this->CollectionFields as $cf) {
            if (isset($entity[$cf])) {
                // ..исправлям баг или фичу, по которой поле Matrix начинает нумерацию строк с единицы
                $entity[$cf] = array_values($entity[$cf]);
                // ..потом очищаем поле на сервере DRX, чтоб заполнить его новыми значениями
                if ($Id) $odata->delete("{$entityType}({$Id})/$cf");
            }
        }
        if ($Id) {
            // Обновляем запись
            $odata->patch("{$entityType}({$Id})", $entity);
        } else {
            // Создаём запись
            $entity = $odata->post("{$entityType}", $entity)[0];
            $Id = $entity["Id"];
        }
        Toast::info("Успешно сохранено");
        return redirect(route(Request::route()->getName()) . "/" . $Id);
    }

    public function Submit() {
        $this->entity['RequestState'] = 'OnReview';
        $this->Save();
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make("entity.Id")->type("hidden"),
                Label::make("entity.RequestState")->title("Состояние заявки")->horizontal(),
                Label::make("entity.Renter.Name")->title("Название компании")->horizontal(),
                Label::make("entity.ResponsibleName")->title("Автор заявки")->horizontal()
             ])
        ];
    }
}
