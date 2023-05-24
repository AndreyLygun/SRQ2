<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use Illuminate\Support\Env;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PlatformScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $url = env();
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
     */
    public function name(): ?string
    {
        return 'Get Started';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Welcome to your Orchid application.';
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
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::view('platform::partials.update-assets'),
            Layout::view('platform::partials.welcome'),
        ];
    }
}
