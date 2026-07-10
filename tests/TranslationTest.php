<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

/**
 * TranslationTest
 *
 * Tests the package translation files (EN/FR).
 */
class TranslationTest extends TestCase
{
    /** @test */
    public function english_translations_are_loaded()
    {
        $this->app->setLocale('en');

        $this->assertEquals(
            'Session expired. Please dial again.',
            __('ussd::ussd.session_expired')
        );
        $this->assertEquals(
            'A system error occurred. Please try again later.',
            __('ussd::ussd.system_error')
        );
        $this->assertEquals(
            'Too many requests. Please try again later.',
            __('ussd::ussd.too_many_requests')
        );
        $this->assertEquals('Welcome', __('ussd::ussd.welcome'));
        $this->assertEquals('Back', __('ussd::ussd.back'));
        $this->assertEquals('Confirm', __('ussd::ussd.confirm'));
    }

    /** @test */
    public function french_translations_are_loaded()
    {
        $this->app->setLocale('fr');

        $this->assertEquals(
            'Session expirée. Veuillez recomposer le code.',
            __('ussd::ussd.session_expired')
        );
        $this->assertEquals(
            'Une erreur système est survenue. Veuillez réessayer.',
            __('ussd::ussd.system_error')
        );
        $this->assertEquals('Bienvenue', __('ussd::ussd.welcome'));
        $this->assertEquals('Retour', __('ussd::ussd.back'));
        $this->assertEquals('Confirmer', __('ussd::ussd.confirm'));
    }

    /** @test */
    public function fallback_to_english_when_translation_missing()
    {
        $this->app->setLocale('de');

        $this->assertEquals(
            'Session expired. Please dial again.',
            __('ussd::ussd.session_expired')
        );
    }

    /** @test */
    public function all_translation_keys_exist_in_both_locales()
    {
        $en = require __DIR__.'/../resources/lang/en/ussd.php';
        $fr = require __DIR__.'/../resources/lang/fr/ussd.php';

        foreach ($en as $key => $value) {
            $this->assertArrayHasKey($key, $fr, "Missing French translation for: {$key}");
        }

        foreach ($fr as $key => $value) {
            $this->assertArrayHasKey($key, $en, "Missing English translation for: {$key}");
        }
    }
}
