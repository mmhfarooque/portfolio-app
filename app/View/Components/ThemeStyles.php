<?php

namespace App\View\Components;

use App\Services\ThemeService;
use Illuminate\View\Component;

class ThemeStyles extends Component
{
    public string $css;
    public bool $isDark;
    public string $themeName;

    public function __construct()
    {
        $themeService = app(ThemeService::class);
        $this->css = $themeService->getCssVariables();
        $this->isDark = $themeService->isDarkTheme();
        $this->themeName = $themeService->getCurrentTheme();
    }

    public function render()
    {
        return view('components.theme-styles');
    }
}
