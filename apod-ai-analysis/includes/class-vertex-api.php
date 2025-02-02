<?php
class APOD_Vertex_API {
    private $api_key;
    private $project_id;
    private $location;
    private $endpoint;

    public function __construct() {
        $this->api_key = get_option('apod_ai_vertex_key');
        $this->project_id = get_option('apod_ai_vertex_project');
        $this->location = 'us-central1';
        $this->endpoint = get_option('apod_ai_vertex_endpoint');
    }

    public function analyze_apod($title, $explanation) {
        if (empty($this->api_key)) {
            return array('error' => 'Vertex AI API key not configured');
        }

        $endpoint_url = sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/endpoints/%s:predict',
            $this->location,
            $this->project_id,
            $this->location,
            $this->endpoint
        );

        $prompt = $this->generate_prompt($title, $explanation);
        
        $response = wp_remote_post(
            $endpoint_url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($this->generate_request_body($prompt)),
                'timeout' => 30
            )
        );

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['predictions'][0] ?? array('error' => 'No prediction received');
    }

    private function generate_prompt($title, $explanation) {
        return <<<EOT
You are an expert astronomer analyzing NASA's Astronomy Picture of the Day.

Title: {$title}
Description: {$explanation}

Please provide:
1. A brief explanation for general audience
2. Scientific significance
3. Key astronomical features
4. Related astronomical concepts
5. Interesting facts for amateur astronomers

Format your response in clear sections.
EOT;
    }

    private function generate_request_body($prompt) {
        return array(
            'instances' => [
                [
                    'prompt' => $prompt
                ]
            ],
            'parameters' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
                'topP' => 0.8,
                'topK' => 40
            ]
        );
    }
}