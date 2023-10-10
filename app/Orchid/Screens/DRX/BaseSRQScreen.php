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
    public $Title = '';
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
            $entity = $odata->getEntity($this->EntityType, $id, $this->ExpandFields());
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
        if (isset($this->entity['Id']))
            return $this->entity['DocumentKind']['Name']  . ' (' . $this->entity['Id'] . ')';
        else
            return $this->Title  . ' (новая)' ;
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {

        $buttons = [];
//        $buttons[] = Button::make("Копировать");
        switch ($this->entity["RequestState"]) {
            case 'Draft':
                if (isset($this->entity["Id"])) {
//                    $buttons[] = Button::make("Удалить")->method("Delete")->confirm('Удалить заявку?');
                    $buttons[] = Button::make("Отправить на согласование")->method("Submit");
                }
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


    /**
     * @return mixed|object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */

    //TODO: исправить сохранение инициатора заявки: сейчас сохраняется арендатор вместо сотрудника
    public function SaveEntity() {
        $this->entity['Creator'] = Auth()->user()->name;
        $odata = new DRXClient();
        $entity = $odata->saveEntity($this->EntityType, $this->entity, $this->ExpandFields(), $this->CollectionFields);
        return $entity;
    }

    public function Save() {
        $this->entity = request()->get('entity');
        $this->entity = $this->SaveEntity();
        Toast::info("Успешно сохранено");
        return redirect(route(Request::route()->getName()) . "/" . $this->entity['Id']);
    }

    public function Submit() {
        $this->entity = request()->get('entity');
        $this->entity['RequestState'] = 'OnReview';
        $this->SaveEntity();
        Toast::info("Заявка сохранена и отправлена на согласование");
        return redirect(route('drx.srqlist'));
    }

    public function Delete() {
        $odata = new DRXClient();
        $odata->deleteEntity($this->EntityType, request('entity.Id'));
        Toast::info("Заявка удалена");
        return redirect(route('drx.srqlist'));
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
                Label::make("entity.RequestState")-> title("Состояние заявки")->horizontal(),
                Label::make("entity.Renter.Name")->title("Название компании")->horizontal(),
                Label::make("entity.Creator")->title("Автор заявки")->horizontal()
             ])
        ];
    }
}
