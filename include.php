<?php

/**
 * 清歌AI爬虫屏蔽
 *
 * @author 清歌
 * @link https://jiyun.xin/yun/
 */

if (!defined('ZBP_PATH')) {
    exit;
}

if (!defined('QING_BOTBLOCK_INCLUDED')) {
    define('QING_BOTBLOCK_INCLUDED', true);

    require_once dirname(__FILE__) . '/function.php';

    RegisterPlugin('qingBotBlock', 'ActivePlugin_qingBotBlock');

    function ActivePlugin_qingBotBlock()
    {
        Add_Filter_Plugin('Filter_Plugin_Index_Begin', 'qingBotBlock_Check');
        Add_Filter_Plugin('Filter_Plugin_ViewPost_Begin', 'qingBotBlock_Check');
        Add_Filter_Plugin('Filter_Plugin_ViewList_Begin', 'qingBotBlock_Check');
        Add_Filter_Plugin('Filter_Plugin_ViewAuto_Begin', 'qingBotBlock_Check');
        Add_Filter_Plugin('Filter_Plugin_Feed_Begin', 'qingBotBlock_Check');
        Add_Filter_Plugin('Filter_Plugin_Search_Begin', 'qingBotBlock_Check');
        Add_Filter_Plugin('Filter_Plugin_Admin_TopMenu', 'qingBotBlock_TopMenu');
    }

    function qingBotBlock_TopMenu(&$menus)
    {
        global $zbp;
        $menus[] = MakeTopMenu('root', 'AI爬虫屏蔽', $zbp->host . 'zb_users/plugin/qingBotBlock/main.php', '', '');
    }

    function InstallPlugin_qingBotBlock()
    {
        global $zbp;
        if ($zbp->HasConfig('qingBotBlock')) {
            return;
        }
        $cfg = $zbp->Config('qingBotBlock');
        $cfg->enable = 1;
        $cfg->status = '403';
        $cfg->content = 'preset';
        $cfg->custom_body = qingBotBlock_DefaultBody();
        $cfg->block_empty = 0;
        $cfg->log = 1;
        $cfg->rules = qingBotBlock_DefaultRules();
        $cfg->whitelist = qingBotBlock_DefaultWhitelist();
        $cfg->DelConfig = 0;
        $cfg->cfg_ver = 3;

        if ($zbp->HasConfig('aiBotBlock')) {
            $old = $zbp->Config('aiBotBlock');
            if ($old->HasKey('rules')) {
                $cfg->rules = $old->rules;
            }
            if ($old->HasKey('whitelist')) {
                $cfg->whitelist = $old->whitelist;
            }
            if ($old->HasKey('block_empty_ua')) {
                $cfg->block_empty = $old->block_empty_ua;
            }
            if ($old->HasKey('enable_log')) {
                $cfg->log = $old->enable_log;
            }
            if ($old->HasKey('enable')) {
                $cfg->enable = $old->enable;
            }
            if ($old->HasKey('mode') && $old->mode == 'empty') {
                $cfg->status = '200';
                $cfg->content = 'blank';
            }
        }

        $zbp->SaveConfig('qingBotBlock');
    }

    function UninstallPlugin_qingBotBlock()
    {
        global $zbp;
        if ($zbp->Config('qingBotBlock')->DelConfig == 1) {
            $zbp->DelConfig('qingBotBlock');
            if (is_file(qingBotBlock_LogFile())) {
                @unlink(qingBotBlock_LogFile());
            }
        }
    }
}
