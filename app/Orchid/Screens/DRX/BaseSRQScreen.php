<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Illuminate\Support\Facades\Request;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
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
            if ($expandFields = $this->ExpandFields())
                $query = $query->expand($expandFields);
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
        $buttons[] = Button::make("Копировать");
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


    /**
     * @return mixed|object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function SaveEntity() {
        $odata = new DRXClient();
        $entity = $this->entity;
        $entityType = $this->EntityType;
        $Id = (int) $entity['Id']??null;
        unset($entity['Id']);

        // обрабатываем странное поведение контрола Orchid Select, который возвращает строку вместо целого числа\
        // у нас такая хрень мешает в полях-ссылках (в терминах DRX), которые здесь выглядят как Select::make('entity.somefield.Id')
        // TODO нужно попытаться исправить это в коде контрола
        foreach ($entity as $key => $field) {
            if (isset($field['Id'])) {
                $entity[$key]['Id'] = (int) $field['Id'];
            }
        }
        // Обрабатываем поля-коллекции из списка $this->CollectionFields
        foreach($this->CollectionFields as $cf) {
            if (isset($entity[$cf])) {
                // ..исправлям баг|фичу, из-за которой поле Matrix начинает нумерацию строк с единицы
                $entity[$cf] = array_values($entity[$cf]);
                // ..потом очищаем поле на сервере DRX, чтоб заполнить его новыми значениями
                if ($Id) $odata->delete("{$entityType}({$Id})/$cf");
            }
        }
        //dd(json_encode($entity));
        if ($Id) {
            // Обновляем запись
            $entity = ($odata->from($entityType)->expand($this->ExpandFields())->whereKey($Id)->patch($entity))[0];
        } else {
            // Создаём запись
            $entity = ($odata->from($entityType)->expand($this->ExpandFields())->post($entity))[0];
        }
        return $entity;
    }

    public function Save() {
        $this->entity = request()->get('entity');
        $this->entity = $this->SaveEntity();
        $Id = $this->entity['Id'];
        Toast::info("Успешно сохранено");
        return redirect(route(Request::route()->getName()) . "/" . $Id);
    }

    public function Submit() {
        $this->entity = request()->get('entity');
        $this->entity['RequestState'] = 'OnReview';
        $this->SaveEntity();
        Toast::info("Заявка сохранена и отправлена на согласование");
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
                Label::make("entity.RequestState")->title("Состояние заявки")->horizontal(),
                Label::make("entity.Renter.Name")->title("Название компании")->horizontal(),
                Label::make("entity.ResponsibleName")->title("Автор заявки")->horizontal()
             ])
        ];
    }
}
