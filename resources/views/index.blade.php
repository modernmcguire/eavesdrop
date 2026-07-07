<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Eavesdrop</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style type="text/tailwindcss">
        @theme {
            --color-canvas: #ECECE7;
            --color-surface: #FBFBF9;
            --color-panel: #F4F3EF;
            --color-line: #DAD8D0;
            --color-line-soft: #EAE8E1;
            --color-hairline: #F0EEE7;
            --color-ink: #1A1A17;
            --color-ink-soft: #3A382F;
            --color-muted: #6B675D;
            --color-faint: #8C887E;
            --color-ghost: #9A968C;
            --color-hair: #B0ACA0;
            --color-brand: #4F46E5;

            --font-display: 'Space Grotesk', sans-serif;
            --font-sans: 'Instrument Sans', system-ui, sans-serif;
            --font-mono: 'IBM Plex Mono', monospace;
        }
    </style>

    <style>
        html, body { margin: 0; height: 100%; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,.14); border-radius: 20px; border: 3px solid transparent; background-clip: content-box; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,.26); background-clip: content-box; }
        input::placeholder { color: #9A968C; }
        .em-body a { color: #4F46E5; }
    </style>
</head>
<body class="bg-canvas font-sans text-ink antialiased">
<div class="flex h-screen flex-col overflow-hidden"
     x-data="inbox('{{ url(config('eavesdrop.prefix').'/'.config('eavesdrop.endpoint')) }}', @js($channels))"
     x-init="load()">

    {{-- Top bar --}}
    <header class="flex h-[60px] flex-none items-center justify-between border-b border-line bg-surface px-5">
        <div class="flex items-center gap-[11px]">
            <div class="flex h-[34px] w-[34px] items-center justify-center rounded-[9px] bg-ink">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                    <path d="M4 5.5h16a1 1 0 0 1 1 1V15a1 1 0 0 1-1 1H9.5L5 19.5V16H4a1 1 0 0 1-1-1V6.5a1 1 0 0 1 1-1Z" stroke="#FBFBF9" stroke-width="1.6" stroke-linejoin="round"/>
                    <circle cx="9" cy="10.7" r="1.15" fill="#FBFBF9"/>
                    <circle cx="15" cy="10.7" r="1.15" fill="#FBFBF9"/>
                    <path d="M7.6 8.4C8.4 7.7 9 8 9 8m8 .4c-.8-.7-1.4-.4-1.4-.4" stroke="#FBFBF9" stroke-width="1.3" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="flex flex-col leading-none">
                <span class="font-display text-[17px] font-bold tracking-[-0.01em]">Eavesdrop</span>
                <span class="mt-[3px] font-mono text-[11px] text-faint">outbound message inspector</span>
            </div>
        </div>
        <div class="flex items-center gap-[14px]">
            <span class="text-[12.5px] text-faint"><b class="font-semibold text-ink" x-text="total"></b> captured</span>
            <button type="button" x-show="total > 0" @click="clearAll()"
                    class="cursor-pointer rounded-lg border border-[#F0D3CC] bg-[#FBEDEA] px-[13px] py-[7px] font-sans text-[12.5px] font-semibold text-[#B4402E] hover:bg-[#F6DED8] disabled:opacity-50"
                    :disabled="clearing">Clear all</button>
        </div>
    </header>

    <div class="flex min-h-0 flex-1">

        {{-- LEFT: list --}}
        <aside class="flex w-[392px] min-h-0 flex-none flex-col border-r border-line bg-panel">
            <div class="flex-none px-[14px] pb-[10px] pt-[14px]">
                <div class="relative mb-[11px]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" class="absolute left-3 top-1/2 -translate-y-1/2">
                        <circle cx="11" cy="11" r="7" stroke="#9A968C" stroke-width="2"/>
                        <path d="m20 20-3.2-3.2" stroke="#9A968C" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input type="search" placeholder="Search recipient, subject or body…"
                           class="h-[38px] w-full rounded-[10px] border border-line bg-surface pl-[34px] pr-3 font-sans text-[13.5px] text-ink outline-none focus:border-brand focus:ring-[3px] focus:ring-brand/15"
                           x-model.debounce.500ms="search" @input="load()">
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="chip in ['all', ...channels]" :key="chip">
                        <button type="button" @click="setChannel(chip)"
                                class="cursor-pointer whitespace-nowrap rounded-[20px] border px-[11px] py-[5px] font-sans text-[12px] font-semibold capitalize"
                                :class="channel === chip ? 'border-ink bg-ink text-surface' : 'border-line bg-surface text-muted'"
                                x-text="chip === 'all' ? 'All' : chip"></button>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-[10px] pb-3">
                <template x-for="message in messages" :key="message.id">
                    <div @click="open(message.id)"
                         class="mb-[3px] cursor-pointer rounded-[11px] border px-3 py-[11px] transition-colors"
                         :class="selected && selected.id === message.id
                            ? 'border-[#C7C3FF] bg-surface shadow-[0_2px_8px_rgba(26,26,23,.06)]'
                            : 'border-transparent bg-transparent'">
                        <div class="mb-[5px] flex items-center gap-[7px]">
                            <span class="inline-flex flex-none items-center gap-1 whitespace-nowrap rounded-[5px] px-[7px] py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.02em]"
                                  :style="typeMeta(message.type).style">
                                <span x-html="typeMeta(message.type).icon"></span>
                                <span x-text="typeMeta(message.type).label"></span>
                            </span>
                            <span class="flex-none whitespace-nowrap rounded-[5px] px-[7px] py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.02em]"
                                  :style="providerStyle(message.channel)" x-text="message.channel"></span>
                            <span class="ml-auto flex-none font-mono text-[11px] text-hair" x-text="'#' + message.id"></span>
                        </div>
                        <div x-show="message.type === 'email' && message.subject"
                             class="mb-[3px] truncate text-[13.5px] font-semibold text-ink" x-text="message.subject"></div>
                        <div class="mb-[3px] truncate font-mono text-[11.5px] text-faint" x-text="message.to || 'Unknown'"></div>
                        <div class="line-clamp-2 text-[13px] leading-[1.42] text-muted" x-text="message.content"></div>
                        <div class="mt-[5px] text-[11px] text-[#A8A498]" x-text="relative(message.created_at)"></div>
                    </div>
                </template>

                <div x-show="!messages.length && !loading" class="px-6 py-[54px] text-center">
                    <div class="text-[13.5px] leading-relaxed text-faint" x-text="emptyMessage()"></div>
                </div>

                <div x-show="hasMore" class="flex justify-center py-3">
                    <button type="button" @click="loadMore()" :disabled="loading"
                            class="cursor-pointer rounded-[9px] border border-line bg-surface px-4 py-2 font-sans text-[12.5px] font-semibold text-muted hover:text-ink disabled:opacity-50">
                        <span x-show="!loading">Load more</span>
                        <span x-show="loading">Loading…</span>
                    </button>
                </div>
            </div>
        </aside>

        {{-- RIGHT: detail --}}
        <main class="flex min-w-0 flex-1 flex-col bg-canvas">
            {{-- EMAIL detail --}}
            <template x-if="selected && selected.type === 'email'">
                <div class="flex-1 overflow-y-auto px-[34px] pb-10 pt-[22px]">
                    <div class="mx-auto max-w-[820px]">

                        {{-- metadata card --}}
                        <div class="overflow-hidden rounded-[14px] border border-line bg-surface">
                            <div class="flex items-center justify-between border-b border-line-soft px-5 py-[15px]">
                                <div class="flex items-center gap-[10px]">
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-[5px] px-[7px] py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.02em]"
                                          :style="typeMeta('email').style">
                                        <span x-html="typeMeta('email').icon"></span>Email
                                    </span>
                                    <span class="whitespace-nowrap rounded-[5px] px-[7px] py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.02em]"
                                          :style="providerStyle(selected.channel)" x-text="selected.channel"></span>
                                    <span class="text-[12.5px] text-faint" x-text="'via ' + (selected.channel || 'log') + ' mailer'"></span>
                                </div>
                                <span class="font-mono text-[12px] text-hair" x-text="'#' + selected.id"></span>
                            </div>
                            <div class="grid grid-cols-[auto_1fr] gap-x-[22px] px-5 py-4 text-[13.5px]">
                                <span class="py-[5px] text-ghost">From</span>
                                <span class="break-all py-[5px] font-mono font-medium" x-text="selected.payload?.from || '—'"></span>
                                <span class="border-t border-hairline py-[5px] text-ghost">To</span>
                                <span class="break-all border-t border-hairline py-[5px] font-mono font-medium" x-text="selected.to || '—'"></span>
                                <span x-show="selected.payload?.cc" class="border-t border-hairline py-[5px] text-ghost">Cc</span>
                                <span x-show="selected.payload?.cc" class="break-all border-t border-hairline py-[5px] font-mono font-medium" x-text="selected.payload?.cc"></span>
                                <span x-show="selected.payload?.bcc" class="border-t border-hairline py-[5px] text-ghost">Bcc</span>
                                <span x-show="selected.payload?.bcc" class="break-all border-t border-hairline py-[5px] font-mono font-medium" x-text="selected.payload?.bcc"></span>
                                <span class="border-t border-hairline py-[5px] text-ghost">Subject</span>
                                <span class="border-t border-hairline py-[5px] font-semibold" x-text="selected.subject || '—'"></span>
                                <span class="border-t border-hairline py-[5px] text-ghost">Date</span>
                                <span class="border-t border-hairline py-[5px] font-mono font-medium" x-text="datetime(selected.created_at)"></span>
                            </div>
                        </div>

                        {{-- attachments --}}
                        <template x-if="selected.payload?.attachments?.length">
                            <div class="mt-4 flex flex-wrap gap-2">
                                <template x-for="(att, i) in selected.payload.attachments" :key="i">
                                    <span class="inline-flex items-center gap-[6px] rounded-[8px] border border-line bg-surface px-[10px] py-[6px] text-[12px] text-ink-soft">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M21 12.5 12.7 20.8a5 5 0 0 1-7.1-7.1l8.3-8.3a3.3 3.3 0 0 1 4.7 4.7l-8.3 8.3a1.7 1.7 0 0 1-2.4-2.4l7.6-7.6" stroke="#8C887E" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span class="font-medium" x-text="att.filename || 'attachment'"></span>
                                        <span class="font-mono text-[11px] text-faint" x-text="att.content_type"></span>
                                    </span>
                                </template>
                            </div>
                        </template>

                        {{-- body tabs --}}
                        <div class="mt-[18px] flex gap-1 border-b border-line">
                            <template x-for="t in [['html','HTML'],['source','Source'],['text','Text']]" :key="t[0]">
                                <button type="button" @click="tab = t[0]"
                                        class="-mb-px cursor-pointer border-b-2 bg-none px-[15px] py-[9px] font-sans text-[13px] font-semibold"
                                        :class="tab === t[0] ? 'border-brand text-ink' : 'border-transparent text-faint'"
                                        x-text="t[1]"></button>
                            </template>
                        </div>
                        <div class="mt-5">
                            <div x-show="tab === 'html'" class="em-body overflow-hidden rounded-[12px] border border-line bg-[#EDF0F2]"
                                 x-html="selected.payload?.html || '<div style=&quot;padding:24px;color:#8C887E&quot;>No HTML body.</div>'"></div>
                            <pre x-show="tab === 'source'" class="overflow-x-auto whitespace-pre-wrap break-words rounded-[12px] bg-ink p-5 font-mono text-[12px] leading-[1.65] text-[#E7E5DE]"><code x-text="selected.payload?.html || 'No HTML body.'"></code></pre>
                            <pre x-show="tab === 'text'" class="whitespace-pre-wrap break-words rounded-[12px] border border-line bg-surface p-[22px] font-sans text-[14px] leading-[1.6] text-ink-soft" x-text="selected.payload?.text || selected.content || 'No text body.'"></pre>
                        </div>

                    </div>
                </div>
            </template>

            {{-- SMS detail --}}
            <template x-if="selected && selected.type !== 'email'">
                <div class="flex-1 overflow-y-auto px-[34px] pb-10 pt-[26px]">
                    <div class="mx-auto max-w-[760px]">

                        {{-- metadata card --}}
                        <div class="overflow-hidden rounded-[14px] border border-line bg-surface">
                            <div class="flex items-center justify-between border-b border-line-soft px-5 py-[15px]">
                                <div class="flex items-center gap-[10px]">
                                    <span class="whitespace-nowrap rounded-[5px] px-[7px] py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.02em]"
                                          :style="providerStyle(selected.channel)" x-text="selected.channel"></span>
                                    <span class="text-[12.5px] text-faint" x-text="'via ' + (selected.channel || 'log') + ' driver'"></span>
                                </div>
                                <span class="font-mono text-[12px] text-hair" x-text="'#' + selected.id"></span>
                            </div>
                            <div class="grid grid-cols-[auto_1fr] gap-x-[22px] px-5 py-4 text-[13.5px]">
                                <span class="py-[5px] text-ghost">To</span>
                                <span class="py-[5px] font-mono font-medium" x-text="selected.to || '—'"></span>
                                <span class="border-t border-hairline py-[5px] text-ghost">Notification</span>
                                <span class="break-all border-t border-hairline py-[5px] font-mono font-medium" x-text="selected.notification || '—'"></span>
                                <span class="border-t border-hairline py-[5px] text-ghost">Sent</span>
                                <span class="border-t border-hairline py-[5px] font-mono font-medium" x-text="datetime(selected.created_at)"></span>
                            </div>
                        </div>

                        {{-- body bubble --}}
                        <div class="mt-[22px] flex items-center gap-[9px]">
                            <span class="font-display text-[12px] font-semibold uppercase tracking-[0.04em] text-faint">Message body</span>
                            <div class="h-px flex-1 bg-line"></div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <div class="max-w-[80%] whitespace-pre-wrap rounded-[20px_20px_6px_20px] bg-brand px-[18px] py-[14px] text-[15px] leading-[1.5] text-white shadow-[0_6px_18px_rgba(79,70,229,.22)]"
                                 x-text="selected.content"></div>
                        </div>
                        <div class="mt-[9px] flex justify-end gap-[14px] font-mono text-[11px] text-[#A8A498]">
                            <span x-text="chars(selected.content) + ' chars'"></span>
                            <span x-text="segments(selected.content) + ' segment(s)'"></span>
                            <span x-text="encoding(selected.content)"></span>
                        </div>

                        {{-- payload --}}
                        <div x-show="selected.payload" class="mt-[26px] flex items-center gap-[9px]">
                            <span class="font-display text-[12px] font-semibold uppercase tracking-[0.04em] text-faint">Payload</span>
                            <div class="h-px flex-1 bg-line"></div>
                        </div>
                        <div x-show="selected.payload" class="mt-4 overflow-hidden rounded-[14px] border border-line bg-surface">
                            <pre class="overflow-x-auto p-4 font-mono text-[12.5px] leading-[1.55] text-ink-soft"><code x-text="JSON.stringify(selected.payload, null, 2)"></code></pre>
                        </div>

                    </div>
                </div>
            </template>

            <div x-show="!selected" class="flex flex-1 flex-col items-center justify-center gap-[18px] p-10 text-center">
                <div class="flex h-[66px] w-[66px] items-center justify-center rounded-[18px] border border-line bg-panel">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <path d="M4 5.5h16a1 1 0 0 1 1 1V15a1 1 0 0 1-1 1H9.5L5 19.5V16H4a1 1 0 0 1-1-1V6.5a1 1 0 0 1 1-1Z" stroke="#B0ACA0" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <div class="font-display text-[17px] font-semibold" x-text="total > 0 ? 'Select a message' : 'Inbox is empty'"></div>
                    <div class="mt-[6px] max-w-[320px] text-[13.5px] leading-relaxed text-faint"
                         x-text="total > 0
                            ? 'Choose a captured message from the list to inspect its metadata and body.'
                            : 'No messages captured yet. Trigger an outbound SMS or email from your app and it will appear here.'"></div>
                </div>
            </div>
        </main>

    </div>
</div>

<script>
    const TYPE_META = {
        email: {
            label: 'Email',
            style: { backgroundColor: '#E7F1EC', color: '#15704E' },
            icon: '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" style="display:block"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.9"/><path d="m4 7 8 5.5L20 7" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/></svg>',
        },
        sms: {
            label: 'SMS',
            style: { backgroundColor: '#E7EAFB', color: '#4338CA' },
            icon: '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" style="display:block"><path d="M4 5.5h16a1 1 0 0 1 1 1V15a1 1 0 0 1-1 1H9.5L5 19.5V16H4a1 1 0 0 1-1-1V6.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/></svg>',
        },
    };

    const PROVIDER_STYLES = {
        twilio: { backgroundColor: '#FDE7EA', color: '#C0213B' },
        vonage: { backgroundColor: '#F0E7FF', color: '#6D28D9' },
        plivo: { backgroundColor: '#E4F4EE', color: '#0E7C5A' },
        messagebird: { backgroundColor: '#E5F0FB', color: '#1E6FB8' },
        postmark: { backgroundColor: '#FCEFD4', color: '#B45309' },
        ses: { backgroundColor: '#FCEBD9', color: '#C2410C' },
        mailgun: { backgroundColor: '#FDE7EA', color: '#C0213B' },
        smtp: { backgroundColor: '#EEECE6', color: '#6B675D' },
    };

    const DEFAULT_BADGE_STYLE = { backgroundColor: '#EEECE6', color: '#6B675D' };

    function inbox(base, channels) {
        return {
            base,
            channels,
            messages: [],
            selected: null,
            search: '',
            channel: 'all',
            tab: 'html',
            page: 1,
            lastPage: 1,
            total: 0,
            loading: false,
            clearing: false,

            get hasMore() {
                return this.page < this.lastPage;
            },

            async load(reset = true) {
                if (reset) {
                    this.page = 1;
                }
                this.loading = true;
                const url = `${this.base}/list?search=${encodeURIComponent(this.search)}&channel=${encodeURIComponent(this.channel)}&page=${this.page}`;
                const json = await (await fetch(url)).json();
                this.messages = reset ? json.data : [...this.messages, ...json.data];
                this.lastPage = json.last_page;
                this.total = json.total;
                this.loading = false;
            },

            loadMore() {
                this.page++;
                this.load(false);
            },

            setChannel(channel) {
                this.channel = channel;
                this.load();
            },

            async open(id) {
                this.tab = 'html';
                this.selected = await (await fetch(`${this.base}/${id}`)).json();
            },

            async clearAll() {
                if (! confirm('Permanently delete all captured messages? This cannot be undone.')) {
                    return;
                }
                this.clearing = true;
                await fetch(this.base, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.selected = null;
                this.channel = 'all';
                this.search = '';
                this.clearing = false;
                await this.load();
            },

            emptyMessage() {
                if (this.total === 0) {
                    return 'No captured messages. New outbound messages will appear here.';
                }
                return 'No messages match your filters.';
            },

            typeMeta(type) {
                return TYPE_META[type] || TYPE_META.sms;
            },

            providerStyle(channel) {
                return PROVIDER_STYLES[(channel || '').toLowerCase()] || DEFAULT_BADGE_STYLE;
            },

            chars(content) {
                return content ? content.length : 0;
            },

            segments(content) {
                if (! content) {
                    return 0;
                }
                const perSegment = /[^\u0000-\u007f]/.test(content) ? 70 : 160;
                return Math.max(1, Math.ceil(content.length / perSegment));
            },

            encoding(content) {
                return content && /[^\u0000-\u007f]/.test(content) ? 'UCS-2' : 'GSM-7';
            },

            datetime(value) {
                if (! value) {
                    return '—';
                }
                const d = new Date(value);
                const pad = (n) => String(n).padStart(2, '0');
                return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
            },

            relative(value) {
                if (! value) {
                    return '';
                }
                const seconds = Math.floor((Date.now() - new Date(value)) / 1000);
                if (seconds < 60) {
                    return `${seconds} second${seconds === 1 ? '' : 's'} ago`;
                }
                const minutes = Math.floor(seconds / 60);
                if (minutes < 60) {
                    return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
                }
                const hours = Math.floor(minutes / 60);
                if (hours < 24) {
                    return `${hours} hour${hours === 1 ? '' : 's'} ago`;
                }
                const days = Math.floor(hours / 24);
                return `${days} day${days === 1 ? '' : 's'} ago`;
            },
        };
    }
</script>
</body>
</html>
