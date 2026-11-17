<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { cancelBooking, createBooking, fetchSlots } from './bookingApi';

const props = defineProps({ timezone: String, today: String });
const slots = ref([]);
const selectedDate = ref('');
const loading = ref(true);
const submitting = ref(false);
const scheduleError = ref('');
const notice = ref('');

const dialogOpen = ref(false);
const action = ref(null);
const form = reactive({ customer_name: '', customer_email: '' });
const fieldErrors = ref({});
const actionError = ref('');
const conflict = ref(false);

const statuses = {
    available: { label: 'Свободно', severity: 'success' },
    booked: { label: 'Занято', severity: 'warn' },
    past: { label: 'Прошло', severity: 'secondary' },
};

const dateFormatter = new Intl.DateTimeFormat('ru-RU', {
    weekday: 'short', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC',
});
const formatDate = (date) => dateFormatter.format(new Date(`${date}T00:00:00Z`));
const slotDate = (slot) => slot.starts_at.slice(0, 10);
const slotTime = (slot) => `${slot.starts_at.slice(11, 16)}–${slot.ends_at.slice(11, 16)}`;

const dates = computed(() => [...new Set(slots.value.map(slotDate))].map((date) => ({
    value: date,
    label: date === props.today ? `Сегодня · ${formatDate(date)}` : formatDate(date),
})));
const daySlots = computed(() => slots.value.filter((slot) => slotDate(slot) === selectedDate.value));
const freeCount = computed(() => daySlots.value.filter((slot) => slot.status === 'available').length);

async function loadSlots() {
    loading.value = true;
    scheduleError.value = '';

    try {
        slots.value = await fetchSlots();
        if (!dates.value.some(({ value }) => value === selectedDate.value)) {
            selectedDate.value = dates.value.find(({ value }) => value >= props.today)?.value
                ?? dates.value.at(-1)?.value ?? '';
        }
    } catch (error) {
        scheduleError.value = error.message;
    } finally {
        loading.value = false;
    }
}

function openDialog(type, slot) {
    action.value = { type, slot };
    form.customer_name = '';
    form.customer_email = '';
    fieldErrors.value = {};
    actionError.value = '';
    conflict.value = false;
    notice.value = '';
    dialogOpen.value = true;
}

async function submit() {
    if (submitting.value || conflict.value) return;

    const { type, slot } = action.value;
    submitting.value = true;
    actionError.value = '';
    fieldErrors.value = {};

    try {
        if (type === 'book') {
            await createBooking({ slot_id: slot.id, ...form });
            notice.value = `Бронь создана · ${slotTime(slot)}`;
        } else {
            await cancelBooking(slot.booking_id);
            notice.value = 'Бронирование отменено.';
        }
        dialogOpen.value = false;
        await loadSlots();
    } catch (error) {
        if (error.status === 422) {
            fieldErrors.value = error.fields;
        } else {
            actionError.value = error.message;

            if (error.status === 409 || error.status === 404) {
                conflict.value = true;
                await loadSlots();
            }
        }
    } finally {
        submitting.value = false;
    }
}

onMounted(loadSlots);
</script>

<template>
    <header class="site-header">
        <div class="page-width header-inner">
            <div class="wordmark">
                <span class="brand-icon"><i class="pi pi-calendar" aria-hidden="true"></i></span>
                <span>Mini Booking</span>
            </div>
            <span class="header-context"><i class="pi pi-map-marker" aria-hidden="true"></i> Офис · переговорная 01</span>
        </div>
    </header>

    <main class="page-width">
        <section class="hero" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">ОФИСНОЕ ПРОСТРАНСТВО</p>
                <h1 id="page-title">Переговорная</h1>
                <p class="hero-text">Выберите удобный час и забронируйте комнату для встречи.</p>
            </div>
            <div class="room-hours">
                <i class="pi pi-clock" aria-hidden="true"></i>
                <div><strong>09:00–18:00</strong><span>Каждый день</span></div>
            </div>
        </section>

        <section class="schedule" aria-labelledby="schedule-title" :aria-busy="loading">
            <div class="schedule-heading">
                <div>
                    <h2 id="schedule-title">Расписание</h2>
                    <p>Часовой пояс: {{ timezone }}</p>
                </div>
                <div v-if="dates.length" class="date-filter">
                    <label id="slot-day-label" for="slot-day">Дата</label>
                    <Select
                        v-model="selectedDate" input-id="slot-day" :options="dates"
                        aria-labelledby="slot-day-label" option-label="label" option-value="value" :disabled="loading || submitting"
                        class="date-select"
                    />
                </div>
            </div>

            <Message v-if="notice" severity="success" class="feedback">{{ notice }}</Message>
            <div v-if="scheduleError" class="schedule-error feedback">
                <Message severity="error">{{ scheduleError }}</Message>
                <Button label="Обновить расписание" icon="pi pi-refresh" severity="secondary" :disabled="loading || submitting" @click="loadSlots" />
            </div>

            <div v-if="loading" class="loading-state" role="status">
                <ProgressSpinner class="spinner" stroke-width="5" aria-hidden="true" />
                <span>Загружаем расписание…</span>
            </div>
            <Message v-else-if="!slots.length && !scheduleError" severity="info" class="feedback">
                В расписании пока нет слотов.
            </Message>

            <template v-if="daySlots.length">
                <div class="day-summary">
                    <h3>{{ formatDate(selectedDate) }}</h3>
                    <span>Свободно: {{ freeCount }} из {{ daySlots.length }}</span>
                </div>
                <ul class="slot-grid">
                    <li v-for="slot in daySlots" :key="slot.id" class="slot-card" :class="{ 'slot-past': slot.status === 'past' }">
                        <div class="slot-top">
                            <strong>{{ slotTime(slot) }}</strong>
                            <Tag :value="statuses[slot.status].label" :severity="statuses[slot.status].severity" rounded />
                        </div>
                        <div class="slot-actions">
                            <Button
                                v-if="slot.status === 'available'" label="Забронировать" icon="pi pi-arrow-right"
                                icon-pos="right" size="small" text
                                :aria-label="`Забронировать ${slotTime(slot)}`"
                                :disabled="loading || submitting || !!scheduleError" @click="openDialog('book', slot)"
                            />
                            <Button
                                v-else-if="slot.booking_id !== null" label="Отменить бронь" severity="secondary" size="small" text
                                :aria-label="`Отменить бронь ${slotTime(slot)}`"
                                :disabled="loading || submitting || !!scheduleError" @click="openDialog('cancel', slot)"
                            />
                        </div>
                    </li>
                </ul>
            </template>
        </section>

        <Dialog
            v-if="action" v-model:visible="dialogOpen" modal :closable="!submitting" :close-on-escape="!submitting"
            :header="action.type === 'book' ? 'Новая бронь' : 'Отменить бронирование?'"
            :style="{ width: '27rem', maxWidth: 'calc(100vw - 2rem)' }"
        >
            <p class="dialog-time">{{ formatDate(slotDate(action.slot)) }} · {{ slotTime(action.slot) }}</p>
            <form class="dialog-form" @submit.prevent="submit">
                <template v-if="action.type === 'book'">
                    <div class="form-field">
                        <label for="customer-name">Имя</label>
                        <InputText
                            id="customer-name" v-model.trim="form.customer_name" autocomplete="name"
                            required maxlength="255" autofocus :disabled="submitting" :invalid="!!fieldErrors.customer_name"
                            :aria-describedby="fieldErrors.customer_name ? 'name-error' : undefined"
                        />
                        <small v-if="fieldErrors.customer_name" id="name-error" role="alert">{{ fieldErrors.customer_name[0] }}</small>
                    </div>
                    <div class="form-field">
                        <label for="customer-email">Email</label>
                        <InputText
                            id="customer-email" v-model.trim="form.customer_email" type="email" autocomplete="email"
                            required maxlength="254" :disabled="submitting" :invalid="!!fieldErrors.customer_email"
                            :aria-describedby="fieldErrors.customer_email ? 'email-error' : undefined"
                        />
                        <small v-if="fieldErrors.customer_email" id="email-error" role="alert">{{ fieldErrors.customer_email[0] }}</small>
                    </div>
                    <Message v-if="fieldErrors.slot_id" severity="error">{{ fieldErrors.slot_id[0] }}</Message>
                </template>
                <p v-else class="cancel-text">Подтвердите отмену этой брони.</p>
                <Message v-if="actionError" severity="error">{{ actionError }}</Message>
                <div class="dialog-actions">
                    <Button type="button" label="Закрыть" severity="secondary" outlined :disabled="submitting" @click="dialogOpen = false" />
                    <Button
                        type="submit" :label="action.type === 'book' ? 'Забронировать' : 'Да, отменить'"
                        :severity="action.type === 'cancel' ? 'danger' : undefined"
                        :loading="submitting" :disabled="submitting || conflict"
                    />
                </div>
            </form>
        </Dialog>
    </main>
</template>
