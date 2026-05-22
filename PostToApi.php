<?php

/**
 * POST completed survey answers to your API (edit API_URL below).
 */
class PostToApi extends PluginBase
{
    protected $storage = 'DbStorage';

    protected static $name = 'PostToApi';

    protected static $description = 'POST survey answers to an external API on completion';

    /** @var string Your API endpoint — change this */
    private const API_URL = 'https://script.google.com/macros/s/AKfycbx-VURkHmQCrlP3cZ56gQJHnHcOAA0asEXElMnbi-UIdcv_foGCeDwZb39VMdZGgTofRQ/exec';

    public function init()
    {
        $this->subscribe('afterSurveyComplete', 'sendToApi');
    }

    public function sendToApi()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');
        $responseId = $event->get('responseId');

        if (empty($surveyId) || empty($responseId)) {
            return;
        }

        $answers = $this->pluginManager->getAPI()->getResponse($surveyId, $responseId);
        $body = json_encode(array(
            'surveyId' => (int) $surveyId,
            'responseId' => (int) $responseId,
            'answers' => $answers,
        ));

        if (!function_exists('curl_init')) {
            return;
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
        ));
        curl_exec($ch);
        curl_close($ch);
    }
}
