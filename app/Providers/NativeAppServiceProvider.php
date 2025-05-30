<?php

namespace App\Providers;

use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\Menu;
use Native\Laravel\Facades\MenuBar;
use Native\Laravel\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->title('AI Email Manager')
            ->width(1200)
            ->height(800)
            ->minWidth(800)
            ->minHeight(600)
            ->route('settings')
            ->titleBarHidden();

        MenuBar::create()
            ->showDockIcon()
            ->onlyShowContextMenu()
            ->withContextMenu(
                Menu::make(
                    Menu::label('My Application'),
                    Menu::separator(),
                    Menu::link('https://nativephp.com', 'Learn more…')
                        ->openInBrowser(),
                    Menu::separator(),
                    Menu::quit()
                )
            );
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
