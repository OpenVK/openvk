//import { html, render } from './render.js';
const { html, render } = await es6import_Im(import.meta.url, './render.js');

export class CalendarComponent {
    constructor({ initialDate = null, onSelectDate = null, onClose = null } = {}) {
        this.onSelectDate = onSelectDate || (() => {});
        this.onClose = onClose || (() => {});

        let init = new Date();
        if (initialDate) {
            if (typeof initialDate === "number") {
                init = new Date(initialDate > 1e11 ? initialDate : initialDate * 1000);
            } else if (typeof initialDate === "string") {
                const s = initialDate.trim();
                const ddmmyyyy = s.match(/^(\d{1,2})[./-](\d{1,2})[./-](\d{4})$/);
                if (ddmmyyyy) {
                    init = new Date(Number(ddmmyyyy[3]), Number(ddmmyyyy[2]) - 1, Number(ddmmyyyy[1]));
                } else {
                    const parsed = new Date(s);
                    if (!isNaN(parsed.getTime())) {
                        init = parsed;
                    }
                }
            } else if (initialDate instanceof Date && !isNaN(initialDate.getTime())) {
                init = initialDate;
            }
        }

        this.currentYear = init.getFullYear();
        this.currentMonth = init.getMonth();
        this.selectedDay = init.getDate();
        this.today = new Date();
        this.container = null;
    }

    setMonth(month) {
        this.currentMonth = month;
        if (this.currentMonth < 0) {
            this.currentMonth = 11;
            this.currentYear--;
        } else if (this.currentMonth > 11) {
            this.currentMonth = 0;
            this.currentYear++;
        }
        this.update();
    }

    setYear(year) {
        this.currentYear = parseInt(year);
        this.update();
    }

    selectDay(d) {
        const targetDate = new Date(this.currentYear, this.currentMonth, d, 0, 0, 0);
        this.onSelectDate(targetDate, {
            year: this.currentYear,
            month: this.currentMonth,
            day: d,
            timestamp: Math.floor(targetDate.getTime() / 1000)
        });
    }

    render(container) {
        this.container = container;
        this.update();
    }

    update() {
        if (!this.container) return;

        const monthNames = [];
        for (let i = 1; i <= 12; i++) {
            monthNames.push(tr("month_" + i));
        }
        const weekdays = [];
        for (let i = 1; i != 8; i++) {
            weekdays.push(tr("week_day_short_" + i));
        }

        const firstDayOfMonth = new Date(this.currentYear, this.currentMonth, 1);
        let firstDayIndex = firstDayOfMonth.getDay() - 1;
        if (firstDayIndex === -1) firstDayIndex = 6;

        const daysInPrevMonth = new Date(this.currentYear, this.currentMonth, 0).getDate();
        const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

        const prevDays = [];
        for (let i = firstDayIndex - 1; i >= 0; i--) {
            prevDays.push(daysInPrevMonth - i);
        }

        const currentDays = [];
        for (let d = 1; d <= daysInMonth; d++) {
            currentDays.push(d);
        }

        const totalCells = firstDayIndex + daysInMonth;
        const nextDaysCount = (7 - (totalCells % 7)) % 7;
        const nextDays = [];
        for (let d = 1; d <= nextDaysCount; d++) {
            nextDays.push(d);
        }

        const thisYear = new Date().getFullYear();
        const years = [];

        // 11.11.2019 овк запустили
        for (let y = 2019; y <= thisYear; y++) {
            years.push(y);
        }

        render(html`
            <div class="ovk-calendar-widget">
                <div class="ovk-cal-header">
                    <button type="button" class="ovk-cal-nav-btn" onClick=${() => this.setMonth(this.currentMonth - 1)}>‹</button>
                    <div class="ovk-cal-selects">
                        <select onChange=${(e) => this.setMonth(parseInt(e.target.value))}>
                            ${monthNames.map((name, idx) => html`
                                <option value=${idx} selected=${idx === this.currentMonth}>${name}</option>
                            `)}
                        </select>
                        <select onChange=${(e) => this.setYear(e.target.value)}>
                            ${years.map(y => html`
                                <option value=${y} selected=${y === this.currentYear}>${y}</option>
                            `)}
                        </select>
                    </div>
                    <button type="button" class="ovk-cal-nav-btn" onClick=${() => this.setMonth(this.currentMonth + 1)}>›</button>
                </div>

                <div class="ovk-cal-grid">
                    ${weekdays.map(wd => html`<div class="ovk-cal-weekday">${wd}</div>`)}
                    ${prevDays.map(d => html`<div class="ovk-cal-day ovk-cal-day-other">${d}</div>`)}
                    ${currentDays.map(d => {
                        const isToday = this.today.getDate() === d &&
                                        this.today.getMonth() === this.currentMonth &&
                                        this.today.getFullYear() === this.currentYear;
                        const isSelected = this.selectedDay === d;
                        const cls = [
                            'ovk-cal-day',
                            isToday ? 'ovk-cal-day-today' : '',
                            isSelected ? 'ovk-cal-day-selected' : ''
                        ].filter(Boolean).join(' ');

                        return html`
                            <div class="${cls}" onClick=${() => this.selectDay(d)}>${d}</div>
                        `;
                    })}
                    ${nextDays.map(d => html`<div class="ovk-cal-day ovk-cal-day-other">${d}</div>`)}
                </div>
            </div>
        `, this.container);
    }
}

export function openCalendarModal({ initialDate = null, peerId = null, onSelectDate = null } = {}) {
    const peer_id = peerId || window.im?.messenger?.getCurrentChat()?.peer?.id;

    const modal = new CMessageBox({
        title: (typeof tr === 'function' ? tr("day_selection") : null) || "Выбор дня",
        body: `<div id="ovk_calendar_modal_body"></div>`,
        buttons: [(typeof tr === 'function' ? tr("cancel") : null) || "Отмена"],
        callbacks: [() => {}],
    });

    const bodyNode = modal.getNode().find("#ovk_calendar_modal_body");
    const container = bodyNode && bodyNode.nodes ? bodyNode.nodes[0] : null;

    const cal = new CalendarComponent({
        initialDate: initialDate,
        onClose: () => modal.close(),
        onSelectDate: async (dateObj, meta) => {
            modal.close();
            if (onSelectDate) {
                onSelectDate(dateObj, meta);
                return;
            }

            const targetTimestamp = meta.timestamp;
            const endTimestamp = targetTimestamp + 86400;
            const currentConv = window.im?.messenger?.getCurrentChat();
            const peerId = currentConv.peer.id;

            try {
                const res = await window.OVKAPI.call("messages.getNearestMessageForDate", {
                    date: targetTimestamp,
                    peer_id: peerId
                });

                if (res && (res.id || res.conversation_message_id)) {
                    const msgId = res.id || res.conversation_message_id;
                    window.im.messenger.goToMessage({
                        id: msgId,
                        peer_id: peerId
                    });
                    return;
                }
            } catch (err) {
                console.error("Calendar getNearestMessageForDate error:", err);
            }
        }
    });

    if (container) {
        cal.render(container);
    }

    return modal;
}

window.openCalendarModal = openCalendarModal;
window.CalendarComponent = CalendarComponent;
