class ApiError extends Error {
    constructor(status, body) {
        const serverFailed = status >= 500;
        super(serverFailed ? 'Сервер временно недоступен. Попробуйте ещё раз.' : body.message || 'Не удалось выполнить действие. Попробуйте ещё раз.');
        this.status = status;
        this.fields = body.errors ?? {};
    }
}

async function request(path, { method = 'GET', payload } = {}) {
    const headers = { Accept: 'application/json' };
    const options = { method, headers };

    if (payload) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }

    let response;
    try {
        response = await fetch(path, options);
    } catch {
        throw new Error('Нет связи с сервером. Проверьте подключение и попробуйте ещё раз.');
    }

    if (!response.ok) {
        throw new ApiError(response.status, (await response.json().catch(() => ({}))) ?? {});
    }

    return response.status === 204 ? null : response.json();
}

export const fetchSlots = () => request('/api/slots');
export const createBooking = (payload) => request('/api/bookings', { method: 'POST', payload });
export const cancelBooking = (id) => request(`/api/bookings/${id}`, { method: 'DELETE' });
