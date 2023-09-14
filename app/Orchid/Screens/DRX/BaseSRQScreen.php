<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
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

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsBaseSRQs";
    public $Title = "Заявка";
    public $entity;


    // Возвращает список полей-ссылок и полей-коллекций, который используются в форме. Нужен, чтобы OData-API вернул значения этих полей
    // Как правило, перекрытый метод в классе-наследнике добавляет свои поля к результату метода из класса-предка
    public function ExpandFields() {
        return ["Author", "DocumentKind", "Renter"];
    }


    // Используется для заполнения значений для новых сущностей (значения по-умолчанию).
    public function NewEntity() {
        $entity = [
//            "Author" => Auth()->user()->DrxAccount,
            "Renter" => [
                "Id" => Auth()->user()->DrxAccount["Id"],
                "Name" => Auth()->user()->DrxAccount["Name"]
            ],
            "Creator" => Auth()->user()->name,
            "LifeCycleState" => "Draft"
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
        switch ($this->entity["LifeCycleState"]) {
            case 'Draft':
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
        $odata = new DRXClient();
        $Id = $this->entity['Id']??null;
        unset($this->entity['Id']);
        if ($Id) {
            $odata->patch("{$this->EntityType}({$Id})", $this->entity);
        } else {
            $entity = $odata->post("{$this->EntityType}", $this->entity)[0];
            $Id = $entity["Id"];
        }
        Toast::info('Заявка сохранена');
        return route(Request::route()->getName()) . "/" . $Id;
    }

    public function Submit() {
        Toast::info("Заглушка отправки на согласование.");
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
