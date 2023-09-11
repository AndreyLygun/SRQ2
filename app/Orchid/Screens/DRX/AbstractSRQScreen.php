<?php

namespace App\Orchid\Screens\DRX;

use Illuminate\Http\Request;
use App\DRX\DRXClient;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;



class AbstractSRQScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsAbstractSRQs";
    public $Title = "Заявка";
    public $entity;


    // Возвращает список полей-ссылок и полей-коллекций, который используются в форме. Нужен, чтобы OData-API вернул значения этих полей
    // Как правило, перекрытый метод в классе-наследнике добавляет свои поля к результату метода из класса-предка
    public function ExpandFields() {
        return [];
    }


    // Используется для заполнения значений для новых сущностей (значения по-умолчанию).
    public function NewEntity() {
        $entity = [
            "ExternalAccount" => Auth()->user()->DrxAccount,
            "ExternalUser" => Auth()->user()->name;
            "LifeCycleState" -> "Draft"
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
        if ($this->entity['Id'] != null) {
            $Id = $this->entity['Id'];
            unset($this->entity['Id']);
            $odata->patch("{$this->EntityType}({$Id})", $this->entity);
        } else {
            try {
                unset($this->entity['Id']);
                dd($this->entity);
                $entity = $odata->post("{$this->EntityType}", $this->entity);
            }
            catch(ClientException $e) {
                dd($e);
            }
        }
        Toast::info('Что-то сохранилось');
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
                Label::make("entity.LifeCycleState")->title("Состояние заявки")->horizontal(),
                Label::make("entity.ExternalAccount.Name")->title("Название компании")->horizontal(),
                Label::make("entity.ResponsibleName")->title("Автор заявки")->horizontal()
             ])
        ];
    }
}
