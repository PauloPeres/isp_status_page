<?php
declare(strict_types=1);

namespace App\Service\Voice;

use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;

/**
 * TTS Message Builder
 *
 * Builds text-to-speech messages for voice call alerts in multiple locales.
 * Uses Amazon Polly voices via Twilio for natural-sounding speech.
 */
class TtsMessageBuilder
{
    /**
     * Message templates for down alerts by locale.
     *
     * @var array<string, string>
     */
    private const DOWN_TEMPLATES = [
        'en' => 'Alert: Monitor %s is down since %s. %s',
        'pt_BR' => 'Alerta: O monitor %s esta fora do ar desde %s. %s',
        'es' => 'Alerta: El monitor %s esta caido desde %s. %s',
    ];

    /**
     * Message templates for resolved alerts by locale.
     *
     * @var array<string, string>
     */
    private const RESOLVED_TEMPLATES = [
        'en' => 'Recovery: Monitor %s is back online and operating normally.',
        'pt_BR' => 'Recuperacao: O monitor %s voltou ao normal e esta operando corretamente.',
        'es' => 'Recuperacion: El monitor %s ha vuelto a la normalidad y esta operando correctamente.',
    ];

    /**
     * IVR prompts by locale.
     *
     * @var array<string, string>
     */
    private const IVR_PROMPTS = [
        'en' => 'Press 1 to acknowledge. Press 2 to escalate to the next person.',
        'pt_BR' => 'Pressione 1 para reconhecer. Pressione 2 para escalar para a proxima pessoa.',
        'es' => 'Presione 1 para reconocer. Presione 2 para escalar a la siguiente persona.',
    ];

    /**
     * Acknowledgement confirmations by locale.
     *
     * @var array<string, string>
     */
    private const ACK_CONFIRMATIONS = [
        'en' => 'Incident acknowledged. Thank you.',
        'pt_BR' => 'Incidente reconhecido. Obrigado.',
        'es' => 'Incidente reconocido. Gracias.',
    ];

    /**
     * Escalation confirmations by locale.
     *
     * @var array<string, string>
     */
    private const ESCALATE_CONFIRMATIONS = [
        'en' => 'Escalating to the next person. Goodbye.',
        'pt_BR' => 'Escalando para a proxima pessoa. Ate logo.',
        'es' => 'Escalando a la siguiente persona. Hasta luego.',
    ];

    /**
     * No input messages by locale.
     *
     * @var array<string, string>
     */
    private const NO_INPUT_MESSAGES = [
        'en' => 'No response received. Escalating to the next person.',
        'pt_BR' => 'Nenhuma resposta recebida. Escalando para a proxima pessoa.',
        'es' => 'No se recibio respuesta. Escalando a la siguiente persona.',
    ];

    /**
     * Amazon Polly voices by locale.
     *
     * @var array<string, string>
     */
    private const POLLY_VOICES = [
        'en' => 'Polly.Joanna',
        'pt_BR' => 'Polly.Camila',
        'es' => 'Polly.Lupe',
    ];

    /**
     * Twilio language codes by locale.
     *
     * @var array<string, string>
     */
    private const TWILIO_LANGUAGES = [
        'en' => 'en-US',
        'pt_BR' => 'pt-BR',
        'es' => 'es-US',
    ];

    /**
     * Build a down alert message.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor that is down
     * @param \App\Model\Entity\Incident $incident The related incident
     * @param string $locale The target locale (en, pt_BR, es)
     * @return string The TTS message
     */
    public function buildDownMessage(Monitor $monitor, Incident $incident, string $locale): string
    {
        $template = self::DOWN_TEMPLATES[$locale] ?? self::DOWN_TEMPLATES['en'];
        $time = $incident->started_at ? $incident->started_at->format('H:i') : 'now';
        $ivr = $this->getIvrPrompt($locale);

        return sprintf($template, $monitor->name, $time, $ivr);
    }

    /**
     * Build a resolved alert message.
     *
     * @param \App\Model\Entity\Monitor $monitor The recovered monitor
     * @param string $locale The target locale (en, pt_BR, es)
     * @return string The TTS message
     */
    public function buildResolvedMessage(Monitor $monitor, string $locale): string
    {
        $template = self::RESOLVED_TEMPLATES[$locale] ?? self::RESOLVED_TEMPLATES['en'];

        return sprintf($template, $monitor->name);
    }

    /**
     * Get the IVR prompt for DTMF input.
     *
     * @param string $locale The target locale
     * @return string The IVR prompt
     */
    public function getIvrPrompt(string $locale): string
    {
        return self::IVR_PROMPTS[$locale] ?? self::IVR_PROMPTS['en'];
    }

    /**
     * Get the acknowledgement confirmation message.
     *
     * @param string $locale The target locale
     * @return string The confirmation message
     */
    public function getAckConfirmation(string $locale): string
    {
        return self::ACK_CONFIRMATIONS[$locale] ?? self::ACK_CONFIRMATIONS['en'];
    }

    /**
     * Get the escalation confirmation message.
     *
     * @param string $locale The target locale
     * @return string The confirmation message
     */
    public function getEscalateConfirmation(string $locale): string
    {
        return self::ESCALATE_CONFIRMATIONS[$locale] ?? self::ESCALATE_CONFIRMATIONS['en'];
    }

    /**
     * Get the no-input message.
     *
     * @param string $locale The target locale
     * @return string The no-input message
     */
    public function getNoInputMessage(string $locale): string
    {
        return self::NO_INPUT_MESSAGES[$locale] ?? self::NO_INPUT_MESSAGES['en'];
    }

    /**
     * Get the Amazon Polly voice name for Twilio TTS.
     *
     * @param string $locale The target locale
     * @return string The Polly voice name
     */
    public function getTwilioVoice(string $locale): string
    {
        return self::POLLY_VOICES[$locale] ?? self::POLLY_VOICES['en'];
    }

    /**
     * Get the Twilio language code.
     *
     * @param string $locale The target locale
     * @return string The language code
     */
    public function getTwilioLanguage(string $locale): string
    {
        return self::TWILIO_LANGUAGES[$locale] ?? self::TWILIO_LANGUAGES['en'];
    }
}
