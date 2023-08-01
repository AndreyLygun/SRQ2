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
        return ["ExternalAccount", "DocumentKind"];
    }


    // Используется для заполнения значений для новых сущностей (значения по-умолчанию).
    public function NewEntity() {
        $entity["ExternalAccount"] = Auth()->user()->DrxAccount;
        $entity["ExternalUser"] = Auth()->user()->name;
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
        return [
            Button::make("Отправить на согласование")->method("Submit"),
            Button::make("Сохранить")->method("Save"),
        ];
    }


    //Полностью удаляет свойство коллекцию экземпляра
    public function DeleteCollectionProperty($CollectionName) {
        if (!isset($this->entity['Id'])) return;
        $odata = new DRXClient();
        $odata->delete("{$this->EntityType}({$this->entity['Id']})/$CollectionName");
    }

    public function Save() {
        $odata = new DRXClient();
        if ($this->entity['Id'] != null) {
            $odata->patch("{$this->EntityType}({$this->entity['Id']})", $this->entity);
        } else {
            try {
                unset($this->entity['Id']);
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
        //dd($this->query()["entity"]);
        return [
            Layout::columns([
                Layout::rows([
                    Input::make("v1")->title("Что-то 1"),
                    Input::make("v1")->title("Что-то 2"),
                ])->,
                Layout::rows([
                    Input::make("v1")->title("Что-то 3"),
                    Input::make("v1")->title("Что-то 4"),
                ])
            ]),
            Layout::rows([
                Input::make("entity.Id")->type("hidden"),
                Label::make("entity.ExternalAccount.Name")->title("Название компании")->horizontal(),
                Label::make("entity.ResponsibleName")->title("Автор заявки")->readonly()->horizontal()
             ])
        ];
    }
}
