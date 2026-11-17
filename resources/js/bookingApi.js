class ApiError extends Error {
    constructor(status, body) {
        const serverFailed = status >= 500;
        super(serverFailed ? 'Сервер временно недоступен. Попробуйте ещё раз.' : body.message || 'Не удалось выполнить действие. Попробуйте ещё раз.');
        this.status = status;
        this.fields = body.errors ?? {};
    }
}

async function request(path, { method = 'GET', payload } = {}) {
    const controller = new AbortController();
    const headers = { Accept: 'application/json' };
    const options = { method, headers, signal: controller.signal };

    if (payload) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }

    const timeout = setTimeout(() => controller.abort(), 15_000);
    try {
        const response = await fetch(path, options);

        if (!response.ok) {
            throw new ApiError(response.status, (await response.json().catch(() => ({}))) ?? {});
        }

        return response.status === 204 ? null : await response.json();
    } catch (error) {
        if (controller.signal.aborted) {
            throw new Error('Сервер не ответил вовремя. Попробуйте ещё раз.');
        }

        if (error instanceof TypeError) {
            throw new Error('Нет связи с сервером. Проверьте подключение и попробуйте ещё раз.');
        }

        throw error;
    } finally {
        clearTimeout(timeout);
    }
}

export const fetchSlots = () => request('/api/slots');
export const createBooking = (payload) => request('/api/bookings', { method: 'POST', payload });
export const cancelBooking = (id) => request(`/api/bookings/${id}`, { method: 'DELETE' });
