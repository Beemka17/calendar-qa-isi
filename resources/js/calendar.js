import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

function getCsrf() {
  const el = document.querySelector('meta[name="csrf-token"]');
  return el ? el.getAttribute('content') : '';
}

async function api(url, options = {}) {
  const res = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCsrf(),
    },
    credentials: 'same-origin',
    ...options,
  });

  if (!res.ok) {
    let data = {};
    try { data = await res.json(); } catch (e) { }
    throw { status: res.status, data };
  }

  if (res.status === 204) return null;
  return res.json();
}

function pad(n) {
  return String(n).padStart(2, '0');
}

function toDatetimeLocal(date) {
  // date: Date object
  const y = date.getFullYear();
  const m = pad(date.getMonth() + 1);
  const d = pad(date.getDate());
  const hh = pad(date.getHours());
  const mm = pad(date.getMinutes());
  return `${y}-${m}-${d}T${hh}:${mm}`;
}

function fromDatetimeLocal(value) {
  // value: "YYYY-MM-DDTHH:mm"
  // kirim ke server sebagai ISO string agar aman
  const dt = new Date(value);
  return dt.toISOString();
}

window.calendarPage = function () {
  return {
    calendar: null,

    loadingGroups: false,
    groups: [],
    activeGroupIds: [],

    filters: {
      q: '',
      pic: '',
      location: '',
    },

    modal: {
      open: false,
      mode: 'create', // create|edit
    },

    groupModal: {
      open: false,
    },

    groupForm: {
      id: null,
      name: '',
      color_hex: '#3b82f6',
      is_active: true,
    },

    groupErrors: {},
    groupSaving: false,

    form: {
      id: null,
      title: '',
      event_group_id: '',
      start_at: '',
      end_at: '',
      location: '',
      pic: '',
      description: '',
    },

    errors: {},
    saving: false,

    toasts: [],
    toastSeq: 1,

    async init() {
      // load groups dulu
      await this.loadGroups();

      // init FullCalendar
      const el = document.getElementById('calendar');
      if (!el) return;

      this.calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        firstDay: 1,
        eventTimeFormat: {
          hour: '2-digit',
          minute: '2-digit',
          hour12: false,
        },
        slotLabelFormat: {
          hour: '2-digit',
          minute: '2-digit',
          hour12: false,
        },
        //height: 'auto',
        height: '100%',
        expandRows: true,
        handleWindowResize: true,
        nowIndicator: true,
        selectable: true,
        editable: true, // drag/drop + resize
        eventDurationEditable: true,
        eventStartEditable: true,

        events: async (info, successCallback, failureCallback) => {
          try {
            const params = new URLSearchParams();
            params.set('start', info.startStr);
            params.set('end', info.endStr);

            // groups filter
            this.activeGroupIds.forEach(id => params.append('group_ids[]', id));

            if (this.filters.q) params.set('q', this.filters.q);
            if (this.filters.pic) params.set('pic', this.filters.pic);
            if (this.filters.location) params.set('location', this.filters.location);

            const data = await api(`/api/events?${params.toString()}`);
            successCallback(data);
          } catch (e) {
            failureCallback(e);
            this.toast('Error', 'Gagal load events.');
          }
        },

        dateClick: (info) => {
          // default: 1 jam
          const start = info.date;
          const end = new Date(start.getTime() + 60 * 60 * 1000);

          this.openCreate({
            start,
            end,
            allDay: info.allDay,
          });
        },

        eventClick: (info) => {
          this.openEdit(info.event);
        },

        eventDrop: async (info) => {
          try {
            await this.patchEventTime(info.event);
            this.toast('Updated', 'Event time updated.');
          } catch (e) {
            info.revert();
            this.toast('Error', 'Tidak bisa update (permission/validasi).');
          }
        },

        eventResize: async (info) => {
          try {
            await this.patchEventTime(info.event);
            this.toast('Updated', 'Event duration updated.');
          } catch (e) {
            info.revert();
            this.toast('Error', 'Tidak bisa update (permission/validasi).');
          }
        },

        eventDidMount: (arg) => {
          // tooltip simple
          const ep = arg.event.extendedProps || {};
          const tip = [
            arg.event.title,
            ep.event_group_name ? `Group: ${ep.event_group_name}` : '',
            ep.pic ? `PIC: ${ep.pic}` : '',
            ep.location ? `Loc: ${ep.location}` : '',
          ].filter(Boolean).join('\n');

          arg.el.setAttribute('title', tip);
        },
      });

      this.calendar.render();
    },

    refetch() {
      if (this.calendar) this.calendar.refetchEvents();
    },

    async loadGroups() {
      this.loadingGroups = true;
      try {
        const data = await api('/api/event-groups');
        this.groups = data;

        // default: select all active groups
        this.activeGroupIds = data.filter(g => g.is_active).map(g => g.id);
      } catch (e) {
        this.toast('Error', 'Gagal load event groups.');
      } finally {
        this.loadingGroups = false;
      }
    },

    toggleGroup(id) {
      id = Number(id);
      if (this.activeGroupIds.includes(id)) {
        this.activeGroupIds = this.activeGroupIds.filter(x => x !== id);
      } else {
        this.activeGroupIds.push(id);
      }
      this.refetch();
    },

    selectAllGroups() {
      this.activeGroupIds = this.groups.filter(g => g.is_active).map(g => g.id);
      this.refetch();
    },

    clearGroups() {
      this.activeGroupIds = [];
      this.refetch();
    },

    /* ================================
   GROUP MANAGEMENT (ADMIN)
================================ */

    openGroupManager() {
      this.groupModal.open = true;
      this.groupErrors = {};
      this.resetGroupForm();
    },

    closeGroupManager() {
      this.groupModal.open = false;
      this.groupErrors = {};
    },

    resetGroupForm() {
      this.groupForm = {
        id: null,
        name: '',
        color_hex: '#3b82f6',
        is_active: true,
      };
      this.groupErrors = {};
    },

    startCreateGroup() {
      this.resetGroupForm();
    },

    startEditGroup(g) {
      this.groupForm = {
        id: g.id,
        name: g.name,
        color_hex: g.color_hex,
        is_active: !!g.is_active,
      };
      this.groupErrors = {};
    },

    async saveGroup() {
      this.groupSaving = true;
      this.groupErrors = {};

      try {
        const payload = {
          name: this.groupForm.name,
          color_hex: this.groupForm.color_hex,
          is_active: !!this.groupForm.is_active,
        };

        if (this.groupForm.id) {
          await api(`/api/event-groups/${this.groupForm.id}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
          });
          this.toast('Updated', 'Event group updated.');
        } else {
          await api('/api/event-groups', {
            method: 'POST',
            body: JSON.stringify(payload),
          });
          this.toast('Created', 'Event group created.');
        }

        await this.loadGroups();

        // sync filter
        this.activeGroupIds = this.groups
          .filter(g => g.is_active)
          .map(g => g.id);

        this.refetch();
        this.resetGroupForm();

      } catch (e) {
        this.groupErrors = this.normalizeErrors(e);
        this.toast('Error', 'Gagal save event group.');
      } finally {
        this.groupSaving = false;
      }
    },

    async toggleGroupActive(g) {
      this.groupSaving = true;

      try {
        await api(`/api/event-groups/${g.id}`, {
          method: 'PATCH',
          body: JSON.stringify({ is_active: !g.is_active }),
        });

        await this.loadGroups();

        this.activeGroupIds = this.groups
          .filter(x => x.is_active)
          .map(x => x.id);

        this.refetch();
        this.toast('Updated', 'Group status updated.');

      } catch (e) {
        this.toast('Error', 'Gagal update status.');
      } finally {
        this.groupSaving = false;
      }
    },

    async deleteGroup(g) {
      if (!confirm(`Delete group "${g.name}"?`)) return;

      this.groupSaving = true;

      try {
        await api(`/api/event-groups/${g.id}`, {
          method: 'DELETE',
        });

        await this.loadGroups();

        this.activeGroupIds = this.groups
          .filter(x => x.is_active)
          .map(x => x.id);

        this.refetch();
        this.toast('Deleted', 'Group deleted.');

      } catch (e) {
        const msg = e?.data?.message || 'Gagal delete group.';
        this.toast('Error', msg);
      } finally {
        this.groupSaving = false;
      }
    },

    resetForm() {
      this.form = {
        id: null,
        title: '',
        event_group_id: '',
        start_at: '',
        end_at: '',
        location: '',
        pic: '',
        description: '',
      };
      this.errors = {};
    },

    openCreate({ start, end }) {
      this.resetForm();
      this.modal.mode = 'create';
      this.modal.open = true;

      this.form.start_at = toDatetimeLocal(start);
      this.form.end_at = toDatetimeLocal(end);

      // default group: first active
      const first = this.groups.find(g => g.is_active);
      if (first) this.form.event_group_id = first.id;
    },

    openEdit(event) {
      this.resetForm();
      this.modal.mode = 'edit';
      this.modal.open = true;

      const ep = event.extendedProps || {};

      this.form.id = event.id;
      this.form.title = event.title;
      this.form.event_group_id = ep.event_group_id ?? '';
      this.form.start_at = toDatetimeLocal(event.start);

      // end can be null in FC, but our DB requires end_at
      const end = event.end ? event.end : new Date(event.start.getTime() + 60 * 60 * 1000);
      this.form.end_at = toDatetimeLocal(end);

      this.form.location = ep.location ?? '';
      this.form.pic = ep.pic ?? '';
      this.form.description = ep.description ?? '';
    },

    closeModal() {
      this.modal.open = false;
      this.errors = {};
    },

    normalizeErrors(e) {
      // Laravel validation: { errors: {field:[msg]} }
      const out = {};
      if (e?.data?.errors) {
        Object.keys(e.data.errors).forEach(k => {
          out[k] = e.data.errors[k][0];
        });
      } else if (e?.data?.message) {
        out._general = e.data.message;
      }
      return out;
    },

    async submit() {
      this.saving = true;
      this.errors = {};

      try {
        const payload = {
          title: this.form.title,
          event_group_id: this.form.event_group_id,
          start_at: fromDatetimeLocal(this.form.start_at),
          end_at: fromDatetimeLocal(this.form.end_at),
          location: this.form.location || null,
          pic: this.form.pic || null,
          description: this.form.description || null,
        };

        if (this.modal.mode === 'create') {
          await api('/api/events', {
            method: 'POST',
            body: JSON.stringify(payload),
          });
          this.toast('Created', 'Event berhasil dibuat.');
        } else {
          await api(`/api/events/${this.form.id}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
          });
          this.toast('Updated', 'Event berhasil diupdate.');
        }

        this.closeModal();
        this.refetch();
      } catch (e) {
        this.errors = this.normalizeErrors(e);
        this.toast('Error', 'Gagal save event.');
      } finally {
        this.saving = false;
      }
    },

    async doDelete() {
      if (!confirm('Yakin ingin delete event ini?')) return;

      this.saving = true;
      try {
        await api(`/api/events/${this.form.id}`, { method: 'DELETE' });
        this.toast('Deleted', 'Event dihapus.');
        this.closeModal();
        this.refetch();
      } catch (e) {
        this.toast('Error', 'Gagal delete (permission?).');
      } finally {
        this.saving = false;
      }
    },

    async patchEventTime(event) {
      const payload = {
        start_at: event.start.toISOString(),
        end_at: (event.end ? event.end : new Date(event.start.getTime() + 60 * 60 * 1000)).toISOString(),
      };

      await api(`/api/events/${event.id}`, {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });
    },

    toast(title, message) {
      const id = this.toastSeq++;
      this.toasts.push({ id, title, message });
      setTimeout(() => {
        this.toasts = this.toasts.filter(t => t.id !== id);
      }, 2500);
    },
  };
};