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
        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->stderr("File not found or not readable: {$filePath}\n");
            return ExitCode::NOINPUT;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->stderr("Cannot open file: {$filePath}\n");
            return ExitCode::IOERR;
        }

        $count = 0;
        $errors = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue; // пропускаем пустые строки
            }
            try {
                $parsedData = $this->parseLogLine($line);
                if ($parsedData) {
                    $this->processLogEntry($parsedData);
                    $count++;
                } else {
                    $errors++;
                    $this->stderr("Failed to parse line: {$line}\n");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->stderr("Error processing line: {$line}\nError: {$e->getMessage()}\n");
            }

            if ($count > 0 && $count % 100 === 0) {
                $this->stdout("Processed {$count} entries...\n");
            }
        }

        fclose($handle);
        $this->stdout("Successfully processed {$count} log entries with {$errors} errors.\n");

        return ExitCode::OK;
    }

    protected function parseLogLine(string $line): ?array
    {
        $pattern = '/^
            (?P<ip>\d{1,3}(?:\.\d{1,3}){3})\s+-\s+-\s+
            \[(?P<date>[^\]]+)\]\s+
            "(?P<method>\w+)\s+(?P<url>[^"]+)\s+(?P<protocol>[^"]+)"\s+
            (?P<status>\d{3})\s+
            (?P<size>\d+|-)\s+
            "(?P<referer>[^"]*)"\s+
            "(?P<userAgent>[^"]*)"
        $/x';

        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $date = DateTime::createFromFormat('d/M/Y:H:i:s O', $matches['date']);
        if (!$date) {
            throw new \Exception("Invalid date format: " . $matches['date']);
        }

        return [
            'ip' => $matches['ip'],
            'date' => $date,
            'method' => $matches['method'],
            'url' => $matches['url'],
            'protocol' => $matches['protocol'],
            'status' => (int)$matches['status'],
            'size' => $matches['size'] === '-' ? 0 : (int)$matches['size'],
            'referer' => $matches['referer'],
            'userAgent' => $matches['userAgent'],
        ];
    }

    protected function processLogEntry(array $data): void
    {
        // Ограничиваем длину URL (можно увеличить или убрать, если не нужно)
        $maxUrlLength = 1024;
        if (mb_strlen($data['url']) > $maxUrlLength) {
            $data['url'] = mb_substr($data['url'], 0, $maxUrlLength);
        }

        $url = Url::findOrCreate($data['url']);
        if (!$url || !$url->id) {
            throw new \Exception("Failed to find or create URL for path: {$data['url']}");
        }

        $uaInfo = $this->parseUserAgent($data['userAgent']);

        // Если не определилась ОС или браузер, подставляем "Unknown"
        $osName = !empty($uaInfo['os']) ? $uaInfo['os'] : 'Unknown';
        $osArch = !empty($uaInfo['architecture']) ? $uaInfo['architecture'] : 'unknown';

        $browserName = !empty($uaInfo['browser']) ? $uaInfo['browser'] : 'Unknown';
        $browserVersion = $uaInfo['version'] ?? null;

        $browser = Browser::findOrCreate($browserName, $browserVersion);
        $os = Os::findOrCreate($osName, $osArch);

        $log = new Log();
        $log->ip = $data['ip'];
        $log->request_date = $data['date']->format('Y-m-d H:i:s');
        $log->url_id = $url->id;
        $log->browser_id = $browser->id;
        $log->os_id = $os->id;

        if (!$log->save()) {
            throw new \Exception('Failed to save log: ' . json_encode($log->errors));
        }
    }

    protected function parseUserAgent(string $userAgent): array
    {
        if ($userAgent === '') {
            return [
                'os' => null,
                'architecture' => null,
                'browser' => null,
                'version' => null,
            ];
        }

        $result = [
            'os' => null,
            'architecture' => null,
            'browser' => null,
            'version' => null,
        ];

        if (preg_match('/\((.*?)\)/', $userAgent, $matches)) {
            $systemInfo = $matches[1];
            $systemParts = array_map('trim', explode(';', $systemInfo));

            foreach ($systemParts as $part) {
                if (stripos($part, 'Windows NT') !== false) {
                    $result['os'] = 'Windows';
                    if (stripos($part, '10.0') !== false) $result['os'] = 'Windows 10';
                    elseif (stripos($part, '6.3') !== false) $result['os'] = 'Windows 8.1';
                    elseif (stripos($part, '6.2') !== false) $result['os'] = 'Windows 8';
                    elseif (stripos($part, '6.1') !== false) $result['os'] = 'Windows 7';
                } elseif (stripos($part, 'Linux') !== false) {
                    $result['os'] = 'Linux';
                } elseif (stripos($part, 'Android') !== false) {
                    $result['os'] = 'Android';
                } elseif (stripos($part, 'iPhone') !== false || stripos($part, 'iPad') !== false) {
                    $result['os'] = 'iOS';
                } elseif (stripos($part, 'Macintosh') !== false) {
                    $result['os'] = 'Mac OS';
                }

                if (stripos($part, 'x64') !== false || stripos($part, 'Win64') !== false || stripos($part, 'WOW64') !== false) {
                    $result['architecture'] = 'x64';
                } elseif (stripos($part, 'x86') !== false || stripos($part, 'Win32') !== false) {
                    $result['architecture'] = 'x86';
                } elseif (stripos($part, 'arm64') !== false || stripos($part, 'aarch64') !== false) {
                    $result['architecture'] = 'arm64';
                }
            }
        }

        if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera|MSIE|YaBrowser|Trident)[\/ ]([\d.]+)/i', $userAgent, $matches)) {
            $result['browser'] = $matches[1];
            $result['version'] = $matches[2];
        } elseif (stripos($userAgent, 'Googlebot') !== false) {
            $result['browser'] = 'Googlebot';
        } elseif (stripos($userAgent, 'YandexBot') !== false) {
            $result['browser'] = 'YandexBot';
        }

        if (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Mobile') !== false) {
            $result['browser'] = 'Chrome Mobile';
        }

        return $result;
    }
}

