<?php
namespace local_aiquiz;

defined('MOODLE_INTERNAL') || die();

class api_client_student {
    private $api_base_url = 'https://exam-generator.com/api/v1';
    private $access_token;

    public function __construct() {
        //$this->access_token = $this->get_access_token();
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
        //print_r("request ".json_encode($data));
        //echo $token.json_encode($data);
        $response = $curl->post($this->api_base_url . "/exams/{$exam_id}/responses", json_encode($data));
        //print_r("response ".$response );
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
            //'Content-Type: application/json'
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
    
      
}