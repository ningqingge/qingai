/* 清歌AI蜘蛛屏蔽 · 后台交互 */

((window, document) => {
    const cfg = window.QBB || {};
    const defaultRules = cfg.rules || '';
    const defaultBody = cfg.body || '';
    const siteUrl = cfg.site || '';
    const cssUrl = cfg.css || '';

    const trim = (text) => String(text).replace(/^\s+|\s+$/g, '');

    const checkedValue = (name, fallback) => {
        const el = document.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : fallback;
    };

    const sub = (text, key, val) => text.replace(new RegExp('\\{' + key + '\\}', 'g'), () => val);

    const pad = (n) => (n < 10 ? '0' : '') + n;

    const nowText = () => {
        const d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' '
            + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    };

    const bindCounter = (boxId, outId, unit) => {
        const box = document.getElementById(boxId);
        const out = document.getElementById(outId);
        if (!box || !out) {
            return;
        }
        const refresh = () => {
            let n = 0;
            box.value.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').forEach((row) => {
                if (trim(row) !== '') {
                    n++;
                }
            });
            out.innerHTML = '共 ' + n + ' ' + unit;
        };
        box.addEventListener('input', refresh);
        refresh();
    };

    const nudge = (box) => {
        box.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const insertAtCursor = (box, text) => {
        box.focus();
        box.setRangeText(text, box.selectionStart, box.selectionEnd, 'end');
    };

    const escText = (text) => String(text)
        .replace(/\[/g, '[lb]')
        .replace(/</g, '[lt]')
        .replace(/>/g, '[gt]')
        .replace(/\r\n|\r|\n/g, '[nl]');

    const packField = (boxId, escId) => {
        const box = document.getElementById(boxId);
        const esc = document.getElementById(escId);
        if (!box || !esc) {
            return;
        }
        esc.value = escText(box.value);
        box.removeAttribute('name');
    };

    const readState = () => {
        const code = checkedValue('status', '403');
        const content = checkedValue('content', 'preset');
        return {
            code: code,
            text: code === '200' ? 'OK' : 'Forbidden',
            ok: code === '200',
            content: content
        };
    };

    const EMPTY_PAGE = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8">'
        + '<title>{status}</title></head><body></body></html>';

    const renderPage = (state, tpl) => {
        let source = defaultBody;
        if (state.content === 'blank') {
            source = EMPTY_PAGE;
        } else if (state.content === 'custom' && trim(tpl) !== '') {
            source = tpl;
        }
        let html = sub(source, 'site', siteUrl);
        html = sub(html, 'url', siteUrl + '114.html');
        html = sub(html, 'ip', '203.0.113.9');
        html = sub(html, 'ua', 'GPTBot/1.0');
        html = sub(html, 'time', nowText());
        html = sub(html, 'code', state.code);
        return sub(html, 'status', state.text);
    };

    const warnText = (state) => {
        if (state.content === 'preset') {
            return '当前内容类型是「内置拦截页」，下面预览的就是插件内置的页面（内容固定），模板框里写的内容不会被使用。';
        }
        if (state.content === 'blank') {
            return '当前内容类型是「空白页」，下面预览的就是实际返回的空页面 —— 只有状态码，没有任何可见内容。';
        }
        return '';
    };

    const addRow = (parent, key, val) => {
        const row = document.createElement('div');
        row.className = 'qbb-pv-kv';
        const name = document.createElement('span');
        name.textContent = key;
        const value = document.createElement('b');
        value.textContent = val;
        row.append(name, value);
        parent.appendChild(row);
    };

    const preview = (tpl) => {
        const state = readState();
        const win = window.open('', '_blank', 'width=1000,height=820,scrollbars=yes');
        if (!win) {
            window.alert('浏览器拦截了预览窗口，请允许弹出窗口后重试。');
            return;
        }
        const doc = win.document;
        doc.title = '拦截页预览';
        if (cssUrl) {
            const link = doc.createElement('link');
            link.rel = 'stylesheet';
            link.href = cssUrl;
            (doc.head || doc.documentElement).appendChild(link);
        }

        const head = doc.createElement('div');
        head.className = 'qbb-pv-hd';

        const row = doc.createElement('div');
        row.className = 'qbb-pv-row';
        const code = doc.createElement('span');
        code.className = 'qbb-pv-code ' + (state.ok ? 'qbb-pv-code-ok' : 'qbb-pv-code-no');
        code.textContent = 'HTTP/1.1 ' + state.code + ' ' + state.text;
        const label = doc.createElement('span');
        label.className = 'qbb-pv-label';
        label.textContent = '响应头';
        row.append(code, label);
        head.appendChild(row);

        addRow(head, 'Content-Type', 'text/html; charset=utf-8');
        addRow(head, 'X-Robots-Tag', 'noindex, nofollow, noarchive');
        addRow(head, 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        const tip = doc.createElement('p');
        tip.className = 'qbb-pv-tip';
        tip.append('状态码属于 HTTP 响应头，页面内容里不显示它，所以下面的页面本身看不到这个数字。蜘蛛收到的是 ');
        const tipCode = doc.createElement('b');
        tipCode.textContent = state.code + ' ' + state.text;
        tip.append(tipCode, '。下面是实际输出的页面内容：');

        const warn = warnText(state);
        const box = doc.createElement('div');
        box.className = 'qbb-pv-frame';
        const frame = doc.createElement('iframe');
        box.appendChild(frame);

        doc.body.append(head, tip);
        if (warn) {
            const note = doc.createElement('p');
            note.className = 'qbb-pv-warn';
            note.textContent = warn;
            doc.body.appendChild(note);
        }
        doc.body.appendChild(box);
        frame.srcdoc = renderPage(state, tpl);
    };

    const panes = {
        preset: document.querySelector('.qbb-pane-preset'),
        blank: document.querySelector('.qbb-pane-blank'),
        custom: document.querySelector('.qbb-pane-custom')
    };
    const contentHint = document.getElementById('qbbContentHint');
    const hints = cfg.hints || {};
    const bodyBox = document.getElementById('qbbBody');
    let bodyDirty = false;

    const syncContent = () => {
        const content = checkedValue('content', 'preset');
        Object.keys(panes).forEach((key) => {
            const pane = panes[key];
            if (pane) {
                pane.classList.toggle('qbb-pane-on', key === content);
            }
        });
        if (bodyBox) {
            if (content === 'custom') {
                bodyBox.removeAttribute('readonly');
            } else {
                bodyBox.setAttribute('readonly', 'readonly');
            }
        }
        if (contentHint && hints[content]) {
            contentHint.innerHTML = hints[content];
        }
    };

    if (bodyBox) {
        bodyBox.addEventListener('input', () => {
            bodyDirty = true;
        });
    }

    document.querySelectorAll('.qbb-seg-item input[name="content"]').forEach((radio) => {
        radio.addEventListener('change', syncContent);
    });
    syncContent();

    const rulesBox = document.getElementById('qbbRules');
    bindCounter('qbbRules', 'qbbRulesCount', '条');
    bindCounter('qbbWhite', 'qbbWhiteCount', '条');
    bindCounter('qbbBody', 'qbbBodyCount', '行');

    document.querySelectorAll('#qbbForm button[data-act]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const box = document.getElementById(btn.dataset.target);
            if (!box) {
                return;
            }
            const act = btn.dataset.act;
            if (act === 'preview') {
                preview(box.value);
                return;
            }
            if (act === 'reset') {
                box.value = defaultRules;
            } else if (act === 'tpl') {
                box.value = defaultBody;
            } else if (window.confirm('确定清空当前列表吗？')) {
                box.value = '';
            } else {
                return;
            }
            nudge(box);
        });
    });

    document.querySelectorAll('.qbb-chip[data-var]').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!bodyBox) {
                return;
            }
            insertAtCursor(bodyBox, chip.dataset.var);
            nudge(bodyBox);
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('select[data-auto-submit]').forEach((select) => {
        select.addEventListener('change', () => {
            if (select.form) {
                select.form.submit();
            }
        });
    });

    const form = document.getElementById('qbbForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const on = form.querySelector('input[name="enable"]');
            if (on && on.checked && rulesBox && rulesBox.value.replace(/\s/g, '') === '') {
                if (!window.confirm('拦截规则为空，开启拦截后不会拦截任何请求，仍要保存吗？')) {
                    e.preventDefault();
                    return;
                }
            }
            const keep = document.getElementById('qbbBodyKeep');
            if (bodyBox) {
                if (bodyDirty) {
                    packField('qbbBody', 'qbbBodyEsc');
                    if (keep) {
                        keep.value = '';
                    }
                } else {
                    if (keep) {
                        keep.value = '1';
                    }
                    bodyBox.removeAttribute('name');
                }
            }
            packField('qbbRules', 'qbbRulesEsc');
            packField('qbbWhite', 'qbbWhiteEsc');
        });
    }

    if (window.console && window.console.info) {
        window.console.info('[qingBotBlock] admin.js v' + (cfg.ver || '?') + ' loaded');
    }
})(window, document);
