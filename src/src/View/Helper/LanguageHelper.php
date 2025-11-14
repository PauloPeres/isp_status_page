<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\SettingService;
use Cake\View\Helper;

/**
 * Language Helper
 *
 * Provides helper methods for language/locale management in views
 */
class LanguageHelper extends Helper
{
    /**
     * Setting service instance
     *
     * @var \App\Service\SettingService
     */
    private SettingService $settingService;

    /**
     * Initialize method
     *
     * @param array $config Configuration options
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->settingService = new SettingService();
    }

    /**
     * Get list of available languages
     *
     * Returns an associative array with language codes as keys
     * and language names as values
     *
     * @return array<string, string>
     */
    public function getAvailableLanguages(): array
    {
        return [
            'pt_BR' => __d('settings', 'Português (Brasil)'),
            'en' => __d('settings', 'English'),
            'es' => __d('settings', 'Español'),
        ];
    }

    /**
     * Get current system language
     *
     * Retrieves the current language setting from the database
     * Falls back to 'pt_BR' if not set
     *
     * @return string The current language code (e.g., 'pt_BR', 'en', 'es')
     */
    public function getCurrentLanguage(): string
    {
        return $this->settingService->getString('site_language', 'pt_BR');
    }

    /**
     * Get current language name
     *
     * Returns the human-readable name of the current language
     *
     * @return string The current language name
     */
    public function getCurrentLanguageName(): string
    {
        $currentLang = $this->getCurrentLanguage();
        $languages = $this->getAvailableLanguages();

        return $languages[$currentLang] ?? $languages['pt_BR'];
    }

    /**
     * Check if a language is available
     *
     * @param string $languageCode The language code to check
     * @return bool True if the language is available
     */
    public function isLanguageAvailable(string $languageCode): bool
    {
        return array_key_exists($languageCode, $this->getAvailableLanguages());
    }

    /**
     * Get language flag emoji
     *
     * Returns a flag emoji for the given language code
     *
     * @param string|null $languageCode Language code (null = current language)
     * @return string Flag emoji
     */
    public function getLanguageFlag(?string $languageCode = null): string
    {
        $code = $languageCode ?? $this->getCurrentLanguage();

        $flags = [
            'pt_BR' => '🇧🇷',
            'en' => '🇺🇸',
            'es' => '🇪🇸',
        ];

        return $flags[$code] ?? '🌐';
    }
}
