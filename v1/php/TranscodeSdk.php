<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/*
* This file is part of BravoSystem.
* Copyright(c) 2013 by chnvideo.com
* All rights reserved.
*
* Author
*      - winlin <winlin@chnvideo.com>
*/

/**
 * bravo transcode service sdk,
 * provides class(interfaces) to invoke to create/cancel/query transcode task,
 * with invoke samples on some framework, for example, CI framework.
 */

/*
//////////////////////////////////////////////////////////////////
/////////////////Usage for CI framework///////////////////////////
//////////////////////////////////////////////////////////////////
 
// usage for jquery client to access the api
// of transcode sample
// where backend server root is: http://192.168.1.170/vms2/
var root = "http://192.168.1.170/vms2/";
$.ajax({
    type: 'POST',
    data: {
        input: "/home/winlin/test_22m.flv",
        report_url: root + "transcoder/vms_report",
        output: "/home/winlin/test_22m.mp4"
    },
    async: true,
    url: root + "transcoder/vms_transcode",
    success: function(res) {
        var task_id = parseInt(res);
        alert(task_id);
    }
});

// usage for CI framework
// to use the transcode sdk.
// where transcoder service server is:
// transcode service ip: 192.168.1.3
// transcode service port: 1971
// transcode service version: TranscodeSdk::API_V1
class Transcoder extends CI_Controller
{
    private $_sdk = null;

    public function __construct() {
        parent::__construct();
        $this->load->library('TranscodeSdk');
    }
    public function vms_report() {
        $json_req = file_get_contents("php://input");
        $data = json_decode($json_req);

        $res = array("code"=> 0, "data"=> NULL);
        $res_str = json_encode($res);
        echo $res_str;
    }
    public function vms_transcode() {
        $input = $_POST['input'];
        $report_url = $_POST['report_url'];
        $outputs = array($_POST['output']);

        if (!$this->_sdk) {
            $this->_sdk = new TranscodeSdk();
        }

        $code = $this->_sdk->initialize("192.168.1.3", 1971, TranscodeSdk::API_V1);
        if (!TranscodeSdk::is_success($code)) {
            echo "-1";
            return;
        }

        $ret = $this->_sdk->create_transcode_task(
            "vms", $input, $report_url, $outputs, TranscodeSdk::PRIORITY_NORMAL,
            TranscodeSdk::PROFILE_HIGH, TranscodeSdk::PASS_ONE, TranscodeSdk::PRESET_SLOWER,
            TranscodeSdk::BFRAME_AUTO, TranscodeSdk::LEVEL_AUTO, TranscodeSdk::WIDTH_AUTO, 576,
            true, "4:3", 800, 25, 10, 64, TranscodeSdk::AUDIO_SAMPLERATE_44100, TranscodeSdk::AUDIO_CHANNEL_STEREO,
            TranscodeSdk::AUDIO_VOLUME_100, TranscodeSdk::NO_CROP, TranscodeSdk::NO_LOGO(),
            array("provider"=>"bravo_vms")
        );

        if (!TranscodeSdk::is_success($ret["code"])) {
            echo "-1";
            return;
        }

        echo $ret["task_id"];
        return;
    }
}
 */

/**
 * Class TranscodeSdk is the class for user.
 */
class TranscodeSdk
{
    // error code definitions
    const SUCCESS = 0;
    const ERROR_HTTP_REQUEST = 3000;
    const ERROR_RESPONSE_JSON_PARSE = 3001;
    const ERROR_RESPONSE_SCHEMA_INVALID = 3002;
    const ERROR_RESPONSE_FAILED = 3003;

    // sdk version
    const API_V1 = "v1";

    // task priority
    const PRIORITY_IMMEDIATELY = 0;
    const PRIORITY_EMERGENCY = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 4;

    // x264 profile
    const PROFILE_BASELINE = "baseline";
    const PROFILE_MAIN = "main";
    const PROFILE_HIGH = "high";

    // x264 preset
    const PRESET_FASTER = "faster";
    const PRESET_FAST = "fast";
    const PRESET_MEDIUM = "medium";
    const PRESET_SLOW = "slow";
    const PRESET_SLOWER = "slower";
    const PRESET_VERYSLOW = "veryslow";

    // x264 1pass.
    const PASS_ONE = "one";

    // system auto set the level according to the x264 settings.
    const LEVEL_AUTO = NULL;
    // system auto set the bframe according to the preset/profile.
    const BFRAME_AUTO = NULL;
    // system auto calc the width by height
    const WIDTH_AUTO = 0;

    // audio sample rate, in KHZ
    const AUDIO_SAMPLERATE_44100 = 44100;
    const AUDIO_SAMPLERATE_22050 = 22050;

    // audio channel
    const AUDIO_CHANNEL_MONO = 1;
    const AUDIO_CHANNEL_STEREO = 2;

    // audio volume, 100 for 100%
    const AUDIO_VOLUME_100 = 100;
    const AUDIO_VOLUME_200 = 200;
    const AUDIO_VOLUME_300 = 300;

    // donot crop the video.
    const NO_CROP = NULL;
    // without logo, return an empty array.
    static public function NO_LOGO() {
        return array();
    }

    // api server info
    private $_server = NULL;
    private $_port = 0;

    public function __construct() {
    }

    /**
     * whether the code of sdk is success.
     */
    public static function is_success($code) {
        return $code === TranscodeSdk::SUCCESS;
    }

    /**
     * initialize the sdk. reserved for future multiple sdks.
     * @param $server the server name or ip.
     * @param $port the server service port.
     * @param $version a strnig indicates the version of sdk, it can be:
     *      TranscodeSdk::API_V1    version 1.0 transcode service.
     */
    public function initialize($server, $port, $version) {
        $this->_server = $server;
        $this->_port = $port;

        return TranscodeSdk::SUCCESS;
    }

    /**
     * create transcode task.
     * @param agent a string indicates the provider, for example,
     *      "vms" indicates the task is created by vms,
     *      "watch-folder" indicates the task is created by watch-folder scaner,
     *      "xxx" any value is ok, user can search the agent keyword of tasks.
     * @param input a string indicates the input file to transcode, current supports:
     *      local file, /data/videos/sample.flv, for example, canbe url in the public storage.
     *      http file, http://server/dir/file.flv
     *      ftp file, ftp://user:passwd@server/dir/file.flv
     * @param report_url a string indicates the report url when task finished.
     *      it must be a http url.
     * @param outputs a array contains the outputs urls, each url must be ftp or local path.
     *      for example, array("ftp://server/dir/output.mp4")
     * @param priority an int value indicates the priority, defines in consts of this class.
     *      for example, TranscodeSdk::PRIORITY_NORMAL
     * @param profile
     * @param preset
     * @param pass
     * @param b_frame
     * @param level
     * @param width
     * @param height
     * @param keep_aspect
     * @param aspect
     * @param vbitrate
     * @param fps
     * @param gop
     * @param abitrate
     * @param asamplerate
     * @param achannels
     * @param avolume
     * @param crop an object contains the crop infomartion.
     *      None to disable crop.
     *      {
     *          "top":0,
     *          "bottom":0,
     *          "left":0,
     *          "right":0
     *      }
     * @param logos an object contains the logo information. // TODO: FIXME: use object or array.
     *      None to disable logo.
     *      {
     *          "url":"/data/sample.png",
     *          "width": 120,
     *          "height": 50,
     *          "horizon_margin": 10,
     *          "vertical_margin": 10,
     *          "horizon": "left",
     *          "vertical": "top"
     *      }
     * @param $private_object the private object in the request, transcoder will send it in report
     * @return an object contains:
     *      code: an int error code.
     *      task_id: the id of task, NULL if error.
     */
    public function create_transcode_task($agent, $input, $report_url, $outputs, $priority,
          $profile, $pass, $preset, $b_frame, $level, $width, $height, $keep_aspect, $aspect, $vbitrate, $fps, $gop,
          $abitrate, $asamplerate, $achannels, $avolume, $crop, $logos, $private_object
    ) {
        $req = array(
            "agent" => $agent,
            "input" => $input,
            "report_url" => $report_url,
            "outputs" => $outputs,
            "type" => "transcode",
            "priority" => $priority,
            "transcode" => array(
                "transcode_threads" => 0,
                "profile" => $profile,
                "pass" => $pass,
                "preset" => $preset,
                "b_frame" => $b_frame,
                "level" => $level,
                "width" => $width,
                "height" => $height,
                "keep_aspect" => $keep_aspect,
                "aspect" => $aspect,
                "vbitrate" => $vbitrate,
                "fps" => $fps,
                "gop" => $gop,
                "abitrate" => $abitrate,
                "asamplerate" => $asamplerate,
                "achannels" => $achannels,
                "avolume" => $avolume,
                "crop" => $crop,
                "logos" => $logos
            ),
            "private_object"=>$private_object
        );

        $url = "http://" . $this->_server . ":" . $this->_port. "/api/v1/tasks";
        $data = json_encode($req);
        $ret = $this->bravo_json_request($url, "POST", $data);

        if (!TranscodeSdk::is_success($ret["code"])) {
            return array("code"=>$ret["code"], "task_id"=>NULL);
        }

        return array("code"=>TranscodeSdk::SUCCESS, "task_id"=>$ret["res"]["task_id"]);
    }

    /**
     * query the detail of task.
     * @param $task_id the id of task to query.
     * @return array an object contains:
     *      code: an int error code.
     *      info: the task info query from transcode service.
     */
    public function query_task($task_id) {
        $url = "http://" . $this->_server . ":" . $this->_port. "/api/v1/tasks/" .$task_id;
        $ret = $this->bravo_json_request($url, "GET", NULL);

        if (!TranscodeSdk::is_success($ret["code"])) {
            return array("code"=>$ret["code"], "info"=>NULL);
        }

        $info = $ret["res"]["tasks"][0];
        return array("code"=>TranscodeSdk::SUCCESS, "info"=>$info);
    }

    /**
     * get the progress from task info which return by api query_task(task_id):info
     * @param $info the object return by api query_task(task_id):info
     * @return an int progress value.
     * @remark please ensure the info object is valid, that is, the query_task return code is success.
     */
    public function get_task_progress($info) {
        $progress = $info["progress"];
        return $progress;
    }

    /**
     * @param $url
     * @param $method can be "POST", "GET", "PUT", "DELETE"
     * @param $data a string contains the post data, NULL if other method.
     * @return an object contains the:
     *      code: an int error code.
     *      res: the parsed json object. NULL if error.
     */
    private function bravo_json_request($url, $method, $data) {
        $s = curl_init();

        curl_setopt($s, CURLOPT_URL, $url);
        if (strtoupper($method) === "POST") {
            curl_setopt($s, CURLOPT_POST, true);
            curl_setopt($s, CURLOPT_POSTFIELDS, $data);
            curl_setopt($s, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-length: ".strlen($data)));
        }
        // TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($s);
        curl_close($s);

        if (!res) {
            return array("code"=>TranscodeSdk::ERROR_HTTP_REQUEST, "res"=>NULL);
        }

        $json_res = json_decode($res, true);
        if (!$json_res) {
            return array("code"=>TranscodeSdk::ERROR_RESPONSE_JSON_PARSE, "res"=>NULL);
        }

        if (!isset($json_res["code"])) {
            return array("code"=>TranscodeSdk::ERROR_RESPONSE_SCHEMA_INVALID, "res"=>NULL);
        }

        if ($json_res["code"] !== 0) {
            return array("code"=>TranscodeSdk::ERROR_RESPONSE_FAILED, "res"=>NULL);
        }

        return array("code"=>TranscodeSdk::SUCCESS, "res"=>$json_res["data"]);
    }
}

?>
