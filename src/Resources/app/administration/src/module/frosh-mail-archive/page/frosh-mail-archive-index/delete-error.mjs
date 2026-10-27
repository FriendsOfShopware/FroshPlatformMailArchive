export function getDeleteErrorDetail(errorResponse) {
    const errors =
        errorResponse?.response?.data?.errors ??
        errorResponse?.response?.errors ??
        errorResponse?.errors;

    if (!Array.isArray(errors)) {
        return null;
    }

    const detail = errors
        .map((error) => error?.detail)
        .filter((message) => typeof message === 'string' && message !== '')
        .join('\n');

    return detail === '' ? null : detail;
}
