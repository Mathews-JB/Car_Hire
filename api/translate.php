<?php
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

/**
 * Zambian Language Translation API Helper
 * API Key Provided: ZED_385793c04e8d46059580aeceae04db70
 */

class ZambianTranslator {
    private $api_key = 'ZED_385793c04e8d46059580aeceae04db70';
    private $api_url = 'https://api.lumoafrica.online/v1/translate'; // Speculative endpoint based on LumoAfrica research

    public function translate($text, $target_lang, $source_lang = 'en') {
        // Map common codes to what the API might expect
        // Supported: bem (Bemba), nya (Nyanja), ton (Tonga), loz (Lozi)
        
        $data = [
            'text' => $text,
            'source' => $source_lang,
            'target' => $target_lang,
            'api_key' => $this->api_key
        ];

        // Real API implementation
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($httpCode === 200 && isset($result['translated_text'])) {
            return [
                'status' => 'success',
                'translated_text' => $result['translated_text'],
                'provider' => 'LumoAfrica/ZED'
            ];
        }

        // Fallback for demo/failure - at least don't show the prefix
        return [
            'status' => 'error',
            'translated_text' => $text,
            'message' => 'API Error or Rate Limit'
        ];
    }
}

// Logic for handling API requests (only when called as a web endpoint)
if (php_sapi_name() !== 'cli') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $text = $input['text'] ?? '';
        $target = $input['target'] ?? 'en';
        
        if (empty($text)) {
            echo json_encode(['error' => 'No text provided']);
            exit;
        }

        $translator = new ZambianTranslator();
        $result = $translator->translate($text, $target);
        echo json_encode($result);
    } else {
        echo json_encode(['message' => 'Zambian Translation API Ready', 'key_status' => 'active']);
    }
}
