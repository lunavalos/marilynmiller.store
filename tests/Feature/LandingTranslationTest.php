<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LandingTranslationTest extends TestCase
{
    public function test_landing_page_loads_english_by_default_with_json_stats_and_content(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-target="500"', false);
        $response->assertSee('Women Transformed');
        $response->assertSee('Start Your Journey');
        $response->assertSee('Sofia R.');
        $response->assertSee('MillyFit Member — 6 months');
    }

    public function test_landing_page_switches_to_spanish_with_json_stats_and_content(): void
    {
        $switchResponse = $this->get(route('lang.switch', 'es'));
        $switchResponse->assertRedirect();

        $response = $this->withSession(['locale' => 'es'])->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-target="500"', false);
        $response->assertSee('Mujeres Transformadas');
        $response->assertSee('Inicia tu Transformación');
        $response->assertSee('Sofia R.');
        $response->assertSee('Miembro de MillyFit — 6 meses');
    }

    public function test_json_translations_are_accessible_via_laravel_translator(): void
    {
        App::setLocale('en');
        $this->assertSame('500', __('landing.stats.women.number'));
        $this->assertSame('Women Transformed', __('landing.stats.women.label'));

        App::setLocale('es');
        $this->assertSame('500', __('landing.stats.women.number'));
        $this->assertSame('Mujeres Transformadas', __('landing.stats.women.label'));
    }
}
