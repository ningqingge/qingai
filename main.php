<?php

require '../../../zb_system/function/c_system_base.php';

require '../../../zb_system/function/c_system_admin.php';

$zbp->Load();

$action = 'root';
if (!$zbp->CheckRights($action)) {
    $zbp->ShowError(6);
    die();
}

if (!$zbp->CheckPlugin('qingBotBlock')) {
    $zbp->ShowError(48);
    die();
}

$blogtitle = '清歌AI蜘蛛屏蔽';

qingBotBlock_Upgrade();

$tab = GetVars('tab', 'GET');
if ($tab != 'log') {
    $tab = 'rules';
}

if (count($_POST) > 0) {
    if (function_exists('CheckIsRefererValid')) {
        CheckIsRefererValid();
    }
    $post_tab = GetVars('tab', 'POST');
    if ($post_tab != 'log') {
        $post_tab = 'rules';
    }
    $act = GetVars('act', 'POST');
    if ($act == 'clearlog') {
        qingBotBlock_ClearLog();
    } elseif ($act == 'logper') {
        $cfg = $zbp->Config('qingBotBlock');
        $cfg->log_per = qingBotBlock_PerPage(GetVars('log_per', 'POST'));
        $zbp->SaveConfig('qingBotBlock');
    } else {
        $cfg = $zbp->Config('qingBotBlock');
        $cfg->enable = (int) GetVars('enable', 'POST');
        $cfg->status = (GetVars('status', 'POST') == '200') ? '200' : '403';
        $content = GetVars('content', 'POST');
        $cfg->content = ($content == 'blank' || $content == 'custom') ? $content : 'preset';
        $new_body = qingBotBlock_PostBody();
        if ($new_body !== null) {
            $cfg->custom_body = str_replace("\r\n", "\n", $new_body);
        }
        $cfg->block_empty = (int) GetVars('block_empty', 'POST');
        $cfg->log = (int) GetVars('log', 'POST');
        $cfg->rules = qingBotBlock_PostText('rules');
        $cfg->whitelist = qingBotBlock_PostText('whitelist');
        $cfg->DelConfig = (int) GetVars('DelConfig', 'POST');
        $cfg->cfg_ver = 3;
        $zbp->SaveConfig('qingBotBlock');
    }
    $zbp->SetHint('good');
    Redirect('./main.php?tab=' . $post_tab);
}

$cfg = $zbp->Config('qingBotBlock');
$rules = qingBotBlock_ToArray($cfg->rules);
$white = qingBotBlock_ToArray($cfg->whitelist);
$body = (trim((string) $cfg->custom_body) == '') ? qingBotBlock_DefaultBody() : $cfg->custom_body;
list($pref_status, $pref_content) = qingBotBlock_Prefs($cfg);

$per_page = qingBotBlock_LogPer($cfg);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$log = qingBotBlock_ReadLog($per_page, ($page - 1) * $per_page);
$page_count = (int) ceil($log['total'] / $per_page);
if ($page_count < 1) {
    $page_count = 1;
}
if ($page > $page_count) {
    $page = $page_count;
    $log = qingBotBlock_ReadLog($per_page, ($page - 1) * $per_page);
}

$content_hint = array(
    'preset' => '使用插件自带的拦截页模板，内容固定不可修改，会跟随上方状态码显示对应字样',
    'blank' => '只返回状态码与空页面，不输出页面内容',
    'custom' => '使用下方自定义模板，可用变量插入站点、IP、UA 等信息',
);

$var_help = array(
    '{code}' => array('状态码', '当前选择的状态码数字，如 403、200'),
    '{status}' => array('状态短语', '状态码对应的英文短语，如 Forbidden、OK'),
    '{site}' => array('站点地址', '本站首页地址，末尾带斜杠'),
    '{url}' => array('请求网址', '被拦截的那个完整网址'),
    '{ip}' => array('访客 IP', '发起请求的客户端 IP'),
    '{ua}' => array('浏览器标识', '该请求的完整 User-Agent'),
    '{time}' => array('当前时间', '拦截发生的服务器时间'),
);

Add_Filter_Plugin('Filter_Plugin_Admin_Header', 'qingBotBlock_AdminHeader');

require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';

?>
<div id="divMain">

  <div class="divHeader"><?php echo $blogtitle; ?></div>
  <div class="SubMenu"> <a href="main.php?tab=rules"><span class="m-left<?php echo $tab == 'rules' ? ' m-now' : ''; ?>">拦截设置</span></a><a href="main.php?tab=log"><span class="m-left<?php echo $tab == 'log' ? ' m-now' : ''; ?>">拦截日志</span></a> </div>

  <div id="divMain2">
  <div class="qbb">

    <div class="qbb-hero">
      <div class="qbb-hero-main">
        <img class="qbb-hero-logo" src="<?php echo qingBotBlock_Esc(qingBotBlock_AssetUrl('logo.png')); ?>" alt="" width="46" height="46">
        <div class="qbb-hero-body">
          <span class="qbb-badge qbb-badge-<?php echo $cfg->enable ? 'on' : 'off'; ?>"><i></i><?php echo $cfg->enable ? '防护已开启' : '防护已关闭'; ?></span>
          <h2>清歌AI蜘蛛屏蔽</h2>
          <p>命中拦截列表的请求会在模板渲染之前直接返回，不查询数据库、不加载主题模板。判定依据是 User-Agent 字符串，伪造 UA 的请求无法被拦截。</p>
        </div>
      </div>
      <div class="qbb-stats">
        <div class="qbb-stat"><b><?php echo count($rules); ?></b><span>拦截规则</span></div>
        <div class="qbb-stat"><b><?php echo count($white); ?></b><span>白名单</span></div>
        <div class="qbb-stat"><b><?php echo $log['today']; ?></b><span>今日拦截</span></div>
        <div class="qbb-stat"><b><?php echo $log['total']; ?></b><span>累计拦截</span></div>
      </div>
    </div>

<?php if ($tab == 'rules'): ?>

    <form id="qbbForm" method="post" action="./main.php">
    <input type="hidden" name="csrfToken" value="<?php echo $zbp->GetCSRFToken(); ?>">
    <input type="hidden" name="tab" value="rules">
    <input type="hidden" name="pack_rules" id="qbbRulesEsc" value="">
    <input type="hidden" name="pack_whitelist" id="qbbWhiteEsc" value="">
    <input type="hidden" name="custom_body_esc" id="qbbBodyEsc" value="">
    <input type="hidden" name="custom_body_keep" id="qbbBodyKeep" value="">

      <div class="qbb-card">
        <div class="qbb-card-hd"><h3>基础设置</h3><span class="qbb-hint">总开关与响应状态码</span></div>
        <div class="qbb-card-bd">
          <div class="qbb-line">
            <div class="qbb-line-txt"><strong>启用拦截</strong><span>关闭后所有请求直接放行，配置保持不变</span></div>
            <label class="qbb-switch"><input type="checkbox" name="enable" value="1" <?php echo $cfg->enable ? 'checked' : ''; ?>><i></i></label>
          </div>
          <div class="qbb-line">
            <div class="qbb-line-txt"><strong>响应状态码</strong><span>写在 HTTP 响应头里，页面内容看不到它。403 更规范；200 对蜘蛛更隐蔽，但它会认为抓取成功</span></div>
            <div class="qbb-seg">
              <label class="qbb-seg-item"><input type="radio" name="status" value="403" <?php echo $pref_status == '403' ? 'checked' : ''; ?>><span>403 Forbidden</span></label>
              <label class="qbb-seg-item"><input type="radio" name="status" value="200" <?php echo $pref_status == '200' ? 'checked' : ''; ?>><span>200 OK</span></label>
            </div>
          </div>
          <div class="qbb-line">
            <div class="qbb-line-txt"><strong>拦截空 User-Agent</strong><span>拒绝完全不发送 UA 的请求，正常浏览器不会命中</span></div>
            <label class="qbb-switch"><input type="checkbox" name="block_empty" value="1" <?php echo $cfg->block_empty ? 'checked' : ''; ?>><i></i></label>
          </div>
          <div class="qbb-line">
            <div class="qbb-line-txt"><strong>记录拦截日志</strong><span>写入 blocked.log，可在「拦截日志」中查看</span></div>
            <label class="qbb-switch"><input type="checkbox" name="log" value="1" <?php echo $cfg->log ? 'checked' : ''; ?>><i></i></label>
          </div>
        </div>
      </div>

      <div class="qbb-card">
        <div class="qbb-card-hd">
          <h3>响应内容</h3><span class="qbb-hint">命中拦截后返回的页面</span>
          <div class="qbb-tools">
            <button type="button" class="qbb-btn qbb-btn-ghost" data-act="preview" data-target="qbbBody">预览效果</button>
          </div>
        </div>
        <div class="qbb-card-bd">
          <div class="qbb-line">
            <div class="qbb-line-txt"><strong>内容类型</strong><span id="qbbContentHint"><?php echo $content_hint[$pref_content]; ?></span></div>
            <div class="qbb-seg">
              <label class="qbb-seg-item"><input type="radio" name="content" value="preset" <?php echo $pref_content == 'preset' ? 'checked' : ''; ?>><span>内置拦截页</span></label>
              <label class="qbb-seg-item"><input type="radio" name="content" value="blank" <?php echo $pref_content == 'blank' ? 'checked' : ''; ?>><span>空白页</span></label>
              <label class="qbb-seg-item"><input type="radio" name="content" value="custom" <?php echo $pref_content == 'custom' ? 'checked' : ''; ?>><span>自定义模板</span></label>
            </div>
          </div>

          <div class="qbb-pane qbb-pane-preset">
            <div class="qbb-note">
              <strong>内置拦截页 · 内容固定</strong>
              <span>由插件提供，无法修改。页面会跟随上方状态码显示对应的数字与短语；点右上角「预览效果」可查看实际输出的样子。需要自定义请把内容类型切换为「自定义模板」。</span>
            </div>
          </div>

          <div class="qbb-pane qbb-pane-blank">
            <div class="qbb-note">
              <strong>空白页 · 不输出内容</strong>
              <span>只返回一行状态码和一个空 body，不渲染任何可见内容，不输出页面内容。点右上角「预览效果」可查看实际返回的页面。</span>
            </div>
          </div>

          <div class="qbb-pane qbb-pane-custom">
            <textarea id="qbbBody" name="custom_body" class="qbb-ta qbb-ta-code" rows="17" spellcheck="false"><?php echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="qbb-meta">
              <b id="qbbBodyCount"></b>
              <button type="button" class="qbb-btn qbb-btn-ghost qbb-btn-mini" data-act="tpl" data-target="qbbBody">恢复默认模板</button>
            </div>
            <div class="qbb-varbox">
              <div class="qbb-varbox-hd">可用变量<span>点击插入到光标处，保存后由服务器替换</span></div>
              <div class="qbb-vars">
<?php foreach ($var_help as $var => $info): ?>
                <button type="button" class="qbb-chip" data-var="<?php echo $var; ?>" title="<?php echo htmlspecialchars($info[0] . '：' . $info[1], ENT_QUOTES, 'UTF-8'); ?>"><b><?php echo $var; ?></b><i><?php echo $info[0]; ?></i></button>
<?php endforeach; ?>
              </div>
            </div>
            <div class="qbb-note qbb-note-warn">
              <strong>保存被「网站防火墙」拦下？</strong>
              <span>模板与规则里含有 HTML 或换行，个别防火墙会误判成攻击。本插件已把它们转义后再提交来规避；若仍被拦，请到面板「网站防火墙 → 拦截日志」查看命中的规则，把本页面地址加入白名单。</span>
            </div>
          </div>
        </div>
      </div>

      <div class="qbb-card">
        <div class="qbb-card-hd">
          <h3>拦截规则</h3><span class="qbb-hint">每行一个 UA 关键词，子串匹配，忽略大小写</span>
          <div class="qbb-tools">
            <button type="button" class="qbb-btn qbb-btn-ghost" data-act="reset" data-target="qbbRules">恢复推荐</button>
            <button type="button" class="qbb-btn qbb-btn-ghost" data-act="empty" data-target="qbbRules">清空</button>
          </div>
        </div>
        <div class="qbb-card-bd">
          <textarea id="qbbRules" name="rules" class="qbb-ta" rows="14" spellcheck="false" wrap="off"><?php echo htmlspecialchars($cfg->rules, ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div class="qbb-meta"><b id="qbbRulesCount"></b><span>UA 中命中其中任意一行关键词，即按蜘蛛处理</span></div>
        </div>
      </div>

      <div class="qbb-card">
        <div class="qbb-card-hd"><h3>白名单</h3><span class="qbb-hint">优先级高于拦截规则，命中即放行</span></div>
        <div class="qbb-card-bd">
          <textarea id="qbbWhite" name="whitelist" class="qbb-ta" rows="8" spellcheck="false" wrap="off"><?php echo htmlspecialchars($cfg->whitelist, ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div class="qbb-meta"><b id="qbbWhiteCount"></b><span>主流搜索引擎默认在列，命中即放行</span></div>
        </div>
      </div>

      <div class="qbb-card qbb-card-warn">
        <div class="qbb-card-hd"><h3>停用清理</h3><span class="qbb-hint">仅在停用插件时生效</span></div>
        <div class="qbb-card-bd">
          <div class="qbb-line">
            <div class="qbb-line-txt"><strong>停用时删除配置</strong><span>勾选后停用本插件会一并清除配置与拦截日志</span></div>
            <label class="qbb-switch qbb-switch-danger"><input type="checkbox" name="DelConfig" value="1" <?php echo $cfg->DelConfig ? 'checked' : ''; ?>><i></i></label>
          </div>
        </div>
      </div>

      <div class="qbb-bar">
        <span class="qbb-bar-tip">拦截在请求最开始执行，白名单命中的请求直接放行</span>
        <button type="submit" class="qbb-btn qbb-btn-save">保存设置</button>
      </div>

    </form>

<?php else: ?>

    <div class="qbb-card">
      <div class="qbb-card-hd">
        <h3>拦截日志</h3><span class="qbb-hint">累计 <?php echo $log['total']; ?> 条，第 <?php echo $page; ?> / <?php echo $page_count; ?> 页，每页 <?php echo $per_page; ?> 条</span>
        <div class="qbb-tools">
          <form method="post" action="./main.php">
            <input type="hidden" name="csrfToken" value="<?php echo $zbp->GetCSRFToken(); ?>">
            <input type="hidden" name="tab" value="log">
            <input type="hidden" name="act" value="logper">
            <label class="qbb-per">
              <span>每页</span>
              <select name="log_per" data-auto-submit="1">
<?php foreach (array(50, 100, 200, 500) as $opt): ?>
                <option value="<?php echo $opt; ?>"<?php echo $opt == $per_page ? ' selected' : ''; ?>><?php echo $opt; ?> 条</option>
<?php endforeach; ?>
              </select>
            </label>
          </form>
          <form method="post" action="./main.php" data-confirm="确定清空全部拦截日志吗？此操作不可恢复。">
            <input type="hidden" name="csrfToken" value="<?php echo $zbp->GetCSRFToken(); ?>">
            <input type="hidden" name="tab" value="log">
            <input type="hidden" name="act" value="clearlog">
            <button type="submit" class="qbb-btn qbb-btn-ghost">清空日志</button>
          </form>
        </div>
      </div>
      <div class="qbb-card-bd">
        <div class="qbb-note">
          <strong>日志为空但请求确实被拒绝了？</strong>
          <span>这说明拦截发生在 PHP 之前。本插件运行在 Z-Blog 内部，只有请求真正进入 PHP 才能记录；若 Nginx、宝塔网站防火墙或 CDN 已经先把该 UA 拦掉，返回的错误页由它们生成，插件不会留下日志，属于正常现象。</span>
        </div>
<?php if (count($log['rows']) > 0): ?>
        <table class="qbb-table">
          <thead><tr><th>时间</th><th>IP</th><th>命中规则</th><th>User-Agent</th></tr></thead>
          <tbody>
<?php foreach ($log['rows'] as $row): ?>
<?php $hit = ($row['ua'] == '') ? '空 UA' : qingBotBlock_Match($row['ua'], $cfg->rules); ?>
            <tr>
              <td class="qbb-td-time"><?php echo htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="qbb-td-ip"><?php echo htmlspecialchars($row['ip'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="qbb-tag"><?php echo htmlspecialchars($hit == '' ? '未匹配' : $hit, ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td class="qbb-td-ua"><?php echo htmlspecialchars($row['ua'] == '' ? '(空)' : $row['ua'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
<?php if ($page_count > 1): ?>
        <div class="qbb-pager">
          <span class="qbb-pager-info">共 <?php echo $log['total']; ?> 条记录，当前第 <?php echo $page; ?> / <?php echo $page_count; ?> 页</span>
          <span class="qbb-pager-links">
<?php
$qbb_first = max(1, $page - 2);
$qbb_last = min($page_count, $page + 2);
$qbb_pages = array();
if ($qbb_first > 1) {
    $qbb_pages[] = 1;
}
if ($qbb_first > 2) {
    $qbb_pages[] = 0;
}
for ($qbb_i = $qbb_first; $qbb_i <= $qbb_last; $qbb_i++) {
    $qbb_pages[] = $qbb_i;
}
if ($qbb_last < $page_count - 1) {
    $qbb_pages[] = 0;
}
if ($qbb_last < $page_count) {
    $qbb_pages[] = $page_count;
}
?>
            <a class="qbb-page<?php echo $page <= 1 ? ' qbb-page-off' : ''; ?>" href="./main.php?tab=log&amp;page=<?php echo $page - 1; ?>">上一页</a>
<?php foreach ($qbb_pages as $qbb_p): ?>
<?php if ($qbb_p == 0): ?>
            <span class="qbb-page-gap">…</span>
<?php else: ?>
            <a class="qbb-page<?php echo $qbb_p == $page ? ' qbb-page-now' : ''; ?>" href="./main.php?tab=log&amp;page=<?php echo $qbb_p; ?>"><?php echo $qbb_p; ?></a>
<?php endif; ?>
<?php endforeach; ?>
            <a class="qbb-page<?php echo $page >= $page_count ? ' qbb-page-off' : ''; ?>" href="./main.php?tab=log&amp;page=<?php echo $page + 1; ?>">下一页</a>
          </span>
        </div>
<?php endif; ?>
<?php else: ?>
        <div class="qbb-blank">
          <i></i>
          <strong>暂无拦截记录</strong>
          <span>开启「启用拦截」与「记录拦截日志」后，被拒绝的请求会显示在这里</span>
        </div>
<?php endif; ?>
      </div>
    </div>

<?php endif; ?>

    <div class="qbb-foot">
      <span class="qbb-foot-tip">自测：终端执行 <code>curl -A "GPTBot" <?php echo qingBotBlock_Esc(qingBotBlock_SiteUrl()); ?></code>，返回 403 或你设置的拦截页即表示本插件生效</span>
      <span class="qbb-sign">v<?php echo qingBotBlock_Esc(qingBotBlock_Ver()); ?> &nbsp;&copy; <?php echo date('Y'); ?> <a href="https://jiyun.xin/yun/" target="_blank" rel="noopener">清歌</a></span>
    </div>

  </div>
  </div>
</div>
<?php $qbb_json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE; ?>
<script>window.QBB = {"ver": <?php echo json_encode(qingBotBlock_Ver(), $qbb_json_flags); ?>, "rules": <?php echo json_encode(qingBotBlock_DefaultRules(), $qbb_json_flags); ?>, "body": <?php echo json_encode(qingBotBlock_DefaultBody(), $qbb_json_flags); ?>, "site": <?php echo json_encode(qingBotBlock_SiteUrl(), $qbb_json_flags); ?>, "css": <?php echo json_encode(qingBotBlock_AssetUrl('css/preview.css'), $qbb_json_flags); ?>, "hints": <?php echo json_encode($content_hint, $qbb_json_flags); ?>};</script>
<script src="<?php echo qingBotBlock_Esc(qingBotBlock_AssetUrl('js/admin.js')); ?>"></script>
<script>ActiveLeftMenu("aPluginMng");</script>
<script>AddHeaderIcon("<?php echo qingBotBlock_Esc(qingBotBlock_AssetUrl('logo.png')); ?>");</script>
<?php

require $blogpath . 'zb_system/admin/admin_footer.php';

RunTime();

?>
