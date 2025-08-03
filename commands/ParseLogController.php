<?php
namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Log;
use app\models\Browser;
use app\models\Os;
use app\models\Url;
use DateTime;

class ParseLogController extends Controller
{
    public function actionIndex($filePath)
    {
        if (!file_exists($filePath)) {
            $this->stderr("File not found: {$filePath}\n");
            return ExitCode::NOINPUT;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->stderr("Cannot open file: {$filePath}\n");
            return ExitCode::IOERR;
        }

        $pattern = '/^(\S+) \S+ \S+ \[([^\]]+)\] "([^"]+)" \d+ \d+ "[^"]*" "([^"]*)"$/';
        $count = 0;

        while (($line = fgets($handle)) !== false) {
            if (preg_match($pattern, $line, $matches)) {
                $this->processLogEntry($matches);
                $count++;
                
                // Выводим прогресс каждые 100 записей
                if ($count % 100 === 0) {
                    $this->stdout("Processed {$count} entries...\n");
                }
            }
        }

        fclose($handle);
        $this->stdout("Successfully processed {$count} log entries.\n");
        return ExitCode::OK;
    }

    protected function processLogEntry($matches)
    {
        list(, $ip, $datetime, $request, $userAgent) = $matches;
        
        $date = DateTime::createFromFormat('d/M/Y:H:i:s O', $datetime);
        if (!$date) {
            return;
        }
        
        $urlPath = $this->extractUrl($request);
        $url = Url::findOrCreate($urlPath);
        
        $browserInfo = $this->parseUserAgent($userAgent);
        $browser = Browser::findOrCreate($browserInfo['browser'], $browserInfo['version']);
        $os = Os::findOrCreate($browserInfo['os'], $browserInfo['architecture']);
        
        $log = new Log();
        $log->ip = $ip;
        $log->request_date = $date->format('Y-m-d H:i:s');
        $log->url_id = $url->id;
        $log->browser_id = $browser->id;
        $log->os_id = $os->id;
        $log->save();
    }

    protected function extractUrl($request)
    {
        if (preg_match('/^(GET|POST|PUT|DELETE|HEAD|OPTIONS) (\S+)/', $request, $matches)) {
            return $matches[2];
        }
        return $request;
    }

    protected function parseUserAgent($userAgent)
    {
        $os = 'Unknown';
        $architecture = 'x64';
        $browser = 'Unknown';
        $version = '';

        if (preg_match('/(Windows NT|Linux|Macintosh|Android|iOS)/', $userAgent, $matches)) {
            $os = $matches[1];
        }
        
        if (strpos($userAgent, 'x86_64') !== false || strpos($userAgent, 'WOW64') !== false) {
            $architecture = 'x64';
        } elseif (strpos($userAgent, 'x86') !== false || strpos($userAgent, 'Win32') !== false) {
            $architecture = 'x86';
        }

        if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera|MSIE|Trident)/', $userAgent, $matches)) {
            $browser = $matches[1];
            if ($browser === 'Trident') $browser = 'Internet Explorer';
            
            if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera|MSIE|Version)\/([\d\.]+)/', $userAgent, $versionMatches)) {
                $version = $versionMatches[2];
            }
        }

        return [
            'os' => $os,
            'architecture' => $architecture,
            'browser' => $browser,
            'version' => $version,
        ];
    }
}
