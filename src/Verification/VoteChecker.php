<?php

namespace Azuriom\Plugin\Vote\Verification;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class VoteChecker
{
    /**
     * The votes sites supporting verification.
     *
     * @var array
     */
    private $sites = [

    ];

    public function __construct()
    {
        $this->sites['liste-serv-minecraft.fr'] = $this->verifyByJson(
            'https://liste-serv-minecraft.fr/api/check?server={server}&ip={ip}',
            'status',
            '200');

        $this->sites['serveurs-minecraft.org'] = $this->verifyByJson(
            'https://www.serveurs-minecraft.org/api/is_valid_vote.php?id={id}&ip={ip}&duration=5&format=json',
            'votes',
            '200');

        $this->sites['serveursminecraft.org'] = $this->verifyByText(
            'https://www.serveursminecraft.org/sm_api/peutVoter.php?id={id}&ip={ip}');

        $this->sites['serveurs-minecraft.org'] = $this->verifyByJson(
            'https://serveur-minecraft.fr/api-{id}_{ip}.json',
            'status',
            'Success');

        $this->sites['liste-serveur.fr'] = $this->verifyByJson(
          'https://www.liste-serveur.fr/api/hasVoted/{server_token}/{ip}',
          'hasVoted',
          'true');
    }

    /**
     * Try to verify if the user voted if the website is supported.
     * In case of failure or unsupported website true is returned.
     *
     * @param  string  $voteSite
     * @param  string  $userIp
     * @param  string  $userName
     * @return bool
     */
    public function verifyVote(string $voteSite, string $userIp, string $userName)
    {
        $url = parse_url($voteSite);

        if ($url === false) {
            return true;
        }

        $host = $url['host'];

        if (Str::startsWith($host, 'www.')) {
            $host = substr($url, 4);
        }

        if (! array_key_exists($host, $this->sites)) {
            return false;
        }

        $checkMethod = $this->sites[$host];

        return $checkMethod($userIp, $userName);
    }

    protected function verifyByJson(string $url, string $key, string $exceptedValue)
    {
        return function ($ip, $userName) use ($url, $key, $exceptedValue) {
            $content = $this->readUrl($url, $ip, $userName);
            $json = json_decode($content, true);

            if (json_last_error()) {
                return true;
            }

            return array_key_exists($key, $json) && $json[$key] === $exceptedValue;
        };
    }

    protected function verifyByText(string $url)
    {
        return function ($ip, $userName) use ($url) {
            return $this->readUrl($url, $ip, $userName) !== null;
        };
    }

    protected function readUrl(string $url, string $ip = '0.0.0.0', string $name = '')
    {
        $client = new Client();

        $fullUrl = str_replace(['{player}', '{ip}'], [$ip, $name], $url);

        return $client->get($fullUrl)->getBody()->getContents();
    }
}
