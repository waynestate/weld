<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class UiBrandingTest extends TestCase
{
    private function path(string $relative): string
    {
        return dirname(__DIR__, 2) . '/' . $relative;
    }

    public function test_the_user_menu_offers_sign_out()
    {
        $this->assertStringContainsString(
            'sesame.logout',
            file_get_contents($this->path('resources/js/components/UserMenuContent.vue')),
        );
    }

    public function test_the_branding_drops_the_laravel_starter_kit()
    {
        foreach (['AppLogo.vue', 'AppHeader.vue'] as $component) {
            $contents = file_get_contents($this->path('resources/js/components/' . $component));

            $this->assertStringNotContainsString('Laravel Starter Kit', $contents);
            $this->assertStringNotContainsString('laravel/vue-starter-kit', $contents);
            $this->assertStringNotContainsString('laravel.com/docs/starter-kits', $contents);
        }
    }

    public function test_editorconfig_covers_compose_files()
    {
        $this->assertStringContainsString(
            'compose',
            file_get_contents($this->path('.editorconfig')),
        );
    }
}
