<?php
namespace local_aiquiz;

defined('MOODLE_INTERNAL') || die();

class api_client {
    private $api_base_url = 'https://examgenerator-ai-dev-slot.azurewebsites.net/api/v1';
    private $access_token;

    public function __construct() {
        $this->access_token = $this->get_access_token();
    }

    private function get_access_token() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $api_key = get_config('local_aiquiz', 'apikey'); //"nd-668b0c7b118f07c51893efc20b07c5c601745ff93fc826ef92f1899b523c5c4c";//
        $user_email = get_config('local_aiquiz', 'email'); //"test@studywise.io";//
        //debugging('Aapi_key'.$user_email, DEBUG_DEVELOPER);
        if (empty($api_key) || empty($user_email)) {
            throw new \moodle_exception('missingcredentials', 'local_aiquiz', new \moodle_url('/admin/settings.php?section=local_aiquiz'));
        }

         

        $curl = new \curl();
        $headers = [
            'X-API-KEY: ' . $api_key,
            'Content-Type: application/json'
        ];
        $curl->setHeader($headers);
        
        $post_data = json_encode([
            'email' => $user_email
        ]);
        
        $response = $curl->post($this->api_base_url . '/auth/access-token/external', $post_data);
        
        $response_data = json_decode($response);
        //debugging('Aapi_key'.$response_data, DEBUG_DEVELOPER);
        if ($response_data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('invalidjsonresponse', 'local_aiquiz', '', $response);
        }

        if (isset($response_data->error)) {
            throw new \moodle_exception('apiauthenticationfailed', 'local_aiquiz', '', $response_data->error);
        }

        if (!isset($response_data->access_token)) {
            throw new \moodle_exception('noaccesstokenreceived', 'local_aiquiz', '', $response);
        }

        return $response_data->access_token;
    }

    public function generate_exam($text, $questions, $difficulty, $language, $focus = '', $example_question = '', $is_closed_content = true) {
        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ]);
        
        $data = [
            'text' => $text,
            'questions' => $questions,
            'difficulty' => $difficulty,
            'language' => $language,
            'focus' => $focus,
            'exampleQuestion' => $example_question,
            'isClosedContent' => $is_closed_content
        ];
        //print_r($data);
        $response = $curl->post($this->api_base_url . '/gen/exam/sync', json_encode($data));

        //debugging('result'.$this->access_token.$data.$response, DEBUG_DEVELOPER);
        
        $result = json_decode($response, true);
        
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('invalidjsonresponse', 'local_aiquiz', '', $response);
        }
        
        if (isset($result['error'])) {
            throw new \moodle_exception('examgenerationfailed', 'local_aiquiz', '', $result['error']);
        }
        
        return $result;
    }
    private function issue_one_time_token() {
        $curl = new \curl();
        /*$curl->setHeader([
            'X-API-KEY: ' . $this->api_key,
            'Content-Type: application/json'
        ]);*/

        $response = $curl->post($this->api_base_url . "/issue-token", json_encode([]));
        $result = json_decode($response, true);

        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('invalidjsonresponse', 'local_aiquiz', '', $response);
        }

        if (!isset($result['token'])) {
            throw new \moodle_exception('tokenissuefailed', 'local_aiquiz', '', $response);
        }

        return $result['token'];
    }

    public function evaluate_exam($exam_id, $answers, $student_details) {
        $token = $this->issue_one_time_token();

        $curl = new \curl();
        $curl->setHeader([
            'X-Token:' . $token,
            'Content-Type: application/json'
        ]);

        $data = [
            'answers' => $answers,
            'studentDetails' => $student_details,
            'allow_auto_grade' => true
        ];
        //echo $token.json_encode($data);
        $response = $curl->post($this->api_base_url . "/exams/{$exam_id}/responses", json_encode($data));
        //print_r("response from ".$this->api_base_url . "/exams/{$exam_id}/responses ".$response );
        $result = json_decode($response, true);
        
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('invalidjsonresponse', 'local_aiquiz', '', $response);
        }
        
        if (isset($result['error'])) {
            throw new \moodle_exception('evaluationfailed', 'local_aiquiz', '', $result['error']);
        }
        
        return $result;
    }

    public function read_file_content($endpoint, $file_path, $file_name, $file_param_name) {
        $curl = new \curl();
        
        // Set the Authorization header
        $curl->setHeader([
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ]);
    
        //print_r($file_param_name.$file_path.$file_name);
        $post_data = [
            $file_param_name => new \CURLFile($file_path, mime_content_type($file_path), $file_name),
        ];
         
        
    
        // Perform the POST request
        $response = $curl->post($this->api_base_url . $endpoint, $post_data);
    
        // Print the response for debugging
        echo "<pre>API Response:\n" . print_r($response, true) . "</pre>";
    
         
        $result = json_decode($response, true);
        
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('invalidjsonresponse', 'local_aiquiz', '', $response);
        }
        
        if (isset($result['error'])) {
            throw new \moodle_exception('filereadingerror', 'local_aiquiz', '', $result['error']);
        }
        
        return $result;
    }
    
    private function get_curl_command($url, $file_param_name, $file_name, $token) {
        $command = "curl -X POST \"{$url}\" \\\n";
        $command .= "    -H \"Authorization: Bearer {$token}\" \\\n";
        $command .= "    -F \"{$file_param_name}=@{$file_name}\"";
        return $command;
    }
}