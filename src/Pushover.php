<?php
namespace AppZz\Http;
use AppZz\Http\CurlClient;
use AppZz\Helpers\Arr;

/**
 * Pushover client library
 */
class Pushover {

    const ENDPOINT = 'https://api.pushover.net/1/messages.json';
    const UA       = 'AppZz Pushover Client';

    private $_params;

    /**
     * Allowed methods
     * @var array
     */
    private $_methods = [
        'token',
        'user',
        'message',
        'device',
        'title',
        'html',
        'url',
        'url_title',
        'priority',
        'timestamp',
        'sound'
    ];

    /**
     * Allowed sounds
     * @var array
     */
    private $_sounds = [
        'pushover',
        'bike',
        'bugle',
        'cashregister',
        'classical',
        'cosmic',
        'falling',
        'gamelan',
        'incoming',
        'intermission',
        'magic',
        'mechanical',
        'pianobar',
        'siren',
        'spacealarm',
        'tugboat',
        'alien',
        'climb',
        'persistent',
        'echo',
        'updown',
        'none'
    ];

    const PRIORITY_LOWEST = -2;
    const PRIORITY_LOW = -1;
    const PRIORITY_NORMAL = 0;
    const PRIORITY_HIGH = 1;
    const PRIORITY_HIGHEST = 2;

	public function __construct ($user = NULL, $token = NULL)
    {
        if ($user) {
            $this->_params['user'] = $user;
        }

        if ($token) {
            $this->_params['token'] = $token;
        }
	}

    public static function factory ($user = NULL, $token = NULL)
    {
        return new Pushover ($user, $token);
    }

    /**
     * Set params avoid setters
     * @param  array  $params
     * @return $this
     */
    public function params (array $params = [])
    {
        $this->_params = $params;
        return $this;
    }

    public function __call ($method, $params)
    {
        if ( ! in_array ($method, $this->_methods)) {
            throw new \Exception ('Wrong method: ' . $method);
        }

        $value = Arr::get($params, 0);

        switch ($method) {
            case 'url':
                $this->_params['url'] = Arr::get($params, 0);
                $this->_params['url_title'] = Arr::get($params, 1);
            break;

            case 'message':
                $this->_params['message'] = trim (Arr::get($params, 0));
                $this->_params['html'] = (int) Arr::get($params, 1, 0);
            break;

            case 'timestamp':
            case 'priority':
                $value = intval ($value);

            case 'priority':
                if ($value === 2) {
                    $this->_params['expire'] = Arr::get($params, 1, 3600);
                    $this->_params['retry'] = Arr::get($params, 1, 60);
                }
            break;

            case 'sound':
                if ( ! in_array ($value, $this->_sounds)) {
                    $value = 'pushover';
                }
            break;
        }

        $this->_params[$method] = $value;

        return $this;
    }

    public function send ()
    {
        $request = CurlClient::post(Pushover::ENDPOINT, $this->_params, [], ['CURLOPT_SAFE_UPLOAD'=>TRUE])
                        ->form()
                        ->user_agent(Pushover::UA)
                        ->accept('gzip', 'json');

        $response = $request->send();
        $ret = new \stdClass;
        $ret->response = $response->get_status();
        $ret->result = $response->get_body();
        return $ret;
    }
}
