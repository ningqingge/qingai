<?php

/**
 * 清歌AI蜘蛛屏蔽
 *
 * @author 清歌
 * @link https://jiyun.xin/yun/
 */

if (!function_exists('qingBotBlock_LogFile')) {

    function qingBotBlock_LogFile()
    {
        return dirname(__FILE__) . '/blocked.log';
    }

    function qingBotBlock_AssetUrl($rel)
    {
        global $bloghost;
        $file = dirname(__FILE__) . '/' . ltrim($rel, '/');
        $ver = is_file($file) ? filemtime($file) : '1';
        return rtrim($bloghost, '/') . '/zb_users/plugin/qingBotBlock/' . ltrim($rel, '/') . '?v=' . $ver;
    }

    function qingBotBlock_SiteUrl()
    {
        global $zbp;
        return rtrim($zbp->host, '/') . '/';
    }

    function qingBotBlock_Esc($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    function qingBotBlock_AdminHeader()
    {
        echo '<link rel="stylesheet" href="' . qingBotBlock_Esc(qingBotBlock_AssetUrl('css/admin.css')) . '">' . "\n";
    }

    function qingBotBlock_StatusText($code)
    {
        return ((string) $code === '200') ? 'OK' : 'Forbidden';
    }

    function qingBotBlock_Ver()
    {
        static $ver = null;
        if ($ver !== null) {
            return $ver;
        }
        $ver = '';
        $file = dirname(__FILE__) . '/plugin.xml';
        if (is_readable($file)) {
            $raw = (string) @file_get_contents($file);
            if (preg_match('/<version>([^<]+)<\/version>/', $raw, $match)) {
                $ver = trim($match[1]);
            }
        }
        return $ver;
    }

    function qingBotBlock_EscapeText($text)
    {
        $text = str_replace('[', '[lb]', (string) $text);
        $text = str_replace(array('<', '>'), array('[lt]', '[gt]'), $text);
        return str_replace(array("\r\n", "\r", "\n"), '[nl]', $text);
    }

    function qingBotBlock_UnescapeText($text)
    {
        $text = str_replace('[nl]', "\n", (string) $text);
        $text = str_replace(array('[lt]', '[gt]'), array('<', '>'), $text);
        return str_replace('[lb]', '[', $text);
    }

    function qingBotBlock_PostBody()
    {
        if (isset($_POST['custom_body_keep']) && $_POST['custom_body_keep'] !== '') {
            return null;
        }
        if (isset($_POST['custom_body_esc']) && $_POST['custom_body_esc'] !== '') {
            return qingBotBlock_UnescapeText($_POST['custom_body_esc']);
        }
        return isset($_POST['custom_body']) ? (string) $_POST['custom_body'] : null;
    }

    function qingBotBlock_PostText($name)
    {
        $packed = 'pack_' . $name;
        if (isset($_POST[$packed]) && $_POST[$packed] !== '') {
            return trim(qingBotBlock_UnescapeText($_POST[$packed]));
        }
        return trim(GetVars($name, 'POST'));
    }

    function qingBotBlock_DefaultRules()
    {
        return implode("\n", array(
            'GPTBot',
            'ChatGPT-User',
            'OAI-SearchBot',
            'Google-Extended',
            'Google-CloudVertexBot',
            'ClaudeBot',
            'Claude-Web',
            'Claude-SearchBot',
            'anthropic-ai',
            'cohere-ai',
            'MistralAI-User',
            'Bytespider',
            'PetalBot',
            'Applebot-Extended',
            'CCBot',
            'Meta-ExternalAgent',
            'Meta-ExternalFetcher',
            'PerplexityBot',
            'Perplexity-User',
            'YouBot',
            'Amazonbot',
            'Diffbot',
            'DataForSeoBot',
            'ImagesiftBot',
            'AI2Bot',
            'Timpibot',
            'Webzio-Extended',
            'omgilibot',
            'FacebookBot',
            'facebookexternalhit',
            'img2dataset',
        ));
    }

    function qingBotBlock_DefaultWhitelist()
    {
        return implode("\n", array(
            'Googlebot',
            'Bingbot',
            'Baiduspider',
            'YisouSpider',
            'Sogou web spider',
            'Sogou Orion spider',
            '360Spider',
            'DuckDuckBot',
            'YandexBot',
        ));
    }

    function qingBotBlock_DefaultBody()
    {
        $lines = array(
            '<!DOCTYPE html>',
            '<html lang="zh-CN">',
            '<head>',
            '<meta charset="utf-8">',
            '<meta name="robots" content="noindex, nofollow">',
            '<title>{code} {status}</title>',
            '<style>',
            'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f5f7fb;color:#43506b;font-family:-apple-system,"Microsoft YaHei",sans-serif}',
            '.b{text-align:center}',
            '.b b{display:block;font-size:46px;letter-spacing:3px;color:#2b6ef0}',
            '.b p{margin:10px 0 0;font-size:13px;color:#7b8aa3}',
            '</style>',
            '</head>',
            '<body>',
            '<div class="b"><b>{code}</b><p>该请求已被拒绝</p></div>',
            '</body>',
            '</html>',
        );
        return implode("\n", $lines);
    }

    function qingBotBlock_BodyV1()
    {
        $lines = array(
            '<!DOCTYPE html>',
            '<html lang="zh-CN">',
            '<head>',
            '<meta charset="utf-8">',
            '<meta name="robots" content="noindex, nofollow">',
            '<title>403 Forbidden</title>',
            '<style>',
            'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f5f7fb;color:#43506b;font-family:-apple-system,"Microsoft YaHei",sans-serif}',
            '.b{text-align:center}',
            '.b b{display:block;font-size:46px;letter-spacing:3px;color:#2b6ef0}',
            '.b p{margin:10px 0 0;font-size:13px;color:#7b8aa3}',
            '</style>',
            '</head>',
            '<body>',
            '<div class="b"><b>403</b><p>该请求已被拒绝</p></div>',
            '</body>',
            '</html>',
        );
        return implode("\n", $lines);
    }

    function qingBotBlock_ToArray($text)
    {
        $rows = array();
        $text = str_replace(array("\r\n", "\r"), "\n", (string) $text);
        foreach (explode("\n", $text) as $row) {
            $row = trim($row);
            if ($row != '') {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    function qingBotBlock_Match($ua, $text)
    {
        foreach (qingBotBlock_ToArray($text) as $key) {
            if (stripos($ua, $key) !== false) {
                return $key;
            }
        }
        return '';
    }

    function qingBotBlock_WriteLog($ua, $ip)
    {
        global $zbp;
        if (!$zbp->Config('qingBotBlock')->log) {
            return;
        }
        $ua = str_replace(array("\r", "\n", "\t"), ' ', $ua);
        $line = date('Y-m-d H:i:s') . "\t" . $ip . "\t" . $ua . "\n";
        @file_put_contents(qingBotBlock_LogFile(), $line, FILE_APPEND | LOCK_EX);
    }

    function qingBotBlock_PerPage($value)
    {
        $value = (int) $value;
        return in_array($value, array(50, 100, 200, 500), true) ? $value : 50;
    }

    function qingBotBlock_LogPer($cfg)
    {
        $value = (method_exists($cfg, 'HasKey') && $cfg->HasKey('log_per')) ? $cfg->log_per : 50;
        return qingBotBlock_PerPage($value);
    }

    function qingBotBlock_ReadLog($limit, $offset = 0)
    {
        $result = array('total' => 0, 'today' => 0, 'rows' => array());
        $file = qingBotBlock_LogFile();
        if (!is_file($file) || !is_readable($file)) {
            return $result;
        }
        $handle = @fopen($file, 'rb');
        if (!$handle) {
            return $result;
        }

        $today = date('Y-m-d');
        $limit = (int) $limit;
        $offset = (int) $offset;
        if ($limit < 1) {
            $limit = 50;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        $total = 0;
        $todayCount = 0;
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }
            $total++;
            if (substr($line, 0, 10) == $today) {
                $todayCount++;
            }
        }
        $result['total'] = $total;
        $result['today'] = $todayCount;

        $high = $total - 1 - $offset;
        $low = $total - $offset - $limit;
        if ($low < 0) {
            $low = 0;
        }
        if ($high >= $low && $high >= 0 && $total > 0) {
            rewind($handle);
            $index = 0;
            $buffer = array();
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '') {
                    continue;
                }
                if ($index > $high) {
                    break;
                }
                if ($index >= $low) {
                    $buffer[] = $line;
                }
                $index++;
            }
            foreach (array_reverse($buffer) as $line) {
                $part = explode("\t", $line);
                $result['rows'][] = array(
                    'time' => isset($part[0]) ? $part[0] : '',
                    'ip' => isset($part[1]) ? $part[1] : '',
                    'ua' => isset($part[2]) ? $part[2] : '',
                );
            }
        }
        fclose($handle);
        return $result;
    }

    function qingBotBlock_ClearLog()
    {
        @file_put_contents(qingBotBlock_LogFile(), '');
    }

    function qingBotBlock_CurrentUrl()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        return $scheme . '://' . $host . $uri;
    }

    function qingBotBlock_RenderBody($tpl, $code = 403)
    {
        $tpl = (string) $tpl;
        if (trim($tpl) == '') {
            $tpl = qingBotBlock_DefaultBody();
        }
        $code = ((string) $code === '200') ? 200 : 403;
        $vars = array(
            '{site}' => qingBotBlock_SiteUrl(),
            '{url}' => qingBotBlock_CurrentUrl(),
            '{ip}' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            '{ua}' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            '{time}' => date('Y-m-d H:i:s'),
            '{code}' => $code,
            '{status}' => qingBotBlock_StatusText($code),
        );
        $find = array();
        $repl = array();
        foreach ($vars as $key => $value) {
            $find[] = $key;
            $repl[] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return str_replace($find, $repl, $tpl);
    }

    function qingBotBlock_Prefs($cfg)
    {
        if (!method_exists($cfg, 'HasKey') || !$cfg->HasKey('content')) {
            $mode = (string) $cfg->mode;
            if ($mode == 'empty') {
                return array('200', 'blank');
            }
            if ($mode == 'custom') {
                return array(((string) $cfg->custom_status === '200') ? '200' : '403', 'custom');
            }
            return array('403', 'preset');
        }
        $status = ((string) $cfg->status === '200') ? '200' : '403';
        $content = (string) $cfg->content;
        if ($content != 'blank' && $content != 'custom') {
            $content = 'preset';
        }
        return array($status, $content);
    }

    function qingBotBlock_Reject()
    {
        global $zbp;
        $cfg = $zbp->Config('qingBotBlock');
        list($status, $content) = qingBotBlock_Prefs($cfg);
        $code = ((string) $status === '200') ? 200 : 403;

        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Content-Type: text/html; charset=utf-8');
        header('HTTP/1.1 ' . $code . ' ' . qingBotBlock_StatusText($code), true, $code);

        if ($content == 'blank') {
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><title>' . qingBotBlock_StatusText($code)
                . '</title></head><body></body></html>';
        } elseif ($content == 'custom') {
            echo qingBotBlock_RenderBody($cfg->custom_body, $code);
        } else {
            echo qingBotBlock_RenderBody(qingBotBlock_DefaultBody(), $code);
        }
        exit;
    }

    function qingBotBlock_Check()
    {
        global $zbp;
        $cfg = $zbp->Config('qingBotBlock');
        if (!$cfg->enable) {
            return;
        }
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
        if ($ua != '' && qingBotBlock_Match($ua, $cfg->whitelist) != '') {
            return;
        }
        $hit = false;
        if ($ua == '') {
            $hit = (bool) $cfg->block_empty;
        } elseif (qingBotBlock_Match($ua, $cfg->rules) != '') {
            $hit = true;
        }
        if ($hit) {
            qingBotBlock_WriteLog($ua, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-');
            qingBotBlock_Reject();
        }
    }

    function qingBotBlock_Upgrade()
    {
        global $zbp;
        if (!$zbp->HasConfig('qingBotBlock')) {
            return;
        }
        $cfg = $zbp->Config('qingBotBlock');
        $ver = (method_exists($cfg, 'HasKey') && $cfg->HasKey('cfg_ver')) ? (int) $cfg->cfg_ver : 0;
        if ($ver >= 3) {
            return;
        }

        list($status, $content) = qingBotBlock_Prefs($cfg);
        $cfg->status = $status;
        $cfg->content = $content;

        $body = trim((string) $cfg->custom_body);
        if ($body === '' || $body === trim(qingBotBlock_BodyV1())) {
            $cfg->custom_body = qingBotBlock_DefaultBody();
        }
        $cfg->cfg_ver = 3;
        $zbp->SaveConfig('qingBotBlock');
    }
}
