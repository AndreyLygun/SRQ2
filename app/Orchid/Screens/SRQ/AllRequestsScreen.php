<?php

namespace App\Orchid\Screens\SRQ;

use DrxClient\DrxClient;
use Orchid\Screen\Screen;

class AllRequestsScreen extends Screen
{
    public $drxType = "AbstractRequests";
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    public function query(): iterable
    {
        $url = "http://192.168.1.241/Integration/odata/";
        $login = 'lygun';
        $password = '31185';

        $drxClient = new DrxClient($url, $login, $password);
        $response = $drxClient->from('IBaseRequests')->expand("BusinessUnit, Author")->get();

        $requests = [];
        foreach($response as $item) {
            $requests[] = $item["properties"];
        }
//        dd($requests);
        return ["requests" => $requests];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'AllReqeustsScreen';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [];
    }
}
